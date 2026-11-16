<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->timestamps();

            $table->unique(['organization_id', 'slug']);
        });

        Schema::create('courses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('owner_id')->constrained('users');
            $table->foreignUuid('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->string('status')->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'slug']);
        });

        Schema::create('lessons', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('course_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position');
            $table->string('title');
            $table->text('content')->nullable();
            $table->timestamps();

            // Checked at commit, so lessons can be reordered in one transaction.
            $table->unique(['course_id', 'position'])->deferrable()->initiallyImmediate(false);
        });

        Schema::create('lesson_prerequisites', function (Blueprint $table) {
            $table->foreignUuid('lesson_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('prerequisite_id')->constrained('lessons')->cascadeOnDelete();
            $table->timestamps();

            $table->primary(['lesson_id', 'prerequisite_id']);
        });

        DB::statement('alter table lesson_prerequisites add constraint lesson_prerequisites_not_self check (lesson_id <> prerequisite_id)');

        Schema::create('enrollments', function (Blueprint $table) {
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('course_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->primary(['user_id', 'course_id']);
        });

        Schema::create('lesson_completions', function (Blueprint $table) {
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('lesson_id')->constrained()->cascadeOnDelete();
            $table->string('source');
            $table->timestamps();

            $table->primary(['user_id', 'lesson_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_completions');
        Schema::dropIfExists('enrollments');
        Schema::dropIfExists('lesson_prerequisites');
        Schema::dropIfExists('lessons');
        Schema::dropIfExists('courses');
        Schema::dropIfExists('categories');
    }
};
