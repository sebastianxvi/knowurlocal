<?php

namespace Database\Seeders;

use App\Models\AgencyType;
use Illuminate\Database\Seeder;

class AgencyTypeSeeder extends Seeder
{
    /**
     * Seed the initial agency types.
     */
    public function run(): void
    {
        // Define the initial agency classifications.
        $types = [
            'NGA',
            'NGO',
        ];

        // Create each type only if it does not already exist.
        foreach ($types as $type) {
            AgencyType::firstOrCreate([
                'name' => $type,
            ]);
        }
    }
}