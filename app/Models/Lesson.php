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

    protected function casts(): array
    {
        return [
            'position' => 'integer',
        ];
    }
}
