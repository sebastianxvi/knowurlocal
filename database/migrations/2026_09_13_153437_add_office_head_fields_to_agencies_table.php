<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add office head information to the agencies table.
     */
    public function up(): void
    {
        Schema::table('agencies', function (Blueprint $table) {

            /*
             * Stores the name of the current head of the office.
             *
             * Nullable allows existing agency records to remain valid
             * even if their office head information is not yet available.
             */
            $table->string('office_head_name')
                ->nullable()
                ->after('agency_description');


            /*
             * Stores the official position of the office head.
             *
             * Example:
             * Municipal Director
             * Officer-in-Charge
             * Executive Director
             */
            $table->string('office_head_position')
                ->nullable()
                ->after('office_head_name');
        });
    }


    /**
     * Remove office head information from the agencies table.
     */
    public function down(): void
    {
        Schema::table('agencies', function (Blueprint $table) {

            /*
             * Removes the office head columns if the migration
             * is rolled back.
             */
            $table->dropColumn([
                'office_head_name',
                'office_head_position',
            ]);
        });
    }
};