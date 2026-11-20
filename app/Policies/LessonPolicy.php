<?php

namespace App\Policies;

use App\Enums\CourseStatus;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\User;

class LessonPolicy
{
    /**
     * Whoever edits the course sees all its lessons; students see the lessons of the
     * published courses they are enrolled in. The prerequisite check is added with #34.
     */
    public function view(User $user, Lesson $lesson): bool
    {
        /** @var Course $course */
        $course = $lesson->course;

        if ($user->can('update', $course)) {
            return true;
        }

        return $course->status === CourseStatus::Published
            && $course->students()->whereKey($user->id)->exists();
    }
}
