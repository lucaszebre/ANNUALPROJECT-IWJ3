<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Text search configurations, one per transcript language.
     * They remove accents before stemming, so "recursive" also finds "récursive".
     *
     * @var array<string, string>
     */
    private array $searchConfigs = [
        'fr_unaccent' => 'french',
        'en_unaccent' => 'english',
    ];

    public function up(): void
    {
        DB::statement('create extension if not exists unaccent');

        foreach ($this->searchConfigs as $config => $language) {
            // migrate:fresh drops tables but not configurations, so recreate them.
            DB::statement("drop text search configuration if exists {$config}");
            DB::statement("create text search configuration {$config} (copy = {$language})");
            DB::statement("alter text search configuration {$config} alter mapping for hword, hword_part, word with unaccent, {$language}_stem");
        }

        Schema::create('videos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('lesson_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('status')->default('uploaded');
            $table->string('source_path');
            $table->string('hls_path')->nullable();
            $table->string('thumbnail_path')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->string('language', 2)->default('fr');
            $table->string('transcript_status')->default('pending');
            $table->timestamp('ready_at')->nullable();
            $table->timestamps();
        });

        Schema::create('transcript_segments', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('video_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('start_ms');
            $table->unsignedInteger('end_ms');
            $table->text('text');
            $table->timestamps();

            $table->index(['video_id', 'start_ms']);
        });

        // The schema builder has no regconfig type, so these columns are added in SQL.
        DB::statement('alter table transcript_segments add column search_config regconfig not null');
        DB::statement('alter table transcript_segments add column search_vector tsvector generated always as (to_tsvector(search_config, text)) stored');
        DB::statement('create index transcript_segments_search_vector_index on transcript_segments using gin (search_vector)');
    }

    public function down(): void
    {
        Schema::dropIfExists('transcript_segments');
        Schema::dropIfExists('videos');

        foreach (array_keys($this->searchConfigs) as $config) {
            DB::statement("drop text search configuration if exists {$config}");
        }
    }
};
