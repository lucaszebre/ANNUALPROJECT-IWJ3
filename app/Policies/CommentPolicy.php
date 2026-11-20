<?php

namespace App\Policies;

use App\Enums\MemberRole;
use App\Models\Comment;
use App\Models\User;

class CommentPolicy
{
    public function view(User $user, Comment $comment): bool
    {
        return app(LessonPolicy::class)->view($user, $comment->lesson);
    }

    public function create(User $user): bool
    {
        return $user->memberships()
            ->where('organization_id', request()->hasSession()
                ? request()->session()->get('current_organization_id')
                : null)
            ->exists();
    }

    public function update(User $user, Comment $comment): bool
    {
        if (! $this->view($user, $comment)) {
            return false;
        }

        return $comment->user_id === $user->getKey()
            || app(CoursePolicy::class)->canManageCourse($user, $comment->lesson->course);
    }

    public function delete(User $user, Comment $comment): bool
    {
        return $this->update($user, $comment);
    }

    public function moderate(User $user, Comment $comment): bool
    {
        return $this->view($user, $comment)
            && $user->memberships()
                ->where('organization_id', request()->session()->get('current_organization_id'))
                ->whereIn('role', [MemberRole::Teacher->value, MemberRole::Admin->value])
                ->exists();
    }
}
