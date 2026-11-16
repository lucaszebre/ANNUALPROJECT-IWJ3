<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exercises', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('lesson_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('language');
            $table->text('statement');
            $table->text('starter_code')->nullable();
            $table->text('solution_code')->nullable();
            $table->unsignedInteger('time_limit_ms')->default(2000);
            $table->unsignedInteger('memory_limit_mb')->default(128);
            $table->timestamps();
        });

        // Input/output tests: the sandbox sends stdin and compares stdout.
        Schema::create('exercise_tests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('exercise_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position');
            $table->string('name');
            $table->text('stdin')->nullable();
            $table->text('expected_stdout');
            $table->boolean('is_hidden')->default(false);
            $table->timestamps();

            $table->unique(['exercise_id', 'position'])->deferrable()->initiallyImmediate(false);
        });

        Schema::create('submissions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('exercise_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->text('code');
            $table->string('status')->default('queued');
            $table->unsignedInteger('tests_passed')->default(0);
            $table->unsignedInteger('tests_total')->default(0);
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            // Monthly code execution quota of the organisation.
            $table->index(['organization_id', 'created_at']);
            $table->index(['exercise_id', 'user_id']);
        });

        Schema::create('submission_results', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('submission_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('exercise_test_id')->constrained()->cascadeOnDelete();
            $table->boolean('passed');
            $table->text('actual_stdout')->nullable();
            $table->text('stderr')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->boolean('timed_out')->default(false);
            $table->timestamps();

            $table->unique(['submission_id', 'exercise_test_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('submission_results');
        Schema::dropIfExists('submissions');
        Schema::dropIfExists('exercise_tests');
        Schema::dropIfExists('exercises');
    }
};
