<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
       Schema::create('agencies', function (Blueprint $table) {
            $table->id();

            $table->string('agency_name');
            $table->string('agency_location');

            $table->text('agency_description')->nullable(); // better than string

            // Contact info (store as strings)
            $table->string('agency_landline', 30)->nullable();
            $table->string('agency_hotline', 30);
            $table->string('agency_email')->nullable();

            // Links
            $table->string('agency_website')->nullable();
            $table->string('agency_fb')->nullable();

            $table->text('office_hours');

            // Coordinates
            $table->decimal('lat', 10, 6)->nullable();
            $table->decimal('lng', 10, 6)->nullable();

            // For uploaded image path/filename
            $table->string('profile_pic')->nullable();

            //for softdeletion of data hehehehhe
            $table->softDeletes();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agencies');
    }
};
