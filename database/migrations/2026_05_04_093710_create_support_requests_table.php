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
    Schema::create('support_requests', function (Blueprint $table) {
        $table->id();

        // 🔗 relationships (safe & scalable)
        $table->foreignId('user_id')
              ->nullable()
              ->constrained()
              ->nullOnDelete();

        $table->foreignId('agency_id')
              ->nullable()
              ->constrained()
              ->nullOnDelete();

        // 🧾 main content
        $table->text('question');
        $table->text('answer')->nullable();

        // 📊 workflow
        $table->enum('status', ['pending', 'answered'])
              ->default('pending');

        // 🔒 security / tracking
        $table->string('ip_address', 45)->nullable(); // supports IPv6

        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('support_requests');
    }
};
