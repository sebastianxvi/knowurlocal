<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agency_evaluations', function (Blueprint $table) {
            $table->dropUnique('agency_evaluations_agency_user_unique');
            $table->softDeletes();
            $table->index(['agency_id', 'user_id', 'deleted_at'], 'agency_eval_active_user_lookup');
        });
    }

    public function down(): void
    {
        // Reinstating the old unique key is only possible if replacement
        // evaluations have not created multiple historical rows per user/agency.
        Schema::table('agency_evaluations', function (Blueprint $table) {
            $table->dropIndex('agency_eval_active_user_lookup');
            $table->dropSoftDeletes();
            $table->unique(['agency_id', 'user_id'], 'agency_evaluations_agency_user_unique');
        });
    }
};
