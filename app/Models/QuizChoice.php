<?php

namespace App\Models;

use Database\Factories\QuizChoiceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['quiz_question_id', 'label', 'is_correct'])]
#[Hidden(['is_correct'])]
class QuizChoice extends Model
{
    /** @use HasFactory<QuizChoiceFactory> */
    use HasFactory, HasUuids;

    public function question(): BelongsTo
    {
        return $this->belongsTo(QuizQuestion::class, 'quiz_question_id');
    }

    protected function casts(): array
    {
        return [
            'is_correct' => 'boolean',
        ];
    }
}
