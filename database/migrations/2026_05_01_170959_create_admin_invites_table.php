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
        Schema::create('admin_invites', function (Blueprint $table) {
    $table->id();

    $table->string('email')->index();

    // 🔐 Secure token (NOT guessable)
    $table->string('token')->unique();

    // who invited
    $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();

    // expiration
    $table->timestamp('expires_at');

    // track usage
    $table->boolean('used')->default(false);

    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_invites');
    }
};
