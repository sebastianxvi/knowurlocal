<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ContactType;

class ContactTypeSeeder extends Seeder
{
    /**
     * Insert the predefined contact types used by KNOWURLOCAL.
     */
    public function run(): void
    {
        /*
         * These are system-defined contact types.
         *
         * Administrators will select one of these types when
         * adding contact information to an agency.
         */
        $contactTypes = [
            [
                'name' => 'Hotline',
                'slug' => 'hotline',
                'is_active' => true,
                'sort_order' => 1,
            ],

            [
                'name' => 'Landline',
                'slug' => 'landline',
                'is_active' => true,
                'sort_order' => 2,
            ],

            [
                'name' => 'Email',
                'slug' => 'email',
                'is_active' => true,
                'sort_order' => 3,
            ],

            [
                'name' => 'Website',
                'slug' => 'website',
                'is_active' => true,
                'sort_order' => 4,
            ],

            [
                'name' => 'Facebook',
                'slug' => 'facebook',
                'is_active' => true,
                'sort_order' => 5,
            ],
        ];


        /*
         * updateOrCreate() prevents duplicate contact types
         * if the seeder is executed more than once.
         *
         * The slug is the stable identifier.
         */
        foreach ($contactTypes as $contactType) {

            ContactType::updateOrCreate(
                [
                    'slug' => $contactType['slug'],
                ],
                $contactType
            );
        }
    }
}