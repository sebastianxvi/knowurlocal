<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // This migration may be applied to databases where the column was
        // already created manually or by an earlier development revision.
        // Checking first keeps the migration history repairable and prevents
        // MySQL error 1060 (duplicate column).
        if (!Schema::hasColumn('faqs', 'response_components')) {
            Schema::table('faqs', function (Blueprint $table) {
                $table->json('response_components')
                    ->nullable()
                    ->after('image');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('faqs', 'response_components')) {
            Schema::table('faqs', function (Blueprint $table) {
                $table->dropColumn('response_components');
            });
        }
    }
};
