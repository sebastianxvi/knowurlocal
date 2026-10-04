<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserLog extends Model
{
    /**
     * 🔒 Mass assignable fields
     */
    protected $fillable = [
    'user_id',
    'target_user_id',
    'agency_id',
    'faq_id',
    'category_id',
    'support_request_id',
    'target_type',
    'target_id',

    'action',
    'page',
    'role',
    'ip_address',
    'device',

    'old_values',
    'new_values',

    'old_value',
    'new_value',
    'description',
];


    /**
     * Convert JSON audit snapshots into PHP arrays.
     *
     * This allows controllers and the Blade view to work with
     * structured audit information without manually decoding JSON.
     */
    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    /**
     * Return the most useful historical "before" payload.
     *
     * The audit system originally used the scalar old_value column and
     * later introduced old_values JSON. Some older records (especially
     * destructive admin actions) can therefore legitimately contain an
     * empty JSON array alongside a populated legacy value.
     */
    public function getAuditOldDataAttribute(): mixed
    {
        if (is_array($this->old_values) && $this->old_values !== []) {
            return $this->old_values;
        }

        if ($this->old_values !== null && $this->old_values !== '') {
            return $this->old_values;
        }

        return $this->old_value;
    }

    /**
     * Return the most useful historical "after" payload.
     *
     * Prefer structured JSON, but fall back to the legacy scalar column
     * when the structured payload is empty or missing.
     */
    public function getAuditNewDataAttribute(): mixed
    {
        if (is_array($this->new_values) && $this->new_values !== []) {
            return $this->new_values;
        }

        if ($this->new_values !== null && $this->new_values !== '') {
            return $this->new_values;
        }

        return $this->new_value;
    }


    /**
     * 🔗 RELATION: UserLog → Agency
     *
     * withTrashed() is important because an agency may have
     * already been deleted when an administrator views its log.
     */
    public function agency()
    {
        return $this->belongsTo(
            \App\Models\Agency::class
        )->withTrashed();
    }


    /**
     * 🔗 RELATION: UserLog → Category
     *
     * withTrashed() keeps historical category targets available
     * after a category has been moved to the trash.
     */
    public function category()
    {
        return $this->belongsTo(
            \App\Models\Category::class
        )->withTrashed();
    }

    /**
     * Historical FAQ target. Soft-deleted FAQs remain resolvable.
     */
    public function faq()
    {
        return $this->belongsTo(
            \App\Models\Faq::class
        )->withTrashed();
    }

    /**
 * 🔗 RELATION: Support Request
 *
 * Include soft-deleted Support Requests so historical
 * audit records remain resolvable while the request is
 * still in the recovery area.
 */
public function supportRequest()
{
    return $this->belongsTo(
        \App\Models\SupportRequest::class,
        'support_request_id'
    )->withTrashed();
}


    /**
     * Generic relationship for collaboration-task audit targets.
     */
    public function collaborationTask()
    {
        return $this->belongsTo(
            \App\Models\CollaborationTask::class,
            'target_id'
        );
    }


    /**
     * 🔗 RELATION: UserLog → User
     *
     * Represents the administrator or public user who generated
     * the activity log.
     */
    public function user()
    {
        return $this->belongsTo(
            \App\Models\User::class
        );
    }


    /**
     * 🎯 ACCESSOR: Actor Name
     *
     * Uses the users table as the authoritative source for the
     * person's current name.
     */
    public function getActorNameAttribute()
    {
        if ($this->user) {

            return $this->user->first_name .
                ' ' .
                $this->user->last_name;
        }

        return 'Unknown actor';
    }


    /**
     * 🎯 ACCESSOR: Clean Action Label
     *
     * Converts internal machine-readable action codes into
     * administrator-facing labels.
     *
     * The database continues storing stable action identifiers.
     */
    public function getActionLabelAttribute(): string
    {
        return config("activity_logs.actions.{$this->action}.label")
            ?? ucwords(str_replace('_', ' ', (string) $this->action));
    }

    public function getActionIconAttribute(): string
    {
        return config("activity_logs.actions.{$this->action}.icon", 'ph-lightning');
    }

    public function getActionGroupAttribute(): string
    {
        return config("activity_logs.actions.{$this->action}.group", 'Other Activity');
    }



    /**
     * 🎯 ACCESSOR: Clean Page Label
     *
     * Converts internal page identifiers into administrator-
     * facing page names.
     */
    public function getPageLabelAttribute(): string
    {
        return match ($this->page) {
            'nga_ngo_management' => 'NGA & NGO Management',
            'nga_ngo_recovery' => 'NGA & NGO Recovery',
            'admin_faq' => 'FAQ Management',
            'admin_faq_recovery' => 'FAQ Recovery',
            'admin_category' => 'Category Management',
            'admin_users' => 'User Management',
            'admin_support_requests' => 'Support Requests',
            'admin_management' => 'Admin Management',
            'admin_dashboard' => 'Admin Dashboard',
            'map' => 'Map',
            'agencies_list' => 'Agencies',
            'agency_details' => 'Agency Details',
            'user_inquiries' => 'My Inquiries',
            'chatbot' => 'Chatbot',
            'login' => 'Authentication',
            'navbar' => 'Navigation',
            default => $this->page
                ? ucwords(str_replace('_', ' ', $this->page))
                : 'System',
        };
    }



    /**
     * 🔗 RELATION: UserLog → Target User
     *
     * Used by administrator-management logs such as:
     *
     * - approve_admin
     * - promote_admin
     * - demote_admin
     * - delete_admin
     */
    public function targetUser()
    {
        return $this->belongsTo(
            \App\Models\User::class,
            'target_user_id'
        );
    }


    /**
 * 🎯 ACCESSOR: Historical Target User Name
 *
 * Returns the current target user's name when the account
 * still exists.
 *
 * If the target account was permanently deleted, the method
 * falls back to the historical snapshot stored in old_values.
 *
 * This keeps audit logs readable even after permanent deletion.
 */
public function getTargetUserNameAttribute(): ?string
{
    /*
     * Prefer the live User relationship when available.
     *
     * This is the authoritative source while the account exists.
     */
    if ($this->targetUser) {

        return trim(
            $this->targetUser->first_name .
            ' ' .
            $this->targetUser->last_name
        );
    }

    /*
     * Once the target account is permanently deleted,
     * targetUser() can no longer resolve the record.
     *
     * old_values therefore becomes the historical source
     * of truth.
     */
    if (
        $this->target_user_id &&
        is_array($this->old_values)
    ) {

        $firstName =
            $this->old_values['first_name'] ?? '';

        $lastName =
            $this->old_values['last_name'] ?? '';

        $name = trim(
            $firstName .
            ' ' .
            $lastName
        );

        if ($name !== '') {
            return $name;
        }

        /*
         * Fall back to the historical email if a name was
         * not available when the audit record was created.
         */
        if (
            !empty(
                $this->old_values['email']
            )
        ) {
            return $this->old_values['email'];
        }
    }

    return null;
}
}