<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('email_verifications', function (Blueprint $table) {
            $table->timestamp('legal_accepted_at')->nullable();
            $table->string('terms_version', 32)->nullable();
            $table->string('privacy_version', 32)->nullable();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('legal_accepted_at')->nullable();
            $table->string('terms_version', 32)->nullable();
            $table->string('privacy_version', 32)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('email_verifications', function (Blueprint $table) {
            $table->dropColumn(['legal_accepted_at', 'terms_version', 'privacy_version']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['legal_accepted_at', 'terms_version', 'privacy_version']);
        });
    }
};
