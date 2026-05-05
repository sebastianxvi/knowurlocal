<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Agency;
use App\Models\UserLog;
use App\Models\AgencyType;

class AgencyController extends Controller
{
    
    /**
     * 📄 DISPLAY AGENCIES (MATCHES YOUR BLADE)
     */
    public function index(Request $request)
    {
        $query = Agency::with('type');

        // 🔍 SEARCH
        if ($request->filled('search')) {
            $query->where('agency_name', 'LIKE', '%' . $request->search . '%');
        }

        // 🔍 FILTER TYPE
        if ($request->filled('type')) {
            $query->where('agency_type_id', $request->type);
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

        return view('admin.nga-management', compact('agencies', 'types'));
    }

    public function getAll()
{
    $agencies = Agency::with('type') // 🔥 LOAD RELATION
        ->select(
            'id',
            'agency_name',
            'agency_abbreviation',
            'agency_type_id',
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
        $request->validate([
            'agency_name'        => 'required|string|max:255',
            'agency_abbreviation'=> 'nullable|string|max:50',
            'agency_type_id'     => 'required|exists:agency_types,id',
            'agency_description' => 'nullable|string',

            'agency_location'    => 'required|string|max:255',
            'agency_hotline'     => 'required|string|max:20',

            'agency_landline'    => 'nullable|string|max:20',
            'agency_email'       => 'nullable|email|max:255',
            'agency_website'     => 'nullable|string|max:255',
            'agency_fb'          => 'nullable|string|max:255',

            'office_hours'       => 'nullable|string',

            'lat' => 'nullable|numeric',
            'lng' => 'nullable|numeric',

            'agency_image' => 'nullable|image|mimes:jpeg,png|max:2048',
        ]);

        /**
         * 📁 HANDLE IMAGE UPLOAD (SECURE)
         */
        $imagePath = null;

        if ($request->hasFile('agency_image')) {
            $imagePath = $request->file('agency_image')->store('agencies', 'public');
        }

        /**
         * 🏗 CREATE AGENCY
         */
        $agency = Agency::create([
            'agency_name'         => $request->agency_name,
            'agency_abbreviation' => $request->agency_abbreviation,
            'agency_type_id'      => $request->agency_type_id,
            'agency_description'  => $request->agency_description,

            'agency_location'     => $request->agency_location,
            'agency_hotline'      => $request->agency_hotline,

            'agency_landline'     => $request->agency_landline,
            'agency_email'        => $request->agency_email,
            'agency_website'      => $request->agency_website,
            'agency_fb'           => $request->agency_fb,

            'office_hours'        => $request->office_hours,

            'lat' => $request->lat,
            'lng' => $request->lng,

            'agency_image' => $imagePath,
        ]);

        /**
         * 🔥 LOGGING
         */
        $this->logAction(
            auth()->user()->role ?? 'admin',
            auth()->id(),
            $agency->id,
            'create_agency',
            'admin_agency',
            null,
            [
                'agency_name' => $agency->agency_name,
                'location'    => $agency->agency_location,
            ],
            'Created Agency: ' . $agency->agency_name
        );

        return redirect()->back()->with('success', 'Agency created successfully.');
    }

    /**
     * ✏️ UPDATE AGENCY
     */
    public function update(Request $request, $id)
    {
        $agency = Agency::findOrFail($id);

        $request->validate([
            'agency_name'        => 'required|string|max:255',
            'agency_abbreviation'=> 'nullable|string|max:50',
            'agency_type_id'     => 'required|exists:agency_types,id',
            'agency_description' => 'nullable|string',

            'agency_location'    => 'required|string|max:255',
            'agency_hotline'     => 'required|string|max:20',

            'agency_landline'    => 'nullable|string|max:20',
            'agency_email'       => 'nullable|email|max:255',
            'agency_website'     => 'nullable|string|max:255',
            'agency_fb'          => 'nullable|string|max:255',

            'office_hours'       => 'nullable|string',

            'lat' => 'nullable|numeric',
            'lng' => 'nullable|numeric',

            'agency_image' => 'nullable|image|mimes:jpeg,png|max:2048',
        ]);

        /**
         * 🔥 CAPTURE OLD DATA
         */
        $oldData = $agency->only([
            'agency_name',
            'agency_location',
            'agency_hotline'
        ]);

        /**
         * 📁 HANDLE IMAGE UPDATE
         */
        if ($request->hasFile('agency_image')) {
            $imagePath = $request->file('agency_image')->store('agencies', 'public');
            $agency->agency_image = $imagePath;
        }

        /**
         * 🔄 UPDATE DATA
         */
        $agency->update([
            'agency_name'         => $request->agency_name,
            'agency_abbreviation' => $request->agency_abbreviation,
            'agency_type_id'      => $request->agency_type_id,
            'agency_description'  => $request->agency_description,

            'agency_location'     => $request->agency_location,
            'agency_hotline'      => $request->agency_hotline,

            'agency_landline'     => $request->agency_landline,
            'agency_email'        => $request->agency_email,
            'agency_website'      => $request->agency_website,
            'agency_fb'           => $request->agency_fb,

            'office_hours'        => $request->office_hours,

            'lat' => $request->lat,
            'lng' => $request->lng,
        ]);

        /**
         * 🔥 LOGGING
         */
        $this->logAction(
            auth()->user()->role ?? 'admin',
            auth()->id(),
            $agency->id,
            'update_agency',
            'admin_agency',
            $oldData,
            [
                'agency_name' => $agency->agency_name,
                'location'    => $agency->agency_location,
            ],
            'Updated Agency: ' . $agency->agency_name
        );

        return redirect()->back()->with('success', 'Agency updated successfully.');
    }

    /**
     * ❌ DELETE AGENCY
     */
    public function destroy($id)
    {
        $agency = Agency::findOrFail($id);

        $oldData = [
            'agency_name' => $agency->agency_name,
        ];

        $agency->delete();

        /**
         * 🔥 LOGGING
         */
        $this->logAction(
            auth()->user()->role ?? 'admin',
            auth()->id(),
            $agency->id,
            'delete_agency',
            'admin_agency',
            $oldData,
            null,
            'Deleted Agency: ' . $oldData['agency_name']
        );

        return redirect()->back()->with('success', 'Agency deleted successfully.');
    }

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
            UserLog::create([
                'user_id' => $userId,
                'target_user_id' => $targetUserId,
                'agency_id' => $agencyId,

                'action' => $action,
                'page'   => $page,
                'role'   => $role,

                'ip_address' => request()->ip(),
                'device'     => substr(request()->userAgent(), 0, 255),

                'old_values' => $oldValues ? json_encode($oldValues) : null,
                'new_values' => $newValues ? json_encode($newValues) : null,

                // backward compatibility
                'old_value' => $oldValues ? json_encode($oldValues) : null,
                'new_value' => $newValues ? json_encode($newValues) : null,

                'description' => $description,
            ]);
        } catch (\Exception $e) {
            \Log::error('Agency log failed: ' . $e->getMessage());
        }
    }
}