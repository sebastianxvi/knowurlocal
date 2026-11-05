<?php

namespace App\Services;

use App\Events\UserNotificationCreated;
use App\Models\AgencyEvaluation;
use App\Models\AgencyEvaluationModerationLog;
use App\Models\UserNotification;
use Illuminate\Support\Facades\Log;
use Throwable;

class AgencyEvaluationNotificationService
{
    /**
     * Notify the evaluation author when an agency posts its first official response.
     * The notification contains no reviewer identity data; evaluation anonymity
     * remains enforced independently by public/admin evaluation rendering.
     */
    public function officialResponse(AgencyEvaluation $evaluation, \App\Models\AgencyEvaluationResponse $response): ?UserNotification
    {
        if (!$evaluation->user_id || !$evaluation->agency_id || !$response->exists) {
            return null;
        }

        $agencyName = $evaluation->agency?->agency_name ?: 'the agency';

        $attributes = [
            'user_id' => (int) $evaluation->user_id,
            'response_id' => (int) $response->id,
            'type' => 'evaluation_response',
            'title' => 'The agency responded to your evaluation',
            'message' => $agencyName . ' has posted an official response to your evaluation.',
            'data' => [
                'agency_id' => (int) $evaluation->agency_id,
                'agency_name' => $agencyName,
                'evaluation_id' => (int) $evaluation->id,
                'evaluation_response_id' => (int) $response->id,
            ],
            'dedupe_key' => 'agency-evaluation-response:' . $response->id,
        ];

        try {
            $notification = UserNotification::query()->firstOrCreate(
                ['dedupe_key' => $attributes['dedupe_key']],
                $attributes
            );

            if ($notification->wasRecentlyCreated) {
                try {
                    broadcast(new UserNotificationCreated($notification));
                } catch (Throwable $exception) {
                    Log::warning('Agency evaluation response notification broadcast failed.', [
                        'notification_id' => $notification->id,
                        'user_id' => $notification->user_id,
                        'error' => $exception->getMessage(),
                    ]);
                }
            }

            return $notification;
        } catch (Throwable $exception) {
            Log::error('Unable to persist agency evaluation response notification.', [
                'evaluation_id' => $evaluation->id,
                'response_id' => $response->id,
                'user_id' => $evaluation->user_id,
                'error' => $exception->getMessage(),
            ]);
            return null;
        }
    }

    public function moderationDecision(AgencyEvaluation $evaluation, AgencyEvaluationModerationLog $log, string $action): ?UserNotification
    {
        if (!$evaluation->user_id || !in_array($action, ['hide', 'restore', 'delete'], true)) {
            return null;
        }

        $agencyName = $evaluation->agency?->agency_name ?: 'the agency';
        [$type, $title, $message] = match ($action) {
            'hide' => [
                'evaluation_hidden',
                'Your evaluation is temporarily hidden',
                'Your evaluation for ' . $agencyName . ' has been temporarily hidden while it is being reviewed. You cannot edit it or submit another evaluation for this agency until the review is completed.',
            ],
            'restore' => [
                'evaluation_restored',
                'Your evaluation has been restored',
                'Your evaluation for ' . $agencyName . ' has been restored and is visible to the public again.',
            ],
            'delete' => [
                'evaluation_removed',
                'Your evaluation was removed',
                'Your evaluation for ' . $agencyName . ' was removed following moderation review. Reason: ' . (trim((string) $log->reason) ?: 'The evaluation violated the platform\'s evaluation guidelines.') . ' You may now submit a new evaluation for this agency.',
            ],
        };

        $attributes = [
            'user_id' => (int) $evaluation->user_id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => [
                'agency_id' => (int) $evaluation->agency_id,
                'agency_name' => $agencyName,
                'evaluation_id' => (int) $evaluation->id,
                'moderation_action' => $action,
                'moderation_reason' => $action === 'delete' ? trim((string) $log->reason) : null,
            ],
            'dedupe_key' => 'evaluation-moderation:' . $evaluation->id . ':' . $action . ':' . $log->id,
        ];

        try {
            $notification = UserNotification::query()->firstOrCreate(['dedupe_key' => $attributes['dedupe_key']], $attributes);
            if ($notification->wasRecentlyCreated) {
                try {
                    broadcast(new UserNotificationCreated($notification));
                } catch (Throwable $exception) {
                    Log::warning('Evaluation moderation notification broadcast failed.', [
                        'notification_id' => $notification->id,
                        'user_id' => $notification->user_id,
                        'error' => $exception->getMessage(),
                    ]);
                }
            }
            return $notification;
        } catch (Throwable $exception) {
            Log::error('Unable to persist evaluation moderation notification.', [
                'evaluation_id' => $evaluation->id,
                'user_id' => $evaluation->user_id,
                'action' => $action,
                'error' => $exception->getMessage(),
            ]);
            return null;
        }
    }
}
