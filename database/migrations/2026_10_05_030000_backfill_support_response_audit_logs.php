<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Backfill the structured official response into historical
     * Forwarded Official Response audit records.
     *
     * The response workflow was moved from SupportRequest.answer into
     * support_request_responses/support_response_components. Existing
     * audit rows therefore only contain the old SupportRequest snapshot
     * and cannot display the response content unless it is copied into
     * the immutable audit payload.
     */
    public function up(): void
    {
        DB::table('user_logs')
            ->where('action', 'forward_support_response')
            ->whereNotNull('support_request_id')
            ->orderBy('id')
            ->chunkById(100, function ($logs) {
                foreach ($logs as $log) {
                    $newValues = $this->decodeJson($log->new_values);

                    if (isset($newValues['official_response'])) {
                        continue;
                    }

                    /*
                     * The audit row is created immediately after the
                     * response transaction succeeds, so the most recent
                     * response forwarded before the log timestamp is the
                     * authoritative response for this audit event.
                     */
                    $response = DB::table('support_request_responses')
                        ->where('support_request_id', $log->support_request_id)
                        ->whereNotNull('forwarded_at')
                        ->where('forwarded_at', '<=', $log->created_at)
                        ->orderByDesc('forwarded_at')
                        ->orderByDesc('id')
                        ->first();

                    if (!$response) {
                        continue;
                    }

                    $components = DB::table('support_response_components')
                        ->where('support_request_response_id', $response->id)
                        ->orderBy('sort_order')
                        ->orderBy('id')
                        ->get();

                    $newValues['official_response'] = [
                        'id' => (int) $response->id,
                        'status' => $response->status,
                        'forwarded_at' => $response->forwarded_at,
                        'responded_at' => $response->responded_at,
                        'follow_up_reason' => $response->follow_up_reason,
                        'components' => $components
                            ->map(function ($component) {
                                $type = (string) $component->type;
                                $hasAttachment = in_array($type, ['image', 'file'], true);

                                return [
                                    'type' => $type,
                                    'label' => $component->label,
                                    'content' => $hasAttachment ? null : $component->content,
                                    'attachment' => $hasAttachment,
                                    'sort_order' => (int) $component->sort_order,
                                ];
                            })
                            ->values()
                            ->all(),
                    ];

                    DB::table('user_logs')
                        ->where('id', $log->id)
                        ->update([
                            'new_values' => json_encode($newValues, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                        ]);
                }
            });
    }

    public function down(): void
    {
        /*
         * Do not remove historical audit data automatically. The response
         * snapshot is intentionally immutable once written to the audit log.
         */
    }

    private function decodeJson($value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (!is_string($value) || trim($value) === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }
};
