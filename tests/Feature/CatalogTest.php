<?php

use App\Enums\CompletionSource;
use App\Enums\CourseLevel;
use App\Models\Category;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Membership;
use App\Models\Organization;
use App\Models\User;
use App\Models\Video;

beforeEach(function () {
    $this->organization = Organization::factory()->create();
    $this->web = Category::factory()->for($this->organization)->create(['name' => 'Développement web', 'slug' => 'web']);
});

function publishedCourse(array $attributes = []): Course
{
    return Course::factory()->published()->for(test()->organization)->create($attributes);
}

it('lists only published courses', function () {
    publishedCourse(['title' => 'Découvrir PHP']);
    Course::factory()->for($this->organization)->create(['title' => 'Brouillon secret']);

    $this->get(route('catalog'))
        ->assertOk()
        ->assertSee('Découvrir PHP')
        ->assertDontSee('Brouillon secret')
        ->assertSee('1 cours pour faire le prochain pas');
});

it('filters the catalogue by search, category and level', function () {
    publishedCourse(['title' => 'Découvrir PHP', 'category_id' => $this->web->id, 'level' => CourseLevel::Beginner]);
    publishedCourse(['title' => 'Laravel en pratique', 'category_id' => $this->web->id, 'level' => CourseLevel::Intermediate]);
    publishedCourse(['title' => 'SQL et PostgreSQL', 'level' => CourseLevel::Beginner]);

    $this->get(route('catalog', ['q' => 'php']))
        ->assertSee('Découvrir PHP')->assertDontSee('Laravel en pratique')->assertDontSee('SQL et PostgreSQL');

    $this->get(route('catalog', ['categorie' => 'web']))
        ->assertSee('Découvrir PHP')->assertSee('Laravel en pratique')->assertDontSee('SQL et PostgreSQL');

    $this->get(route('catalog', ['niveau' => 'intermediate']))
        ->assertSee('Laravel en pratique')->assertDontSee('Découvrir PHP');

    $this->get(route('catalog', ['q' => '100%']))
        ->assertSee('Aucun cours trouvé');
});

it('paginates the catalogue and keeps the filters in the links', function () {
    foreach (range(1, 13) as $number) {
        publishedCourse(['title' => "Cours {$number}", 'category_id' => $this->web->id]);
    }

    $this->get(route('catalog', ['categorie' => 'web']))
        ->assertOk()
        ->assertSee('Page 1 sur 2')
        ->assertSee('categorie=web&amp;page=2', false);
});

it('shows a member only the courses of their organisation', function () {
    publishedCourse(['title' => 'Cours de mon école']);
    Course::factory()->published()->create(['title' => 'Cours d’une autre école']);
    $student = User::factory()->create();
    Membership::factory()->for($this->organization)->for($student)->create();

    $this->actingAs($student)->get(route('catalog'))
        ->assertSee('Cours de mon école')
        ->assertDontSee('Cours d’une autre école');

    $other = Course::where('title', 'Cours d’une autre école')->first();
    $this->actingAs($student)->get(route('courses.show', $other))->assertNotFound();
});

it('shows a course to a visitor with its programme', function () {
    $course = publishedCourse(['title' => 'Découvrir PHP', 'level' => CourseLevel::Beginner, 'cover_label' => 'PHP']);
    $first = Lesson::factory()->for($course)->create(['position' => 1, 'title' => 'Installer PHP', 'content' => 'Texte']);
    Video::factory()->ready()->for($first)->create(['duration_seconds' => 700]);
    Lesson::factory()->for($course)->create(['position' => 2, 'title' => 'Variables et types']);

    $this->get(route('courses.show', $course))
        ->assertOk()
        ->assertSeeInOrder(['Catalogue', 'PHP', 'DÉBUTANT', 'Découvrir PHP', 'Par '.$course->owner->name, '2 leçons'])
        ->assertSeeInOrder(['Installer PHP', 'Vidéo + texte', '12 min', 'Variables et types'])
        ->assertSee('Commencer le cours')
        ->assertSee('Accès avec un compte')
        ->assertDontSee('Votre progression')
        ->assertDontSee('Verrouillée');
});

it('hides draft courses', function () {
    $draft = Course::factory()->for($this->organization)->create();

    $this->get(route('courses.show', $draft))->assertNotFound();
});

it('shows a student their progress, the completed, current and locked lessons', function () {
    $course = publishedCourse();
    [$first, $second, $third] = collect([1, 2, 3])->map(fn ($position) => Lesson::factory()
        ->for($course)->create(['position' => $position, 'title' => "Leçon {$position}"]));
    $third->prerequisites()->attach($second);

    $student = User::factory()->create();
    Membership::factory()->for($this->organization)->for($student)->create();
    $student->completedLessons()->attach($first, ['source' => CompletionSource::Manual]);

    $response = $this->actingAs($student)->get(route('courses.show', $course))->assertOk();

    $response->assertSee('Votre progression')
        ->assertSee('1 leçon terminée sur 3.')
        ->assertSee('aria-valuenow="33"', false)
        ->assertSee('Continuer le cours')
        ->assertSee('Terminée : </span>Leçon 1', false)
        ->assertSee('aria-current="step"', false)
        ->assertSee('Verrouillée')
        ->assertSee('Terminez d’abord : Leçon 2')
        ->assertSee('Des leçons à débloquer');
});

it('sends a student to the current lesson from "Commencer"', function () {
    $course = publishedCourse();
    Lesson::factory()->for($course)->create(['position' => 1]);
    $student = User::factory()->create();
    Membership::factory()->for($this->organization)->for($student)->create();

    $this->actingAs($student)->get(route('courses.start', $course))
        ->assertRedirect(route('courses.show', $course).'#programme');
});
