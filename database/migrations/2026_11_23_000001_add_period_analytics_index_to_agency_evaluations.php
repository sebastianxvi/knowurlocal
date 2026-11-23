<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agency_evaluations', function (Blueprint $table) {
            // Supports system-wide published-evaluation analytics filtered by
            // status and created_at. Agency-scoped analytics already use the
            // agency_id/status/created_at index created with evaluations.
            $table->index(
                ['status', 'created_at'],
                'agency_evaluations_status_created_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('agency_evaluations', function (Blueprint $table) {
            $table->dropIndex('agency_evaluations_status_created_idx');
        });
    }
};
