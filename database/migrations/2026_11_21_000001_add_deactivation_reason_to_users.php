<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'deactivation_reason')) {
            Schema::table('users', function (Blueprint $table) {
                $table->text('deactivation_reason')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'deactivation_reason')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('deactivation_reason');
            });
        }
    }
};
