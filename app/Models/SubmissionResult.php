<?php

namespace App\Models;

use Database\Factories\SubmissionResultFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['submission_id', 'exercise_test_id', 'passed', 'actual_stdout', 'stderr', 'duration_ms', 'timed_out'])]
class SubmissionResult extends Model
{
    /** @use HasFactory<SubmissionResultFactory> */
    use HasFactory, HasUuids;

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }

    public function exerciseTest(): BelongsTo
    {
        return $this->belongsTo(ExerciseTest::class);
    }

    protected function casts(): array
    {
        return [
            'passed' => 'boolean',
            'duration_ms' => 'integer',
            'timed_out' => 'boolean',
        ];
    }
}
