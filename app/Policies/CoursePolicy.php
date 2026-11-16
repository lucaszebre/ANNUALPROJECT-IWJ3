<?php

namespace App\Policies;

use App\Enums\MemberRole;
use App\Models\Course;
use App\Models\User;

class CoursePolicy
{
    /**
     * Teachers only edit their own courses; an admin edits every course of their organisation.
     */
    public function update(User $user, Course $course): bool
    {
        return $course->owner_id === $user->id
            || $user->roleIn($course->organization_id) === MemberRole::Admin;
    }
}
