<?php

namespace App\Services;

use App\Events\UserNotificationCreated;
use App\Models\SupportRequest;
use App\Models\UserNotification;
use Illuminate\Support\Facades\Log;
use Throwable;

class SupportRequestNotificationService
{
    public function ticketTrashed(SupportRequest $ticket, string $actorRole): ?UserNotification
    {
        if (!$ticket->user_id) {
            return null;
        }

        $key = 'ticket-trashed:' . $ticket->id . ':' . ($ticket->deleted_at?->format('Uu') ?? now()->format('Uu'));
        $agencyName = $ticket->agency?->agency_name ?: 'the assigned agency';
        $reason = trim((string) $ticket->trash_reason);
        $message = 'Ticket #' . $ticket->id . ' sent to ' . $agencyName . ' was moved to trash.';
        if ($reason !== '') {
            $message .= ' Reason: ' . $reason;
        }

        return $this->create([
            'user_id' => (int) $ticket->user_id,
            'support_request_id' => (int) $ticket->id,
            'type' => 'ticket_trashed',
            'title' => 'Your ticket was moved to trash',
            'message' => $message,
            'data' => [
                'agency_id' => $ticket->agency_id ? (int) $ticket->agency_id : null,
                'agency_name' => $agencyName,
                'actor_role' => $actorRole,
                'trash_reason' => $reason,
            ],
            'dedupe_key' => $key,
        ]);
    }

    public function responseCreated(SupportRequest $ticket, ?int $responseId, string $actorRole): ?UserNotification
    {
        if (!$ticket->user_id) {
            return null;
        }

        $agencyName = $ticket->agency?->agency_name ?: 'the administration';
        $key = $responseId !== null
            ? 'ticket-response:' . $ticket->id . ':' . $responseId
            : 'ticket-answer:' . $ticket->id . ':' . now()->format('Uu');

        return $this->create([
            'user_id' => (int) $ticket->user_id,
            'support_request_id' => (int) $ticket->id,
            'response_id' => $responseId,
            'type' => 'admin_response',
            'title' => 'New response to your ticket',
            'message' => 'There is a new official response from ' . $agencyName . ' for Ticket #' . $ticket->id . ': ' . trim((string) $ticket->question),
            'data' => [
                'agency_id' => $ticket->agency_id ? (int) $ticket->agency_id : null,
                'agency_name' => $agencyName,
                'actor_role' => $actorRole,
            ],
            'dedupe_key' => $key,
        ]);
    }

    private function create(array $attributes): ?UserNotification
    {
        try {
            $notification = UserNotification::query()->firstOrCreate(
                ['dedupe_key' => $attributes['dedupe_key']],
                $attributes
            );

            if ($notification->wasRecentlyCreated) {
                try {
                    broadcast(new UserNotificationCreated($notification));
                } catch (Throwable $exception) {
                    Log::warning('Citizen notification realtime broadcast failed.', [
                        'notification_id' => $notification->id,
                        'user_id' => $notification->user_id,
                        'error' => $exception->getMessage(),
                    ]);
                }
            }

            return $notification;
        } catch (Throwable $exception) {
            // Do not roll back an already completed ticket/response action if
            // notification persistence is temporarily unavailable. The failure
            // is logged with identifiers only, without notification contents.
            Log::error('Unable to persist citizen support notification.', [
                'user_id' => $attributes['user_id'] ?? null,
                'support_request_id' => $attributes['support_request_id'] ?? null,
                'type' => $attributes['type'] ?? null,
                'error' => $exception->getMessage(),
            ]);

            return null;
        }
    }
}
