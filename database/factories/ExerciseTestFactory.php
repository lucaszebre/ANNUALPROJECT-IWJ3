<?php

namespace Database\Factories;

use App\Models\Exercise;
use App\Models\ExerciseTest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExerciseTest>
 */
class ExerciseTestFactory extends Factory
{
    public function definition(): array
    {
        $a = fake()->numberBetween(-100, 100);
        $b = fake()->numberBetween(-100, 100);

        return [
            'exercise_id' => Exercise::factory(),
            'position' => fn (array $attributes) => ExerciseTest::where('exercise_id', $attributes['exercise_id'])->max('position') + 1,
            'name' => "{$a} + {$b}",
            'stdin' => "{$a} {$b}\n",
            'expected_stdout' => ($a + $b)."\n",
            'is_hidden' => false,
        ];
    }

    public function hidden(): static
    {
        return $this->state(['is_hidden' => true]);
    }
}
