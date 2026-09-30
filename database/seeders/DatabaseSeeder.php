<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed required reference data for a fresh KnowUrLocal database.
     * Demo agencies, FAQs, and test accounts are intentionally not created
     * automatically so production cannot accidentally receive test content.
     */
    public function run(): void
    {
        $this->call([
            AgencyTypeSeeder::class,
            ContactTypeSeeder::class,
            TemporaryCategorySeeder::class,
            SuperAdminSeeder::class,
        ]);
    }
}
