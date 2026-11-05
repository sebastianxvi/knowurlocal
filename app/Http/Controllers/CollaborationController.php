<?php

namespace App\Http\Controllers;

use App\Events\CollaborationTaskUpdated;
use App\Models\Agency;
use App\Models\Category;
use App\Models\CollaborationTask;
use App\Models\Faq;
use App\Models\SupportRequest;
use App\Models\User;
use App\Models\UserLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CollaborationController extends Controller
{
    private const TARGETS = [
        'agency' => Agency::class,
        'faq' => Faq::class,
        'category' => Category::class,
        'support_request' => SupportRequest::class,
        'user' => User::class,
    ];

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'task_type' => ['required', 'in:handoff,review,assist'],
            'assigned_to_id' => ['required', 'integer'],
            'title' => ['required', 'string', 'max:140'],
            'description' => ['nullable', 'string', 'max:1000'],
            'target_type' => ['nullable', 'string', 'in:agency,faq,category,support_request,user'],
            'target_id' => ['nullable', 'integer'],
            'due_at' => ['nullable', 'date', 'after:now'],
        ]);

        $actor = $request->user();
        $assignableRoles = match ($actor->role) {
            'agency_admin' => ['admin', 'superadmin'],
            'admin', 'superadmin' => ['admin', 'superadmin', 'agency_admin'],
            default => [],
        };

        $assignee = User::query()
            ->whereIn('role', $assignableRoles)
            ->where('status', 'active')
            ->findOrFail($data['assigned_to_id']);

        if ($assignee->id === auth()->id()) {
            return response()->json([
                'message' => 'A collaboration task must be assigned to another active administrator.',
            ], 422);
        }

        $this->validateAssignmentScope($actor, $assignee, $data);

        [$targetType, $targetId, $targetLabel] = $this->resolveTarget($data, $assignee);

        $task = CollaborationTask::create([
            'created_by_id' => auth()->id(),
            'assigned_to_id' => $assignee->id,
            'task_type' => $data['task_type'],
            'status' => 'open',
            'target_type' => $targetType,
            'target_id' => $targetId,
            'target_label_snapshot' => $targetLabel,
            'title' => trim($data['title']),
            'description' => filled($data['description'] ?? null) ? trim($data['description']) : null,
            'due_at' => $data['due_at'] ?? null,
        ]);

        $this->log($task, 'create_collaboration_task');
        broadcast(new CollaborationTaskUpdated($task, 'created'));

        return response()->json([
            'message' => 'Collaboration task created and assigned.',
            'task' => $this->taskPayload($task->load(['creator', 'assignee', 'target'])),
        ], 201);
    }

    public function updateStatus(Request $request, CollaborationTask $task): JsonResponse
    {
        $request->validate([
            'status' => ['required', 'in:open,in_progress,completed,cancelled'],
        ]);

        $this->authorizeTask($task, $request->status);

        $task->status = $request->status;
        $task->completed_at = $request->status === 'completed' ? now() : null;
        $task->save();

        $this->log($task, 'update_collaboration_task');
        broadcast(new CollaborationTaskUpdated($task, 'status_updated'));

        return response()->json([
            'message' => 'Collaboration task updated.',
            'status' => $task->status_label,
        ]);
    }

    public function targets(Request $request): JsonResponse
    {
        $type = $request->query('type');
        $search = trim((string) $request->query('search', ''));

        if (!isset(self::TARGETS[$type])) {
            return response()->json(['data' => []]);
        }

        $model = self::TARGETS[$type];
        $query = $model::query();

        if (in_array($model, [Agency::class, Faq::class, Category::class, SupportRequest::class], true)) {
            $query->whereNull('deleted_at');
        }

        if ($model === User::class) {
            $actor = auth()->user();
            $roles = match ($actor->role) {
                'agency_admin' => ['admin', 'superadmin'],
                'admin', 'superadmin' => ['admin', 'superadmin', 'agency_admin'],
                default => [],
            };
            $query->whereIn('role', $roles)->where('status', 'active');
        }

        $assignee = User::query()->find($request->query('assigned_to_id'));
        $this->scopeAgencyTargets($query, $model, $assignee);

        $this->applyTargetSearch($query, $model, $search);

        $items = $query->latest('id')->limit(30)->get();

        return response()->json([
            'data' => $items->map(fn ($item) => [
                'id' => $item->id,
                'label' => $this->targetLabel($model, $item),
            ])->values(),
        ]);
    }

    private function resolveTarget(array $data, User $assignee): array
    {
        if (blank($data['target_type'] ?? null) && blank($data['target_id'] ?? null)) {
            return [null, null, null];
        }

        $type = $data['target_type'] ?? null;
        $id = $data['target_id'] ?? null;

        if (!isset(self::TARGETS[$type]) || !$id) {
            throw ValidationException::withMessages([
                'target_id' => 'A valid collaboration target is required.',
            ]);
        }

        $model = self::TARGETS[$type];
        $query = $model::query();

        if (in_array($model, [Agency::class, Faq::class, Category::class, SupportRequest::class], true)) {
            $query->whereNull('deleted_at');
        }

        if ($model === User::class) {
            $query->whereIn('role', ['admin', 'superadmin', 'agency_admin'])
                ->where('status', 'active');
        }

        $this->scopeAgencyTargets($query, $model, $assignee);

        $target = $query->findOrFail($id);

        return [$model, $target->id, Str::limit($this->targetLabel($model, $target), 255, '…')];
    }

    /**
     * Assignment scope is determined by the recipient as well as the actor.
     * Agency administrators may only receive work tied to records in their
     * own agency, never global account/category administration.
     */
    private function validateAssignmentScope(User $actor, User $assignee, array $data): void
    {
        if ($actor->role === 'agency_admin') {
            if (!in_array($assignee->role, ['admin', 'superadmin'], true)) {
                abort(403, 'Agency administrators may request assistance only from the system administration team.');
            }

            if (in_array($data['target_type'] ?? null, ['category', 'user'], true)) {
                abort(422, 'Agency collaboration can only reference agency, FAQ, or support-request records.');
            }
        }

        if (blank($data['target_type'] ?? null) || empty($data['target_id'])) {
            throw ValidationException::withMessages([
                'target_id' => 'Link the task to the specific record or module that needs action.',
            ]);
        }

        if (blank($data['description'] ?? null)) {
            throw ValidationException::withMessages([
                'description' => 'Provide clear instructions and the expected outcome for this task.',
            ]);
        }

        if ($assignee->role === 'agency_admin'
            && !in_array($data['target_type'], ['agency', 'faq', 'support_request'], true)) {
            throw ValidationException::withMessages([
                'target_type' => 'Agency Admin tasks must concern an agency profile, FAQ, or support request.',
            ]);
        }
    }

    private function scopeAgencyTargets($query, string $model, ?User $assignee = null): void
    {
        $actor = auth()->user();
        $agencyAdmin = $assignee?->role === 'agency_admin' ? $assignee : null;

        if ($actor?->role === 'agency_admin') {
            $agencyAdmin = $actor;
        }

        if (!$agencyAdmin) {
            return;
        }

        if ($model === Agency::class) {
            $query->whereKey($agencyAdmin->agency_id);
        } elseif (in_array($model, [Faq::class, SupportRequest::class], true)) {
            $query->where('agency_id', $agencyAdmin->agency_id);
        } elseif (in_array($model, [Category::class, User::class], true)) {
            $query->whereRaw('1 = 0');
        }
    }

    private function applyTargetSearch($query, string $model, string $search): void
    {
        if ($search === '') {
            return;
        }

        $like = '%' . addcslashes($search, '%_') . '%';

        if ($model === Agency::class) {
            $query->where(function ($q) use ($like) {
                $q->where('agency_name', 'LIKE', $like)->orWhere('agency_abbreviation', 'LIKE', $like);
            });
        } elseif ($model === Faq::class) {
            $query->where(function ($q) use ($like) {
                $q->where('question', 'LIKE', $like)->orWhere('question_fil', 'LIKE', $like);
            });
        } elseif ($model === Category::class) {
            $query->where('category_name', 'LIKE', $like);
        } elseif ($model === SupportRequest::class) {
            $query->where(function ($q) use ($like) {
                $q->where('question', 'LIKE', $like);
            });
        } elseif ($model === User::class) {
            $query->where(function ($q) use ($like) {
                $q->where('first_name', 'LIKE', $like)
                    ->orWhere('last_name', 'LIKE', $like)
                    ->orWhere('email', 'LIKE', $like);
            });
        }
    }

    private function targetLabel(string $model, $target): string
    {
        return match ($model) {
            Agency::class => (string) $target->agency_name,
            Faq::class => Str::limit((string) ($target->question ?: 'FAQ #' . $target->id), 120),
            Category::class => (string) $target->category_name,
            SupportRequest::class => Str::limit('#' . $target->id . ' — ' . (string) $target->question, 120),
            User::class => (trim($target->first_name . ' ' . $target->last_name) ?: (string) $target->email)
                . ($target->role === 'agency_admin' && $target->agency?->agency_name
                    ? ' — ' . $target->agency->agency_name
                    : ''),
            default => 'Linked record',
        };
    }

    private function authorizeTask(CollaborationTask $task, string $nextStatus): void
    {
        $user = auth()->user();

        if ($user->role === 'superadmin') {
            return;
        }

        if ($task->assigned_to_id === $user->id) {
            if ($nextStatus === 'cancelled') {
                abort(403, 'Only the task creator or a superadmin can cancel a collaboration task.');
            }
            return;
        }

        if ($task->created_by_id === $user->id && $nextStatus === 'cancelled') {
            return;
        }

        abort(403, 'You are not allowed to update this collaboration task.');
    }

    private function log(CollaborationTask $task, string $action): void
    {
        UserLog::create([
            'user_id' => auth()->id(),
            'target_type' => 'collaboration_task',
            'target_id' => $task->id,
            'action' => $action,
            'page' => 'admin_dashboard',
            'role' => auth()->user()->role,
            'ip_address' => request()->ip(),
            'device' => substr((string) request()->userAgent(), 0, 255),
            'new_values' => [
                'task_id' => $task->id,
                'title' => $task->title,
                'task_type' => $task->task_type,
                'status' => $task->status,
                'assigned_to_id' => $task->assigned_to_id,
                'target_type' => $task->target_type,
                'target_id' => $task->target_id,
            ],
            'description' => $action === 'create_collaboration_task'
                ? 'Created collaboration task #' . $task->id
                : 'Updated collaboration task #' . $task->id,
        ]);
    }

    private function taskPayload(CollaborationTask $task): array
    {
        return [
            'id' => $task->id,
            'title' => $task->title,
            'type' => $task->task_type_label,
            'status' => $task->status,
            'status_label' => $task->status_label,
            'target_module' => $task->target_module_label,
            'target_label' => $task->target_label,
            'assignee' => trim(($task->assignee?->first_name ?? '') . ' ' . ($task->assignee?->last_name ?? '')) ?: $task->assignee?->email,
        ];
    }
}
