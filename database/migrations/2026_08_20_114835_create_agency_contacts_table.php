<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the agency contacts table.
     */
    public function up(): void
    {
        Schema::create('agency_contacts', function (Blueprint $table) {

            /*
             * Primary key for the individual contact record.
             */
            $table->id();


            /*
             * =====================================================
             * AGENCY RELATIONSHIP
             * =====================================================
             *
             * Determines which agency owns this contact.
             *
             * Example:
             *
             * agency_id = 5
             *
             * means this contact belongs to agency #5.
             */
            $table->foreignId('agency_id')
                ->constrained('agencies')
                ->cascadeOnDelete();


            /*
             * =====================================================
             * CONTACT TYPE RELATIONSHIP
             * =====================================================
             *
             * Determines what kind of contact this is.
             *
             * Example:
             *
             * contact_type_id = 1
             *
             * could represent "Hotline".
             */
            $table->foreignId('contact_type_id')
                ->constrained('contact_types')
                ->restrictOnDelete();


            /*
             * =====================================================
             * CONTACT LABEL
             * =====================================================
             *
             * Optional descriptive name for the contact.
             *
             * Examples:
             *
             * Main Hotline
             * Emergency Hotline
             * General Inquiries
             * Records Office
             */
            $table->string('label')->nullable();


            /*
             * =====================================================
             * CONTACT VALUE
             * =====================================================
             *
             * Stores the actual contact information.
             *
             * Examples:
             *
             * 099995942030
             * agency@gmail.com
             * https://example.com
             */
            $table->string('value', 500);


            /*
             * =====================================================
             * PRIMARY CONTACT
             * =====================================================
             *
             * Determines whether this is the primary contact
             * for its contact type.
             *
             * Example:
             *
             * An agency may have:
             *
             * Main Hotline       → primary
             * Alternate Hotline  → not primary
             */
            $table->boolean('is_primary')->default(false);


            /*
             * =====================================================
             * DISPLAY ORDER
             * =====================================================
             *
             * Determines the order in which contacts appear.
             *
             * This allows the administrator to control which
             * contact appears first without relying on database
             * insertion order.
             */
            $table->unsignedInteger('sort_order')->default(0);


            /*
             * created_at and updated_at.
             */
            $table->timestamps();
        });
    }


    /**
     * Remove the agency contacts table.
     */
    public function down(): void
    {
        Schema::dropIfExists('agency_contacts');
    }
};