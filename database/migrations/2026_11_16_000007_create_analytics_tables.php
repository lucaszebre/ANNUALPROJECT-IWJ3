<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The largest table: bigint ids, and old rows are pruned on a schedule.
        Schema::create('playback_events', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('video_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->uuid('playback_session');
            $table->string('event');
            $table->unsignedInteger('position_seconds');
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['video_id', 'occurred_at']);
        });

        // Drop-off curve, computed from playback_events by a scheduled job.
        Schema::create('video_retention_stats', function (Blueprint $table) {
            $table->foreignUuid('video_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('bucket_start_seconds');
            $table->unsignedInteger('viewers');
            $table->timestamps();

            $table->primary(['video_id', 'bucket_start_seconds']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('video_retention_stats');
        Schema::dropIfExists('playback_events');
    }
};
