<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create structured components for official
     * support ticket responses.
     */
    public function up(): void
    {
        Schema::create('support_response_components', function (Blueprint $table) {
            $table->id();

            /*
             * Each component belongs to one response attempt.
             */
            $table->foreignId('support_request_response_id')
                ->constrained('support_request_responses')
                ->cascadeOnDelete();

            /*
             * Determines how the component should be
             * rendered to the citizen.
             *
             * Examples:
             * text
             * image
             * file
             * link
             * qr_code
             */
            $table->string('type', 30);

            /*
             * Stores the component's actual value.
             *
             * For text:
             *   the response text
             *
             * For image/file:
             *   the stored file path
             *
             * For link:
             *   the URL
             *
             * For QR:
             *   the destination URL
             */
            $table->text('content');

            /*
             * Optional human-readable label.
             *
             * Useful for links/files and accessibility.
             */
            $table->string('label', 255)
                ->nullable();

            /*
             * Determines the order in which components
             * appear in the official response.
             */
            $table->unsignedInteger('sort_order')
                ->default(0);

            $table->timestamps();

            /*
             * Efficiently retrieves components in their
             * intended display order.
             */
            $table->index(
    ['support_request_response_id', 'sort_order'],
    'src_components_response_sort_idx'
);

            /*
             * Allows efficient filtering by component type.
             */
            $table->index('type');
        });
    }

    /**
     * Remove the response component table.
     */
    public function down(): void
    {
        Schema::dropIfExists('support_response_components');
    }
};