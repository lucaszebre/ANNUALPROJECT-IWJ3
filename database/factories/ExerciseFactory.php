<?php

namespace Database\Factories;

use App\Enums\CodeLanguage;
use App\Models\Exercise;
use App\Models\Lesson;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Exercise>
 */
class ExerciseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'lesson_id' => Lesson::factory(),
            'organization_id' => fn (array $attributes) => Lesson::find($attributes['lesson_id'])->organization_id,
            'language' => CodeLanguage::Python,
            'statement' => 'Read two integers on one line and print their sum.',
            'starter_code' => "a, b = map(int, input().split())\n",
            'solution_code' => "a, b = map(int, input().split())\nprint(a + b)\n",
        ];
    }
}
