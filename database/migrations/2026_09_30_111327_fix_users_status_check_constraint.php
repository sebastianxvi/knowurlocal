
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            ALTER TABLE public.users
            DROP CONSTRAINT IF EXISTS users_status_check
        ");

        DB::statement("
            ALTER TABLE public.users
            ADD CONSTRAINT users_status_check
            CHECK (
                status IN ('pending', 'active', 'deactivated')
            )
        ");
    }

    public function down(): void
    {
        $hasDeactivatedUsers = DB::table('users')
            ->where('status', 'deactivated')
            ->exists();

        if ($hasDeactivatedUsers) {
            throw new RuntimeException(
                'Cannot roll back while deactivated users exist.'
            );
        }

        DB::statement("
            ALTER TABLE public.users
            DROP CONSTRAINT IF EXISTS users_status_check
        ");

        DB::statement("
            ALTER TABLE public.users
            ADD CONSTRAINT users_status_check
            CHECK (status IN ('pending', 'active'))
        ");
    }
};