<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('user_notifications')) {
            return;
        }

        Schema::create('user_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // Keep the ticket ID without a foreign key so notification history
            // survives a permanent ticket deletion and can gracefully fall back.
            $table->unsignedBigInteger('support_request_id')->nullable()->index();
            $table->unsignedBigInteger('response_id')->nullable()->index();
            $table->string('type', 50)->index();
            $table->string('title', 180);
            $table->text('message');
            $table->json('data')->nullable();
            $table->string('dedupe_key', 191)->nullable()->unique();
            $table->timestampTz('read_at')->nullable();
            $table->timestampsTz();
            $table->index(['user_id', 'read_at', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_notifications');
    }
};
