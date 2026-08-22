<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Allow office hours to be omitted from an agency record.
     *
     * Office hours are useful information, but they are not
     * required for an agency to be considered a valid record.
     */
    public function up(): void
    {
        Schema::table('agencies', function (Blueprint $table) {

            /*
             * Change office_hours from NOT NULL to nullable.
             *
             * This allows Laravel to store NULL when the
             * administrator leaves the field empty.
             */
            $table->text('office_hours')
                ->nullable()
                ->change();
        });
    }

    /**
     * Restore the previous database constraint.
     *
     * This is the reverse of the migration.
     */
    public function down(): void
    {
        Schema::table('agencies', function (Blueprint $table) {

            /*
             * Restore office_hours as NOT NULL.
             */
            $table->text('office_hours')
                ->nullable(false)
                ->change();
        });
    }
};