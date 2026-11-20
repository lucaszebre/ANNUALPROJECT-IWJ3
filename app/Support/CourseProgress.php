<?php

namespace App\Support;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * A student's progress in one course, computed from lesson_completions and
 * lesson_prerequisites (never stored, see data model 4.8 and 4.9).
 * Loaded once per page: two queries whatever the number of lessons.
 */
class CourseProgress
{
    /** @var Collection<int, string> ids of the completed lessons */
    private Collection $completed;

    /** @var Collection<int|string, Collection<int, string>> prerequisite ids by lesson id */
    private Collection $prerequisites;

    public function __construct(private Course $course, private ?User $user)
    {
        $lessonIds = $course->lessons->pluck('id');

        $this->completed = $user
            ? DB::table('lesson_completions')
                ->where('user_id', $user->id)
                ->whereIn('lesson_id', $lessonIds)
                ->pluck('lesson_id')
            : collect();

        $this->prerequisites = DB::table('lesson_prerequisites')
            ->whereIn('lesson_id', $lessonIds)
            ->get(['lesson_id', 'prerequisite_id'])
            ->groupBy('lesson_id')
            ->map(fn (Collection $rows) => $rows->map(fn (object $row): string => $row->prerequisite_id)->values());
    }

    /**
     * Whether some lessons of the course wait for others.
     */
    public function hasPrerequisites(): bool
    {
        return $this->prerequisites->isNotEmpty();
    }

    public function isEnrolled(): bool
    {
        return $this->user !== null
            && $this->user->enrolledCourses()->whereKey($this->course->id)->exists();
    }

    public function isCompleted(Lesson $lesson): bool
    {
        return $this->completed->contains($lesson->id);
    }

    /**
     * A lesson is unlocked when all its prerequisites are completed.
     */
    public function isUnlocked(Lesson $lesson): bool
    {
        return $this->prerequisites->get($lesson->id, collect())
            ->every(fn (string $id) => $this->completed->contains($id));
    }

    /**
     * Prerequisites of a lesson the student has not completed yet.
     *
     * @return Collection<int, Lesson>
     */
    public function missingPrerequisites(Lesson $lesson): Collection
    {
        $missing = $this->prerequisites->get($lesson->id, collect())
            ->reject(fn (string $id) => $this->completed->contains($id));

        return $this->course->lessons->whereIn('id', $missing)->values();
    }

    /**
     * The lesson to continue with: the first one, in order, not completed and unlocked.
     */
    public function currentLesson(): ?Lesson
    {
        return $this->course->lessons->first(
            fn (Lesson $lesson) => ! $this->isCompleted($lesson) && $this->isUnlocked($lesson),
        );
    }

    public function completedCount(): int
    {
        return $this->completed->count();
    }

    public function percent(): int
    {
        $total = $this->course->lessons->count();

        return $total === 0 ? 0 : (int) round($this->completedCount() / $total * 100);
    }
}
