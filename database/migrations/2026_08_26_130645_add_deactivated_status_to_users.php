<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add the deactivated account state.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {

            /*
             * Rebuild the existing status enum so accounts can
             * explicitly represent a deactivated state.
             *
             * Existing pending and active values remain valid.
             */
            $table->enum('status', [
                'pending',
                'active',
                'deactivated',
            ])
            ->default('active')
            ->change();
        });
    }

    /**
     * Restore the original status values.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {

            /*
             * Before rolling this back, make sure no user has
             * status = deactivated, otherwise MySQL cannot safely
             * reduce the enum values.
             */
            $table->enum('status', [
                'pending',
                'active',
            ])
            ->default('active')
            ->change();
        });
    }
};