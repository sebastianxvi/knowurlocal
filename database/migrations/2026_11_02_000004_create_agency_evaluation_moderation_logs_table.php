<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('agency_evaluation_moderation_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_evaluation_id')->nullable()->constrained('agency_evaluations')->nullOnDelete();
            $table->foreignId('admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor_label')->nullable();
            $table->string('action', 30);
            $table->text('reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['agency_evaluation_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agency_evaluation_moderation_logs');
    }
};
