<?php

use App\Enums\MemberRole;
use App\Models\Comment;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Membership;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

function authorizeInOrganization(User $user, Organization $organization): void
{
    $test = test();
    $test->actingAs($user);

    $session = app('session')->driver();
    $session->put('current_organization_id', $organization->getKey());
    app('request')->setLaravelSession($session);
}

it('prevents a teacher from editing another teacher course', function () {
    $organization = Organization::factory()->create();
    $teacher = User::factory()->create();
    $owner = User::factory()->create();
    Membership::factory()->for($organization)->for($teacher)->create(['role' => MemberRole::Teacher]);
    Membership::factory()->for($organization)->for($owner)->create(['role' => MemberRole::Teacher]);
    $course = Course::factory()->for($organization)->for($owner, 'owner')->create();

    authorizeInOrganization($teacher, $organization);

    expect(Gate::forUser($teacher)->allows('update', $course))->toBeFalse();
});

it('allows an administrator to edit another teachers course', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->create();
    $owner = User::factory()->create();
    Membership::factory()->for($organization)->for($admin)->create(['role' => MemberRole::Admin]);
    Membership::factory()->for($organization)->for($owner)->create(['role' => MemberRole::Teacher]);
    $course = Course::factory()->for($organization)->for($owner, 'owner')->create();

    authorizeInOrganization($admin, $organization);

    expect(Gate::forUser($admin)->allows('update', $course))->toBeTrue();
});

it('allows a teacher to edit their own course', function () {
    $organization = Organization::factory()->create();
    $teacher = User::factory()->create();
    Membership::factory()->for($organization)->for($teacher)->create(['role' => MemberRole::Teacher]);
    $course = Course::factory()->for($organization)->for($teacher, 'owner')->create();

    authorizeInOrganization($teacher, $organization);

    expect(Gate::forUser($teacher)->allows('update', $course))->toBeTrue();
});

it('does not use a role from another organization', function () {
    $selected = Organization::factory()->create();
    $other = Organization::factory()->create();
    $teacher = User::factory()->create();
    Membership::factory()->for($other)->for($teacher)->create(['role' => MemberRole::Teacher]);
    $course = Course::factory()->for($selected)->for($teacher, 'owner')->create();

    authorizeInOrganization($teacher, $selected);

    expect(Gate::forUser($teacher)->allows('update', $course))->toBeFalse();
});

it('prevents lesson and comment access across organizations', function () {
    $selected = Organization::factory()->create();
    $foreign = Organization::factory()->create();
    $student = User::factory()->create();
    Membership::factory()->for($selected)->for($student)->create(['role' => MemberRole::Student]);
    Membership::factory()->for($foreign)->for($student)->create(['role' => MemberRole::Student]);

    $course = Course::factory()->published()->for($foreign)->create();
    $lesson = Lesson::factory()->for($course)->create();
    $comment = Comment::factory()->for($lesson)->for($student, 'user')->create();
    $course->students()->attach($student);

    authorizeInOrganization($student, $selected);

    expect(Gate::forUser($student)->allows('view', $lesson))->toBeFalse()
        ->and(Gate::forUser($student)->allows('view', $comment))->toBeFalse()
        ->and(Gate::forUser($student)->allows('create', [Comment::class, $lesson]))->toBeFalse();
});

it('allows lesson and comment creation in the selected organization', function () {
    $organization = Organization::factory()->create();
    $student = User::factory()->create();
    Membership::factory()->for($organization)->for($student)->create(['role' => MemberRole::Student]);
    $course = Course::factory()->published()->for($organization)->create();
    $lesson = Lesson::factory()->for($course)->create();
    $course->students()->attach($student);

    authorizeInOrganization($student, $organization);

    expect(Gate::forUser($student)->allows('view', $lesson))->toBeTrue()
        ->and(Gate::forUser($student)->allows('create', [Comment::class, $lesson]))->toBeTrue();
});

it('prevents lesson updates and deletion outside the selected organization', function () {
    $selected = Organization::factory()->create();
    $foreign = Organization::factory()->create();
    $admin = User::factory()->create();
    Membership::factory()->for($selected)->for($admin)->create(['role' => MemberRole::Admin]);
    Membership::factory()->for($foreign)->for($admin)->create(['role' => MemberRole::Admin]);
    $course = Course::factory()->for($foreign)->create();
    $lesson = Lesson::factory()->for($course)->create();

    authorizeInOrganization($admin, $selected);

    expect(Gate::forUser($admin)->allows('update', $lesson))->toBeFalse()
        ->and(Gate::forUser($admin)->allows('delete', $lesson))->toBeFalse();
});

it('prevents updating or moderating comments outside the selected organization', function () {
    $selected = Organization::factory()->create();
    $foreign = Organization::factory()->create();
    $student = User::factory()->create();
    Membership::factory()->for($selected)->for($student)->create(['role' => MemberRole::Student]);
    Membership::factory()->for($foreign)->for($student)->create(['role' => MemberRole::Student]);
    $course = Course::factory()->published()->for($foreign)->create();
    $lesson = Lesson::factory()->for($course)->create();
    $comment = Comment::factory()->for($lesson)->for($student, 'user')->create();

    authorizeInOrganization($student, $selected);

    expect(Gate::forUser($student)->allows('update', $comment))->toBeFalse()
        ->and(Gate::forUser($student)->allows('delete', $comment))->toBeFalse()
        ->and(Gate::forUser($student)->allows('moderate', $comment))->toBeFalse();
});
