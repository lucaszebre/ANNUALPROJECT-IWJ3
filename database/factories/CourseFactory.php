<?php

namespace Database\Factories;

use App\Enums\CourseStatus;
use App\Models\Course;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Course>
 */
class CourseFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->unique()->sentence(3);

        return [
            'organization_id' => Organization::factory(),
            'owner_id' => User::factory(),
            'category_id' => null,
            'title' => rtrim($title, '.'),
            'slug' => Str::slug($title),
            'description' => fake()->paragraph(),
            'status' => CourseStatus::Draft,
        ];
    }

    public function published(): static
    {
        return $this->state([
            'status' => CourseStatus::Published,
            'published_at' => now(),
        ]);
    }
}
