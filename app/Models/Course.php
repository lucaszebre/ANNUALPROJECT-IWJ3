<?php

namespace App\Models;

use App\Enums\CourseStatus;
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

#[Fillable(['organization_id', 'owner_id', 'category_id', 'title', 'slug', 'description', 'status', 'published_at'])]
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

    protected function casts(): array
    {
        return [
            'status' => CourseStatus::class,
            'published_at' => 'datetime',
        ];
    }
}
