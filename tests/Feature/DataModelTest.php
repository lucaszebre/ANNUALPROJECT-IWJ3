<?php

use App\Enums\MemberRole;
use App\Enums\VideoLanguage;
use App\Models\Course;
use App\Models\Exercise;
use App\Models\ExerciseTest;
use App\Models\Lesson;
use App\Models\Organization;
use App\Models\PlaybackEvent;
use App\Models\QuizAttempt;
use App\Models\QuizChoice;
use App\Models\SubmissionResult;
use App\Models\TranscriptSegment;
use App\Models\User;
use App\Models\Video;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

it('seeds a demo organisation with one user per role and a published course', function () {
    $this->seed();

    $organization = Organization::where('slug', 'esgi-demo')->firstOrFail();

    expect($organization->members->pluck('pivot.role')->all())
        ->toEqualCanonicalizing([MemberRole::Admin, MemberRole::Teacher, MemberRole::Student]);

    $course = Course::published()->where('slug', 'debuter-en-python')->firstOrFail();
    expect($course->lessons)->toHaveCount(3)
        ->and($course->lessons->pluck('position')->all())->toBe([1, 2, 3])
        ->and($course->lessons[1]->exercise->tests)->toHaveCount(3)
        ->and($course->lessons[2]->quiz->questions)->toHaveCount(2)
        ->and($course->lessons[2]->prerequisites->pluck('id')->all())->toBe([$course->lessons[1]->id]);
});

it('gives child rows the organisation of their parent', function () {
    $event = PlaybackEvent::factory()->create();
    $attempt = QuizAttempt::factory()->create();
    $result = SubmissionResult::factory()->create();

    expect($event->organization_id)->toBe($event->video->lesson->course->organization_id)
        ->and($attempt->organization_id)->toBe($attempt->quiz->lesson->organization_id)
        ->and($result->exerciseTest->exercise_id)->toBe($result->submission->exercise_id);
});

it('stores the role on the membership, per organisation', function () {
    $user = User::factory()->create();
    [$school, $company] = Organization::factory()->count(2)->create();

    $school->members()->attach($user, ['role' => MemberRole::Teacher]);
    $company->members()->attach($user, ['role' => MemberRole::Student]);

    expect($user->organizations()->find($school->id)->pivot->role)->toBe(MemberRole::Teacher)
        ->and($user->organizations()->find($company->id)->pivot->role)->toBe(MemberRole::Student);
});

it('lets lessons be reordered in one transaction', function () {
    $course = Course::factory()->create();
    [$first, $second] = Lesson::factory()->count(2)->for($course)->create();

    // Swapping positions goes through a duplicate; the deferred constraint only checks the end state.
    $first->update(['position' => 2]);
    $second->update(['position' => 1]);
    DB::statement('set constraints all immediate');

    expect($course->lessons()->pluck('id')->all())->toBe([$second->id, $first->id]);
});

it('refuses two lessons at the same position in a course', function () {
    $course = Course::factory()->create();
    [, $second] = Lesson::factory()->count(2)->for($course)->create();

    $second->update(['position' => 1]);
    DB::statement('set constraints all immediate');
})->throws(QueryException::class, 'lessons_course_id_position_unique');

it('refuses a lesson that requires itself', function () {
    $lesson = Lesson::factory()->create();

    $lesson->prerequisites()->attach($lesson);
})->throws(QueryException::class, 'lesson_prerequisites_not_self');

it('searches transcripts with stemming and without accents', function (VideoLanguage $language, string $text, string $search) {
    $video = Video::factory()->create(['language' => $language]);
    $match = TranscriptSegment::factory()->for($video)->create(['text' => $text]);
    TranscriptSegment::factory()->for($video)->create(['text' => 'Something unrelated, rien à voir.']);

    expect(TranscriptSegment::search($search)->pluck('id')->all())->toBe([$match->id]);
})->with([
    'French plural' => [VideoLanguage::French, 'On écrit une fonction récursive.', 'fonctions récursives'],
    'French without accents' => [VideoLanguage::French, 'On écrit une fonction récursive.', 'fonction recursive'],
    'English stemming' => [VideoLanguage::English, 'We are writing recursive functions.', 'recursive function'],
]);

it('only shows videos whose transcoding is finished', function () {
    $ready = Video::factory()->ready()->create();
    Video::factory()->create();

    expect(Video::ready()->pluck('id')->all())->toBe([$ready->id]);
});

it('never serialises solutions, hidden tests or correct answers', function () {
    $exercise = Exercise::factory()->create();
    $visible = ExerciseTest::factory()->for($exercise)->create();
    ExerciseTest::factory()->hidden()->for($exercise)->create();

    expect($exercise->toArray())->not->toHaveKey('solution_code')
        ->and($exercise->tests()->visibleToStudents()->pluck('id')->all())->toBe([$visible->id])
        ->and(QuizChoice::factory()->correct()->create()->toArray())->not->toHaveKey('is_correct');
});
