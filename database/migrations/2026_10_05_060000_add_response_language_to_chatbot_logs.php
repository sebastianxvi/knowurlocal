<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('chatbot_logs', 'response_language')) {
            Schema::table('chatbot_logs', function (Blueprint $table) {
                $table->string('response_language', 10)
                    ->nullable()
                    ->after('score');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('chatbot_logs', 'response_language')) {
            Schema::table('chatbot_logs', function (Blueprint $table) {
                $table->dropColumn('response_language');
            });
        }
    }
};
