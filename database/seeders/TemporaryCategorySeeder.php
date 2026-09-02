<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TemporaryCategorySeeder extends Seeder
{
    /**
     * Seed temporary category data for KNOWURLOCAL testing.
     *
     * This seeder only creates categories.
     * It does not create, update, or delete agencies.
     */
    public function run(): void
    {
        /*
         * Temporary category dataset.
         *
         * Each category has a display color that can be used
         * by the map and other category-based UI elements.
         */
        $categories = [

            [
                'category_name' => 'Agriculture & Agrarian Services',
                'display_color' => '#16A34A',
            ],

            [
                'category_name' => 'Education',
                'display_color' => '#2563EB',
            ],

            [
                'category_name' => 'Public Safety & Security',
                'display_color' => '#DC2626',
            ],

            [
                'category_name' => 'Transportation & Aviation',
                'display_color' => '#7C3AED',
            ],

            [
                'category_name' => 'Science, Technology & Innovation',
                'display_color' => '#0891B2',
            ],

            [
                'category_name' => 'Trade, Industry & Enterprise',
                'display_color' => '#EA580C',
            ],

            [
                'category_name' => 'Financial & Social Security Services',
                'display_color' => '#0F766E',
            ],

            [
                'category_name' => 'Government & Public Administration',
                'display_color' => '#475569',
            ],

            [
                'category_name' => 'Justice & Legal Services',
                'display_color' => '#9333EA',
            ],

            [
                'category_name' => 'Social Welfare & Community Services',
                'display_color' => '#DB2777',
            ],

            [
                'category_name' => 'Health & Public Health',
                'display_color' => '#E11D48',
            ],

            [
                'category_name' => 'Environment & Natural Resources',
                'display_color' => '#15803D',
            ],

            [
                'category_name' => 'Maritime, Ports & Postal Services',
                'display_color' => '#0369A1',
            ],

            [
                'category_name' => 'Labor & Employment',
                'display_color' => '#CA8A04',
            ],

            [
                'category_name' => 'Taxation & Revenue',
                'display_color' => '#B45309',
            ],
        ];

        /*
         * Counters used to provide a simple Artisan console summary.
         */
        $inserted = 0;
        $skipped = 0;

        /*
         * Use a database transaction so the entire category dataset
         * succeeds or fails as one operation.
         *
         * If an unexpected database error occurs halfway through,
         * Laravel rolls back the changes made by this seeder.
         */
        DB::transaction(function () use (
            $categories,
            &$inserted,
            &$skipped
        ) {

            /*
             * Process each category individually.
             */
            foreach ($categories as $data) {

                /*
                 * Include soft-deleted categories in the duplicate check.
                 *
                 * This prevents the seeder from creating another
                 * category with the same name if an old record
                 * is currently in the database Trash.
                 */
                $existingCategory = Category::withTrashed()
                    ->where(
                        'category_name',
                        $data['category_name']
                    )
                    ->first();

                /*
                 * If the category already exists, leave it untouched.
                 */
                if ($existingCategory) {
                    $skipped++;

                    continue;
                }

                /*
                 * Create the category using only fields allowed
                 * by the Category model's $fillable property.
                 */
                Category::create([
                    'category_name' => $data['category_name'],
                    'display_color' => $data['display_color'],
                ]);

                /*
                 * Increase the number of successfully inserted
                 * categories.
                 */
                $inserted++;
            }
        });

        /*
         * Display the result in the Artisan console.
         */
        $this->command->info(
            'Temporary category seeder completed.'
        );

        $this->command->info(
            "Inserted: {$inserted}"
        );

        $this->command->info(
            "Skipped existing: {$skipped}"
        );
    }
}