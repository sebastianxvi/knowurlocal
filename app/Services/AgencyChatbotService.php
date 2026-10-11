<?php

namespace App\Services;

use App\Models\Agency;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Low-cost, database-backed retrieval for public agency information.
 *
 * This service never calls an AI provider. It only returns fields stored
 * on active agency records and creates links using the existing route.
 */
class AgencyChatbotService
{
    private const STOP_WORDS = [
        'a', 'an', 'the', 'is', 'are', 'was', 'were', 'be', 'been', 'being',
        'to', 'of', 'for', 'in', 'on', 'at', 'by', 'with', 'and', 'or',
        'what', 'where', 'when', 'who', 'which', 'how', 'can', 'could',
        'does', 'do', 'did', 'isnt', 'please', 'tell', 'me', 'about',
        'give', 'show', 'find', 'get', 'located', 'location', 'address',
        'offer', 'offers', 'offered', 'provide', 'provides', 'providing',
        'services', 'service', 'office', 'agency', 'department', 'the',
        'ng', 'ang', 'sa', 'si', 'saan', 'nasaan', 'ano', 'anong', 'mga',
        'ba', 'po', 'paki', 'pakisabi', 'pakibigay', 'may', 'meron',
        'dito', 'iyan', 'ito', 'nila', 'para', 'ba', 'yung', 'po', 'ang', 'ako', 'ko', 'muna', 'paano', 'magkano', 'presyo', 'bayad',
    ];

    /**
     * Deterministic search synonyms for common public-agency acronyms.
     * These are search hints only: responses still come exclusively from
     * agency records, and ambiguity is handled by the normal ranker.
     */
    private const AGENCY_ALIASES = [
        'pnp' => ['police'],
        'bir' => ['revenue', 'tax'],
        'lto' => ['transportation', 'transport'],
        'dole' => ['labor', 'employment'],
        'dswd' => ['social', 'welfare'],
        'dti' => ['trade', 'industry'],
        'dfa' => ['foreign', 'affairs'],
        'sss' => ['social', 'security'],
    ];

    /**
     * Return a response when the question is clearly about agency records.
     * Null means this service should not claim the question and the existing
     * FAQ pipeline should continue unchanged.
     */
    public function answer(string $question): ?array
    {
        $intent = $this->detectIntent($question);

        if ($intent === null) {
            return null;
        }

        $tokens = $this->tokens($question);
        if ($tokens === []) {
            return null;
        }

        $candidates = $this->findCandidates($tokens);

        /*
         * Users commonly refer to an agency by an acronym even when the
         * abbreviation field is missing or inconsistently populated. For
         * example, "BIR" can match "Bureau of Internal Revenue" by its
         * initials. Run this compact, cached name-index fallback only when
         * the normal indexed text search found nothing, then fetch full
         * public fields for the small set of matched IDs.
         */
        // Always merge acronym matches: a weak substring hit (for example,
        // an unrelated record containing "dar") must not suppress the exact
        // Department of Agrarian Reform initialism match.
        $candidates = $candidates
            ->concat($this->findCandidatesByInitialism($tokens))
            ->keyBy('id')
            ->values();

        // Alias search is also merged, not used only when the first query is
        // empty; otherwise unrelated partial hits can block a better acronym
        // or Tagalog agency reference.
        $candidates = $candidates
            ->concat($this->findCandidatesByAliases($tokens))
            ->keyBy('id')
            ->values();

        if ($candidates->isEmpty()) {
            Log::debug('KNOWURLOCAL agency lookup found no candidate.', [
                'intent' => $intent,
                'tokens' => $tokens,
            ]);
            return null;
        }

        $ranked = $candidates
            ->map(fn (Agency $agency) => [
                'agency' => $agency,
                'score' => $this->score($agency, $tokens, $question),
            ])
            ->filter(fn (array $item) => $item['score'] >= 25)
            ->sortByDesc('score')
            ->values();

        if ($ranked->isEmpty()) {
            Log::debug('KNOWURLOCAL agency lookup candidates did not meet confidence threshold.', [
                'intent' => $intent,
                'candidate_count' => $candidates->count(),
                'tokens' => $tokens,
            ]);
            return null;
        }

        // If two agencies have similarly strong matches, don't guess which
        // branch the user meant. Return a deterministic clarification.
        $first = $ranked->first();
        $second = $ranked->get(1);
        if (
            $second !== null
            && ($first['score'] - $second['score']) <= 5
        ) {
            return $this->multipleAgencyResponse(
                $ranked->take(3)->pluck('agency'),
                $intent
            );
        }

        /** @var Agency $agency */
        $agency = $first['agency'];

        return $this->agencyResponse($agency, $intent);
    }

    private function detectIntent(string $question): ?string
    {
        $q = Str::lower(trim($question));

        $patterns = [
            'services' => '/\b(service|services|offer|offers|provide|provides|serbisyo|nagbibigay|nag-aalok)\b/u',
            'location' => '/\b(where|address|located|location|directions?|navigate|nearest|near me|saan|nasaan|lokasyon|direksyon|pinakamalapit)\b/u',
            'contact' => '/\b(contact|phone|telephone|hotline|email|website|facebook|number|tumawag|kontak|numero|telepono)\b/u',
            'hours' => '/\b(hours|office hours|open|opening|close|closing|schedule|operating|oras|bukas|sarado)\b/u',
            'general' => '/\b(agency|office|department|branch|establishment|ahensya|opisina|sangay|tell me about|information about|info about|details about|describe|who is)\b/u',
        ];

        foreach ($patterns as $intent => $pattern) {
            if (preg_match($pattern, $q)) {
                return $intent;
            }
        }

        return null;
    }

    private function tokens(string $question): array
    {
        $normalized = Str::lower(Str::ascii($question));
        $words = preg_split('/[^a-z0-9]+/u', $normalized, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $tokens = [];
        foreach ($words as $word) {
            if (strlen($word) < 2 || in_array($word, self::STOP_WORDS, true)) {
                continue;
            }
            $tokens[$word] = $word;
        }

        return array_slice(array_values($tokens), 0, 8);
    }

    private function findCandidates(array $tokens): Collection
    {
        $query = Agency::query()
            ->select([
                'id',
                'agency_name',
                'agency_abbreviation',
                'agency_location',
                'agency_description',
                'services_offered',
                'office_hours',
                'lat',
                'lng',
                'agency_type_id',
                'category_id',
            ])
            ->with([
                'type:id,name',
                'category:id,category_name',
            ]);

        $query->where(function (Builder $outer) use ($tokens) {
            foreach ($tokens as $token) {
                $like = '%' . addcslashes($token, '\\%_') . '%';
                $outer->orWhere('agency_name', 'like', $like)
                    ->orWhere('agency_abbreviation', 'like', $like)
                    ->orWhere('agency_location', 'like', $like)
                    ->orWhere('agency_description', 'like', $like)
                    ->orWhere('services_offered', 'like', $like)
                    ->orWhereHas('type', fn (Builder $typeQuery) => $typeQuery->where('name', 'like', $like))
                    ->orWhereHas('category', fn (Builder $categoryQuery) => $categoryQuery->where('category_name', 'like', $like));
            }
        });

        return $query->limit(40)->get();
    }

    /**
     * Resolve acronym-only references against agency names when an agency's
     * abbreviation field is blank or does not contain the expected acronym.
     *
     * Only compact name fields are cached; complete agency data and contact
     * information are fetched only for the matched IDs.
     */
    private function findCandidatesByInitialism(array $tokens): Collection
    {
        $acronymTokens = array_values(array_filter(
            $tokens,
            fn (string $token) => strlen($token) >= 2 && preg_match('/^[a-z0-9]+$/', $token)
        ));

        if ($acronymTokens === []) {
            return collect();
        }

        $agencyIndex = Cache::remember('chatbot.agency-name-index.v1', now()->addMinutes(5), function () {
            return Agency::query()
                ->select(['id', 'agency_name', 'agency_abbreviation'])
                ->get()
                ->map(fn (Agency $agency) => [
                    'id' => (int) $agency->id,
                    'name' => (string) $agency->agency_name,
                    'abbreviation' => (string) $agency->agency_abbreviation,
                ])
                ->all();
        });

        $matchedIds = [];
        foreach ($agencyIndex as $item) {
            $initials = $this->initialism((string) $item['name']);
            $storedAbbreviation = $this->normalize($item['abbreviation']);
            foreach ($acronymTokens as $token) {
                if ($token === $initials || ($storedAbbreviation !== '' && $token === $storedAbbreviation)) {
                    $matchedIds[] = $item['id'];
                    break;
                }
            }
        }

        $matchedIds = array_values(array_unique($matchedIds));
        if ($matchedIds === []) {
            return collect();
        }

        return Agency::query()
            ->select([
                'id',
                'agency_name',
                'agency_abbreviation',
                'agency_location',
                'agency_description',
                'services_offered',
                'office_hours',
                'lat',
                'lng',
                'agency_type_id',
                'category_id',
            ])
            ->with([
                'type:id,name',
                'category:id,category_name',
            ])
            ->whereIn('id', $matchedIds)
            ->get();
    }

    /**
     * Search existing agency fields using conservative, explicit acronym
     * synonyms when the acronym itself is not stored in the record.
     */
    private function findCandidatesByAliases(array $tokens): Collection
    {
        $aliases = [];
        foreach ($tokens as $token) {
            foreach (self::AGENCY_ALIASES[$token] ?? [] as $alias) {
                $aliases[] = $alias;
            }
        }

        $aliases = array_values(array_unique($aliases));
        if ($aliases === []) {
            return collect();
        }

        $query = Agency::query()
            ->select([
                'id',
                'agency_name',
                'agency_abbreviation',
                'agency_location',
                'agency_description',
                'services_offered',
                'office_hours',
                'lat',
                'lng',
                'agency_type_id',
                'category_id',
            ])
            ->with([
                'type:id,name',
                'category:id,category_name',
            ]);

        $query->where(function (Builder $outer) use ($aliases) {
            foreach ($aliases as $alias) {
                $like = '%' . addcslashes($alias, '\\%_') . '%';
                $outer->orWhere('agency_name', 'like', $like)
                    ->orWhere('agency_abbreviation', 'like', $like)
                    ->orWhere('agency_location', 'like', $like)
                    ->orWhere('agency_description', 'like', $like)
                    ->orWhere('services_offered', 'like', $like)
                    ->orWhereHas('type', fn (Builder $typeQuery) => $typeQuery->where('name', 'like', $like))
                    ->orWhereHas('category', fn (Builder $categoryQuery) => $categoryQuery->where('category_name', 'like', $like));
            }
        });

        return $query->limit(40)->get();
    }

    private function score(Agency $agency, array $tokens, string $question): float
    {
        $name = $this->normalize((string) $agency->agency_name);
        $abbr = $this->normalize((string) $agency->agency_abbreviation);
        $location = $this->normalize((string) $agency->agency_location);
        $description = $this->normalize((string) $agency->agency_description);
        $services = $this->normalize((string) $agency->services_offered);
        $category = $this->normalize((string) ($agency->category?->category_name ?? ''));
        $type = $this->normalize((string) ($agency->type?->name ?? ''));
        $q = $this->normalize($question);

        $score = 0.0;
        if ($name !== '' && str_contains($q, $name)) {
            $score += 30;
        }
        if ($abbr !== '' && str_contains($q, $abbr)) {
            $score += 35;
        }

        $initialism = $this->initialism((string) $agency->agency_name);
        foreach ($tokens as $token) {
            if ($initialism !== '' && $token === $initialism) {
                $score += 35;
            }
            foreach (self::AGENCY_ALIASES[$token] ?? [] as $alias) {
                if (
                    ($name !== '' && str_contains($name, $alias))
                    || ($abbr !== '' && str_contains($abbr, $alias))
                    || ($location !== '' && str_contains($location, $alias))
                    || ($description !== '' && str_contains($description, $alias))
                    || ($services !== '' && str_contains($services, $alias))
                    || ($category !== '' && str_contains($category, $alias))
                    || ($type !== '' && str_contains($type, $alias))
                ) {
                    $score += 35;
                    break;
                }
            }
            if ($abbr !== '' && $abbr === $token) {
                $score += 24;
            } elseif ($name !== '' && preg_match('/\b' . preg_quote($token, '/') . '\b/', $name)) {
                $score += 12;
            } elseif ($services !== '' && str_contains($services, $token)) {
                $score += 5;
            } elseif ($location !== '' && str_contains($location, $token)) {
                $score += 4;
            } elseif ($category !== '' && str_contains($category, $token)) {
                $score += 4;
            } elseif ($type !== '' && str_contains($type, $token)) {
                $score += 3;
            } elseif ($description !== '' && str_contains($description, $token)) {
                $score += 2;
            }
        }

        return $score;
    }

    private function agencyResponse(Agency $agency, string $intent): array
    {
        $lines = [$agency->agency_name];

        if (filled($agency->agency_abbreviation)) {
            $lines[0] .= ' (' . $agency->agency_abbreviation . ')';
        }

        if (in_array($intent, ['location', 'general'], true)) {
            if (filled($agency->agency_location)) {
                $lines[] = 'Address/location: ' . trim((string) $agency->agency_location);
            } else {
                $lines[] = 'A location address is not currently recorded for this agency.';
            }
        }

        if (in_array($intent, ['services', 'general'], true)) {
            if (filled($agency->services_offered)) {
                $lines[] = 'Services offered: ' . trim((string) $agency->services_offered);
            } else {
                $lines[] = 'Services offered are not currently recorded for this agency.';
            }
            if (filled($agency->agency_description)) {
                $lines[] = 'About: ' . trim((string) $agency->agency_description);
            }
        }

        if (in_array($intent, ['hours', 'general'], true)) {
            if (filled($agency->office_hours)) {
                $lines[] = 'Office hours: ' . trim((string) $agency->office_hours);
            } elseif ($intent === 'hours') {
                $lines[] = 'Office hours are not currently recorded for this agency.';
            }
        }

        if (in_array($intent, ['contact', 'general'], true)) {
            $agency->load([
                'contacts' => function ($contacts) {
                    $contacts->select([
                        'id', 'agency_id', 'contact_type_id', 'label',
                        'value', 'is_primary', 'sort_order',
                    ])->with('contactType:id,name,slug,is_active');
                },
            ]);

            $contacts = $agency->contacts
                ->filter(fn ($contact) => $contact->contactType && $contact->contactType->is_active)
                ->sortByDesc(fn ($contact) => $contact->is_primary ? 1 : 0)
                ->take(6);

            if ($contacts->isNotEmpty()) {
                $lines[] = 'Contact information:';
                foreach ($contacts as $contact) {
                    $type = $contact->contactType?->name ?? 'Contact';
                    $label = trim((string) $contact->label);
                    $lines[] = '- ' . $type . ($label !== '' ? ' (' . $label . ')' : '') . ': ' . trim((string) $contact->value);
                }
            } elseif ($intent === 'contact') {
                $lines[] = 'Public contact information is not currently recorded for this agency.';
            }
        }

        if ($intent === 'location' && filled($agency->agency_description)) {
            $lines[] = 'About: ' . trim((string) $agency->agency_description);
        }

        $attachments = [];
        if ($agency->lat !== null && $agency->lng !== null) {
            $attachments[] = [
                'type' => 'link',
                'label' => 'Open navigation to ' . $agency->agency_name,
                'url' => route('navigate', ['agency' => $agency->id]),
                'download' => false,
            ];
        } else {
            $lines[] = 'A navigation link is unavailable because this agency does not have map coordinates recorded yet.';
        }

        return [
            'content' => implode("\n\n", $lines) . "\n\nSource: KNOWURLOCAL agency information database.",
            'attachments' => $attachments,
            'image' => null,
            'match_method' => 'agency_database',
            'agency_id' => (int) $agency->id,
        ];
    }

    private function multipleAgencyResponse(Collection $agencies, string $intent): array
    {
        $lines = ['I found a few agencies that may match. Which one did you mean?'];
        $attachments = [];

        foreach ($agencies as $agency) {
            $lines[] = '- ' . $agency->agency_name
                . (filled($agency->agency_abbreviation) ? ' (' . $agency->agency_abbreviation . ')' : '')
                . (filled($agency->agency_location) ? ' — ' . $agency->agency_location : '');

            if ($agency->lat !== null && $agency->lng !== null) {
                $attachments[] = [
                    'type' => 'link',
                    'label' => 'Navigate to ' . $agency->agency_name,
                    'url' => route('navigate', ['agency' => $agency->id]),
                    'download' => false,
                ];
            }
        }

        return [
            'content' => implode("\n\n", $lines) . "\n\nSource: KNOWURLOCAL agency information database.",
            'attachments' => $attachments,
            'image' => null,
            'match_method' => 'agency_database_clarification',
            'agency_id' => null,
        ];
    }

    private function initialism(string $name): string
    {
        $words = preg_split(
            '/[^a-z0-9]+/u',
            Str::lower(Str::ascii($name)),
            -1,
            PREG_SPLIT_NO_EMPTY
        ) ?: [];

        $initials = '';
        foreach ($words as $word) {
            if (in_array($word, ['and', 'of', 'the', 'for', 'ng', 'sa'], true)) {
                continue;
            }
            $initials .= $word[0] ?? '';
        }

        return $initials;
    }

    private function normalize(string $value): string
    {
        return trim(preg_replace('/[^a-z0-9]+/u', ' ', Str::lower(Str::ascii($value))) ?? '');
    }
}
