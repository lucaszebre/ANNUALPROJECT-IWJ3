<?php

namespace Database\Factories;

use App\Models\Quiz;
use App\Models\QuizQuestion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuizQuestion>
 */
class QuizQuestionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'quiz_id' => Quiz::factory(),
            'position' => fn (array $attributes) => QuizQuestion::where('quiz_id', $attributes['quiz_id'])->max('position') + 1,
            'prompt' => rtrim(fake()->sentence(), '.').'?',
            'multiple' => false,
        ];
    }
}
