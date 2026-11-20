<?php

use App\Models\Category;
use App\Models\Comment;
use App\Models\CommentReport;
use App\Models\Course;
use App\Models\Exercise;
use App\Models\Lesson;
use App\Models\Membership;
use App\Models\Organization;
use App\Models\PlaybackEvent;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Scopes\OrganizationScope;
use App\Models\Submission;
use App\Models\TranscriptSegment;
use App\Models\User;
use App\Models\Video;
use App\Support\CurrentOrganization;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;

beforeEach(function () {
    $this->orgA = Organization::factory()->create(['name' => 'Académie A']);
    $this->orgB = Organization::factory()->create(['name' => 'École B']);

    $this->user = User::factory()->create();
    Membership::factory()->for($this->orgA)->for($this->user)->create();

    $this->courseA = Course::factory()->for($this->orgA)->create(['title' => 'Cours de A']);
    $this->courseB = Course::factory()->for($this->orgB)->create(['title' => 'Cours de B']);

    // A route that reads an organisation's data, like the real ones will.
    Route::middleware(['web', 'auth', 'organization.selected'])
        ->get('/_test/courses/{course}', fn (Course $course) => $course->title);
});

function workIn(Organization $organization): void
{
    app(CurrentOrganization::class)->set($organization->id);
}

it('gives a 404 on a course of another organisation and shows its own', function () {
    $this->actingAs($this->user)->withSession(['current_organization_id' => $this->orgA->id]);

    $this->get("/_test/courses/{$this->courseA->id}")->assertOk()->assertSee('Cours de A');
    $this->get("/_test/courses/{$this->courseB->id}")->assertNotFound();
});

it('refuses an organisation the user is not a member of, even when it is in the session', function () {
    $this->actingAs($this->user)
        ->withSession(['current_organization_id' => $this->orgB->id])
        ->get("/_test/courses/{$this->courseB->id}")
        ->assertRedirect(route('organizations.select'))
        ->assertSessionMissing('current_organization_id')
        ->assertSessionHasErrors('organization_id');
});

it('sends a user who has not picked an organisation to the selection page', function () {
    $this->actingAs($this->user)
        ->get("/_test/courses/{$this->courseA->id}")
        ->assertRedirect(route('organizations.select'));
});

it('only lists the rows of the current organisation, on every tenant table', function () {
    Category::factory()->for($this->orgA)->create();
    Category::factory()->for($this->orgB)->create();
    Lesson::factory()->for($this->courseA)->create();
    Lesson::factory()->for($this->courseB)->create();

    workIn($this->orgA);

    expect(Course::pluck('id')->all())->toBe([$this->courseA->id])
        ->and(Course::find($this->courseB->id))->toBeNull()
        ->and(Category::pluck('organization_id')->unique()->all())->toBe([$this->orgA->id])
        ->and(Lesson::pluck('organization_id')->unique()->all())->toBe([$this->orgA->id]);
});

it('does not filter when no organisation is set, for visitors, commands and jobs', function () {
    expect(Course::count())->toBe(2);
});

it('fills the organisation of new rows and refuses another one', function () {
    workIn($this->orgA);

    $category = Category::create(['name' => 'Web', 'slug' => 'web']);

    expect($category->organization_id)->toBe($this->orgA->id);

    Category::create(['organization_id' => $this->orgB->id, 'name' => 'Intrus', 'slug' => 'intrus']);
})->throws(LogicException::class, 'Cannot create a Category in another organisation.');

it('only accepts ids of the current organisation in exists validation', function () {
    workIn($this->orgA);
    $rules = ['course_id' => [app(CurrentOrganization::class)->exists('courses')]];

    expect(Validator::make(['course_id' => $this->courseA->id], $rules)->passes())->toBeTrue()
        ->and(Validator::make(['course_id' => $this->courseB->id], $rules)->passes())->toBeFalse();
});

it('refuses every id in exists validation when no organisation is set', function () {
    $rules = ['course_id' => [app(CurrentOrganization::class)->exists('courses')]];

    expect(Validator::make(['course_id' => $this->courseA->id], $rules)->passes())->toBeFalse();
});

it('keeps listing all of a user\'s memberships, whatever the current organisation', function () {
    Membership::factory()->for($this->orgB)->for($this->user)->create();

    workIn($this->orgA);

    expect($this->user->memberships()->count())->toBe(2);
});

it('forgets the organisation once the request is over', function () {
    $this->actingAs($this->user)
        ->withSession(['current_organization_id' => $this->orgA->id])
        ->get("/_test/courses/{$this->courseA->id}")
        ->assertOk();

    expect(app(CurrentOrganization::class)->id())->toBeNull()
        ->and(Course::count())->toBe(2);
});

it('shows nothing of any organisation to a signed-in user who has not picked one', function () {
    Route::middleware(['web', 'auth'])->get('/_test/auth-only/courses/{course}', fn (Course $course) => $course->title);

    $this->actingAs(User::factory()->create())
        ->get("/_test/auth-only/courses/{$this->courseB->id}")
        ->assertNotFound();
});

it('refuses to create a row for a signed-in user who has not picked an organisation', function () {
    app(CurrentOrganization::class)->lock();

    Category::create(['organization_id' => $this->orgA->id, 'name' => 'Web', 'slug' => 'web']);
})->throws(LogicException::class, 'Cannot create a Category without a current organisation.');

it('scopes every tenant model of the data model', function (string $model) {
    expect((new $model)->hasGlobalScope(OrganizationScope::class))->toBeTrue();
})->with([
    Category::class, Course::class, Lesson::class, Video::class, TranscriptSegment::class,
    Comment::class, CommentReport::class, Exercise::class, Submission::class, Quiz::class,
    QuizAttempt::class, PlaybackEvent::class,
]);

it('does not scope memberships', function () {
    expect((new Membership)->hasGlobalScope(OrganizationScope::class))->toBeFalse();
});
