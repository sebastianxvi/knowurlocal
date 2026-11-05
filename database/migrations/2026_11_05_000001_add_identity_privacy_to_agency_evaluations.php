<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agency_evaluations', function (Blueprint $table) {
            // Existing evaluations become anonymous by default; identity disclosure
            // must be an explicit per-evaluation opt-in.
            $table->boolean('keep_identity_hidden')->default(true)->after('comment');
        });
    }

    public function down(): void
    {
        Schema::table('agency_evaluations', function (Blueprint $table) {
            $table->dropColumn('keep_identity_hidden');
        });
    }
};
