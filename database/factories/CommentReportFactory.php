<?php

namespace Database\Factories;

use App\Models\Comment;
use App\Models\CommentReport;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CommentReport>
 */
class CommentReportFactory extends Factory
{
    public function definition(): array
    {
        return [
            'comment_id' => Comment::factory(),
            'organization_id' => fn (array $attributes) => Comment::find($attributes['comment_id'])->organization_id,
            'user_id' => User::factory(),
            'reason' => fake()->sentence(),
        ];
    }
}
