<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Make the core agency fields mandatory and protect
     * their foreign-key relationships from accidental
     * cascading or NULL assignment.
     */
    public function up(): void
    {
        Schema::table('agencies', function (Blueprint $table) {

            /*
             * The existing category foreign key uses
             * ON DELETE SET NULL.
             *
             * That is incompatible with a NOT NULL category_id,
             * so the foreign key must be removed first.
             */
            $table->dropForeign(['category_id']);

            /*
             * The existing agency type foreign key uses
             * ON DELETE CASCADE.
             *
             * We do not want deleting an agency type to
             * automatically delete agencies.
             */
            $table->dropForeign(['agency_type_id']);
        });


        Schema::table('agencies', function (Blueprint $table) {

            /*
             * Core agency identity.
             */
            $table->string('agency_name')
                ->nullable(false)
                ->change();

            $table->string('agency_abbreviation')
                ->nullable(false)
                ->change();


            /*
             * Agency classification.
             *
             * These are now mandatory relationships.
             */
            $table->foreignId('agency_type_id')
                ->nullable(false)
                ->change();

            $table->foreignId('category_id')
                ->nullable(false)
                ->change();


            /*
             * Agency location and primary contact information.
             */
            $table->string('agency_location')
                ->nullable(false)
                ->change();

            $table->string('agency_hotline', 30)
                ->nullable(false)
                ->change();

            $table->string('agency_email')
                ->nullable(false)
                ->change();
        });


        Schema::table('agencies', function (Blueprint $table) {

            /*
             * Recreate agency_type_id relationship.
             *
             * RESTRICT prevents an agency type from being
             * deleted while agencies still reference it.
             */
            $table->foreign('agency_type_id')
                ->references('id')
                ->on('agency_types')
                ->restrictOnDelete();


            /*
             * Recreate category relationship.
             *
             * RESTRICT prevents a category from being deleted
             * while agencies still reference it.
             */
            $table->foreign('category_id')
                ->references('id')
                ->on('categories')
                ->restrictOnDelete();
        });
    }


    /**
     * Restore the previous schema behavior.
     */
    public function down(): void
    {
        Schema::table('agencies', function (Blueprint $table) {

            /*
             * Remove the restrictive foreign keys first.
             */
            $table->dropForeign(['agency_type_id']);
            $table->dropForeign(['category_id']);
        });


        Schema::table('agencies', function (Blueprint $table) {

            /*
             * Restore the original nullable state.
             */
            $table->string('agency_name')
                ->nullable()
                ->change();

            $table->string('agency_abbreviation')
                ->nullable()
                ->change();

            $table->foreignId('agency_type_id')
                ->nullable()
                ->change();

            $table->foreignId('category_id')
                ->nullable()
                ->change();

            $table->string('agency_location')
                ->nullable()
                ->change();

            $table->string('agency_hotline', 30)
                ->nullable()
                ->change();

            $table->string('agency_email')
                ->nullable()
                ->change();
        });


        Schema::table('agencies', function (Blueprint $table) {

            /*
             * Restore the original agency type behavior.
             */
            $table->foreign('agency_type_id')
                ->references('id')
                ->on('agency_types')
                ->cascadeOnDelete();


            /*
             * Restore the original category behavior.
             */
            $table->foreign('category_id')
                ->references('id')
                ->on('categories')
                ->nullOnDelete();
        });
    }
};