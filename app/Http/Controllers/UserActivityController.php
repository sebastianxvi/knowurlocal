<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\UserLog;
use App\Models\Agency;
use App\Models\Category;

class UserActivityController extends Controller
{
    public function agencyAction(Request $request)
{
    /*
     * Make sure the request comes from an authenticated
     * user before creating an activity log.
     */
    if (!Auth::check()) {
        abort(403);
    }

    /*
     * Only normal public users may use this endpoint.
     *
     * Admin and superadmin activity continues to use
     * the existing admin logging mechanisms.
     */
    if (Auth::user()->role !== 'user') {
        abort(403);
    }

    /*
     * Validate the values coming from JavaScript.
     *
     * The action uses a strict whitelist so the browser
     * cannot invent arbitrary audit-log actions.
     */
    $validated = $request->validate([
        'action' => [
            'required',
            'string',
            'in:view_agency,search_agency,get_directions,contact_agency'
        ],

        

        'agency_id' => [
            'required',
            'integer',
            'exists:agencies,id'
        ],

        'contact_type' => [
            'nullable',
            'string',
            'in:landline,hotline,email,website,facebook'
        ],
    ]);

    /*
     * Retrieve the agency from the database.
     *
     * The browser only provides the agency ID.
     * Laravel remains responsible for determining
     * which agency that ID actually belongs to.
     */
    $agency = Agency::findOrFail(
        $validated['agency_id']
    );

    /*
     * Determine the human-readable audit description
     * from the validated action.
     */
    $description = match ($validated['action']) {

    /*
     * User searched for an agency.
     */
    'search_agency' =>
        'Searched for agency',

    /*
     * User opened the agency details panel.
     */
    'view_agency' =>
        'Viewed agency details',

    /*
     * User requested directions to the agency.
     */
    'get_directions' =>
        'Requested directions to agency',

    /*
 * User intentionally interacted with one
 * of the agency's contact channels.
 */
'contact_agency' => match (
    $validated['contact_type'] ?? null
) {

    /*
     * User clicked the agency's landline number.
     */
    'landline' =>
        'Landline accessed',

    /*
     * User clicked the agency's hotline number.
     */
    'hotline' =>
        'Hotline accessed',

    /*
     * User clicked the agency's email address.
     */
    'email' =>
        'Email accessed',

    /*
     * User clicked the agency's official website.
     */
    'website' =>
        'Website accessed',

    /*
     * User clicked the agency's Facebook page.
     */
    'facebook' =>
        'Facebook accessed',

    /*
     * Fallback for a valid contact action where
     * no specific contact channel was supplied.
     */
    default =>
        'Contact option accessed',
},

};

    /*
     * Create the audit record.
     *
     * user_id and role come from the authenticated
     * Laravel session rather than from the browser.
     */
    UserLog::create([
        'user_id' => Auth::id(),

        'agency_id' => $agency->id,

        'action' => $validated['action'],

        'page' => 'map',

        'role' => Auth::user()->role,

        'ip_address' => $request->ip(),

        'device' => substr(
            $request->userAgent() ?? '',
            0,
            255
        ),

        'description' => $description,
    ]);

    /*
     * Return a small JSON response because the request
     * is performed asynchronously by the map JavaScript.
     */
    return response()->json([
        'success' => true
    ]);
}

/**
 * Record a public user's category-filter interaction.
 *
 * This endpoint is intentionally limited to category
 * filtering so the browser cannot create arbitrary
 * activity-log records.
 */
public function categoryAction(Request $request)
{
    /*
     * Make sure the request comes from an authenticated
     * user before creating an activity log.
     */
    if (!Auth::check()) {
        abort(403);
    }

    /*
     * Only normal public users may use this endpoint.
     *
     * Admin and superadmin activity continues to use
     * the existing admin logging mechanisms.
     */
    if (Auth::user()->role !== 'user') {
        abort(403);
    }

    /*
     * Validate the incoming category interaction.
     *
     * The action is strictly whitelisted so the browser
     * cannot invent arbitrary audit-log actions.
     */
    $validated = $request->validate([
        'action' => [
            'required',
            'string',
            'in:filter_category'
        ],

        'category_id' => [
            'required',
            'integer',
            'exists:categories,id'
        ],
    ]);

    /*
     * Retrieve the category from the database.
     *
     * The browser only supplies the ID.
     * Laravel determines the actual category.
     */
    $category = Category::findOrFail(
        $validated['category_id']
    );

    /*
     * Create the audit record.
     *
     * user_id and role come from the authenticated
     * Laravel session rather than from browser input.
     */
    UserLog::create([
        'user_id' => Auth::id(),

        'category_id' => $category->id,

        'action' => 'filter_category',

        'page' => 'map',

        'role' => Auth::user()->role,

        'ip_address' => $request->ip(),

        'device' => substr(
            $request->userAgent() ?? '',
            0,
            255
        ),

        'description' =>
            'Category filter applied',
    ]);

    /*
     * Return a small JSON response because the request
     * is performed asynchronously by the map JavaScript.
     */
    return response()->json([
        'success' => true
    ]);
}
}