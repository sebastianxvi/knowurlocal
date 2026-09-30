
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add the deactivated account state.
     */
    public function up(): void
    {
        // Preserve the existing column's nullability and default.
        Schema::table('users', function (Blueprint $table) {
            $table->string('status')
                ->default('active')
                ->change();
        });

        // PostgreSQL requires a separate CHECK constraint.
        DB::statement("
    DO $$
    BEGIN
        IF NOT EXISTS (
            SELECT 1
            FROM pg_constraint
            WHERE conname = 'users_status_check'
              AND conrelid = 'users'::regclass
        ) THEN
            ALTER TABLE users
            ADD CONSTRAINT users_status_check
            CHECK (status IN ('pending', 'active', 'deactivated'));
        END IF;
    END
    $$;
");
    }

    /**
     * Restore the original status values.
     */
    public function down(): void
    {
        // Do not silently discard the deactivated state.
        if (DB::table('users')->where('status', 'deactivated')->exists()) {
            throw new RuntimeException(
                'Cannot roll back: users with deactivated status exist.'
            );
        }

        DB::statement(
            'ALTER TABLE users DROP CONSTRAINT users_status_check'
        );

        Schema::table('users', function (Blueprint $table) {
            $table->string('status')
                ->default('active')
                ->change();
        });
    }
};