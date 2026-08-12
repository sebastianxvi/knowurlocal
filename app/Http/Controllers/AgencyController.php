<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Agency;
use App\Models\UserLog;
use App\Models\AgencyType;
use App\Models\Category;

class AgencyController extends Controller
{
    
    /**
     * 📄 DISPLAY AGENCIES (MATCHES YOUR BLADE)
     */
    public function index(Request $request)
    {
        $query = Agency::with(['type', 'category']);

        // 🔍 SEARCH
        if ($request->filled('search')) {
            $query->where('agency_name', 'LIKE', '%' . $request->search . '%');
        }

        // 🔍 FILTER TYPE
        if ($request->filled('type')) {
            $query->where('agency_type_id', $request->type);
        }

        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }

        // 🔒 SORT
        if ($request->sort === 'oldest') {
            $query->orderBy('created_at', 'asc');
        } else {
            $query->orderBy('created_at', 'desc');
        }

        // ✅ PAGINATION (REQUIRED BY YOUR BLADE)
        $agencies = $query->paginate(10)->withQueryString();

        $types = AgencyType::all();

        $categories = Category::orderBy('category_name')->get();

        return view(
            'admin.nga-management',
            compact('agencies', 'types', 'categories')
        );
    }

    public function getAll()
{
    $agencies = Agency::with(['type', 'category']) // 🔥 LOAD RELATION
        ->select(
            'id',
            'agency_name',
            'agency_abbreviation',
            'agency_type_id',
            'category_id',
            'agency_description',
            'agency_email',
            'agency_hotline',
            'agency_landline',
            'agency_website',
            'agency_fb',
            'agency_location',
            'lat',
            'lng',
            'agency_image'
        )
        ->get();

    return response()->json($agencies);
}

    public function navigate($id)
{
    $agency = Agency::with('type')->findOrFail($id);

    return view('public_user.map-navigation', compact('agency'));
}

    /**
 * ➕ STORE AGENCY
 */
public function store(Request $request)
{
    /*
     * 🔒 Validate the incoming data first.
     *
     * Never use raw request values directly when creating
     * database records. Only validated fields should reach
     * the model.
     */
    $validated = $request->validate([
        'agency_name'         => 'required|string|max:255',
        'agency_abbreviation' => 'nullable|string|max:50',

        'agency_type_id'      => 'required|exists:agency_types,id',
        'category_id'         => 'required|exists:categories,id',

        'agency_description'  => 'nullable|string',
        'services_offered' => 'nullable|string',

        'agency_location'     => 'required|string|max:255',
        'agency_hotline'      => 'required|string|max:20',

        'agency_landline'     => 'nullable|string|max:20',
        'agency_email'        => 'nullable|email|max:255',
        'agency_website'      => 'nullable|string|max:255',
        'agency_fb'           => 'nullable|string|max:255',

        'office_hours'        => 'nullable|string',

        'lat' => 'nullable|numeric|between:-90,90',
        'lng' => 'nullable|numeric|between:-180,180',

        'agency_image'        => 'nullable|image|mimes:jpeg,png|max:2048',
    ]);

    /*
     * 📁 Handle the optional agency image.
     *
     * Laravel's validated file is stored on the public disk.
     */
    if ($request->hasFile('agency_image')) {

        $validated['agency_image'] = $request
            ->file('agency_image')
            ->store('agencies', 'public');
    }

    /*
     * 🏗 Create the agency using ONLY validated data.
     */
    $agency = Agency::create($validated);

    /*
     * 📋 Build the audit snapshot AFTER creation.
     *
     * There is no old agency because this record did not
     * exist before this action.
     *
     * We therefore use a descriptive status instead of NULL.
     */
    $newValues = [
        'agency_name'         => $agency->agency_name,
        'agency_abbreviation' => $agency->agency_abbreviation,
        'agency_type_id'      => $agency->agency_type_id,
        'category_id'         => $agency->category_id,
        'agency_description'  => $agency->agency_description,
        'services_offered'      => $agency->services_offered,
        'agency_location'     => $agency->agency_location,
        'agency_hotline'      => $agency->agency_hotline,
        'agency_landline'     => $agency->agency_landline,
        'agency_email'        => $agency->agency_email,
        'agency_website'      => $agency->agency_website,
        'agency_fb'           => $agency->agency_fb,
        'office_hours'        => $agency->office_hours,
        'lat'                 => $agency->lat,
        'lng'                 => $agency->lng,
        'agency_image'        => $agency->agency_image,
    ];

    /*
     * 🔐 Do not leave the old side of the audit empty.
     *
     * "Data did not exist" clearly communicates that this
     * was a creation rather than an update.
     */
    $oldValues = [
        'status' => 'Data did not exist',
    ];

    /*
     * 🔥 Record the creation.
     *
     * The agency ID is now immediately available and is used
     * as the audit target.
     */
    $this->logAction(
        auth()->user()->role ?? 'admin',
        auth()->id(),
        $agency->id,
        'create_agency',
        'admin_agency',
        $oldValues,
        $newValues,
        'Created Agency: ' . $agency->agency_name
    );

    return redirect()
        ->back()
        ->with('success', 'Agency created successfully.');
}




    /**
     * ✏️ UPDATE AGENCY
     */
    /**
 * ✏️ UPDATE AGENCY
 */
public function update(Request $request, $id)
{
    /*
     * 🔎 Find the existing agency.
     *
     * Soft-deleted agencies are automatically excluded by
     * Laravel's SoftDeletes behavior.
     */
    $agency = Agency::findOrFail($id);

    /*
     * 🔒 Validate only fields that the admin is allowed
     * to modify.
     */
    $validated = $request->validate([
        'agency_name'         => 'required|string|max:255',
        'agency_abbreviation' => 'nullable|string|max:50',
        'agency_type_id'      => 'required|exists:agency_types,id',
        'category_id'         => 'required|exists:categories,id',
        'agency_description'  => 'nullable|string',
        'services_offered' => 'nullable|string',

        'agency_location'     => 'required|string|max:255',
        'agency_hotline'      => 'required|string|max:20',

        'agency_landline'     => 'nullable|string|max:20',
        'agency_email'        => 'nullable|email|max:255',
        'agency_website'      => 'nullable|string|max:255',
        'agency_fb'           => 'nullable|string|max:255',

        'office_hours'        => 'nullable|string',

        'lat' => 'nullable|numeric|between:-90,90',
        'lng' => 'nullable|numeric|between:-180,180',

        'agency_image'        => 'nullable|image|mimes:jpeg,png|max:2048',
    ]);

    /*
     * 📸 Remember the old image path before replacing it.
     *
     * This allows the audit system to show that the image
     * itself was changed.
     */
    $oldImage = $agency->agency_image;

    /*
     * 📋 Store the agency's current values BEFORE updating.
     *
     * We need this snapshot so we can compare old vs new.
     */
    $oldData = [
        'agency_name'         => $agency->agency_name,
        'agency_abbreviation' => $agency->agency_abbreviation,
        'agency_type_id'      => $agency->agency_type_id,
        'category_id'         => $agency->category_id,
        'agency_description'  => $agency->agency_description,
        'services_offered'      => $agency->services_offered,
        'agency_location'     => $agency->agency_location,
        'agency_hotline'      => $agency->agency_hotline,
        'agency_landline'     => $agency->agency_landline,
        'agency_email'        => $agency->agency_email,
        'agency_website'      => $agency->agency_website,
        'agency_fb'           => $agency->agency_fb,
        'office_hours'        => $agency->office_hours,
        'lat'                 => $agency->lat,
        'lng'                 => $agency->lng,
        'agency_image'        => $agency->agency_image,
    ];

    /*
     * 📁 Handle a new agency image.
     */
    if ($request->hasFile('agency_image')) {

        /*
         * Store the new image using Laravel's public disk.
         *
         * The uploaded file is already protected by the
         * validation rule above.
         */
        $validated['agency_image'] = $request
            ->file('agency_image')
            ->store('agencies', 'public');
    }

    /*
     * 🔄 Update the agency.
     *
     * Only validated fields are written to the database.
     */
    $agency->update($validated);

    /*
     * 🔄 Refresh the model so it contains the actual
     * values now stored in the database.
     */
    $agency->refresh();

    /*
     * 📋 Capture the agency AFTER the update.
     */
    $newData = [
        'agency_name'         => $agency->agency_name,
        'agency_abbreviation' => $agency->agency_abbreviation,
        'agency_type_id'      => $agency->agency_type_id,
        'category_id'         => $agency->category_id,
        'agency_description'  => $agency->agency_description,
        'services_offered' => $agency->services_offered,
        'agency_location'     => $agency->agency_location,
        'agency_hotline'      => $agency->agency_hotline,
        'agency_landline'     => $agency->agency_landline,
        'agency_email'        => $agency->agency_email,
        'agency_website'      => $agency->agency_website,
        'agency_fb'           => $agency->agency_fb,
        'office_hours'        => $agency->office_hours,
        'lat'                 => $agency->lat,
        'lng'                 => $agency->lng,
        'agency_image'        => $agency->agency_image,
    ];

    /*
     * 🔍 Keep ONLY fields whose values actually changed.
     *
     * This is the important part of the audit system.
     */
    $changedOldValues = [];
    $changedNewValues = [];

    foreach ($oldData as $field => $oldValue) {

        /*
         * Compare the old database value with the new value.
         *
         * Casting to string prevents harmless differences such
         * as numeric database values being compared incorrectly.
         */
        if ((string) $oldValue !== (string) $newData[$field]) {

            $changedOldValues[$field] = $oldValue;
            $changedNewValues[$field] = $newData[$field];
        }
    }

    /*
     * 🚫 Do not create an "Update Agency" audit entry when
     * absolutely nothing changed.
     */
    if (!empty($changedOldValues)) {

        $this->logAction(
            auth()->user()->role ?? 'admin',
            auth()->id(),
            $agency->id,
            'update_agency',
            'admin_agency',
            $changedOldValues,
            $changedNewValues,
            'Updated Agency: ' . $agency->agency_name
        );
    }

    return redirect()
        ->back()
        ->with('success', 'Agency updated successfully.');
}




    /**
 * ❌ DELETE AGENCY
 */
public function destroy($id)
{
    /*
     * 🔎 Find the existing agency.
     *
     * Because this is a normal admin delete action,
     * findOrFail() intentionally only finds active agencies.
     */
    $agency = Agency::findOrFail($id);

    /*
     * 📋 Capture the complete agency state BEFORE deletion.
     *
     * This is important because the agency is about to be
     * soft-deleted.
     */
    $oldValues = [
        'agency_name'         => $agency->agency_name,
        'agency_abbreviation' => $agency->agency_abbreviation,
        'agency_type_id'      => $agency->agency_type_id,
        'category_id'         => $agency->category_id,
        'agency_description'  => $agency->agency_description,
        'services_offered'      => $agency->services_offered,
        'agency_location'     => $agency->agency_location,
        'agency_hotline'      => $agency->agency_hotline,
        'agency_landline'     => $agency->agency_landline,
        'agency_email'        => $agency->agency_email,
        'agency_website'      => $agency->agency_website,
        'agency_fb'           => $agency->agency_fb,
        'office_hours'        => $agency->office_hours,
        'lat'                 => $agency->lat,
        'lng'                 => $agency->lng,
        'agency_image'        => $agency->agency_image,
    ];

    /*
     * 🗑 Soft-delete the agency.
     *
     * The database record is NOT permanently removed.
     *
     * Laravel simply sets deleted_at.
     */
    $agency->delete();

    /*
     * 📋 Represent the result of the delete operation.
     *
     * We intentionally do not use NULL here.
     */
    $newValues = [
        'status' => 'Data deleted',
    ];

    /*
     * 🔥 Record the deletion.
     *
     * The agency ID remains available even though the agency
     * is now soft-deleted.
     */
    $this->logAction(
        auth()->user()->role ?? 'admin',
        auth()->id(),
        $agency->id,
        'delete_agency',
        'admin_agency',
        $oldValues,
        $newValues,
        'Deleted Agency: ' . $agency->agency_name
    );

    return redirect()
        ->back()
        ->with('success', 'Agency deleted successfully.');
}

    /**
     * 🔒 CENTRALIZED LOGGING
     */
    /**
 * 🔒 CENTRALIZED LOGGING
 */
private function logAction(
    $role,
    $userId,
    $agencyId,
    $action,
    $page,
    $oldValues = null,
    $newValues = null,
    $description = null,
    $targetUserId = null
) {
    try {

        /*
         * Create the audit record.
         *
         * old_values and new_values are passed as arrays.
         *
         * UserLog's $casts property allows Laravel to
         * automatically serialize them into JSON.
         */
        UserLog::create([
            'user_id'        => $userId,
            'target_user_id' => $targetUserId,
            'agency_id'      => $agencyId,

            'action' => $action,
            'page'   => $page,
            'role'   => $role,

            'ip_address' => request()->ip(),
            'device'     => substr(request()->userAgent(), 0, 255),

            /*
             * IMPORTANT:
             *
             * Pass arrays directly.
             *
             * Do NOT json_encode() them here.
             */
            'old_values' => $oldValues,
            'new_values' => $newValues,

            /*
             * Legacy fields.
             *
             * We can keep these populated for compatibility
             * with older audit records or code that still
             * references them.
             */
            'old_value' => null,
            'new_value' => null,

            'description' => $description,
        ]);

    } catch (\Exception $e) {

        /*
         * Audit failure should never break the actual
         * agency operation.
         *
         * The error is recorded in Laravel's application log
         * so the developer can investigate it.
         */
        \Log::error(
            'Agency log failed: ' . $e->getMessage()
        );
    }
}
}