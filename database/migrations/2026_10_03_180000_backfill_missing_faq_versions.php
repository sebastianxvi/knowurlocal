<?php

use App\Models\Faq;
use App\Models\FaqVersion;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Repair FAQs created after the versioning migration (for example by a
     * seeder) that do not yet have a published response version.
     */
    public function up(): void
    {
        Faq::withTrashed()
            ->whereNull('current_version_id')
            ->orderBy('id')
            ->each(function (Faq $faq): void {
                $latest = FaqVersion::query()
                    ->where('faq_id', $faq->id)
                    ->orderByDesc('version_number')
                    ->first();

                if ($latest) {
                    $faq->forceFill([
                        'current_version_id' => $latest->id,
                    ])->save();

                    return;
                }

                $version = FaqVersion::create([
                    'faq_id' => $faq->id,
                    'version_number' => 1,
                    'agency_id' => $faq->agency_id,
                    'question' => $faq->question,
                    'answer' => $faq->answer,
                    'question_fil' => $faq->question_fil,
                    'answer_fil' => $faq->answer_fil,
                    'keywords' => $faq->keywords,
                    'image' => $faq->image,
                    'response_components' => $faq->response_components ?? [],
                    'changed_by' => null,
                    'superseded_at' => null,
                ]);

                $faq->forceFill([
                    'current_version_id' => $version->id,
                ])->save();
            });
    }

    /**
     * Intentionally non-destructive. FAQ response history and feedback links
     * must not be destroyed by a migration rollback.
     */
    public function down(): void
    {
        // No-op by design.
    }
};
