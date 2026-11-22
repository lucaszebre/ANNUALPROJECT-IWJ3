<?php

namespace App\Policies;

use App\Enums\MemberRole;
use App\Models\Comment;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\User;

class CommentPolicy
{
    public function view(User $user, Comment $comment): bool
    {
        $lesson = $comment->lesson()->first();

        return $lesson instanceof Lesson
            && app(LessonPolicy::class)->view($user, $lesson);
    }

    public function create(User $user, Lesson $lesson): bool
    {
        $course = $lesson->course()->first();

        return $course instanceof Course
            && app(CoursePolicy::class)->canAccessCourse($user, $course);
    }

    public function update(User $user, Comment $comment): bool
    {
        if (! $this->view($user, $comment)) {
            return false;
        }

        if ($comment->user_id === $user->getKey()) {
            return true;
        }

        $lesson = $comment->lesson()->first();

        if (! $lesson instanceof Lesson) {
            return false;
        }

        $course = $lesson->course()->first();

        return $course instanceof Course
            && app(CoursePolicy::class)->canManageCourse($user, $course);
    }

    public function delete(User $user, Comment $comment): bool
    {
        return $this->update($user, $comment);
    }

    public function moderate(User $user, Comment $comment): bool
    {
        $organizationId = request()->hasSession()
            ? request()->session()->get('current_organization_id')
            : null;

        return $this->view($user, $comment)
            && $user->memberships()
                ->where('organization_id', $organizationId)
                ->whereIn('role', [MemberRole::Teacher->value, MemberRole::Admin->value])
                ->exists();
    }
}
