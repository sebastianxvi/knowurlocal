<?php

namespace App\Http\Controllers;

use App\Models\Agency;
use App\Models\AgencyContact;
use App\Models\AgencyType;
use App\Models\Category;
use App\Models\ContactType;
use App\Models\Faq;
use App\Models\SupportRequest;
use App\Models\SupportRequestResponse;
use App\Services\SupportRequestResponseService;
use App\Services\SupportRequestNotificationService;
use App\Services\FaqSimilarityService;
use App\Events\SupportRequestResponseCreated;
use App\Events\SupportRequestUpdated;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AgencyAdminController extends Controller
{
    private function agency(): Agency
    {
        $user = auth()->user();

        abort_unless(
            $user && $user->role === 'agency_admin' && $user->agency_id,
            403
        );

        return Agency::query()
            ->whereNull('deleted_at')
            ->findOrFail($user->agency_id);
    }

    public function dashboard()
    {
        $agency = $this->agency();

        $counts = [
            'pending' => SupportRequest::query()->where('agency_id', $agency->id)->whereNull('deleted_at')->where('status', 'pending')->count(),
            'follow_up' => SupportRequest::query()->where('agency_id', $agency->id)->whereNull('deleted_at')->where('status', 'needs_follow_up')->count(),
            'awaiting_confirmation' => SupportRequest::query()->where('agency_id', $agency->id)->whereNull('deleted_at')->where('status', 'awaiting_confirmation')->count(),
            'answered' => SupportRequest::query()->where('agency_id', $agency->id)->whereNull('deleted_at')->where('status', 'answered')->count(),
            'faqs' => Faq::query()->where('agency_id', $agency->id)->count(),
        ];

        $recentRequests = SupportRequest::query()
            ->with('user')
            ->where('agency_id', $agency->id)
            ->whereNull('deleted_at')
            ->latest()
            ->limit(8)
            ->get();

        return view('agency-admin.dashboard', compact('agency', 'counts', 'recentRequests'));
    }

    public function tickets(Request $request)
    {
        $agency = $this->agency();
        $search = trim((string) $request->query('search', ''));

        // Load only tickets belonging to the authenticated administrator's agency.
        // Grouping happens after the agency scope is applied, never from client-supplied agency IDs.
        // Keep trashed tickets in the same inbox so their position and history
        // remain visible. Destructive actions are separately scoped below.
        $query = SupportRequest::withTrashed()
            ->with(['user', 'latestResponse.components', 'responses.admin', 'responses.components'])
            ->where('agency_id', $agency->id)
            ->orderByDesc('updated_at');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('question', 'like', '%' . $search . '%')
                    ->orWhere('id', ctype_digit($search) ? (int) $search : -1)
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('first_name', 'like', '%' . $search . '%')
                            ->orWhere('last_name', 'like', '%' . $search . '%')
                            ->orWhere('email', 'like', '%' . $search . '%');
                    });
            });
        }

        $tickets = $query->get();
        $conversations = $tickets
            ->groupBy('user_id')
            ->map(function ($userTickets) {
                return $userTickets->sortByDesc('updated_at')->values();
            })
            ->sortByDesc(fn ($userTickets) => $userTickets->first()?->updated_at)
            ->values();

        $requestedTicketId = (int) $request->query('ticket', 0);
        $requestedUserId = (int) $request->query('user', 0);

        // A selected ticket determines the selected citizen, but only if it is in this agency's scoped result set.
        $ticketFromList = $requestedTicketId ? $tickets->firstWhere('id', $requestedTicketId) : null;
        $selectedUserId = $ticketFromList?->user_id ?? $requestedUserId;
        // Selecting a citizen opens only their ticket list. The actual conversation
        // is opened only when a valid ticket ID is explicitly selected.
        $selectedConversation = $selectedUserId
            ? $conversations->first(function ($userTickets) use ($selectedUserId) {
                return (int) $userTickets->first()?->user_id === $selectedUserId;
            })
            : null;

        $selectedUser = $selectedConversation?->first()?->user;
        $selectedTickets = $selectedConversation ?? collect();
        $selectedTicket = $ticketFromList && $selectedTickets->contains('id', $ticketFromList->id)
            ? $ticketFromList
            : null;

        if ($selectedTicket) {
            // Viewing a ticket acknowledges its latest user activity. Update only
            // the read marker so this action does not reorder the inbox.
            DB::table('support_requests')
                ->where('id', $selectedTicket->id)
                ->where('agency_id', $agency->id)
                ->update(['agency_seen_at' => now()]);
            $selectedTicket->agency_seen_at = now();
            $selectedTicket->load(['responses.admin', 'responses.components']);
        }

        return view('agency-admin.tickets', compact(
            'agency', 'tickets', 'conversations', 'selectedUser', 'selectedUserId',
            'selectedTickets', 'selectedTicket', 'search'
        ));
    }

    /** Move one of this agency's active tickets to trash with a required reason. */
    public function trashTicket(Request $request, int $ticketId, AuditLogService $auditLog)
    {
        $agency = $this->agency();
        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:3', 'max:1000'],
        ]);

        $ticket = SupportRequest::query()
            ->where('agency_id', $agency->id)
            ->whereNull('deleted_at')
            ->findOrFail($ticketId);
        $oldValues = [
            'status' => $ticket->status,
            'question' => $ticket->question,
            'deleted_at' => null,
        ];
        $originalUpdatedAt = $ticket->updated_at;
        $reason = trim($validated['reason']);
        abort_if(mb_strlen($reason) < 3, 422, 'Please provide a reason of at least 3 characters.');

        DB::transaction(function () use ($ticket, $reason, $originalUpdatedAt) {
            $ticket->trash_reason = $reason;
            $ticket->trash_seen_at = null;
            $ticket->save();
            $ticket->delete();

            // SoftDeletes normally touches updated_at. Restore its previous value
            // so trashing a ticket does not reorder it in the agency inbox.
            DB::table('support_requests')->where('id', $ticket->id)->update([
                'updated_at' => $originalUpdatedAt,
            ]);
            $ticket->updated_at = $originalUpdatedAt;
        });

        try {
            $auditLog->record(
                action: 'agency_trash_support_request',
                page: 'agency_support_inbox',
                targetType: 'support_request',
                targetId: (int) $ticket->id,
                targetUserId: (int) $ticket->user_id,
                agencyId: (int) $agency->id,
                supportRequestId: (int) $ticket->id,
                oldValues: $oldValues,
                newValues: ['deleted_at' => $ticket->deleted_at?->toDateTimeString(), 'trash_reason' => $reason],
                description: 'Agency moved Support Request #' . $ticket->id . ' to trash. Reason: ' . $reason,
            );
        } catch (\Throwable $e) {
            \Log::warning('Agency ticket trash audit log failed.', ['ticket_id' => $ticket->id, 'error' => $e->getMessage()]);
        }

        app(SupportRequestNotificationService::class)->ticketTrashed(
            $ticket->fresh() ?? $ticket,
            'agency_admin'
        );
        $this->broadcastTicketLifecycle($ticket, 'trashed');

        return redirect()->route('agency-admin.tickets', [
            'user' => $ticket->user_id,
            'ticket' => $ticket->id,
            'search' => $request->input('search'),
        ])->with('success', 'Ticket moved to trash. The citizen can see the reason you provided.');
    }

    /** Restore only a ticket trashed by/for this agency. */
    public function restoreTicket(int $ticketId, AuditLogService $auditLog)
    {
        $agency = $this->agency();
        $ticket = SupportRequest::onlyTrashed()
            ->where('agency_id', $agency->id)
            ->findOrFail($ticketId);
        $userId = (int) $ticket->user_id;
        $oldValues = ['deleted_at' => $ticket->deleted_at?->toDateTimeString(), 'trash_reason' => $ticket->trash_reason];
        $ticket->restore();

        try {
            $auditLog->record(
                action: 'agency_restore_support_request',
                page: 'agency_support_inbox',
                targetType: 'support_request',
                targetId: (int) $ticket->id,
                targetUserId: $userId,
                agencyId: (int) $agency->id,
                supportRequestId: (int) $ticket->id,
                oldValues: $oldValues,
                newValues: ['deleted_at' => null],
                description: 'Agency restored Support Request #' . $ticket->id,
            );
        } catch (\Throwable $e) {
            \Log::warning('Agency ticket restore audit log failed.', ['ticket_id' => $ticket->id, 'error' => $e->getMessage()]);
        }

        $this->broadcastTicketLifecycle($ticket, 'restored');

        return redirect()->route('agency-admin.tickets', ['user' => $userId, 'ticket' => $ticket->id])
            ->with('success', 'Ticket restored successfully.');
    }

    /** Permanently delete a ticket that is already in this agency's trash. */
    public function permanentlyDeleteTicket(int $ticketId, AuditLogService $auditLog)
    {
        $agency = $this->agency();
        $ticket = SupportRequest::onlyTrashed()
            ->where('agency_id', $agency->id)
            ->with(['responses.components'])
            ->findOrFail($ticketId);

        $ticketId = (int) $ticket->id;
        $userId = (int) $ticket->user_id;
        $oldValues = [
            'question' => $ticket->question,
            'status' => $ticket->status,
            'agency_id' => (int) $ticket->agency_id,
            'trash_reason' => $ticket->trash_reason,
            'created_at' => $ticket->created_at?->toDateTimeString(),
            'deleted_at' => $ticket->deleted_at?->toDateTimeString(),
        ];
        $privateFiles = $ticket->responses
            ->flatMap(fn ($response) => $response->components
                ->filter(fn ($component) => in_array($component->type, ['image', 'file'], true) && filled($component->content))
                ->pluck('content'))
            ->unique()->values()->all();
        $answerImage = $ticket->answer_image;

        try {
            DB::transaction(function () use ($ticket) {
                $ticket->responses->each(function ($response) {
                    $response->components()->delete();
                    $response->delete();
                });
                $ticket->forceDelete();
            });
        } catch (\Throwable $e) {
            \Log::error('Agency ticket permanent deletion failed.', ['ticket_id' => $ticketId, 'agency_id' => $agency->id, 'error' => $e->getMessage()]);
            return redirect()->route('agency-admin.tickets', ['user' => $userId])
                ->with('error', 'The ticket could not be permanently deleted. No data was removed.');
        }

        foreach ($privateFiles as $path) {
            try {
                if (Storage::disk('private')->exists($path)) {
                    Storage::disk('private')->delete($path);
                }
            } catch (\Throwable $e) {
                \Log::warning('Agency ticket attachment cleanup failed after permanent deletion.', ['ticket_id' => $ticketId, 'path' => $path, 'error' => $e->getMessage()]);
            }
        }
        if ($answerImage) {
            try {
                if (Storage::disk('public')->exists($answerImage)) {
                    Storage::disk('public')->delete($answerImage);
                }
            } catch (\Throwable $e) {
                \Log::warning('Agency legacy answer image cleanup failed after permanent deletion.', ['ticket_id' => $ticketId, 'path' => $answerImage, 'error' => $e->getMessage()]);
            }
        }

        try {
            $auditLog->record(
                action: 'agency_force_delete_support_request',
                page: 'agency_support_inbox',
                targetType: 'support_request',
                targetId: $ticketId,
                targetUserId: $userId,
                agencyId: (int) $agency->id,
                supportRequestId: null,
                oldValues: $oldValues,
                newValues: null,
                description: 'Agency permanently deleted Support Request #' . $ticketId,
            );
        } catch (\Throwable $e) {
            \Log::warning('Agency ticket permanent deletion audit log failed.', ['ticket_id' => $ticketId, 'error' => $e->getMessage()]);
        }

        try {
            broadcast(new SupportRequestUpdated($ticketId, 'force_deleted', null, $userId));
        } catch (\Throwable $e) {
            \Log::warning('Agency ticket permanent deletion broadcast failed.', ['ticket_id' => $ticketId, 'error' => $e->getMessage()]);
        }

        return redirect()->route('agency-admin.tickets', ['user' => $userId])
            ->with('success', 'Ticket permanently deleted.');
    }

    private function broadcastTicketLifecycle(SupportRequest $ticket, string $action): void
    {
        try {
            broadcast(new SupportRequestUpdated(
                id: (int) $ticket->id,
                action: $action,
                userId: (int) $ticket->user_id,
            ));
        } catch (\Throwable $e) {
            \Log::warning('Agency ticket lifecycle broadcast failed.', ['ticket_id' => $ticket->id, 'action' => $action, 'error' => $e->getMessage()]);
        }
    }

    /** Create an official response only for a ticket assigned to this agency. */
    public function respondToTicket(Request $request, SupportRequestResponseService $responseService)
    {
        $agency = $this->agency();
        $validated = $request->validate([
            'request_id' => ['required', 'integer'],
            'components' => ['required', 'array', 'min:1', 'max:10'],
            'components.*.type' => ['required', 'in:text,image,file,link,qr_code'],
            'components.*.content' => ['nullable', 'string', 'max:5000'],
            'components.*.label' => ['nullable', 'string', 'max:255'],
            'components.*.file' => ['nullable', 'file', 'max:5120', 'mimes:jpg,jpeg,png,webp,pdf,doc,docx,xls,xlsx'],
        ]);

        $ticket = SupportRequest::query()->where('agency_id', $agency->id)
            ->whereNull('deleted_at')->findOrFail($validated['request_id']);
        abort_unless(!in_array($ticket->status, ['awaiting_confirmation', 'answered'], true), 409,
            'This ticket is awaiting the citizen’s confirmation or has already been resolved.');

        $components = [];
        foreach ($validated['components'] as $component) {
            $type = $component['type'];
            if ($type === 'text' && trim((string) ($component['content'] ?? '')) === '') {
                return response()->json(['message' => 'Text responses cannot be empty.'], 422);
            }
            if (in_array($type, ['link', 'qr_code'], true)) {
                $url = (string) ($component['content'] ?? '');
                if (!filter_var($url, FILTER_VALIDATE_URL) || !in_array(strtolower(parse_url($url, PHP_URL_SCHEME) ?? ''), ['http', 'https'], true)) {
                    return response()->json(['message' => 'Links and QR codes must use a valid HTTP or HTTPS URL.'], 422);
                }
            }
            if (in_array($type, ['image', 'file'], true) && empty($component['file'])) {
                return response()->json(['message' => ucfirst($type) . ' components require a file.'], 422);
            }
            $components[] = ['type' => $type, 'content' => $component['content'] ?? null,
                'label' => $component['label'] ?? null, 'file' => $component['file'] ?? null];
        }

        $response = $responseService->createAndForward($ticket, (int) auth()->id(), (int) $agency->id, $components);
        app(SupportRequestNotificationService::class)->responseCreated($ticket->fresh(), (int) $response->id, 'agency_admin');
        try {
            broadcast(new SupportRequestResponseCreated((int) $ticket->user_id, (int) $ticket->id, (int) $response->id, 'awaiting_confirmation'));
        } catch (\Throwable $e) {
            \Log::warning('Agency ticket response realtime broadcast failed.', ['ticket_id' => $ticket->id, 'error' => $e->getMessage()]);
        }

        // Notify every authorized browser viewing this agency's inbox. The
        // citizen-specific response event above is not a substitute for the
        // agency inbox event, which has its own private channel authorization.
        try {
            broadcast(new SupportRequestUpdated(
                id: (int) $ticket->id,
                action: 'response_created',
                responseId: (int) $response->id,
                userId: (int) $ticket->user_id,
            ));
        } catch (\Throwable $e) {
            \Log::warning('Agency ticket inbox realtime broadcast failed.', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json(['success' => true, 'message' => 'Official response sent to the citizen.', 'redirect' => route('agency-admin.tickets', ['ticket' => $ticket->id])]);
    }

    /** Securely stream a response attachment for a ticket owned by this agency. */
    public function responseAttachment(int $ticketId, int $responseId, int $componentId)
    {
        $agency = $this->agency();
        $component = \App\Models\SupportResponseComponent::query()
            ->where('id', $componentId)->where('support_request_response_id', $responseId)
            ->whereHas('response.supportRequest', function ($q) use ($ticketId, $agency) {
                $q->withTrashed()->where('id', $ticketId)->where('agency_id', $agency->id);
            })->firstOrFail();
        abort_unless(in_array($component->type, ['image', 'file'], true), 404);
        abort_unless($component->content && Storage::disk('private')->exists($component->content), 404);
        return Storage::disk('private')->response($component->content, basename($component->content), [
            'Content-Disposition' => 'inline; filename="' . addslashes(basename($component->content)) . '"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /** Check only this agency's FAQs before preparing a ticket as a new FAQ. */
    public function similarFaqsForTicket(int $ticketId, FaqSimilarityService $similarityService)
    {
        $agency = $this->agency();
        $ticket = SupportRequest::query()
            ->where('agency_id', $agency->id)
            ->where('status', 'answered')
            ->findOrFail($ticketId);

        $matches = $similarityService->findSimilar($ticket->question, $agency->id, 5, true);

        return response()->json([
            'success' => true,
            'matches' => $matches,
        ]);
    }

    public function faqs(Request $request)
    {
        $agency = $this->agency();
        $query = Faq::query()
            ->with('agency')
            ->where('agency_id', $agency->id)
            ->withCount([
                'currentFeedback as feedback_likes_count' => fn ($q) => $q->where('rating', 'helpful'),
                'currentFeedback as feedback_dislikes_count' => fn ($q) => $q->where('rating', 'not_helpful'),
            ]);

        $search = trim((string) $request->query('search', ''));
        if ($search !== '') {
            $like = '%' . $search . '%';
            $query->where(function ($q) use ($like) {
                $q->where('question', 'LIKE', $like)
                    ->orWhere('answer', 'LIKE', $like)
                    ->orWhere('keywords', 'LIKE', $like);
            });
        }

        if ($request->filled('date')) {
            $request->validate(['date' => ['date_format:Y-m-d']]);
            $query->whereDate('updated_at', $request->query('date'));
        }

        if ($request->query('feedback') === 'needs_review') {
            $minimumRatings = (int) config('faq_feedback.minimum_ratings_for_review', 5);
            $needsReviewIds = \App\Models\FaqFeedback::query()
                ->select('faq_id')
                ->whereColumn('faq_version_id', 'faqs.current_version_id')
                ->whereNotNull('faq_id')
                ->groupBy('faq_id')
                ->havingRaw('COUNT(*) >= ?', [$minimumRatings])
                ->havingRaw("SUM(CASE WHEN rating = 'not_helpful' THEN 1 ELSE 0 END) > SUM(CASE WHEN rating = 'helpful' THEN 1 ELSE 0 END)");
            $query->whereIn('faqs.id', $needsReviewIds);
        }

        $sortDirection = $request->query('sort') === 'oldest' ? 'asc' : 'desc';
        $faqs = $query->orderBy('updated_at', $sortDirection)
            ->paginate(10)
            ->withQueryString();

        $agencies = collect([$agency]);
        $availableDates = Faq::query()
            ->where('agency_id', $agency->id)
            ->selectRaw('DATE(updated_at) as date')
            ->distinct()->orderByDesc('date')->limit(15)->pluck('date');
        $conversionSupport = null;
        if ($request->filled('convert_support')) {
            $supportId = (int) $request->query('convert_support');
            $responseId = (int) $request->query('response_id', 0);

            // Scope the source ticket to this authenticated agency before exposing conversion data.
            $sourceTicket = SupportRequest::query()
                ->where('agency_id', $agency->id)
                ->where('status', 'answered')
                ->findOrFail($supportId);

            if ($responseId > 0) {
                $sourceResponse = SupportRequestResponse::query()
                    ->where('support_request_id', $sourceTicket->id)
                    ->where('status', 'accepted')
                    ->findOrFail($responseId);
            } else {
                // Legacy tickets may have a saved answer but no response history.
                abort_unless(
                    $responseId === 0
                    && !$sourceTicket->responses()->exists()
                    && (filled($sourceTicket->answer) || filled($sourceTicket->answer_image)),
                    422,
                    'Select a completed official response before preparing this FAQ.'
                );
            }

            $conversionSupport = [
                'id' => $sourceTicket->id,
                'agency_id' => $agency->id,
                'response_id' => $responseId,
            ];
        }
        $status = 'active';
        $activeCount = Faq::query()->where('agency_id', $agency->id)->count();
        $trashedCount = 0;

        return view('agency-admin.faqs', compact(
            'agency', 'faqs', 'agencies', 'availableDates', 'conversionSupport',
            'status', 'activeCount', 'trashedCount'
        ));
    }

    /** Create a FAQ for this agency using the shared system-admin FAQ workflow. */
    public function storeFaq(Request $request, FaqController $faqController)
    {
        $agency = $this->agency();
        // Never trust agency_id supplied by the browser.
        $request->merge(['agency_id' => $agency->id]);

        return $faqController->store($request);
    }

    /** Update only a FAQ owned by this authenticated agency. */
    public function updateFaq(Request $request, int $faq, FaqController $faqController)
    {
        $agency = $this->agency();
        Faq::query()->where('agency_id', $agency->id)->findOrFail($faq);
        $request->merge(['agency_id' => $agency->id]);

        return $faqController->update($request, $faq);
    }

    /** Soft-delete only a FAQ owned by this authenticated agency. */
    public function destroyFaq(Request $request, int $faq, FaqController $faqController)
    {
        $agency = $this->agency();
        Faq::query()->where('agency_id', $agency->id)->findOrFail($faq);

        return $faqController->destroy($request, $faq);
    }

    /** Feedback is visible only to the agency that owns the FAQ. */
    public function faqFeedback(Request $request, int $faq, FaqFeedbackController $feedbackController)
    {
        $agency = $this->agency();
        $record = Faq::query()->where('agency_id', $agency->id)->findOrFail($faq);

        return $feedbackController->forFaq($request, $record);
    }

    /** Serve private response attachments only for FAQs owned by this agency. */
    public function faqResponseAttachment(int $faqId, int $componentIndex, FaqController $faqController)
    {
        $agency = $this->agency();
        Faq::query()->where('agency_id', $agency->id)->findOrFail($faqId);

        return $faqController->responseAttachment($faqId, $componentIndex);
    }

    public function editAgency()
    {
        $agency = $this->agency()->load(['type', 'category', 'contacts.contactType']);
        $types = AgencyType::query()->orderBy('name')->get();
        $categories = Category::query()->orderBy('category_name')->get();
        $contactTypes = ContactType::query()->where('is_active', true)->orderBy('sort_order')->get();
        $agencyImageUrl = $agency->agency_image
            ? Storage::disk('public')->url($agency->agency_image)
            : '';

        // Prepare the JSON payload in PHP instead of embedding a nested array
        // and arrow function inside Blade's @json directive (which breaks
        // Blade's directive argument parser on this view).
        $agencyProfileData = [
            'id' => $agency->id,
            'name' => old('agency_name', $agency->agency_name),
            'abbreviation' => old('agency_abbreviation', $agency->agency_abbreviation),
            'type_id' => old('agency_type_id', $agency->agency_type_id),
            'category_id' => old('category_id', $agency->category_id),
            'description' => old('agency_description', $agency->agency_description),
            'office_head_name' => old('office_head_name', $agency->office_head_name),
            'office_head_position' => old('office_head_position', $agency->office_head_position),
            'services_offered' => old('services_offered', $agency->services_offered),
            'location' => old('agency_location', $agency->agency_location),
            'office' => old('office_hours', $agency->office_hours),
            'lat' => old('lat', $agency->lat),
            'lng' => old('lng', $agency->lng),
            'image' => $agency->agency_image,
            'imageUrl' => $agencyImageUrl,
            'contacts' => old('contacts', $agency->contacts->map(function ($contact) {
                return [
                    'id' => $contact->id,
                    'contact_type_id' => $contact->contact_type_id,
                    'label' => $contact->label,
                    'value' => $contact->value,
                    'is_primary' => (bool) $contact->is_primary,
                    'sort_order' => $contact->sort_order,
                ];
            })->values()->all()),
        ];

        return view('agency-admin.agency', compact(
            'agency',
            'types',
            'categories',
            'contactTypes',
            'agencyImageUrl',
            'agencyProfileData'
        ));
    }

    public function updateAgency(Request $request, AuditLogService $auditLog)
    {
        $agency = $this->agency();

        $validated = $request->validate([
            'agency_name' => ['required', 'string', 'max:255', Rule::unique('agencies', 'agency_name')->ignore($agency->id)],
            'agency_abbreviation' => ['required', 'string', 'max:255', Rule::unique('agencies', 'agency_abbreviation')->ignore($agency->id)],
            'agency_type_id' => ['required', 'integer', 'exists:agency_types,id'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'agency_description' => ['nullable', 'string', 'max:5000'],
            'agency_location' => ['required', 'string', 'max:255'],
            'services_offered' => ['nullable', 'string', 'max:5000'],
            'office_hours' => ['required', 'string', 'max:1000'],
            'office_head_name' => ['nullable', 'string', 'max:255'],
            'office_head_position' => ['nullable', 'string', 'max:255'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
            'agency_image' => ['nullable', 'image', 'mimes:jpeg,png,webp', 'max:5120'],
            'contacts' => ['required', 'array', 'min:2'],
            'contacts.*.contact_type_id' => ['required', 'integer', 'exists:contact_types,id'],
            'contacts.*.label' => ['nullable', 'string', 'max:255'],
            'contacts.*.value' => ['required', 'string', 'max:500'],
            'contacts.*.is_primary' => ['nullable', 'boolean'],
            'contacts.*.sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $contactTypeIds = collect($validated['contacts'])->pluck('contact_type_id')->unique();
        $contactTypes = ContactType::query()->where('is_active', true)->whereIn('id', $contactTypeIds)->get()->keyBy('id');
        if ($contactTypes->count() !== $contactTypeIds->count()) {
            return back()->withErrors(['contacts' => 'One or more selected contact types are invalid or inactive.'])->withInput();
        }

        $hasHotline = false;
        $hasEmail = false;
        foreach ($validated['contacts'] as $index => $contact) {
            $type = $contactTypes[$contact['contact_type_id']];
            $slug = strtolower((string) $type->slug);
            if ($slug === 'email') {
                $request->validate(["contacts.$index.value" => ['required', 'email', 'max:500']]);
                $hasEmail = true;
            }
            if (in_array($slug, ['website', 'facebook'], true)) {
                $request->validate(["contacts.$index.value" => ['required', 'url', 'max:500']]);
            }
            if ($slug === 'hotline') {
                $hasHotline = true;
            }
        }
        if (!$hasHotline || !$hasEmail) {
            $missing = array_filter(['Hotline' => !$hasHotline, 'Email' => !$hasEmail], fn ($required) => $required);
            return back()->withErrors(['contacts' => 'The following contact information is required: ' . implode(' and ', array_keys($missing)) . '.'])->withInput();
        }

        $agencyFields = collect($validated)->except('contacts')->toArray();
        $oldImage = $agency->agency_image;
        $newImage = null;
        if ($request->hasFile('agency_image')) {
            $newImage = $request->file('agency_image')->store('agencies', 'public');
            $agencyFields['agency_image'] = $newImage;
        }

        $before = $agency->only(array_keys($agencyFields));
        $oldContacts = $agency->contacts()->with('contactType')->get()->map(fn ($contact) => [
            'type' => $contact->contactType?->name,
            'type_slug' => $contact->contactType?->slug,
            'label' => $contact->label,
            'value' => $contact->value,
            'is_primary' => (bool) $contact->is_primary,
            'sort_order' => (int) $contact->sort_order,
        ])->values()->all();
        $newContacts = collect($validated['contacts'])->map(function ($contact, $index) use ($contactTypes) {
            $type = $contactTypes[$contact['contact_type_id']];
            return [
                'type' => $type->name,
                'type_slug' => $type->slug,
                'label' => $contact['label'] ?? null,
                'value' => $contact['value'],
                'is_primary' => !empty($contact['is_primary']),
                'sort_order' => (int) ($contact['sort_order'] ?? $index),
            ];
        })->values()->all();

        try {
            DB::transaction(function () use ($agency, $agencyFields, $validated) {
                $agency->fill($agencyFields)->save();
                $agency->contacts()->delete();
                foreach ($validated['contacts'] as $index => $contact) {
                    AgencyContact::query()->create([
                        'agency_id' => $agency->id,
                        'contact_type_id' => $contact['contact_type_id'],
                        'label' => $contact['label'] ?? null,
                        'value' => $contact['value'],
                        'is_primary' => !empty($contact['is_primary']),
                        'sort_order' => (int) ($contact['sort_order'] ?? $index),
                    ]);
                }
            });
        } catch (\Throwable $exception) {
            if ($newImage) {
                Storage::disk('public')->delete($newImage);
            }
            throw $exception;
        }

        $agency->refresh();
        $after = $agency->only(array_keys($agencyFields));
        $changedOld = [];
        $changedNew = [];
        foreach ($before as $field => $oldValue) {
            if ((string) $oldValue !== (string) ($after[$field] ?? null)) {
                $changedOld[$field] = $oldValue;
                $changedNew[$field] = $after[$field] ?? null;
            }
        }
        $normalizeContacts = fn ($items) => collect($items)->sortBy(fn ($item) => implode('|', [
            $item['sort_order'] ?? 0, $item['type_slug'] ?? '', $item['label'] ?? '', $item['value'] ?? '', !empty($item['is_primary']) ? '1' : '0',
        ]))->values()->all();
        if ($normalizeContacts($oldContacts) !== $normalizeContacts($newContacts)) {
            $changedOld['contacts'] = $oldContacts;
            $changedNew['contacts'] = $newContacts;
        }

        if ($oldImage && $newImage && $oldImage !== $newImage) {
            Storage::disk('public')->delete($oldImage);
        }

        if ($changedOld) {
            $auditLog->record(
                action: 'update_agency_profile',
                page: 'agency_admin_profile',
                targetType: 'agency',
                targetId: (int) $agency->id,
                agencyId: (int) $agency->id,
                oldValues: $changedOld,
                newValues: $changedNew,
                description: 'Updated profile details for ' . $agency->agency_name,
            );
        }

        return redirect()->route('agency-admin.agency')->with('success', 'Agency profile updated successfully.');
    }

}
