<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::create('chatbot_logs', function (Blueprint $table) {
        $table->id();

        // 🔐 USER (nullable because guest users can use chatbot)
        $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

        // 🧠 QUESTION & ANSWER
        $table->text('question');
        $table->text('answer');

        // 🏢 AGENCY CONTEXT (nullable for global chatbot)
        $table->foreignId('agency_id')->nullable()->constrained()->nullOnDelete();

        // 📊 TYPE (faq, fallback, options, irrelevant, etc.)
        $table->string('type')->nullable();

        // 🎯 MATCH SCORE (you added this already 👌)
        $table->integer('score')->nullable();

        // 🌐 SECURITY / TRACKING
        $table->ipAddress('ip_address')->nullable();

        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chatbot_logs');
    }
};
