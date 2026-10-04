<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Activity Log Catalog
    |--------------------------------------------------------------------------
    |
    | Keep the audit vocabulary in one place. Controllers store the stable
    | machine action while the Activity Logs UI uses this catalog for labels,
    | icons, grouping and access classification.
    |
    */
    'actions' => [
        'login' => ['label' => 'Sign In', 'icon' => 'ph-sign-in', 'group' => 'Authentication', 'audience' => 'user'],
        'logout' => ['label' => 'Sign Out', 'icon' => 'ph-sign-out', 'group' => 'Authentication', 'audience' => 'user'],
        'admin_login' => ['label' => 'Admin Sign In', 'icon' => 'ph-shield-check', 'group' => 'Authentication', 'audience' => 'admin'],
        'admin_logout' => ['label' => 'Admin Sign Out', 'icon' => 'ph-shield', 'group' => 'Authentication', 'audience' => 'admin'],
        'verify_account' => ['label' => 'Verified Account', 'icon' => 'ph-check-circle', 'group' => 'Authentication', 'audience' => 'user'],
        'password_reset' => ['label' => 'Reset Password', 'icon' => 'ph-lock-key', 'group' => 'Authentication', 'audience' => 'user'],
        'session_expired' => ['label' => 'Session Expired', 'icon' => 'ph-timer', 'group' => 'Authentication', 'audience' => 'user'],

        'view_agencies' => ['label' => 'Viewed Agencies', 'icon' => 'ph-buildings', 'group' => 'Discovery', 'audience' => 'user'],
        'view_agency' => ['label' => 'Viewed Agency', 'icon' => 'ph-building', 'group' => 'Discovery', 'audience' => 'user'],
        'search_agency' => ['label' => 'Searched Agency', 'icon' => 'ph-magnifying-glass', 'group' => 'Discovery', 'audience' => 'user'],
        'get_directions' => ['label' => 'Requested Directions', 'icon' => 'ph-navigation-arrow', 'group' => 'Discovery', 'audience' => 'user'],
        'contact_agency' => ['label' => 'Contacted Agency', 'icon' => 'ph-address-book', 'group' => 'Discovery', 'audience' => 'user'],
        'filter_category' => ['label' => 'Filtered by Category', 'icon' => 'ph-funnel-simple', 'group' => 'Discovery', 'audience' => 'user'],
        'navigate' => ['label' => 'Opened Navigation', 'icon' => 'ph-compass', 'group' => 'Discovery', 'audience' => 'user'],

        'submit_support_request' => ['label' => 'Submitted Support Request', 'icon' => 'ph-paper-plane-tilt', 'group' => 'Support', 'audience' => 'user'],
        'view_support_response' => ['label' => 'Viewed Support Response', 'icon' => 'ph-eye', 'group' => 'Support', 'audience' => 'user'],
        'view_trashed_inquiry' => ['label' => 'Viewed Trashed Inquiry', 'icon' => 'ph-trash', 'group' => 'Support', 'audience' => 'user'],
        'confirm_support_response' => ['label' => 'Confirmed Support Response', 'icon' => 'ph-check-circle', 'group' => 'Support', 'audience' => 'user'],
        'request_support_follow_up' => ['label' => 'Requested Follow-up', 'icon' => 'ph-arrow-counter-clockwise', 'group' => 'Support', 'audience' => 'user'],
        'submit_faq_feedback' => ['label' => 'Submitted FAQ Feedback', 'icon' => 'ph-thumbs-up', 'group' => 'Feedback', 'audience' => 'user'],

        'create_agency' => ['label' => 'Create Agency', 'icon' => 'ph-plus', 'group' => 'Agency Management', 'audience' => 'admin'],
        'update_agency' => ['label' => 'Update Agency', 'icon' => 'ph-pencil-simple', 'group' => 'Agency Management', 'audience' => 'admin'],
        'trash_agency' => ['label' => 'Move Agency to Trash', 'icon' => 'ph-trash', 'group' => 'Agency Management', 'audience' => 'admin'],
        'restore_agency' => ['label' => 'Restore Agency', 'icon' => 'ph-arrow-counter-clockwise', 'group' => 'Agency Management', 'audience' => 'admin'],
        'force_delete_agency' => ['label' => 'Permanently Delete Agency', 'icon' => 'ph-trash-simple', 'group' => 'Agency Management', 'audience' => 'admin'],
        'delete_agency' => ['label' => 'Delete Agency', 'icon' => 'ph-trash', 'group' => 'Agency Management', 'audience' => 'admin'],

        'create_faq' => ['label' => 'Create FAQ', 'icon' => 'ph-chat-centered-dots', 'group' => 'FAQ Management', 'audience' => 'admin'],
        'update_faq' => ['label' => 'Update FAQ', 'icon' => 'ph-pencil-simple', 'group' => 'FAQ Management', 'audience' => 'admin'],
        'delete_faq' => ['label' => 'Move FAQ to Trash', 'icon' => 'ph-trash', 'group' => 'FAQ Management', 'audience' => 'admin'],
        'restore_faq' => ['label' => 'Restore FAQ', 'icon' => 'ph-arrow-counter-clockwise', 'group' => 'FAQ Management', 'audience' => 'admin'],
        'force_delete_faq' => ['label' => 'Permanently Delete FAQ', 'icon' => 'ph-trash-simple', 'group' => 'FAQ Management', 'audience' => 'admin'],
        'convert_support_to_faq' => ['label' => 'Converted Support Request to FAQ', 'icon' => 'ph-arrow-right', 'group' => 'FAQ Management', 'audience' => 'admin'],

        'create_category' => ['label' => 'Create Category', 'icon' => 'ph-plus', 'group' => 'Category Management', 'audience' => 'admin'],
        'update_category' => ['label' => 'Update Category', 'icon' => 'ph-pencil-simple', 'group' => 'Category Management', 'audience' => 'admin'],
        'delete_category' => ['label' => 'Move Category to Trash', 'icon' => 'ph-trash', 'group' => 'Category Management', 'audience' => 'admin'],
        'restore_category' => ['label' => 'Restore Category', 'icon' => 'ph-arrow-counter-clockwise', 'group' => 'Category Management', 'audience' => 'admin'],
        'force_delete_category' => ['label' => 'Permanently Delete Category', 'icon' => 'ph-trash-simple', 'group' => 'Category Management', 'audience' => 'admin'],

        'update_support_answer' => ['label' => 'Updated Support Answer', 'icon' => 'ph-pencil-simple', 'group' => 'Support Management', 'audience' => 'admin'],
        'answer_support_request' => ['label' => 'Answered Support Request', 'icon' => 'ph-chat-circle-text', 'group' => 'Support Management', 'audience' => 'admin'],
        'forward_support_response' => ['label' => 'Forwarded Official Response', 'icon' => 'ph-paper-plane-tilt', 'group' => 'Support Management', 'audience' => 'admin'],
        'delete_support_request' => ['label' => 'Move Support Request to Trash', 'icon' => 'ph-trash', 'group' => 'Support Management', 'audience' => 'admin'],
        'restore_support_request' => ['label' => 'Restore Support Request', 'icon' => 'ph-arrow-counter-clockwise', 'group' => 'Support Management', 'audience' => 'admin'],
        'force_delete_support_request' => ['label' => 'Permanently Delete Support Request', 'icon' => 'ph-trash-simple', 'group' => 'Support Management', 'audience' => 'admin'],

        'create_collaboration_task' => ['label' => 'Created Collaboration Task', 'icon' => 'ph-users-three', 'group' => 'Collaboration', 'audience' => 'admin'],
        'update_collaboration_task' => ['label' => 'Updated Collaboration Task', 'icon' => 'ph-arrow-right', 'group' => 'Collaboration', 'audience' => 'admin'],

        'approve_admin' => ['label' => 'Approved Admin', 'icon' => 'ph-user-check', 'group' => 'Admin Management', 'audience' => 'admin'],
        'invite_admin' => ['label' => 'Invited Admin', 'icon' => 'ph-envelope-simple', 'group' => 'Admin Management', 'audience' => 'admin'],
        'promote_admin' => ['label' => 'Promoted Admin', 'icon' => 'ph-arrow-up', 'group' => 'Admin Management', 'audience' => 'admin'],
        'demote_admin' => ['label' => 'Demoted Admin', 'icon' => 'ph-arrow-down', 'group' => 'Admin Management', 'audience' => 'admin'],
        'deactivate_admin' => ['label' => 'Deactivated Admin', 'icon' => 'ph-user-minus', 'group' => 'Admin Management', 'audience' => 'admin'],
        'reactivate_admin' => ['label' => 'Reactivated Admin', 'icon' => 'ph-user-plus', 'group' => 'Admin Management', 'audience' => 'admin'],
        'delete_admin' => ['label' => 'Deleted Admin', 'icon' => 'ph-user-minus', 'group' => 'Admin Management', 'audience' => 'admin'],

        'deactivate_user' => ['label' => 'Deactivated User', 'icon' => 'ph-user-minus', 'group' => 'User Management', 'audience' => 'admin'],
        'reactivate_user' => ['label' => 'Reactivated User', 'icon' => 'ph-user-plus', 'group' => 'User Management', 'audience' => 'admin'],
        'delete_user' => ['label' => 'Permanently Deleted User', 'icon' => 'ph-user-minus', 'group' => 'User Management', 'audience' => 'admin'],
    ],

    'admin_actions' => [
        'admin_login', 'admin_logout',
        'create_agency', 'update_agency', 'trash_agency', 'restore_agency', 'force_delete_agency', 'delete_agency',
        'create_faq', 'update_faq', 'delete_faq', 'restore_faq', 'force_delete_faq', 'convert_support_to_faq',
        'create_category', 'update_category', 'delete_category', 'restore_category', 'force_delete_category',
        'answer_support_request', 'update_support_answer', 'forward_support_response', 'delete_support_request', 'restore_support_request', 'force_delete_support_request',
        'create_collaboration_task', 'update_collaboration_task',
        'approve_admin', 'invite_admin', 'promote_admin', 'demote_admin', 'deactivate_admin', 'reactivate_admin', 'delete_admin',
        'deactivate_user', 'reactivate_user', 'delete_user',
    ],
];
