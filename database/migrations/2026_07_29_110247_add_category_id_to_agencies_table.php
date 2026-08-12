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

        $table->foreignId('category_id')
            ->nullable()
            ->constrained('categories')
            ->nullOnDelete()
            ->after('agency_type_id');

    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agencies', function (Blueprint $table) {
            //
        });
    }
};
