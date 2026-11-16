<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quizzes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('lesson_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('pass_threshold_percent')->default(70);
            $table->timestamps();
        });

        Schema::create('quiz_questions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('quiz_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position');
            $table->text('prompt');
            $table->boolean('multiple')->default(false);
            $table->timestamps();

            $table->unique(['quiz_id', 'position'])->deferrable()->initiallyImmediate(false);
        });

        Schema::create('quiz_choices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('quiz_question_id')->constrained()->cascadeOnDelete();
            $table->string('label');
            $table->boolean('is_correct')->default(false);
            $table->timestamps();
        });

        Schema::create('quiz_attempts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('quiz_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('score_percent');
            $table->boolean('passed');
            $table->timestamps();
        });

        Schema::create('quiz_attempt_choice', function (Blueprint $table) {
            $table->foreignUuid('quiz_attempt_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('quiz_choice_id')->constrained()->cascadeOnDelete();

            $table->primary(['quiz_attempt_id', 'quiz_choice_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_attempt_choice');
        Schema::dropIfExists('quiz_attempts');
        Schema::dropIfExists('quiz_choices');
        Schema::dropIfExists('quiz_questions');
        Schema::dropIfExists('quizzes');
    }
};
