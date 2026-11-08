<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add an explicit agency profile revision for stale-form protection.
     *
     * updated_at is also retained for existing consumers; this revision is
     * an unambiguous token that advances on every successful profile save.
     */
    public function up(): void
    {
        Schema::table('agencies', function (Blueprint $table) {
            $table->unsignedBigInteger('profile_revision')->default(1);
        });
    }

    public function down(): void
    {
        Schema::table('agencies', function (Blueprint $table) {
            $table->dropColumn('profile_revision');
        });
    }
};
