<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\User;

class LessonPolicy
{
    public function view(User $user, Lesson $lesson): bool
    {
        $course = $lesson->course()->first();

        return $course instanceof Course
            && app(CoursePolicy::class)->view($user, $course);
    }

    public function create(User $user, Course $course): bool
    {
        return app(CoursePolicy::class)->create($user);
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
