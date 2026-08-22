<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the agencies table.
     */
    public function up(): void
    {
        Schema::create('agencies', function (Blueprint $table) {

            /*
             * Primary key.
             *
             * Laravel automatically creates an unsigned BIGINT
             * column named "id" and makes it the primary key.
             */
            $table->id();


            /*
             * =====================================================
             * AGENCY IDENTITY
             * =====================================================
             */

            /*
             * Official agency / organization name.
             *
             * Required because an agency record is not useful
             * without identifying the organization.
             */
            $table->string('agency_name');


            /*
             * Short agency identifier.
             *
             * Example:
             * DOLE
             * DSWD
             * DENR
             *
             * Required because this is part of the agency's
             * core identification information in KNOWURLOCAL.
             */
            $table->string('agency_abbreviation');


            /*
             * =====================================================
             * AGENCY CLASSIFICATION
             * =====================================================
             */

            /*
             * Identifies whether the organization is an NGA,
             * NGO, etc.
             *
             * The referenced agency_types record must exist.
             *
             * restrictOnDelete() prevents an agency type from
             * being deleted while agencies are still using it.
             */
            $table->foreignId('agency_type_id')
                ->constrained('agency_types')
                ->restrictOnDelete();


            /*
             * Identifies the agency's category.
             *
             * This is also required.
             *
             * restrictOnDelete() prevents a category from being
             * deleted while agencies are still assigned to it.
             */
            $table->foreignId('category_id')
                ->constrained('categories')
                ->restrictOnDelete();


            /*
             * =====================================================
             * AGENCY INFORMATION
             * =====================================================
             */

            /*
             * Physical office location.
             *
             * Required because location is one of the primary
             * pieces of information KNOWURLOCAL provides.
             */
            $table->string('agency_location');


            /*
             * General description of the agency.
             *
             * Optional because some organizations may not yet
             * have a verified description.
             */
            $table->text('agency_description')->nullable();


            /*
             * Services provided by the agency.
             *
             * Optional because service information may not
             * always be available.
             */
            $table->text('services_offered')->nullable();


            /*
             * Office operating hours.
             *
             * Optional because some organizations may not
             * provide verified office-hour information.
             */
            $table->text('office_hours')->nullable();


            /*
             * =====================================================
             * MAP COORDINATES
             * =====================================================
             */

            /*
             * Latitude used by the Leaflet map.
             *
             * Nullable because an agency may initially be added
             * before its exact coordinates are verified.
             */
            $table->decimal('lat', 10, 6)->nullable();


            /*
             * Longitude used by the Leaflet map.
             */
            $table->decimal('lng', 10, 6)->nullable();


            /*
             * =====================================================
             * AGENCY IMAGE
             * =====================================================
             */

            /*
             * Stores the path / filename of the uploaded agency
             * image rather than storing the actual image binary
             * inside MySQL.
             */
            $table->string('agency_image')->nullable();


            /*
             * =====================================================
             * SOFT DELETION
             * =====================================================
             */

            /*
             * Adds "deleted_at".
             *
             * This allows agencies to be moved to Trash without
             * immediately destroying their database record.
             */
            $table->softDeletes();


            /*
             * created_at and updated_at.
             */
            $table->timestamps();
        });
    }


    /**
     * Remove the agencies table.
     */
    public function down(): void
    {
        /*
         * Drop the complete table when this migration is
         * rolled back.
         */
        Schema::dropIfExists('agencies');
    }
};