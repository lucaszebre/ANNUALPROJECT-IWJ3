<?php

namespace Database\Factories;

use App\Enums\SubmissionStatus;
use App\Models\Exercise;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Submission>
 */
class SubmissionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'exercise_id' => Exercise::factory(),
            'organization_id' => fn (array $attributes) => Exercise::find($attributes['exercise_id'])->organization_id,
            'user_id' => User::factory(),
            'code' => "a, b = map(int, input().split())\nprint(a + b)\n",
            'status' => SubmissionStatus::Queued,
        ];
    }
}
