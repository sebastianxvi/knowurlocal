<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the official response records associated
     * with support tickets.
     */
    public function up(): void
    {
        Schema::create('support_request_responses', function (Blueprint $table) {
            $table->id();

            /*
             * Every response belongs to exactly one ticket.
             *
             * cascadeOnDelete() ensures orphaned response
             * records cannot remain after a ticket is deleted.
             */
            $table->foreignId('support_request_id')
                ->constrained('support_requests')
                ->cascadeOnDelete();

            /*
             * The administrator who prepared the response.
             *
             * Nullable protects historical responses if an
             * administrator account is later removed.
             */
            $table->foreignId('admin_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            /*
             * Indicates the lifecycle of this particular
             * response attempt.
             *
             * draft       = still being prepared
             * forwarded   = sent to the citizen
             * rejected    = citizen said it did not resolve
             * accepted    = citizen confirmed resolution
             */
            $table->string('status', 20)
                ->default('draft');

            /*
             * Records when the response was forwarded to
             * the requesting citizen.
             */
            $table->timestamp('forwarded_at')
                ->nullable();

            /*
             * Records when the citizen accepted or rejected
             * this response.
             */
            $table->timestamp('responded_at')
    ->nullable();

    $table->text('follow_up_reason')
    ->nullable();

            $table->timestamps();

            /*
             * Makes retrieving responses for a ticket efficient.
             */
            $table->index([
                'support_request_id',
                'created_at',
            ]);

            /*
             * Makes response lifecycle filtering efficient.
             */
            $table->index('status');
        });
    }

    /**
     * Remove the response table.
     */
    public function down(): void
    {
        Schema::dropIfExists('support_request_responses');
    }
};