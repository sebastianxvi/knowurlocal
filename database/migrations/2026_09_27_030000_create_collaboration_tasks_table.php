<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('collaboration_tasks', function (Blueprint $table) {
            $table->id();

            $table->foreignId('created_by_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->foreignId('assigned_to_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->string('task_type', 32);
            $table->string('status', 24)->default('open');

            // Polymorphic-style target without a database FK so one task can
            // safely reference agencies, FAQs, categories, support requests,
            // or administrator/user records.
            $table->string('target_type', 80)->nullable();
            $table->unsignedBigInteger('target_id')->nullable();
            $table->string('target_label_snapshot', 255)->nullable();

            $table->string('title', 140);
            $table->text('description')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['assigned_to_id', 'status']);
            $table->index(['created_by_id', 'status']);
            $table->index(['target_type', 'target_id']);
            $table->index(['status', 'due_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collaboration_tasks');
    }
};
