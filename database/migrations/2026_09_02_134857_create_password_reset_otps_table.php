<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the password reset OTP storage table.
     */
    public function up(): void
    {
        Schema::create('password_reset_otps', function (Blueprint $table) {

            $table->id();

            // Email address of the account requesting a password reset.
            $table->string('email')->index();

            // Hashed OTP. The plaintext OTP is never stored.
            $table->string('otp');

            // Determines when this OTP becomes invalid.
            $table->timestamp('expires_at');

            // Number of failed OTP verification attempts.
            $table->unsignedTinyInteger('attempts')->default(0);

            $table->timestamps();
        });
    }

    /**
     * Remove the password reset OTP storage table.
     */
    public function down(): void
    {
        Schema::dropIfExists('password_reset_otps');
    }
};