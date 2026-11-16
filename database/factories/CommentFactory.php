<?php

namespace Database\Factories;

use App\Enums\CommentStatus;
use App\Models\Comment;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Comment>
 */
class CommentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'lesson_id' => Lesson::factory(),
            'organization_id' => fn (array $attributes) => Lesson::find($attributes['lesson_id'])->organization_id,
            'user_id' => User::factory(),
            'body' => fake()->paragraph(),
            'status' => CommentStatus::Visible,
        ];
    }

    public function replyTo(Comment $parent): static
    {
        return $this->state([
            'lesson_id' => $parent->lesson_id,
            'parent_id' => $parent->id,
        ]);
    }
}
