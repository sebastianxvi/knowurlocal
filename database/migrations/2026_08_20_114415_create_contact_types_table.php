<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the contact types table.
     */
    public function up(): void
    {
        Schema::create('contact_types', function (Blueprint $table) {

            /*
             * Primary key.
             */
            $table->id();


            /*
             * Human-readable contact type.
             *
             * Examples:
             * Hotline
             * Landline
             * Email
             * Website
             * Facebook
             */
            $table->string('name');


            /*
             * Machine-readable identifier.
             *
             * Examples:
             * hotline
             * landline
             * email
             * website
             * facebook
             *
             * UNIQUE prevents two contact types from having
             * the same identifier.
             */
            $table->string('slug')->unique();


            /*
             * Allows a contact type to be temporarily disabled
             * without deleting it from the database.
             *
             * This is safer for historical agency records.
             */
            $table->boolean('is_active')->default(true);


            /*
             * Determines the order in which contact types
             * appear in the admin form and public interface.
             */
            $table->unsignedInteger('sort_order')->default(0);


            /*
             * created_at and updated_at.
             */
            $table->timestamps();
        });
    }


    /**
     * Remove the contact types table.
     */
    public function down(): void
    {
        Schema::dropIfExists('contact_types');
    }
};