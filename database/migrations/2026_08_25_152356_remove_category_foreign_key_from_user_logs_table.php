<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Remove the category foreign-key constraint.
     *
     * The category_id column remains in user_logs because
     * audit records still need to remember which category
     * was affected, even after that category is permanently
     * deleted.
     */
    public function up(): void
    {
        Schema::table('user_logs', function (Blueprint $table) {

            /*
             * Remove the database relationship that currently
             * sets category_id to NULL when a category is
             * permanently deleted.
             */
            $table->dropForeign(['category_id']);
        });
    }

    /**
     * Restore the foreign-key constraint if this migration
     * is rolled back.
     */
    public function down(): void
    {
        Schema::table('user_logs', function (Blueprint $table) {

            /*
             * Recreate the relationship that existed before
             * this migration.
             *
             * nullOnDelete() is intentionally restored here
             * because this method reverses our change.
             */
            $table->foreign('category_id')
                ->references('id')
                ->on('categories')
                ->nullOnDelete();
        });
    }
};