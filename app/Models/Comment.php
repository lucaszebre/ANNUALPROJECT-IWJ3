<?php

namespace App\Models;

use App\Enums\CommentStatus;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\CommentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['organization_id', 'lesson_id', 'user_id', 'parent_id', 'timecode_seconds', 'body', 'status'])]
class Comment extends Model
{
    /** @use HasFactory<CommentFactory> */
    use BelongsToOrganization, HasFactory, HasUuids, SoftDeletes;

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function reports(): HasMany
    {
        return $this->hasMany(CommentReport::class);
    }

    #[Scope]
    protected function visible(Builder $query): void
    {
        $query->where('status', CommentStatus::Visible);
    }

    protected function casts(): array
    {
        return [
            'status' => CommentStatus::class,
            'timecode_seconds' => 'integer',
        ];
    }
}
