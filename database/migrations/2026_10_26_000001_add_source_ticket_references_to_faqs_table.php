<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('faqs', function (Blueprint $table) {
            // Keep provenance even if a source ticket is later permanently removed.
            $table->unsignedBigInteger('source_support_request_id')->nullable()->index();
            $table->unsignedBigInteger('source_response_id')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('faqs', function (Blueprint $table) {
            $table->dropIndex(['source_support_request_id']);
            $table->dropIndex(['source_response_id']);
            $table->dropColumn(['source_support_request_id', 'source_response_id']);
        });
    }
};
