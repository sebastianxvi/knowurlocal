<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {

            // 🔐 ROLE: controls WHAT user can do
            $table->enum('role', ['user', 'admin', 'superadmin'])
                  ->default('user')
                  ->after('password');

            // 🔐 STATUS: controls IF user can access system
            $table->enum('status', ['pending', 'active'])
                  ->default('active')
                  ->after('role');

        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'status']);
        });
    }
};