<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Update support requests for the ticket-based
     * response and confirmation workflow.
     */
    public function up(): void
    {
        Schema::table('support_requests', function (Blueprint $table) {

            /*
             * Change the status column from a restrictive
             * database enum to a normal string.
             *
             * The application will control which status
             * values are valid.
             *
             * This allows the workflow to evolve without
             * requiring another database enum modification.
             */
            $table->string('status', 40)
                ->default('pending')
                ->change();
        });
    }

    /**
     * Restore the original support request structure.
     */
    public function down(): void
    {
        Schema::table('support_requests', function (Blueprint $table) {

            /*
             * Restore the original database enum.
             *
             * This assumes all records have been converted
             * back to either pending or answered before rollback.
             */
            $table->enum('status', ['pending', 'answered'])
                ->default('pending')
                ->change();
        });
    }
};