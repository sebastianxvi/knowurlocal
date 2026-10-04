<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Remove map page-view activity records.
     *
     * Opening the map is passive navigation and does not provide useful
     * audit value. New map visits are no longer logged.
     */
    public function up(): void
    {
        DB::table('user_logs')
            ->where('action', 'view_map')
            ->delete();
    }

    /**
     * The removed page-view records are intentionally not recreated.
     */
    public function down(): void
    {
        // Intentionally left empty.
    }
};
