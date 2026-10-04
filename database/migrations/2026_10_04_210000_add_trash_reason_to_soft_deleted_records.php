<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach ([
            'faqs',
            'agencies',
            'categories',
            'support_requests',
        ] as $tableName) {
            if (!Schema::hasColumn($tableName, 'trash_reason')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->text('trash_reason')->nullable();
                });
            }
        }
    }

    public function down(): void
    {
        Schema::table('support_requests', fn (Blueprint $table) => $table->dropColumn('trash_reason'));
        Schema::table('categories', fn (Blueprint $table) => $table->dropColumn('trash_reason'));
        Schema::table('agencies', fn (Blueprint $table) => $table->dropColumn('trash_reason'));
        Schema::table('faqs', fn (Blueprint $table) => $table->dropColumn('trash_reason'));
    }
};
