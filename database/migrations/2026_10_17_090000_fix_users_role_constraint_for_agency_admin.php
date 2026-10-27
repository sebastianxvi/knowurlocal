<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
         * Earlier deployments may retain a CHECK constraint from the original
         * users.role definition (user/admin/superadmin). That constraint blocks
         * inserting agency_admin even though the later migration changed the
         * column to a string. Remove only role-related CHECK constraints.
         */
        if (DB::getDriverName() === 'pgsql') {
            DB::statement(<<<'SQL'
DO $$
DECLARE
    constraint_row RECORD;
BEGIN
    FOR constraint_row IN
        SELECT conname
        FROM pg_constraint
        WHERE conrelid = 'public.users'::regclass
          AND contype = 'c'
          AND pg_get_constraintdef(oid) ILIKE '%role%'
    LOOP
        EXECUTE format(
            'ALTER TABLE public.users DROP CONSTRAINT %I',
            constraint_row.conname
        );
    END LOOP;
END
$$;
SQL);
            DB::statement(
                'ALTER TABLE public.users ALTER COLUMN role TYPE varchar(32) USING role::text'
            );
        } else {
            Schema::table('users', function (Blueprint $table) {
                $table->string('role', 32)->default('user')->change();
            });
        }
    }

    public function down(): void
    {
        // Intentionally do not restore the legacy role restriction: agency_admin
        // accounts may already exist, and narrowing the allowed role set would
        // make valid accounts unusable.
    }
};
