<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('agency_evaluation_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_evaluation_id')->unique()->constrained('agency_evaluations')->cascadeOnDelete();
            $table->foreignId('responder_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('responder_role', 30);
            $table->text('body');
            $table->timestamps();
            $table->index(['responder_role', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agency_evaluation_responses');
    }
};
