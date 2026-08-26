<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\UserLog;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * Display categories.
     *
     * Supports both:
     * - active categories
     * - trashed categories
     */
    public function index(Request $request)
    {

    
        /*
         * Determine which dataset the administrator wants
         * to view.
         *
         * Only "active" and "trashed" are accepted.
         * Anything else safely falls back to "active".
         */
        $status = $request->input('status', 'active');

        if (!in_array($status, ['active', 'trashed'], true)) {
            $status = 'active';
        }

        /*
         * Start the category query.
         *
         * withCount('agencies') counts only active agencies
         * because the Agency model uses SoftDeletes.
         */
        $query = Category::withCount('agencies');

        /*
         * Laravel normally excludes soft-deleted categories.
         *
         * onlyTrashed() changes that behavior so the recovery
         * view contains only categories currently in the trash.
         */
        if ($status === 'trashed') {

    /*
     * Only Superadmins can access the recovery view.
     *
     * This protects the endpoint even if someone manually
     * changes the status parameter in the URL.
     */
    abort_unless(
        auth()->user()->role === 'superadmin',
        403
    );

    /*
     * Retrieve only soft-deleted categories.
     */
    $query->onlyTrashed();
}

        /*
         * Apply category search.
         */
        if ($request->filled('search')) {

            $search = trim(
                $request->input('search')
            );

            $query->where(
                'category_name',
                'like',
                '%' . $search . '%'
            );
        }

        /*
         * Apply sorting.
         *
         * Newest remains the default behavior.
         */
        if ($request->input('sort') === 'oldest') {

            $query->oldest();

        } else {

            $query->latest();
        }

        /*
         * Paginate the results.
         *
         * withQueryString() preserves:
         * - search
         * - sort
         * - status
         *
         * when navigating between pages.
         */
        $categories = $query
            ->paginate(10)
            ->withQueryString();

        /*
         * Calculate the two status counts separately.
         *
         * Category::count()
         * automatically excludes soft-deleted records.
         */
        $activeCount = Category::count();

        /*
         * onlyTrashed() returns only deleted categories.
         */
        $trashedCount = Category::onlyTrashed()->count();

        return view(
            'admin.category-management',
            compact(
                'categories',
                'status',
                'activeCount',
                'trashedCount'
            )
        );
    }


    /**
     * Store a new category.
     */
    public function store(Request $request)
    {
        /*
         * Validate the incoming category data before
         * interacting with the database.
         */
        $validated = $request->validate([
            'category_name' =>
                'required|string|max:50|unique:categories,category_name',

            'display_color' =>
                'required|string',
        ]);

        /*
         * Create the category.
         */
        $category = Category::create($validated);

        /*
         * Record the successful creation.
         */
        $this->logAction(
            'create_category',
            $category->id,
            null,
            [
                'category_name' => $category->category_name,
                'display_color' => $category->display_color,
            ],
            'Created Category: ' . $category->category_name
        );

        return back()->with(
            'success',
            'Category added successfully.'
        );
    }


    /**
     * Update an existing category.
     */
    public function update(
        Request $request,
        Category $category
    ) {
        /*
         * Validate the updated category.
         *
         * The current category ID is excluded from the
         * unique-name check.
         */
        $validated = $request->validate([
            'category_name' =>
                'required|string|max:50|unique:categories,category_name,' .
                $category->id,

            'display_color' =>
                'required|string',
        ]);

        /*
         * Capture the original values before changing them.
         */
        $oldData = [
            'category_name' => $category->category_name,
            'display_color' => $category->display_color,
        ];

        /*
         * Apply the update.
         */
        $category->update($validated);

        /*
         * Capture the resulting values.
         */
        $newData = [
            'category_name' => $category->category_name,
            'display_color' => $category->display_color,
        ];

        /*
         * Determine exactly which fields changed.
         */
        $changes = $this->getChangedValues(
            $oldData,
            $newData
        );

        /*
         * Do not create an unnecessary audit record if
         * nothing actually changed.
         */
        if (
            empty($changes['old']) &&
            empty($changes['new'])
        ) {
            return back()->with(
                'success',
                'No changes were made.'
            );
        }

        /*
         * Record the actual changes.
         */
        $this->logAction(
            'update_category',
            $category->id,
            $changes['old'],
            $changes['new'],
            'Updated Category: ' . $category->category_name
        );

        return back()->with(
            'success',
            'Category updated successfully.'
        );
    }


    /**
     * Move a category to the trash.
     *
     * This is a soft delete.
     */
    public function destroy(Category $category)
    {
        /*
         * Capture the category information before the
         * soft-delete occurs.
         */
        $oldData = [
            'category_name' => $category->category_name,
            'display_color' => $category->display_color,
        ];

        $categoryName = $category->category_name;
        $categoryId = $category->id;

        /*
         * Soft-delete the category.
         *
         * Laravel sets deleted_at instead of removing
         * the database row.
         */
        $category->delete();

        /*
         * Record the move-to-trash operation.
         */
        $this->logAction(
            'delete_category',
            $categoryId,
            $oldData,
            null,
            'Moved Category to Trash: ' . $categoryName
        );

        return back()->with(
            'success',
            'Category moved to trash successfully.'
        );
    }


    /**
     * Restore a category from the trash.
     */
    public function restore(int $id)
    {

    abort_unless(
    auth()->user()->role === 'superadmin',
    403
);
        /*
         * Only retrieve records that are actually
         * soft-deleted.
         */
        $category = Category::onlyTrashed()
            ->findOrFail($id);

        $categoryName = $category->category_name;

        /*
         * Restore the category.
         */
        $category->restore();

        /*
         * Record the restoration.
         */
        $oldData = [
    'category_name' => $category->category_name,
    'display_color' => $category->display_color,
];

$this->logAction(
    'restore_category',
    $category->id,
    $oldData,
    null,
    'Restored Category: ' . $categoryName
);

        return back()->with(
            'success',
            'Category restored successfully.'
        );
    }


   /**
 * Permanently delete a category.
 *
 * A category may only be permanently deleted when no active
 * agency is currently assigned to it.
 *
 * The audit record is created BEFORE forceDelete() so the
 * historical category information is captured while the
 * category still exists.
 */
public function forceDestroy(int $id)
{
    /*
     * Only Superadmins may permanently delete categories.
     *
     * Authorization must always be enforced server-side.
     */
    abort_unless(
        auth()->user()->role === 'superadmin',
        403
    );

    /*
     * Only retrieve categories that are already in the trash.
     *
     * This prevents an active category from being permanently
     * deleted through this endpoint.
     */
    $category = Category::onlyTrashed()
        ->findOrFail($id);

    /*
     * Count active agencies currently assigned to this category.
     *
     * The Agency model uses SoftDeletes, so the normal
     * relationship excludes already-trashed agencies.
     */
    $agencyCount = $category->agencies()->count();

    /*
     * A category that is still being used by an active agency
     * must not be permanently deleted.
     *
     * This protects referential integrity.
     */
    if ($agencyCount > 0) {

        return back()->with(
            'error',
            "This category cannot be permanently deleted because it is still assigned to {$agencyCount} " .
            ($agencyCount === 1 ? 'agency.' : 'agencies.') .
            " Please reassign the agency category first to proceed."
        );
    }

    /*
     * Capture the category identity BEFORE deleting it.
     *
     * Once forceDelete() succeeds, the Category model can no
     * longer be resolved by UserLog::category().
     */
    $categoryId = $category->id;

    $oldData = [
        'category_name' => $category->category_name,
        'display_color' => $category->display_color,
    ];

    $categoryName = $category->category_name;

    /*
     * Create the audit record BEFORE permanently deleting
     * the category.
     *
     * This ensures the audit system has the category ID and
     * historical values while the category still exists.
     */
    $this->logAction(
        'force_delete_category',
        $categoryId,
        $oldData,
        null,
        'Permanently Deleted Category: ' . $categoryName
    );

    /*
     * Only permanently delete the category after the audit
     * record has been successfully created.
     */
    $category->forceDelete();

    return back()->with(
        'success',
        'Category permanently deleted.'
    );
}


    /**
     * Create a category audit log.
     */
    private function logAction(
        string $action,
        int $categoryId,
        ?array $oldValues,
        ?array $newValues,
        string $description
    ): void {
        try {

            UserLog::create([

                /*
                 * Administrator performing the operation.
                 */
                'user_id' => auth()->id(),

                /*
                 * Categories are not user-owned.
                 */
                'target_user_id' => null,

                /*
                 * Categories are not directly agencies.
                 */
                'agency_id' => null,

                /*
                 * FAQ is unrelated to this operation.
                 */
                'faq_id' => null,

                /*
                 * Category affected by the operation.
                 */
                'category_id' => $categoryId,

                /*
                 * Stable machine-readable action.
                 */
                'action' => $action,

                /*
                 * Module where the action occurred.
                 */
                'page' => 'admin_category',

                /*
                 * Preserve the administrator's role.
                 */
                'role' => auth()->user()->role ?? 'admin',

                /*
                 * Security metadata.
                 */
                'ip_address' => request()->ip(),

                'device' => substr(
                    request()->userAgent() ?? 'Unknown',
                    0,
                    255
                ),

                /*
                 * Structured before/after values.
                 */
                'old_values' => $oldValues,

                'new_values' => $newValues,

                /*
                 * Human-readable audit description.
                 */
                'description' => $description,
            ]);

        } catch (\Throwable $e) {

    /*
     * Record the technical failure in Laravel's application log.
     *
     * This is useful for debugging without exposing database
     * details to the administrator.
     */
    \Log::error(
        'Category audit logging failed.',
        [
            'action' => $action,
            'category_id' => $categoryId,
            'error' => $e->getMessage(),
        ]
    );

    /*
     * Re-throw the exception.
     *
     * This prevents a destructive category operation from
     * succeeding without its required audit record.
     */
    throw $e;
}
    }


    /**
     * Determine which category values changed.
     */
    private function getChangedValues(
        array $oldData,
        array $newData
    ): array {
        $oldChanged = [];
        $newChanged = [];

        foreach ($newData as $field => $newValue) {

            /*
             * Retrieve the previous value.
             */
            $oldValue = $oldData[$field] ?? null;

            /*
             * Strict comparison prevents false changes caused
             * by different PHP data types.
             */
            if ($oldValue !== $newValue) {

                $oldChanged[$field] = $oldValue;

                $newChanged[$field] = $newValue;
            }
        }

        return [
            'old' => $oldChanged,
            'new' => $newChanged,
        ];
    }
}