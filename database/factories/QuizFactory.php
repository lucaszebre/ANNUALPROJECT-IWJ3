<?php

namespace Database\Factories;

use App\Models\Lesson;
use App\Models\Quiz;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Quiz>
 */
class QuizFactory extends Factory
{
    public function definition(): array
    {
        return [
            'lesson_id' => Lesson::factory(),
            'organization_id' => fn (array $attributes) => Lesson::find($attributes['lesson_id'])->organization_id,
            'pass_threshold_percent' => 70,
        ];
    }
}
