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
    /**
     * Last position handed out per course. count(n)->create() makes every
     * lesson before saving any, so the database maximum alone would give them
     * all the same position.
     *
     * @var array<string, int>
     */
    private static array $lastPositions = [];

    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'organization_id' => fn (array $attributes) => Course::find($attributes['course_id'])->organization_id,
            'position' => fn (array $attributes) => $this->nextPosition($attributes['course_id']),
            'title' => rtrim(fake()->sentence(4), '.'),
            'content' => fake()->paragraphs(3, true),
        ];
    }

    private function nextPosition(string $courseId): int
    {
        $saved = (int) Lesson::where('course_id', $courseId)->max('position');

        return self::$lastPositions[$courseId] = max($saved, self::$lastPositions[$courseId] ?? 0) + 1;
    }
}
