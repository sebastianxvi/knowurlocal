
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            ALTER TABLE public.support_requests
            DROP CONSTRAINT IF EXISTS support_requests_status_check
        ");

        DB::statement("
            ALTER TABLE public.support_requests
            ADD CONSTRAINT support_requests_status_check
            CHECK (
                status IN (
                    'pending',
                    'awaiting_confirmation',
                    'needs_follow_up',
                    'answered'
                )
            )
        ");
    }

    public function down(): void
    {
        // Refuse to restore the old constraint if records
        // already use newer workflow statuses.
        $hasNewStatuses = DB::table('support_requests')
            ->whereNotIn('status', ['pending', 'answered'])
            ->exists();

        if ($hasNewStatuses) {
            throw new RuntimeException(
                'Cannot restore the old status constraint while support requests use newer statuses.'
            );
        }

        DB::statement("
            ALTER TABLE public.support_requests
            DROP CONSTRAINT IF EXISTS support_requests_status_check
        ");

        DB::statement("
            ALTER TABLE public.support_requests
            ADD CONSTRAINT support_requests_status_check
            CHECK (status IN ('pending', 'answered'))
        ");
    }
};