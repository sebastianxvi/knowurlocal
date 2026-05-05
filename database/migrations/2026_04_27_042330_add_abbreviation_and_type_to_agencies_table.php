<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('agencies', function (Blueprint $table) {

            // ✅ Add abbreviation (optional)
            $table->string('agency_abbreviation')
                  ->nullable()
                  ->after('agency_name');

            // ✅ Add foreign key for type
            $table->foreignId('agency_type_id')
                  ->nullable() // 🔥 IMPORTANT for existing data
                  ->constrained('agency_types')
                  ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agencies', function (Blueprint $table) {

            // 🔴 DROP FK FIRST (important)
            $table->dropForeign(['agency_type_id']);

            // Then drop columns
            $table->dropColumn([
                'agency_abbreviation',
                'agency_type_id'
            ]);
        });
    }
};
