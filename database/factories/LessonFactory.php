<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Lesson;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lesson>
 */
class LessonFactory extends Factory
{
    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'organization_id' => fn (array $attributes) => Course::find($attributes['course_id'])->organization_id,
            'position' => fn (array $attributes) => Lesson::where('course_id', $attributes['course_id'])->max('position') + 1,
            'title' => rtrim(fake()->sentence(4), '.'),
            'content' => fake()->paragraphs(3, true),
        ];
    }
}
