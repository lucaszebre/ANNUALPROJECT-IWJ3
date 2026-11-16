<?php

namespace Database\Factories;

use App\Models\ExerciseTest;
use App\Models\Submission;
use App\Models\SubmissionResult;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SubmissionResult>
 */
class SubmissionResultFactory extends Factory
{
    public function definition(): array
    {
        return [
            'submission_id' => Submission::factory(),
            'exercise_test_id' => fn (array $attributes) => ExerciseTest::factory()
                ->create(['exercise_id' => Submission::find($attributes['submission_id'])->exercise_id])
                ->id,
            'passed' => true,
            'actual_stdout' => "3\n",
            'duration_ms' => fake()->numberBetween(5, 500),
        ];
    }
}
