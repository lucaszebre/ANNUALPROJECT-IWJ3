<?php

use App\Enums\MemberRole;
use App\Models\Course;
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
