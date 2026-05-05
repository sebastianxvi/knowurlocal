<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::table('user_logs', function (Blueprint $table) {

        // 🔥 target user (nullable for normal logs)
        $table->unsignedBigInteger('target_user_id')->nullable()->after('user_id');

        // 🔥 audit fields
        $table->string('old_value')->nullable();
        $table->string('new_value')->nullable();

        // 🔥 readable message
        $table->text('description')->nullable();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_logs', function (Blueprint $table) {
            //
        });
    }
};
