<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class TemporaryUserSeeder extends Seeder
{
    /**
     * Temporary presentation data only.
     *
     * These are synthetic identities. Email addresses are intentionally
     * plausible-looking for screenshots, but must never be used for sending
     * real messages or treated as verified contact information.
     */
    public function run(): void
    {
        $users = [
            ['first_name' => 'Alyssa Mae', 'last_name' => 'Villanueva', 'email' => 'alyssamae.villanueva28@gmail.com'],
            ['first_name' => 'Joshua Miguel', 'last_name' => 'Reyes', 'email' => 'joshua.miguel.reyes17@gmail.com'],
            ['first_name' => 'Camille Andrea', 'last_name' => 'Mendoza', 'email' => 'camille.andrea.mendoza@gmail.com'],
            ['first_name' => 'Rafael Luis', 'last_name' => 'Santos', 'email' => 'rafael.luis.santos91@yahoo.com'],
            ['first_name' => 'Bea Patricia', 'last_name' => 'Dela Cruz', 'email' => 'beapatricia.delacruz@gmail.com'],
            ['first_name' => 'Nathaniel', 'last_name' => 'Bautista', 'email' => 'nathaniel.bautista24@outlook.com'],
            ['first_name' => 'Katrina Joy', 'last_name' => 'Navarro', 'email' => 'katrinajoy.navarro@gmail.com'],
            ['first_name' => 'Mark Anthony', 'last_name' => 'Garcia', 'email' => 'markanthony.garcia82@gmail.com'],
            ['first_name' => 'Danica Faith', 'last_name' => 'Ramos', 'email' => 'danica.faith.ramos@gmail.com'],
            ['first_name' => 'Paolo Enrique', 'last_name' => 'Fernandez', 'email' => 'paolo.enrique.fernandez@gmail.com'],
            ['first_name' => 'Jasmine Nicole', 'last_name' => 'Castillo', 'email' => 'jasmine.nicole.castillo@yahoo.com'],
            ['first_name' => 'John Carlo', 'last_name' => 'Aquino', 'email' => 'johncarlo.aquino19@gmail.com'],
            ['first_name' => 'Trisha Mae', 'last_name' => 'Flores', 'email' => 'trishamae.flores@gmail.com'],
            ['first_name' => 'Angelo', 'last_name' => 'Manalo', 'email' => 'angelo.manalo07@outlook.com'],
            ['first_name' => 'Mary Grace', 'last_name' => 'Rivera', 'email' => 'marygrace.rivera@gmail.com'],
            ['first_name' => 'Christian Paulo', 'last_name' => 'Gonzales', 'email' => 'christian.paulo.gonzales@gmail.com'],
            ['first_name' => 'Nicole Anne', 'last_name' => 'Torres', 'email' => 'nicole.anne.torres26@gmail.com'],
            ['first_name' => 'Francis Gabriel', 'last_name' => 'Lim', 'email' => 'francisgabriel.lim@gmail.com'],
            ['first_name' => 'Mikaela Rose', 'last_name' => 'Soriano', 'email' => 'mikaela.rose.soriano@yahoo.com'],
            ['first_name' => 'Jared', 'last_name' => 'Domingo', 'email' => 'jared.domingo15@gmail.com'],
            ['first_name' => 'Christine Joy', 'last_name' => 'Mercado', 'email' => 'christinejoy.mercado@gmail.com'],
            ['first_name' => 'Kevin Mark', 'last_name' => 'Ocampo', 'email' => 'kevin.mark.ocampo@gmail.com'],
            ['first_name' => 'Angelica', 'last_name' => 'Panganiban', 'email' => 'angelica.panganiban08@outlook.com'],
            ['first_name' => 'Renz Patrick', 'last_name' => 'Alvarez', 'email' => 'renzpatrick.alvarez@gmail.com'],
            ['first_name' => 'Shaira Louise', 'last_name' => 'Gutierrez', 'email' => 'shaira.louise.gutierrez@gmail.com'],
            ['first_name' => 'Miguel Angelo', 'last_name' => 'Padilla', 'email' => 'miguel.angelo.padilla@gmail.com'],
            ['first_name' => 'Precious Anne', 'last_name' => 'Manansala', 'email' => 'precious.anne.manansala@yahoo.com'],
            ['first_name' => 'Gabriel', 'last_name' => 'Soriano', 'email' => 'gabriel.soriano32@gmail.com'],
            ['first_name' => 'Erika Mae', 'last_name' => 'Cabrera', 'email' => 'erikamae.cabrera@gmail.com'],
            ['first_name' => 'Luis Miguel', 'last_name' => 'Dizon', 'email' => 'luismiguel.dizon21@gmail.com'],
        ];

        // Shared demo-only password; stored only as a secure hash.
        $passwordHash = Hash::make('KnowUrLocal-Demo-2026!');

        $created = 0;
        $skipped = 0;

        foreach ($users as $index => $data) {
            // Spread registration dates across roughly the past 11 months.
            $registrationAgeDays = 30 + (($index * 17) % 300);
            $registeredAt = now()
                ->subDays($registrationAgeDays)
                ->subHours(($index * 5) % 24);

            // Keep every last login after registration and within the past 21 days.
            $lastLoginDaysAgo = ($index * 7) % 22;
            $lastLoginAt = now()
                ->subDays($lastLoginDaysAgo)
                ->subHours(($index * 3) % 24);

            // firstOrCreate prevents duplicates and never overwrites an existing
            // account if an address happens to be present already.
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'password' => $passwordHash,
                    'email_verified_at' => $registeredAt->copy()->addHours(2),
                    'role' => 'user',
                    'status' => 'active',
                    'agency_id' => null,
                    'can_moderate_evaluations' => false,
                    'last_login_at' => $lastLoginAt,
                    'profile_picture' => null,
                    // Legal consent fields are intentionally left null.
                    // A demo seeder must not fabricate a user's legal consent.
                    'created_at' => $registeredAt,
                    'updated_at' => $registeredAt,
                ]
            );

            if ($user->wasRecentlyCreated) {
                // created_at/updated_at are intentionally historical for the
                // presentation, so persist them explicitly after Eloquent's
                // normal creation timestamps have been applied.
                DB::table('users')
                    ->where('id', $user->id)
                    ->update([
                        'created_at' => $registeredAt,
                        'updated_at' => $registeredAt,
                    ]);

                $created++;
            } else {
                $skipped++;
            }
        }

        $this->command?->info('Temporary user seeder completed.');
        $this->command?->info("Created: {$created}");
        $this->command?->info("Skipped (email already exists): {$skipped}");
        $this->command?->warn(
            'Synthetic email addresses are for presentation only. Do not send real email to these accounts.'
        );
    }
}
