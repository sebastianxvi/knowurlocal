<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

use App\Models\Agency;
use App\Models\UserLog;
use App\Models\AgencyType;
use App\Models\Category;
use App\Models\AgencyContact;
use App\Models\ContactType;

class AgencyController extends Controller
{
    /**
     * =========================================================
     * 📄 DISPLAY AGENCIES
     * =========================================================
     *
     * Handles both Active and Trashed agencies on the same
     * management page.
     *
     * Active:
     *     /admin/agencies
     *
     * Trashed:
     *     /admin/agencies?status=trashed
     */
    public function index(Request $request)
    {
        /*
         * =====================================================
         * STATUS
         * =====================================================
         *
         * Active is the default state.
         *
         * Only two server-side states are accepted:
         *
         *     active
         *     trashed
         *
         * Anything else safely falls back to active.
         */
        $status = $request->query('status', 'active');

        if (!in_array($status, ['active', 'trashed'], true)) {
            $status = 'active';
        }


        /*
         * =====================================================
         * AGENCY QUERY
         * =====================================================
         *
         * SoftDeletes automatically hides deleted agencies
         * from a normal Agency query.
         */
        if ($status === 'trashed') {

            $query = Agency::onlyTrashed()
                ->with([
                    'type',
                    'category',
                    'contacts.contactType',
                ]);

        } else {

            $query = Agency::query()
                ->with([
                    'type',
                    'category',
                    'contacts.contactType',
                ]);
        }


        /*
         * =====================================================
         * SEARCH
         * =====================================================
         */
        if ($request->filled('search')) {

    $search = trim($request->search);

    $query->where(function ($query) use ($search) {

        $query
            ->where('agency_name', 'LIKE', '%' . $search . '%')
            ->orWhere(
                'agency_abbreviation',
                'LIKE',
                '%' . $search . '%'
            );
    });
}


        /*
         * =====================================================
         * FILTER BY AGENCY TYPE
         * =====================================================
         */
        if ($request->filled('type')) {

            $query->where(
                'agency_type_id',
                $request->type
            );
        }


        /*
         * =====================================================
         * FILTER BY CATEGORY
         * =====================================================
         */
        if ($request->filled('category')) {

            $query->where(
                'category_id',
                $request->category
            );
        }


        /*
         * =====================================================
         * FILTER BY DATA COMPLETENESS
         * =====================================================
         *
         * This remains compatible with the existing
         * incomplete-data filter.
         */
        if ($request->query('filter') === 'incomplete') {

            $query->where(function ($query) {

                $query
                    ->whereNull('agency_location')
                    ->orWhere('agency_location', '')

                    ->orWhereNull('agency_description')
                    ->orWhere('agency_description', '')

                    ->orWhereNull('services_offered')
                    ->orWhere('services_offered', '')

                    ->orWhereNull('office_hours')
                    ->orWhere('office_hours', '')

                    ->orWhereNull('lat')
                    ->orWhereNull('lng')

                    ->orWhereNull('agency_type_id')
                    ->orWhereNull('category_id');
            });
        }


        /*
         * =====================================================
         * SORT
         * =====================================================
         */
        if ($status === 'trashed') {

            if ($request->sort === 'oldest') {

                $query->orderBy(
                    'deleted_at',
                    'asc'
                );

            } else {

                $query->orderBy(
                    'deleted_at',
                    'desc'
                );
            }

        } else {

            if ($request->sort === 'oldest') {

                $query->orderBy(
                    'created_at',
                    'asc'
                );

            } else {

                $query->orderBy(
                    'created_at',
                    'desc'
                );
            }
        }


        /*
         * =====================================================
         * PAGINATION
         * =====================================================
         */
        $agencies = $query
            ->paginate(10)
            ->withQueryString();


        /*
         * =====================================================
         * COUNTS
         * =====================================================
         *
         * These are total counts and are not affected by
         * search/filter parameters.
         */
        $activeCount = Agency::count();

        $trashedCount = Agency::onlyTrashed()->count();


        /*
         * =====================================================
         * FILTER OPTIONS
         * =====================================================
         */
        $types = AgencyType::all();

        $categories = Category::orderBy(
            'category_name'
        )->get();

        /*
 * Load active contact types for the Add/Edit agency form.
 *
 * These are predefined system values such as:
 * Hotline, Email, Landline, Website, Facebook, etc.
 *
 * Administrators can choose from these types but cannot
 * create arbitrary contact-type records through this form.
 */
$contactTypes = ContactType::where('is_active', true)
    ->orderBy('sort_order')
    ->get();


        /*
         * =====================================================
         * RETURN MANAGEMENT PAGE
         * =====================================================
         */
        return view(
            'admin.nga-management',
            compact(
                'agencies',
                'types',
                'categories',
                'contactTypes',
                'activeCount',
                'trashedCount',
                'status'
            )
        );
    }


    /**
     * =========================================================
     * 📦 GET ALL AGENCIES
     * =========================================================
     *
     * Used by endpoints that need agency data in JSON format.
     */
    public function getAll()
{
    /*
     * Load the agency's related information in the same query
     * cycle so the API does not need to repeatedly query the
     * database for each agency's contacts.
     */
    $agencies = Agency::with([
        'type',
        'category',
        'contacts.contactType',
    ])
        ->select([
            'id',
            'agency_name',
            'agency_abbreviation',
            'agency_type_id',
            'category_id',

            'agency_description',
            'services_offered',
            'office_hours',

            'agency_location',
            'lat',
            'lng',

            'agency_image',
        ])
        ->get();

    return response()->json($agencies);
}


    /**
     * =========================================================
     * 🧭 MAP NAVIGATION
     * =========================================================
     */
    public function navigate($id)
{
    /*
     * Load all information required by the navigation page,
     * including the agency's contact information.
     */
    $agency = Agency::with([
        'type',
        'category',
        'contacts.contactType',
    ])
        ->findOrFail($id);


    return view(
        'public_user.map-navigation',
        compact('agency')
    );
}


    /**
     * =========================================================
     * ➕ STORE AGENCY
     * =========================================================
     */
    public function store(Request $request)
{
    /*
     * =====================================================
     * VALIDATE AGENCY INFORMATION
     * =====================================================
     *
     * These are the fields that define the minimum usable
     * agency record.
     */
    $validated = $request->validate([

        'agency_name' =>
            'required|string|max:255',

        'agency_abbreviation' =>
            'required|string|max:255',

        'agency_type_id' =>
            'required|exists:agency_types,id',

        'category_id' =>
            'required|exists:categories,id',

        'agency_location' =>
            'required|string|max:255',

        'agency_description' =>
            'nullable|string',

        'services_offered' =>
            'nullable|string',

        'office_hours' =>
            'nullable|string',

        'lat' =>
            'nullable|numeric|between:-90,90',

        'lng' =>
            'nullable|numeric|between:-180,180',

        'agency_image' =>
            'nullable|image|mimes:jpeg,png|max:2048',

        /*
         * =================================================
         * CONTACTS
         * =================================================
         *
         * Contacts are now stored in agency_contacts.
         *
         * At least two records are required because every
         * agency must have at least:
         *
         * 1 Hotline
         * 1 Email
         */
        'contacts' =>
            'required|array|min:2',

        'contacts.*.contact_type_id' =>
            'required|integer|exists:contact_types,id',

        'contacts.*.label' =>
            'nullable|string|max:255',

        'contacts.*.value' =>
            'required|string|max:500',

        'contacts.*.is_primary' =>
            'nullable|boolean',

        'contacts.*.sort_order' =>
            'nullable|integer|min:0',
    ]);


    /*
     * =====================================================
     * LOAD ACTIVE CONTACT TYPES
     * =====================================================
     *
     * We do not trust numeric IDs alone.
     *
     * The controller uses the contact type's slug to
     * determine whether a contact is actually a Hotline,
     * Email, Facebook, etc.
     */
    $contactTypes = ContactType::where('is_active', true)
        ->whereIn(
            'id',
            collect($validated['contacts'])
                ->pluck('contact_type_id')
                ->unique()
        )
        ->get()
        ->keyBy('id');


    /*
     * =====================================================
     * VERIFY CONTACT TYPES
     * =====================================================
     */
    if (
        $contactTypes->count() !==
        collect($validated['contacts'])
            ->pluck('contact_type_id')
            ->unique()
            ->count()
    ) {
        return back()
            ->withErrors([
                'contacts' =>
                    'One or more selected contact types are invalid or inactive.'
            ])
            ->withInput();
    }


    /*
     * =====================================================
     * CONTACT-SPECIFIC VALIDATION
     * =====================================================
     *
     * The database only knows that "value" is a string.
     *
     * The controller additionally verifies that:
     *
     * Email    → valid email address
     * Website  → valid URL
     * Facebook → valid URL
     */
    foreach ($validated['contacts'] as $index => $contact) {

        $type = $contactTypes[
            $contact['contact_type_id']
        ];

        $slug = strtolower($type->slug);

        if ($slug === 'email') {

            $request->validate([
                "contacts.$index.value" =>
                    'required|email|max:500',
            ]);
        }

        if (
            $slug === 'website' ||
            $slug === 'facebook'
        ) {

            $request->validate([
                "contacts.$index.value" =>
                    'required|url|max:500',
            ]);
        }
    }


    /*
     * =====================================================
     * REQUIRED CONTACT TYPES
     * =====================================================
     *
     * Every agency must have:
     *
     * - at least one Hotline
     * - at least one Email
     */
    $hasHotline = false;
    $hasEmail = false;

    foreach ($validated['contacts'] as $contact) {

        $type = $contactTypes[
            $contact['contact_type_id']
        ];

        $slug = strtolower($type->slug);

        if ($slug === 'hotline') {
            $hasHotline = true;
        }

        if ($slug === 'email') {
            $hasEmail = true;
        }
    }


    /*
     * Stop the request before touching the database when
     * one of the mandatory contact types is missing.
     */
    if (!$hasHotline || !$hasEmail) {

        $missing = [];

        if (!$hasHotline) {
            $missing[] = 'Hotline';
        }

        if (!$hasEmail) {
            $missing[] = 'Email';
        }

        return back()
            ->withErrors([
                'contacts' =>
                    'The following contact information is required: ' .
                    implode(', ', $missing) . '.'
            ])
            ->withInput();
    }


    /*
     * =====================================================
     * PREPARE AGENCY DATA
     * =====================================================
     *
     * Remove contacts from the agency attributes.
     *
     * contacts belong to agency_contacts, not agencies.
     */
    $agencyData = collect($validated)
        ->except('contacts')
        ->toArray();


    /*
     * =====================================================
     * IMAGE
     * =====================================================
     */
    $uploadedImage = null;

    if ($request->hasFile('agency_image')) {

        $uploadedImage = $request
            ->file('agency_image')
            ->store('agencies', 'public');

        $agencyData['agency_image'] = $uploadedImage;
    }


    /*
     * =====================================================
     * CREATE AGENCY + CONTACTS
     * =====================================================
     *
     * Both operations must succeed together.
     */
    try {

        $agency = DB::transaction(function () use (
            $agencyData,
            $validated
        ) {

            /*
             * Create the parent agency first.
             */
            $agency = Agency::create(
                $agencyData
            );


            /*
             * Create every contact belonging to the agency.
             */
            foreach (
                $validated['contacts']
                as $index => $contact
            ) {

                AgencyContact::create([

                    'agency_id' =>
                        $agency->id,

                    'contact_type_id' =>
                        $contact['contact_type_id'],

                    'label' =>
                        $contact['label'] ?? null,

                    'value' =>
                        $contact['value'],

                    'is_primary' =>
                        !empty($contact['is_primary']),

                    'sort_order' =>
                        $contact['sort_order'] ?? $index,
                ]);
            }


            return $agency;
        });

    } catch (\Throwable $e) {

        /*
         * If the database transaction fails after an image
         * was uploaded, remove the orphaned file.
         */
        if ($uploadedImage) {

            Storage::disk('public')
                ->delete($uploadedImage);
        }

        /*
         * Re-throw the exception so Laravel handles the
         * unexpected server-side failure normally.
         */
        throw $e;
    }


    /*
     * =====================================================
     * AUDIT CONTACT SNAPSHOT
     * =====================================================
     *
     * Reload contacts together with their predefined types.
     */
    $agency->load('contacts.contactType');


    $contactSnapshot = $agency->contacts
        ->map(function ($contact) {

            return [
                'type' =>
                    $contact->contactType->name ?? 'Unknown',

                'type_slug' =>
                    $contact->contactType->slug ?? null,

                'label' =>
                    $contact->label,

                'value' =>
                    $contact->value,

                'is_primary' =>
                    (bool) $contact->is_primary,

                'sort_order' =>
                    $contact->sort_order,
            ];
        })
        ->values()
        ->all();


    /*
     * =====================================================
     * AUDIT SNAPSHOT
     * =====================================================
     */
    $newValues = [

        'agency_name' =>
            $agency->agency_name,

        'agency_abbreviation' =>
            $agency->agency_abbreviation,

        'agency_type_id' =>
            $agency->agency_type_id,

        'category_id' =>
            $agency->category_id,

        'agency_location' =>
            $agency->agency_location,

        'agency_description' =>
            $agency->agency_description,

        'services_offered' =>
            $agency->services_offered,

        'office_hours' =>
            $agency->office_hours,

        'lat' =>
            $agency->lat,

        'lng' =>
            $agency->lng,

        'agency_image' =>
            $agency->agency_image,

        'contacts' =>
            $contactSnapshot,
    ];


    /*
     * There was no previous record.
     */
    $oldValues = [
        'status' =>
            'Data did not exist',
    ];


    /*
     * =====================================================
     * AUDIT LOG
     * =====================================================
     */
    $this->logAction(
        auth()->user()->role ?? 'admin',
        auth()->id(),
        $agency->id,
        'create_agency',
        'nga_ngo_management',
        $oldValues,
        $newValues,
        'Created Agency: ' . $agency->agency_name
    );


    return redirect()
        ->back()
        ->with(
            'success',
            'Agency created successfully.'
        );
}



    /**
     * =========================================================
     * ✏️ UPDATE AGENCY
     * =========================================================
     */
    public function update(Request $request, $id)
{
    /*
     * =====================================================
     * FIND ACTIVE AGENCY
     * =====================================================
     *
     * Soft-deleted agencies cannot be edited here.
     */
    $agency = Agency::findOrFail($id);


    /*
     * =====================================================
     * VALIDATE AGENCY
     * =====================================================
     */
    $validated = $request->validate([

        'agency_name' =>
            'required|string|max:255',

        'agency_abbreviation' =>
            'required|string|max:255',

        'agency_type_id' =>
            'required|exists:agency_types,id',

        'category_id' =>
            'required|exists:categories,id',

        'agency_location' =>
            'required|string|max:255',

        'agency_description' =>
            'nullable|string',

        'services_offered' =>
            'nullable|string',

        'office_hours' =>
            'nullable|string',

        'lat' =>
            'nullable|numeric|between:-90,90',

        'lng' =>
            'nullable|numeric|between:-180,180',

        'agency_image' =>
            'nullable|image|mimes:jpeg,png|max:2048',

        /*
         * Contacts are required because Hotline and Email
         * are required for every agency.
         */
        'contacts' =>
            'required|array|min:2',

        'contacts.*.contact_type_id' =>
            'required|integer|exists:contact_types,id',

        'contacts.*.label' =>
            'nullable|string|max:255',

        'contacts.*.value' =>
            'required|string|max:500',

        'contacts.*.is_primary' =>
            'nullable|boolean',

        'contacts.*.sort_order' =>
            'nullable|integer|min:0',
    ]);


    /*
     * =====================================================
     * CONTACT TYPES
     * =====================================================
     */
    $contactTypes = ContactType::where('is_active', true)
        ->whereIn(
            'id',
            collect($validated['contacts'])
                ->pluck('contact_type_id')
                ->unique()
        )
        ->get()
        ->keyBy('id');


    /*
     * Make sure every submitted contact type is active.
     */
    if (
        $contactTypes->count() !==
        collect($validated['contacts'])
            ->pluck('contact_type_id')
            ->unique()
            ->count()
    ) {

        return back()
            ->withErrors([
                'contacts' =>
                    'One or more selected contact types are invalid or inactive.'
            ])
            ->withInput();
    }


    /*
     * =====================================================
     * CONTACT-SPECIFIC VALIDATION
     * =====================================================
     */
    foreach ($validated['contacts'] as $index => $contact) {

        $type = $contactTypes[
            $contact['contact_type_id']
        ];

        $slug = strtolower($type->slug);

        if ($slug === 'email') {

            $request->validate([
                "contacts.$index.value" =>
                    'required|email|max:500',
            ]);
        }

        if (
            $slug === 'website' ||
            $slug === 'facebook'
        ) {

            $request->validate([
                "contacts.$index.value" =>
                    'required|url|max:500',
            ]);
        }
    }


    /*
     * =====================================================
     * REQUIRED CONTACT TYPES
     * =====================================================
     */
    $hasHotline = false;
    $hasEmail = false;

    foreach ($validated['contacts'] as $contact) {

        $type = $contactTypes[
            $contact['contact_type_id']
        ];

        $slug = strtolower($type->slug);

        if ($slug === 'hotline') {
            $hasHotline = true;
        }

        if ($slug === 'email') {
            $hasEmail = true;
        }
    }


    if (!$hasHotline || !$hasEmail) {

        $missing = [];

        if (!$hasHotline) {
            $missing[] = 'Hotline';
        }

        if (!$hasEmail) {
            $missing[] = 'Email';
        }

        return back()
            ->withErrors([
                'contacts' =>
                    'The following contact information is required: ' .
                    implode(', ', $missing) . '.'
            ])
            ->withInput();
    }


    /*
     * =====================================================
     * CAPTURE OLD AGENCY STATE
     * =====================================================
     */
    $oldData = [

        'agency_name' =>
            $agency->agency_name,

        'agency_abbreviation' =>
            $agency->agency_abbreviation,

        'agency_type_id' =>
            $agency->agency_type_id,

        'category_id' =>
            $agency->category_id,

        'agency_location' =>
            $agency->agency_location,

        'agency_description' =>
            $agency->agency_description,

        'services_offered' =>
            $agency->services_offered,

        'office_hours' =>
            $agency->office_hours,

        'lat' =>
            $agency->lat,

        'lng' =>
            $agency->lng,

        'agency_image' =>
            $agency->agency_image,
    ];


    /*
     * =====================================================
     * CAPTURE OLD CONTACTS
     * =====================================================
     */
    $agency->load('contacts.contactType');


    $oldContacts = $agency->contacts
        ->map(function ($contact) {

            return [
                'type' =>
                    $contact->contactType->name ?? 'Unknown',

                'type_slug' =>
                    $contact->contactType->slug ?? null,

                'label' =>
                    $contact->label,

                'value' =>
                    $contact->value,

                'is_primary' =>
                    (bool) $contact->is_primary,

                'sort_order' =>
                    $contact->sort_order,
            ];
        })
        ->values()
        ->all();


    /*
     * =====================================================
     * PREPARE NEW AGENCY DATA
     * =====================================================
     */
    $agencyData = collect($validated)
        ->except('contacts')
        ->toArray();


    /*
     * =====================================================
     * IMAGE
     * =====================================================
     */
    $oldImage = $agency->agency_image;
    $newImage = null;


    if ($request->hasFile('agency_image')) {

        $newImage = $request
            ->file('agency_image')
            ->store('agencies', 'public');

        $agencyData['agency_image'] = $newImage;
    }


    /*
     * =====================================================
     * PREPARE NEW CONTACT SNAPSHOT
     * =====================================================
     *
     * This snapshot is calculated before modifying the
     * database so we can accurately determine whether
     * contacts actually changed.
     */
    $newContacts = collect(
        $validated['contacts']
    )
        ->map(function ($contact, $index) use ($contactTypes) {

            $type = $contactTypes[
                $contact['contact_type_id']
            ];

            return [
                'type' =>
                    $type->name,

                'type_slug' =>
                    $type->slug,

                'label' =>
                    $contact['label'] ?? null,

                'value' =>
                    $contact['value'],

                'is_primary' =>
                    !empty($contact['is_primary']),

                'sort_order' =>
                    $contact['sort_order'] ?? $index,
            ];
        })
        ->values()
        ->all();


    /*
     * =====================================================
     * DATABASE TRANSACTION
     * =====================================================
     */
    try {

        DB::transaction(function () use (
            $agency,
            $agencyData,
            $validated
        ) {

            /*
             * Update the agency itself.
             */
            $agency->update(
                $agencyData
            );


            /*
             * Replace the agency's complete contact set.
             *
             * This is intentionally done inside the same
             * transaction as the agency update.
             */
            $agency->contacts()->delete();


            foreach (
                $validated['contacts']
                as $index => $contact
            ) {

                AgencyContact::create([

                    'agency_id' =>
                        $agency->id,

                    'contact_type_id' =>
                        $contact['contact_type_id'],

                    'label' =>
                        $contact['label'] ?? null,

                    'value' =>
                        $contact['value'],

                    'is_primary' =>
                        !empty($contact['is_primary']),

                    'sort_order' =>
                        $contact['sort_order'] ?? $index,
                ]);
            }
        });

    } catch (\Throwable $e) {

        /*
         * The database rolled back.
         *
         * The newly uploaded image is therefore no longer
         * referenced by the agency and must be removed.
         */
        if ($newImage) {

            Storage::disk('public')
                ->delete($newImage);
        }

        throw $e;
    }


    /*
     * =====================================================
     * REFRESH
     * =====================================================
     */
    $agency->refresh();
    $agency->load('contacts.contactType');


    /*
     * =====================================================
     * NEW AGENCY STATE
     * =====================================================
     */
    $newData = [

        'agency_name' =>
            $agency->agency_name,

        'agency_abbreviation' =>
            $agency->agency_abbreviation,

        'agency_type_id' =>
            $agency->agency_type_id,

        'category_id' =>
            $agency->category_id,

        'agency_location' =>
            $agency->agency_location,

        'agency_description' =>
            $agency->agency_description,

        'services_offered' =>
            $agency->services_offered,

        'office_hours' =>
            $agency->office_hours,

        'lat' =>
            $agency->lat,

        'lng' =>
            $agency->lng,

        'agency_image' =>
            $agency->agency_image,
    ];


    /*
     * =====================================================
     * COMPARE AGENCY FIELDS
     * =====================================================
     */
    $changedOldValues = [];
    $changedNewValues = [];


    foreach ($oldData as $field => $oldValue) {

        if (
            (string) $oldValue !==
            (string) $newData[$field]
        ) {

            $changedOldValues[$field] =
                $oldValue;

            $changedNewValues[$field] =
                $newData[$field];
        }
    }


    /*
     * =====================================================
     * COMPARE CONTACTS
     * =====================================================
     *
     * Sort the snapshots before comparison so harmless
     * ordering differences do not create false audit logs.
     */
    $normalizeContacts = function ($contacts) {

        return collect($contacts)
            ->map(function ($contact) {

                return [
                    'type' =>
                        $contact['type'] ?? null,

                    'type_slug' =>
                        $contact['type_slug'] ?? null,

                    'label' =>
                        $contact['label'] ?? null,

                    'value' =>
                        $contact['value'] ?? null,

                    'is_primary' =>
                        (bool) ($contact['is_primary'] ?? false),

                    'sort_order' =>
                        (int) ($contact['sort_order'] ?? 0),
                ];
            })
            ->sortBy(function ($contact) {

                return implode('|', [
                    $contact['sort_order'],
                    $contact['type_slug'],
                    $contact['label'],
                    $contact['value'],
                    $contact['is_primary'] ? '1' : '0',
                ]);
            })
            ->values()
            ->all();
    };


    $normalizedOldContacts =
        $normalizeContacts($oldContacts);

    $normalizedNewContacts =
        $normalizeContacts($newContacts);


    if (
        $normalizedOldContacts !==
        $normalizedNewContacts
    ) {

        $changedOldValues['contacts'] =
            $oldContacts;

        $changedNewValues['contacts'] =
            $newContacts;
    }


    /*
     * =====================================================
     * DELETE OLD IMAGE
     * =====================================================
     *
     * Only remove the previous image after the transaction
     * successfully completed.
     */
    if (
        $oldImage &&
        $agency->agency_image &&
        $oldImage !== $agency->agency_image
    ) {

        Storage::disk('public')
            ->delete($oldImage);
    }


    /*
     * =====================================================
     * AUDIT LOG
     * =====================================================
     */
    if (
        !empty($changedOldValues)
    ) {

        $this->logAction(
            auth()->user()->role ?? 'admin',
            auth()->id(),
            $agency->id,
            'update_agency',
            'nga_ngo_management',
            $changedOldValues,
            $changedNewValues,
            'Updated Agency: ' . $agency->agency_name
        );
    }


    return redirect()
        ->back()
        ->with(
            'success',
            'Agency updated successfully.'
        );
}




    /**
     * =========================================================
     * 🗑️ TRASH AGENCY
     * =========================================================
     *
     * This performs a SOFT DELETE.
     *
     * The database record remains available for recovery.
     */
    public function destroy($id)
{
    /*
     * Only active agencies can be moved to Trash.
     */
    $agency = Agency::findOrFail($id);


    /*
     * Load contacts before creating the audit snapshot.
     */
    $agency->load('contacts.contactType');


    /*
     * =====================================================
     * CONTACT SNAPSHOT
     * =====================================================
     */
    $contactSnapshot = $agency->contacts
        ->map(function ($contact) {

            return [
                'type' =>
                    $contact->contactType->name ?? 'Unknown',

                'type_slug' =>
                    $contact->contactType->slug ?? null,

                'label' =>
                    $contact->label,

                'value' =>
                    $contact->value,

                'is_primary' =>
                    (bool) $contact->is_primary,

                'sort_order' =>
                    $contact->sort_order,
            ];
        })
        ->values()
        ->all();


    /*
     * =====================================================
     * OLD STATE
     * =====================================================
     */
    $oldValues = [

        'agency_name' =>
            $agency->agency_name,

        'agency_abbreviation' =>
            $agency->agency_abbreviation,

        'agency_type_id' =>
            $agency->agency_type_id,

        'category_id' =>
            $agency->category_id,

        'agency_location' =>
            $agency->agency_location,

        'agency_description' =>
            $agency->agency_description,

        'services_offered' =>
            $agency->services_offered,

        'office_hours' =>
            $agency->office_hours,

        'lat' =>
            $agency->lat,

        'lng' =>
            $agency->lng,

        'agency_image' =>
            $agency->agency_image,

        'contacts' =>
            $contactSnapshot,

        'status' =>
            'Active',
    ];


    /*
     * =====================================================
     * SOFT DELETE
     * =====================================================
     */
    $agency->delete();


    /*
     * Refresh so deleted_at contains the actual timestamp.
     */
    $agency->refresh();


    /*
     * =====================================================
     * NEW STATE
     * =====================================================
     */
    $newValues = [

        'status' =>
            'Trashed',

        'deleted_at' =>
            $agency->deleted_at,
    ];


    /*
     * =====================================================
     * AUDIT
     * =====================================================
     */
    $this->logAction(
        auth()->user()->role ?? 'admin',
        auth()->id(),
        $agency->id,
        'trash_agency',
        'nga_ngo_management',
        $oldValues,
        $newValues,
        'Trashed Agency: ' . $agency->agency_name
    );


    return redirect()
        ->back()
        ->with(
            'success',
            'Agency moved to Trash successfully.'
        );
}




    /**
     * =========================================================
     * ♻️ DISPLAY DELETED AGENCIES
     * =========================================================
     */
    public function recovery(Request $request)
    {
        /*
         * Retrieve only soft-deleted agencies.
         */
        $query = Agency::onlyTrashed()
            ->with([
                'type',
                'category',
                'contacts.contactType',
            ]);


        /*
         * =====================================================
         * SEARCH
         * =====================================================
         */
        if ($request->filled('search')) {

            $query->where(
                'agency_name',
                'LIKE',
                '%' . $request->search . '%'
            );
        }


        /*
         * =====================================================
         * SORT
         * =====================================================
         */
        if ($request->sort === 'oldest') {

            $query->orderBy(
                'deleted_at',
                'asc'
            );

        } else {

            $query->orderBy(
                'deleted_at',
                'desc'
            );
        }


        /*
         * =====================================================
         * PAGINATION
         * =====================================================
         */
        $agencies = $query
            ->paginate(10)
            ->withQueryString();


        return view(
            'admin.agency-recovery',
            compact('agencies')
        );
    }


    /**
 * =========================================================
 * ♻️ RESTORE AGENCY
 * =========================================================
 *
 * Restores a soft-deleted agency back to the active dataset.
 *
 * The audit snapshot intentionally stores the agency's
 * identifying information BEFORE restoration.
 *
 * This is important because the agency may later be
 * permanently deleted. The restore log must remain
 * understandable even after that happens.
 */
public function restore($id)
{
    /*
     * Retrieve only a soft-deleted agency.
     */
    $agency = Agency::onlyTrashed()
        ->with('contacts.contactType')
        ->findOrFail($id);


    /*
     * =====================================================
     * CAPTURE OLD STATE
     * =====================================================
     */
    $deletedAt = $agency->deleted_at;


    $contactSnapshot = $agency->contacts
        ->map(function ($contact) {

            return [
                'type' =>
                    $contact->contactType->name ?? 'Unknown',

                'type_slug' =>
                    $contact->contactType->slug ?? null,

                'label' =>
                    $contact->label,

                'value' =>
                    $contact->value,

                'is_primary' =>
                    (bool) $contact->is_primary,

                'sort_order' =>
                    $contact->sort_order,
            ];
        })
        ->values()
        ->all();


    $oldValues = [

        'agency_name' =>
            $agency->agency_name,

        'agency_abbreviation' =>
            $agency->agency_abbreviation,

        'agency_type_id' =>
            $agency->agency_type_id,

        'category_id' =>
            $agency->category_id,

        'contacts' =>
            $contactSnapshot,

        'status' =>
            'Trashed',

        'deleted_at' =>
            $deletedAt,
    ];


    /*
     * =====================================================
     * RESTORE
     * =====================================================
     */
    $agency->restore();


    $agency->refresh();


    /*
     * =====================================================
     * NEW STATE
     * =====================================================
     */
    $newValues = [

        'agency_name' =>
            $agency->agency_name,

        'agency_abbreviation' =>
            $agency->agency_abbreviation,

        'agency_type_id' =>
            $agency->agency_type_id,

        'category_id' =>
            $agency->category_id,

        'contacts' =>
            $contactSnapshot,

        'status' =>
            'Active',

        'deleted_at' =>
            null,
    ];


    /*
     * =====================================================
     * AUDIT
     * =====================================================
     */
    $this->logAction(
        auth()->user()->role ?? 'superadmin',
        auth()->id(),
        $agency->id,
        'restore_agency',
        'nga_ngo_recovery',
        $oldValues,
        $newValues,
        'Restored Agency: ' . $agency->agency_name
    );


    return redirect()
        ->back()
        ->with(
            'success',
            'Agency restored successfully.'
        );
}


    /**
     * =========================================================
     * 💀 PERMANENTLY DELETE AGENCY
     * =========================================================
     *
     * This operation is irreversible.
     *
     * The route must be protected by SuperAdminOnly.
     */
    public function forceDestroy($id)
{
    /*
     * =====================================================
     * FIND TRASHED AGENCY
     * =====================================================
     */
    $agency = Agency::onlyTrashed()
        ->with([
            'faqs' => function ($query) {
                $query->withTrashed();
            },

            'contacts.contactType',
        ])
        ->findOrFail($id);


    /*
     * =====================================================
     * CONTACT AUDIT SNAPSHOT
     * =====================================================
     */
    $contactSnapshot = $agency->contacts
        ->map(function ($contact) {

            return [
                'type' =>
                    $contact->contactType->name ?? 'Unknown',

                'type_slug' =>
                    $contact->contactType->slug ?? null,

                'label' =>
                    $contact->label,

                'value' =>
                    $contact->value,

                'is_primary' =>
                    (bool) $contact->is_primary,

                'sort_order' =>
                    $contact->sort_order,
            ];
        })
        ->values()
        ->all();


    /*
     * =====================================================
     * OLD STATE
     * =====================================================
     *
     * Everything required for the audit is captured before
     * the agency is permanently destroyed.
     */
    $oldValues = [

        'agency_name' =>
            $agency->agency_name,

        'agency_abbreviation' =>
            $agency->agency_abbreviation,

        'agency_type_id' =>
            $agency->agency_type_id,

        'category_id' =>
            $agency->category_id,

        'agency_description' =>
            $agency->agency_description,

        'services_offered' =>
            $agency->services_offered,

        'agency_location' =>
            $agency->agency_location,

        'office_hours' =>
            $agency->office_hours,

        'lat' =>
            $agency->lat,

        'lng' =>
            $agency->lng,

        'agency_image' =>
            $agency->agency_image,

        'contacts' =>
            $contactSnapshot,

        'status' =>
            'Trashed',

        'deleted_at' =>
            $agency->deleted_at,
    ];


    /*
     * =====================================================
     * FAQ FILES
     * =====================================================
     */
    $faqImages = $agency->faqs
        ->pluck('image')
        ->filter()
        ->values()
        ->all();


    /*
     * Count FAQs before deleting them.
     */
    $faqCount = $agency->faqs->count();


    /*
     * Count contacts for the audit record.
     */
    $contactCount = $agency->contacts->count();


    /*
     * =====================================================
     * DATABASE TRANSACTION
     * =====================================================
     */
    DB::transaction(function () use ($agency) {

        /*
         * The foreign key on agency_contacts should remove
         * the child contact records when the agency is
         * permanently deleted.
         */
        $agency->forceDelete();
    });


    /*
     * =====================================================
     * DELETE AGENCY IMAGE
     * =====================================================
     */
    if ($agency->agency_image) {

        Storage::disk('public')
            ->delete($agency->agency_image);
    }


    /*
     * =====================================================
     * DELETE FAQ IMAGES
     * =====================================================
     */
    foreach ($faqImages as $image) {

        Storage::disk('public')
            ->delete($image);
    }


    /*
     * =====================================================
     * NEW STATE
     * =====================================================
     */
    $newValues = [

        'status' =>
            'Permanently deleted',

        'faqs_deleted' =>
            $faqCount,

        'contacts_deleted' =>
            $contactCount,
    ];


    /*
     * =====================================================
     * AUDIT
     * =====================================================
     */
    $this->logAction(
        auth()->user()->role ?? 'superadmin',
        auth()->id(),
        $agency->id,
        'force_delete_agency',
        'nga_ngo_recovery',
        $oldValues,
        $newValues,
        'Permanently deleted Agency: ' . $agency->agency_name
    );


    return redirect()
        ->back()
        ->with(
            'success',
            'Agency permanently deleted.'
        );
}




    /**
     * =========================================================
     * 🔒 CENTRALIZED AUDIT LOGGING
     * =========================================================
     *
     * All agency-related audit records pass through this
     * method.
     *
     * The structured old_values/new_values fields contain the
     * detailed audit information.
     *
     * The legacy old_value/new_value fields contain short
     * summaries so they are not left NULL unnecessarily and
     * do not become excessively large.
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
             * =================================================
             * BUILD SHORT LEGACY SUMMARIES
             * =================================================
             *
             * The full audit data remains in the JSON columns.
             *
             * The legacy fields receive concise summaries
             * suitable for the existing database/UI.
             */
            $oldValueSummary =
                $this->buildAuditSummary(
                    $oldValues
                );

            $newValueSummary =
                $this->buildAuditSummary(
                    $newValues
                );


            /*
             * =================================================
             * CREATE AUDIT RECORD
             * =================================================
             */
            UserLog::create([

                /*
                 * Actor.
                 */
                'user_id' =>
                    $userId,

                /*
                 * Target user is only relevant for actions
                 * involving another user.
                 */
                'target_user_id' =>
                    $targetUserId,

                /*
                 * Agency target.
                 *
                 * This remains populated for every agency
                 * action, including permanent deletion.
                 */
                'agency_id' =>
                    $agencyId,

                /*
                 * Other entity targets are intentionally
                 * NULL because this controller is recording
                 * agency events.
                 */
                'faq_id' =>
                    null,

                'category_id' =>
                    null,


                /*
                 * Action metadata.
                 */
                'action' =>
                    $action,

                'page' =>
                    $page,

                'role' =>
                    $role,


                /*
                 * Request metadata.
                 */
                'ip_address' =>
                    request()->ip(),

                'device' =>
                    substr(
                        request()->userAgent() ?? 'Unknown',
                        0,
                        255
                    ),


                /*
                 * Detailed structured audit information.
                 *
                 * UserLog casts these arrays to JSON.
                 */
                'old_values' =>
                    $oldValues,

                'new_values' =>
                    $newValues,


                /*
                 * Legacy short-form audit fields.
                 */
                'old_value' =>
                    $oldValueSummary,

                'new_value' =>
                    $newValueSummary,


                /*
                 * Human-readable explanation.
                 */
                'description' =>
                    $description,
            ]);

        } catch (\Throwable $e) {

            /*
             * =================================================
             * AUDIT FAILURE HANDLING
             * =================================================
             *
             * Logging failure should not cause the original
             * agency operation to fail.
             *
             * The failure is still written to Laravel's
             * application log for investigation.
             */
            \Log::error(
                'Agency audit log failed.',
                [
                    'action' => $action,
                    'agency_id' => $agencyId,
                    'user_id' => $userId,
                    'error' => $e->getMessage(),
                ]
            );
        }
    }


    /**
     * =========================================================
     * 🧾 BUILD SHORT AUDIT SUMMARY
     * =========================================================
     *
     * Converts structured audit data into a compact string
     * for the legacy old_value/new_value columns.
     *
     * The full data remains available in old_values/new_values.
     */
    private function buildAuditSummary($values)
    {
        /*
         * No structured values were supplied.
         */
        if (!is_array($values) || empty($values)) {
            return 'No recorded value';
        }


        /*
         * Handle explicit status changes first.
         */
        if (
            isset($values['status']) &&
            count($values) <= 2
        ) {

            $summary =
                'Status: ' .
                $this->formatAuditValue(
                    $values['status']
                );

            /*
             * Include deleted_at when it exists.
             */
            if (isset($values['deleted_at'])) {

                $summary .=
                    ', Deleted at: ' .
                    $this->formatAuditValue(
                        $values['deleted_at']
                    );
            }

            return substr(
                $summary,
                0,
                255
            );
        }


        /*
         * For update operations, summarize the fields that
         * changed without dumping the entire JSON payload
         * into the legacy database column.
         */
        $parts = [];

        foreach ($values as $field => $value) {

            /*
             * Avoid putting very large fields such as full
             * descriptions into the legacy summary.
             */
            $formattedValue =
                $this->formatAuditValue(
                    $value
                );

            $label =
                ucwords(
                    str_replace(
                        '_',
                        ' ',
                        $field
                    )
                );

            $parts[] =
                $label . ': ' . $formattedValue;

            /*
             * Keep the legacy field compact.
             */
            if (strlen(
                implode(
                    '; ',
                    $parts
                )
            ) >= 220) {

                break;
            }
        }


        /*
         * Join the readable pieces.
         */
        $summary =
            implode(
                '; ',
                $parts
            );


        /*
         * Ensure the database field cannot be exceeded.
         */
        return substr(
            $summary,
            0,
            255
        );
    }


    /**
     * =========================================================
     * 🧹 FORMAT AUDIT VALUE
     * =========================================================
     *
     * Converts arrays, objects, NULL values, dates and other
     * values into safe human-readable strings.
     */
    private function formatAuditValue($value)
    {
        /*
         * NULL values should remain understandable instead
         * of becoming an empty string.
         */
        if ($value === null) {
            return 'None';
        }


        /*
         * DateTime values are formatted consistently.
         */
        if ($value instanceof \DateTimeInterface) {

            return $value->format(
                'Y-m-d H:i:s'
            );
        }


        /*
         * Arrays and objects are converted to compact JSON.
         */
        if (is_array($value) || is_object($value)) {

            return json_encode(
                $value,
                JSON_UNESCAPED_SLASHES |
                JSON_UNESCAPED_UNICODE
            );
        }


        /*
         * Everything else becomes a string.
         */
        return (string) $value;
    }
}