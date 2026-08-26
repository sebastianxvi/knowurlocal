<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add last_login_at to the users table.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {

            /*
             * Stores the date and time of the user's
             * most recent successful authentication.
             *
             * Nullable because a newly registered user
             * may not have logged in yet.
             */
            $table->timestamp('last_login_at')
                ->nullable()
                ->after('email_verified_at');
        });
    }


    /**
     * Remove last_login_at if the migration is rolled back.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {

            $table->dropColumn('last_login_at');

        });
    }
};