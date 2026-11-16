<?php

namespace Database\Factories;

use App\Models\TranscriptSegment;
use App\Models\Video;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TranscriptSegment>
 */
class TranscriptSegmentFactory extends Factory
{
    public function definition(): array
    {
        $start = fake()->numberBetween(0, 3_000_000);

        return [
            'video_id' => Video::factory(),
            'organization_id' => fn (array $attributes) => Video::find($attributes['video_id'])->organization_id,
            'start_ms' => $start,
            'end_ms' => $start + fake()->numberBetween(1_000, 8_000),
            'text' => fake()->sentence(12),
            'search_config' => fn (array $attributes) => Video::find($attributes['video_id'])->language->searchConfig(),
        ];
    }
}
