<?php

namespace Database\Factories;

use App\Enums\PlaybackEventType;
use App\Models\PlaybackEvent;
use App\Models\User;
use App\Models\Video;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PlaybackEvent>
 */
class PlaybackEventFactory extends Factory
{
    public function definition(): array
    {
        return [
            'video_id' => Video::factory()->ready(),
            'organization_id' => fn (array $attributes) => Video::find($attributes['video_id'])->organization_id,
            'user_id' => User::factory(),
            'playback_session' => fake()->uuid(),
            'event' => PlaybackEventType::Heartbeat,
            'position_seconds' => fake()->numberBetween(0, 600),
            'occurred_at' => now(),
        ];
    }
}
