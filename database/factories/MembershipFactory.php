<?php

namespace Database\Factories;

use App\Enums\MemberRole;
use App\Models\Membership;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Membership>
 */
class MembershipFactory extends Factory
{
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'user_id' => User::factory(),
            'role' => MemberRole::Student,
        ];
    }

    public function teacher(): static
    {
        return $this->state(['role' => MemberRole::Teacher]);
    }

    public function admin(): static
    {
        return $this->state(['role' => MemberRole::Admin]);
    }
}
