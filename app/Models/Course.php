<?php

namespace App\Models;

use App\Enums\CourseLevel;
use App\Enums\CourseStatus;
use App\Enums\CoverTone;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\CourseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property CourseStatus $status
 * @property CourseLevel $level
 * @property CoverTone $cover_tone
 */
#[Fillable([
    'organization_id', 'owner_id', 'category_id', 'title', 'slug', 'description', 'status', 'published_at',
    'level', 'cover_label', 'cover_tone', 'cover_code',
])]
class Course extends Model
{
    /** @use HasFactory<CourseFactory> */
    use BelongsToOrganization, HasFactory, HasUuids, SoftDeletes;

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return HasMany<Lesson, $this>
     */
    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class)->orderBy('position');
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'enrollments')->withTimestamps();
    }

    #[Scope]
    protected function published(Builder $query): void
    {
        $query->where('status', CourseStatus::Published);
    }

    /**
     * Published courses a user may see: those of their organisations, or every published
     * course for a visitor. The rule for visitors is settled with #39 (multi-tenancy).
     */
    #[Scope]
    protected function visibleTo(Builder $query, ?User $user): void
    {
        $query->where('status', CourseStatus::Published);

        if ($user && $user->memberships()->exists()) {
            $query->whereIn('organization_id', $user->memberships()->select('organization_id'));
        }
    }

    /**
     * Short label of the cover, the title when the teacher did not set one.
     */
    public function coverLabel(): string
    {
        return $this->cover_label ?: $this->title;
    }

    protected function casts(): array
    {
        return [
            'status' => CourseStatus::class,
            'level' => CourseLevel::class,
            'cover_tone' => CoverTone::class,
            'published_at' => 'datetime',
        ];
    }
}
