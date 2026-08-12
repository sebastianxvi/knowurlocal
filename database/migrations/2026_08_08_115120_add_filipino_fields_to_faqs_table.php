<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('faqs', function (Blueprint $table) {

            // Filipino / Taglish version of the question.
            // Nullable because the English version is the required source.
            $table->string('question_fil')->nullable()->after('question');

            // Filipino / Taglish version of the answer.
            // Nullable because the admin may choose to translate it later.
            $table->text('answer_fil')->nullable()->after('answer');
        });
    }

    public function down(): void
    {
        Schema::table('faqs', function (Blueprint $table) {

            // Remove the Filipino fields if this migration is rolled back.
            $table->dropColumn([
                'question_fil',
                'answer_fil',
            ]);
        });
    }
};