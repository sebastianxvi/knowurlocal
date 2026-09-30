<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('faq_feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chatbot_log_id')->constrained('chatbot_logs')->cascadeOnDelete();
            $table->foreignId('faq_id')->nullable()->constrained('faqs')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('rating', 20); // helpful | not_helpful
            $table->string('reason', 40)->nullable();
            $table->text('comment')->nullable();
            $table->timestamps();

            // One rating per authenticated user for each exact chatbot response.
            $table->unique(['chatbot_log_id', 'user_id'], 'faq_feedback_log_user_unique');
            $table->index(['faq_id', 'rating'], 'faq_feedback_faq_rating_index');
            $table->index(['created_at'], 'faq_feedback_created_at_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('faq_feedback');
    }
};
