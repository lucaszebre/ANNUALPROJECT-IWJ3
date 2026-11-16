<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\QuizAttemptFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['organization_id', 'quiz_id', 'user_id', 'score_percent', 'passed'])]
class QuizAttempt extends Model
{
    /** @use HasFactory<QuizAttemptFactory> */
    use BelongsToOrganization, HasFactory, HasUuids;

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The choices the student ticked.
     */
    public function choices(): BelongsToMany
    {
        return $this->belongsToMany(QuizChoice::class, 'quiz_attempt_choice');
    }

    protected function casts(): array
    {
        return [
            'score_percent' => 'integer',
            'passed' => 'boolean',
        ];
    }
}
