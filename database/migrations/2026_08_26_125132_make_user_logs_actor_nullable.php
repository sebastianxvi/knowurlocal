<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Change the UserLog actor relationship so audit records
     * survive permanent deletion of the actor's account.
     */
    public function up(): void
    {
        Schema::table('user_logs', function (Blueprint $table) {

            /*
             * Remove the existing foreign key first.
             *
             * The original relationship uses cascadeOnDelete(),
             * which would delete audit records when the actor
             * account is permanently deleted.
             */
            $table->dropForeign(['user_id']);

            /*
             * Allow user_id to become NULL.
             *
             * This is necessary because the actor's account may
             * no longer exist after permanent deletion.
             */
            $table->unsignedBigInteger('user_id')
                ->nullable()
                ->change();

            /*
             * Recreate the relationship using SET NULL instead
             * of CASCADE.
             *
             * The audit record survives while the actor reference
             * is safely cleared.
             */
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    /**
     * Restore the original cascade behavior if this migration
     * is rolled back.
     */
    public function down(): void
    {
        Schema::table('user_logs', function (Blueprint $table) {

            /*
             * Remove the new SET NULL foreign key.
             */
            $table->dropForeign(['user_id']);

            /*
             * Restore the original non-nullable column.
             */
            $table->unsignedBigInteger('user_id')
                ->nullable(false)
                ->change();

            /*
             * Restore the original cascade behavior.
             */
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });
    }
};