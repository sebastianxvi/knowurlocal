<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Use a string role column so the role model can evolve without
        // MySQL ENUM changes blocking deployments.
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 32)->default('user')->change();
            $table->foreignId('agency_id')
                ->nullable()
                ->after('role')
                ->constrained('agencies')
                ->nullOnDelete();
        });

        Schema::table('admin_invites', function (Blueprint $table) {
            $table->string('role', 32)->default('admin')->after('email');
            $table->foreignId('agency_id')
                ->nullable()
                ->after('role')
                ->constrained('agencies')
                ->nullOnDelete();
        });

        Schema::table('email_verifications', function (Blueprint $table) {
            $table->foreignId('agency_id')
                ->nullable()
                ->after('role')
                ->constrained('agencies')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('email_verifications', function (Blueprint $table) {
            $table->dropConstrainedForeignId('agency_id');
        });

        Schema::table('admin_invites', function (Blueprint $table) {
            $table->dropConstrainedForeignId('agency_id');
            $table->dropColumn('role');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('agency_id');
            $table->string('role', 32)->default('user')->change();
        });
    }
};
