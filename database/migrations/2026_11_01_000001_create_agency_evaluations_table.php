<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agency_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained('agencies')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->text('comment')->nullable();
            $table->string('status', 20)->default('published')->index();
            $table->timestamps();

            $table->unique(['agency_id', 'user_id'], 'agency_evaluations_agency_user_unique');
            $table->index(['agency_id', 'status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agency_evaluations');
    }
};
