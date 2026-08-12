<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\UserLog;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * Display all categories.
     */
    /**
 * Display all categories.
 */
public function index(Request $request)
{
    // Start with the category query.
    $query = Category::withCount('agencies');

    // Apply the search filter when the user entered a search term.
    if ($request->filled('search')) {
        $search = trim($request->input('search'));

        $query->where(
            'category_name',
            'like',
            '%' . $search . '%'
        );
    }

    // Apply the selected sorting option.
    if ($request->input('sort') === 'oldest') {
        $query->oldest();
    } else {
        // Default behavior remains newest first.
        $query->latest();
    }

    // Keep search/sort parameters when moving between pages.
    $categories = $query
        ->paginate(10)
        ->withQueryString();

    return view(
        'admin.category-management',
        compact('categories')
    );
}

    /**
     * Store a new category.
     */
    public function store(Request $request)
    {
        /*
         * Validate the category before creating anything.
         *
         * Laravel rejects invalid input before the database
         * operation is performed.
         */
        $validated = $request->validate([
            'category_name' =>
                'required|string|max:50|unique:categories,category_name',

            'display_color' =>
                'required|string',
        ]);

        /*
         * Create the category and keep the resulting model.
         *
         * We need the generated ID for the audit record.
         */
        $category = Category::create($validated);

        /*
         * Record the creation only after the database operation
         * has succeeded.
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
         * Validate the new category information.
         *
         * The current category ID is excluded from the unique
         * check so keeping the same name remains valid.
         */
        $validated = $request->validate([
            'category_name' =>
                'required|string|max:50|unique:categories,category_name,' .
                $category->id,

            'display_color' =>
                'required|string',
        ]);

        /*
         * Capture the original values BEFORE updating.
         *
         * This gives the audit system an accurate before/after
         * comparison.
         */
        $oldData = [
            'category_name' => $category->category_name,
            'display_color' => $category->display_color,
        ];

        /*
         * Update the category.
         */
        $category->update($validated);

        /*
         * Capture the values AFTER updating.
         */
        $newData = [
            'category_name' => $category->category_name,
            'display_color' => $category->display_color,
        ];

        /*
         * Determine which fields actually changed.
         */
        $changes = $this->getChangedValues(
            $oldData,
            $newData
        );

        /*
         * If nothing actually changed, there is no useful
         * audit event to record.
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
         * Record the actual changed values.
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
     * Delete a category.
     *
     * Category uses Laravel SoftDeletes, so this operation
     * does not permanently remove the database record.
     */
    public function destroy(Category $category)
    {
        /*
         * Capture important information before deletion.
         *
         * The category may no longer appear in normal queries
         * after the soft delete.
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
         * Laravel sets deleted_at instead of permanently
         * deleting the row.
         */
        $category->delete();

        /*
         * Record the deletion after it succeeds.
         */
        $this->logAction(
            'delete_category',
            $categoryId,
            $oldData,
            null,
            'Deleted Category: ' . $categoryName
        );

        return back()->with(
            'success',
            'Category deleted successfully.'
        );
    }

    /**
     * Create an audit record for a category action.
     *
     * This method keeps the audit structure consistent across
     * category operations.
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
                 * The authenticated administrator performing
                 * the operation.
                 */
                'user_id' => auth()->id(),

                /*
                 * Categories are not user-owned.
                 */
                'target_user_id' => null,

                /*
                 * Categories are not agencies.
                 */
                'agency_id' => null,

                /*
                 * FAQ is unrelated to this operation.
                 */
                'faq_id' => null,

                /*
                 * Identify the category being modified.
                 */
                'category_id' => $categoryId,

                /*
                 * Machine-readable action.
                 */
                'action' => $action,

                /*
                 * Identify the module where the action occurred.
                 */
                'page' => 'admin_category',

                /*
                 * Preserve the actor's role at the time
                 * of the operation.
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
                 * Human-readable description.
                 */
                'description' => $description,
            ]);
        } catch (\Throwable $e) {

            /*
             * Logging must not break the actual category
             * operation.
             *
             * The failure is still recorded in Laravel's
             * application log for debugging.
             */
            \Log::error(
                'Category audit logging failed.',
                [
                    'action' => $action,
                    'category_id' => $categoryId,
                    'error' => $e->getMessage(),
                ]
            );
        }
    }

    /**
     * Determine which category values actually changed.
     *
     * Only changed fields are returned.
     */
    private function getChangedValues(
        array $oldData,
        array $newData
    ): array {
        $oldChanged = [];
        $newChanged = [];

        foreach ($newData as $field => $newValue) {

            /*
             * Retrieve the previous value for this field.
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