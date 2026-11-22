<?php

namespace App\Policies;

use App\Enums\MemberRole;
use App\Models\Course;
use App\Models\User;

class CoursePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isMemberOfCurrentOrganization($user);
    }

    public function view(User $user, Course $course): bool
    {
        return $this->canAccessCourse($user, $course);
    }

    public function create(User $user): bool
    {
        return $this->hasRole($user, [MemberRole::Teacher, MemberRole::Admin]);
    }

    public function update(User $user, Course $course): bool
    {
        return $this->canManageCourse($user, $course);
    }

    public function delete(User $user, Course $course): bool
    {
        return $this->canManageCourse($user, $course);
    }

    public function restore(User $user, Course $course): bool
    {
        return $this->canManageCourse($user, $course);
    }

    public function forceDelete(User $user, Course $course): bool
    {
        return $this->canManageCourse($user, $course);
    }

    public function canManageCourse(User $user, Course $course): bool
    {
        if (! $this->canAccessCourse($user, $course)) {
            return false;
        }

        return $this->hasRole($user, [MemberRole::Admin])
            || ($this->hasRole($user, [MemberRole::Teacher]) && $course->owner_id === $user->getKey());
    }

    public function canAccessCourse(User $user, Course $course): bool
    {
        return $course->organization_id === $this->currentOrganizationId()
            && $this->isMemberOfCurrentOrganization($user);
    }

    private function isMemberOfCurrentOrganization(User $user): bool
    {
        return $this->currentOrganizationId() !== null
            && $user->memberships()
                ->where('organization_id', $this->currentOrganizationId())
                ->exists();
    }

    /**
     * @param  list<MemberRole>  $roles
     */
    private function hasRole(User $user, array $roles): bool
    {
        return $user->memberships()
            ->where('organization_id', $this->currentOrganizationId())
            ->whereIn('role', array_map(static fn (MemberRole $role): string => $role->value, $roles))
            ->exists();
    }

    private function currentOrganizationId(): ?string
    {
        if (! request()->hasSession()) {
            return null;
        }

        $organizationId = request()->session()->get('current_organization_id');

        return is_string($organizationId) ? $organizationId : null;
    }
}
