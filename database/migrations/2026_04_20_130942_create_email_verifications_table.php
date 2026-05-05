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
        Schema::create('email_verifications', function (Blueprint $table) {

    $table->id(); 
    // unique identifier

    $table->string('email')->index();
    // we index email for fast lookup (important for performance)

    $table->string('first_name');
    $table->string('last_name');
    // temporarily store user info BEFORE verification

    $table->string('password');
    // hashed password (NEVER plain text)

    $table->string('otp');
    // store generated OTP

    $table->timestamp('expires_at');
    // OTP expiration (security)

    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_verifications');
    }
};
