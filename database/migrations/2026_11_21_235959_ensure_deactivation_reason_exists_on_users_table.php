<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Repairs environments where the original deactivation-reason migration
 * was recorded as completed but the users column is absent (schema drift).
 *
 * This is intentionally idempotent: it is safe when the column already
 * exists and safe after the original migration has run.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'deactivation_reason')) {
            Schema::table('users', function (Blueprint $table) {
                $table->text('deactivation_reason')->nullable();
            });
        }
    }

    public function down(): void
    {
        // Intentionally do not drop the column: this repair migration may
        // be the only migration protecting existing deactivation data.
    }
};
