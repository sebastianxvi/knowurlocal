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

        return 'Guest';
    }


    /**
     * 🎯 ACCESSOR: Clean Action Label
     *
     * Converts internal machine-readable action codes into
     * administrator-facing labels.
     *
     * The database continues storing stable action identifiers.
     */
    public function getActionLabelAttribute()
    {
        return match ($this->action) {

            /*
             * =====================================================
             * AGENCY
             * =====================================================
             */

            'create_agency' =>
                'Create Agency',

            'update_agency' =>
                'Update Agency',

            'trash_agency' =>
                'Move to Trash',

            'restore_agency' =>
                'Restore',

            'force_delete_agency' =>
                'Delete Permanently',

            'delete_agency' =>
                'Delete Agency',


            /*
             * =====================================================
             * FAQ
             * =====================================================
             */

            'create_faq' =>
                'Create FAQ',

            'update_faq' =>
                'Update FAQ',

            'delete_faq' =>
                'Move to Trash',

            'restore_faq' =>
                'Restore',

            'force_delete_faq' =>
                'Delete Permanently',


            /*
             * =====================================================
             * CATEGORY
             * =====================================================
             */

            'create_category' =>
                'Create Category',

            'update_category' =>
                'Update Category',

            'delete_category' =>
                'Move to Trash',

            'restore_category' =>
                'Restore',

            'force_delete_category' =>
                'Delete Permanently',


            /*
             * =====================================================
             * SUPPORT REQUESTS
             * =====================================================
             */

            'delete_support_request' =>
                'Move to Trash',

            'restore_support_request' =>
                'Restore',

            'force_delete_support_request' =>
                'Delete Permanently',


            /*
             * =====================================================
             * AUTHENTICATION
             * =====================================================
             */

            'login' =>
                'Login',

            'logout' =>
                'Logout',

            'admin_login' =>
                'Admin Login',

            'admin_logout' =>
                'Admin Logout',


            /*
            * =====================================================
            * ADMIN MANAGEMENT
            * =====================================================
            */

            'approve_admin' =>
                'Approve Admin',

            'promote_admin' =>
                'Promote Admin',

            'demote_admin' =>
                'Demote Admin',

            'deactivate_admin' =>
                'Deactivate Admin',

            'reactivate_admin' =>
                'Reactivate Admin',

            'delete_admin' =>
                'Delete Admin',

            'invite_admin' =>
                'Invite Admin',

                /*
                * =====================================================
                * PUBLIC USER MANAGEMENT
                * =====================================================
                */

                'deactivate_user' =>
                    'Deactivate User',

                'reactivate_user' =>
                    'Reactivate User',

                'delete_user' =>
                    'Delete User',


            /*
             * =====================================================
             * PUBLIC ACTIVITY
             * =====================================================
             */

            'view_map' =>
                'View Map',

            'view_agencies' =>
                'View Agencies',

            'view_agency' =>
                'View Agency',

            'search_agency' =>
                'Search Agency',

            'get_directions' =>
                'Get Directions',

            'contact_agency' =>
                'Contact Agency',

            'filter_category' =>
                'Filter Category',

            'navigate' =>
                'Navigate',


            /*
             * =====================================================
             * FALLBACK
             * =====================================================
             */

            default =>
                ucwords(
                    str_replace(
                        '_',
                        ' ',
                        $this->action
                    )
                ),
        };
    }


    /**
     * 🎯 ACCESSOR: Clean Page Label
     *
     * Converts internal page identifiers into administrator-
     * facing page names.
     */
    public function getPageLabelAttribute()
    {
        return match ($this->page) {

            /*
             * =====================================================
             * NGA & NGO
             * =====================================================
             */

            'nga_ngo_management' =>
                'NGA & NGO Management',

            'nga_ngo_recovery' =>
                'NGA & NGO Recovery',


            /*
             * =====================================================
             * FAQ
             * =====================================================
             */

            'admin_faq' =>
                'FAQ Management',

            'admin_faq_recovery' =>
                'FAQ Recovery',


            /*
             * =====================================================
             * CATEGORY
             * =====================================================
             */

            'admin_category' =>
                'Category Management',


            /*
            * =====================================================
            * USER MANAGEMENT
            * =====================================================
            */

            'admin_users' =>
                'User Management',


            /*
             * =====================================================
             * SUPPORT REQUESTS
             * =====================================================
             */

            'admin_support_requests' =>
                'Support Requests',


            /*
             * =====================================================
             * FALLBACK
             * =====================================================
             */

            default =>
                ucwords(
                    str_replace(
                        '_',
                        ' ',
                        $this->page
                    )
                ),
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