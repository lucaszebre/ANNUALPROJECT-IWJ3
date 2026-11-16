<?php

namespace Database\Factories;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuizAttempt>
 */
class QuizAttemptFactory extends Factory
{
    public function definition(): array
    {
        $score = fake()->numberBetween(0, 100);

        return [
            'quiz_id' => Quiz::factory(),
            'organization_id' => fn (array $attributes) => Quiz::find($attributes['quiz_id'])->organization_id,
            'user_id' => User::factory(),
            'score_percent' => $score,
            'passed' => $score >= 70,
        ];
    }
}
