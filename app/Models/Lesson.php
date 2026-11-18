<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\LessonFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['organization_id', 'course_id', 'position', 'title', 'content'])]
class Lesson extends Model
{
    /** @use HasFactory<LessonFactory> */
    use BelongsToOrganization, HasFactory, HasUuids;

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * @return HasOne<Video, $this>
     */
    public function video(): HasOne
    {
        return $this->hasOne(Video::class);
    }

    public function exercise(): HasOne
    {
        return $this->hasOne(Exercise::class);
    }

    public function quiz(): HasOne
    {
        return $this->hasOne(Quiz::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * The lessons that must be completed before this one.
     */
    public function prerequisites(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'lesson_prerequisites', 'lesson_id', 'prerequisite_id')->withTimestamps();
    }

    /**
     * The lessons that require this one.
     */
    public function dependents(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'lesson_prerequisites', 'prerequisite_id', 'lesson_id')->withTimestamps();
    }

    public function completedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'lesson_completions')
            ->withPivot('source')
            ->withTimestamps();
    }

    /**
     * What the lesson contains, as shown in the programme: "Vidéo + texte", "Exercice"…
     * Needs the video, exercise and quiz relations loaded.
     */
    public function contentSummary(): string
    {
        $parts = array_keys(array_filter([
            'vidéo' => $this->video !== null,
            'texte' => filled($this->content),
            'exercice' => $this->exercise !== null,
            'quiz' => $this->quiz !== null,
        ]));

        return ucfirst(implode(' + ', $parts));
    }

    /**
     * Length of the lesson video in minutes, null while it is unknown (no video, or not transcoded yet).
     */
    public function durationMinutes(): ?int
    {
        $seconds = $this->video?->duration_seconds;

        return $seconds ? (int) ceil($seconds / 60) : null;
    }

    protected function casts(): array
    {
        return [
            'position' => 'integer',
        ];
    }
}
