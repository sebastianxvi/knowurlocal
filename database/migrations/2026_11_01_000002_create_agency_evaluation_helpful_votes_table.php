<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agency_evaluation_helpful_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_evaluation_id')->constrained('agency_evaluations')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['agency_evaluation_id', 'user_id'], 'agency_eval_helpful_user_unique');
            $table->index(['agency_evaluation_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agency_evaluation_helpful_votes');
    }
};
