<?php

namespace Database\Factories;

use App\Enums\TranscriptStatus;
use App\Enums\VideoLanguage;
use App\Enums\VideoStatus;
use App\Models\Lesson;
use App\Models\Video;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Video>
 */
class VideoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'lesson_id' => Lesson::factory(),
            'organization_id' => fn (array $attributes) => Lesson::find($attributes['lesson_id'])->organization_id,
            'status' => VideoStatus::Uploaded,
            'source_path' => 'videos/'.fake()->uuid().'/source.mp4',
            'size_bytes' => fake()->numberBetween(10_000_000, 500_000_000),
            'language' => VideoLanguage::French,
            'transcript_status' => TranscriptStatus::Pending,
        ];
    }

    public function ready(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => VideoStatus::Ready,
            'hls_path' => dirname($attributes['source_path']).'/hls/master.m3u8',
            'thumbnail_path' => dirname($attributes['source_path']).'/thumbnail.jpg',
            'duration_seconds' => fake()->numberBetween(60, 3600),
            'ready_at' => now(),
        ]);
    }
}
