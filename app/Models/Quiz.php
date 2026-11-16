<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\QuizFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['organization_id', 'lesson_id', 'pass_threshold_percent'])]
class Quiz extends Model
{
    /** @use HasFactory<QuizFactory> */
    use BelongsToOrganization, HasFactory, HasUuids;

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(QuizQuestion::class)->orderBy('position');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }

    protected function casts(): array
    {
        return [
            'pass_threshold_percent' => 'integer',
        ];
    }
}
