<?php

use App\Models\Faq;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('faq_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('faq_id')
                ->constrained('faqs')
                ->cascadeOnDelete();
            $table->unsignedInteger('version_number');
            $table->foreignId('agency_id')
                ->nullable()
                ->constrained('agencies')
                ->nullOnDelete();
            $table->string('question');
            $table->text('answer');
            $table->string('question_fil')->nullable();
            $table->text('answer_fil')->nullable();
            $table->string('keywords')->nullable();
            $table->string('image')->nullable();
            $table->json('response_components')->nullable();
            $table->foreignId('changed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('superseded_at')->nullable();
            $table->timestamps();

            $table->unique(['faq_id', 'version_number'], 'faq_versions_faq_version_unique');
            $table->index(['faq_id', 'superseded_at'], 'faq_versions_faq_superseded_idx');
        });

        Schema::table('faqs', function (Blueprint $table) {
            $table->foreignId('current_version_id')
                ->nullable()
                ->after('response_components')
                ->constrained('faq_versions')
                ->nullOnDelete();
        });

        // Every existing FAQ gets an immutable Version 1 snapshot so the
        // feature is safe to enable on an installation that already has data.
        Faq::query()->orderBy('id')->each(function (Faq $faq): void {
            $versionId = DB::table('faq_versions')->insertGetId([
                'faq_id' => $faq->id,
                'version_number' => 1,
                'agency_id' => $faq->agency_id,
                'question' => $faq->question,
                'answer' => $faq->answer,
                'question_fil' => $faq->question_fil,
                'answer_fil' => $faq->answer_fil,
                'keywords' => $faq->keywords,
                'image' => $faq->image,
                'response_components' => $faq->response_components !== null
                    ? json_encode($faq->response_components)
                    : null,
                'changed_by' => null,
                'superseded_at' => null,
                'created_at' => $faq->created_at,
                'updated_at' => $faq->updated_at,
            ]);

            DB::table('faqs')
                ->where('id', $faq->id)
                ->update(['current_version_id' => $versionId]);
        });

        Schema::table('chatbot_logs', function (Blueprint $table) {
            $table->foreignId('faq_version_id')
                ->nullable()
                ->after('faq_id')
                ->constrained('faq_versions')
                ->nullOnDelete();
            $table->index(
                ['outcome', 'faq_version_id'],
                'chatbot_logs_outcome_faq_version_idx'
            );
        });

        Schema::table('faq_feedback', function (Blueprint $table) {
            $table->foreignId('faq_version_id')
                ->nullable()
                ->after('faq_id')
                ->constrained('faq_versions')
                ->cascadeOnDelete();
            $table->index(
                ['faq_version_id', 'rating'],
                'faq_feedback_version_rating_idx'
            );
        });

        // Existing feedback belongs to the exact chatbot response represented
        // by its log. If an older log has no version link, the FAQ's current
        // snapshot is the only historical state available at migration time.
        // Keep this backfill driver-neutral so the same migration works on
        // MySQL and PostgreSQL deployments.
        DB::table('faq_feedback')
            ->whereNull('faq_version_id')
            ->orderBy('id')
            ->get()
            ->each(function ($feedback): void {
                $versionId = DB::table('chatbot_logs')
                    ->where('id', $feedback->chatbot_log_id)
                    ->value('faq_version_id');

                if (!$versionId && $feedback->faq_id) {
                    $versionId = DB::table('faqs')
                        ->where('id', $feedback->faq_id)
                        ->value('current_version_id');
                }

                if ($versionId) {
                    DB::table('faq_feedback')
                        ->where('id', $feedback->id)
                        ->update(['faq_version_id' => $versionId]);
                }
            });

        // FAQ deletion must own its feedback history. The version FK already
        // cascades through faq_versions, while this FK also protects any
        // legacy feedback row that could not be associated with a version.
        Schema::table('faq_feedback', function (Blueprint $table) {
            $table->dropForeign(['faq_id']);
            $table->foreign('faq_id')
                ->references('id')
                ->on('faqs')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('faq_feedback', function (Blueprint $table) {
            $table->dropForeign(['faq_id']);
            $table->foreign('faq_id')
                ->references('id')
                ->on('faqs')
                ->nullOnDelete();
            $table->dropIndex('faq_feedback_version_rating_idx');
            $table->dropForeign(['faq_version_id']);
            $table->dropColumn('faq_version_id');
        });

        Schema::table('chatbot_logs', function (Blueprint $table) {
            $table->dropIndex('chatbot_logs_outcome_faq_version_idx');
            $table->dropForeign(['faq_version_id']);
            $table->dropColumn('faq_version_id');
        });

        Schema::table('faqs', function (Blueprint $table) {
            $table->dropForeign(['current_version_id']);
            $table->dropColumn('current_version_id');
        });

        Schema::dropIfExists('faq_versions');
    }
};
