<?php

namespace App\Services;

use App\Models\UserLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuditLogService
{
    public function __construct(private readonly Request $request)
    {
    }

    /**
     * Record a normalized audit event.
     *
     * Existing entity-specific foreign keys are intentionally retained for
     * backwards compatibility. target_type/target_id provide a generic target
     * for newer modules such as collaboration tasks.
     */
    public function record(
        string $action,
        string $page,
        ?string $targetType = null,
        ?int $targetId = null,
        ?int $targetUserId = null,
        ?int $agencyId = null,
        ?int $faqId = null,
        ?int $categoryId = null,
        ?int $supportRequestId = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $description = null,
        ?int $actorId = null,
        ?string $role = null,
    ): UserLog {
        $actor = Auth::user();

        return UserLog::create([
            'user_id' => $actorId ?? $actor?->id,
            'target_user_id' => $targetUserId,
            'agency_id' => $agencyId,
            'faq_id' => $faqId,
            'category_id' => $categoryId,
            'support_request_id' => $supportRequestId,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'action' => $action,
            'page' => $page,
            'role' => $role ?? $actor?->role,
            'ip_address' => $this->request->ip(),
            'device' => substr((string) $this->request->userAgent(), 0, 255),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'description' => $description,
        ]);
    }
}
