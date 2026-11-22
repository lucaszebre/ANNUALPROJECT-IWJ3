<?php

namespace App\Policies;

use App\Enums\CourseStatus;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\User;

class LessonPolicy
{
    /**
     * Whoever edits the course sees all its lessons; students see the lessons of
     * published courses they are enrolled in.
     */
    public function view(User $user, Lesson $lesson): bool
    {
        $course = $lesson->course()->first();

        if (! $course instanceof Course) {
            return false;
        }

        $coursePolicy = app(CoursePolicy::class);

        if (! $coursePolicy->canAccessCourse($user, $course)) {
            return false;
        }

        if ($user->can('update', $course)) {
            return true;
        }

        return $course->status === CourseStatus::Published
            && $course->students()->whereKey($user->getKey())->exists();
    }

    public function create(User $user, Course $course): bool
    {
        return app(CoursePolicy::class)->canManageCourse($user, $course);
    }

    public function update(User $user, Lesson $lesson): bool
    {
        $course = $lesson->course()->first();

        return $course instanceof Course
            && app(CoursePolicy::class)->canManageCourse($user, $course);
    }

    public function delete(User $user, Lesson $lesson): bool
    {
        $course = $lesson->course()->first();

        return $course instanceof Course
            && app(CoursePolicy::class)->canManageCourse($user, $course);
    }
}
