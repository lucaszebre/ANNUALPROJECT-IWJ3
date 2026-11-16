<?php

namespace App\Models;

use Database\Factories\ExerciseTestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['exercise_id', 'position', 'name', 'stdin', 'expected_stdout', 'is_hidden'])]
class ExerciseTest extends Model
{
    /** @use HasFactory<ExerciseTestFactory> */
    use HasFactory, HasUuids;

    public function exercise(): BelongsTo
    {
        return $this->belongsTo(Exercise::class);
    }

    public function results(): HasMany
    {
        return $this->hasMany(SubmissionResult::class);
    }

    /**
     * Tests whose input and expected output may be shown to students.
     */
    #[Scope]
    protected function visibleToStudents(Builder $query): void
    {
        $query->where('is_hidden', false);
    }

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'is_hidden' => 'boolean',
        ];
    }
}
