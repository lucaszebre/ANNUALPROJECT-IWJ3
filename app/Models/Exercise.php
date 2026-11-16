<?php

namespace App\Models;

use App\Enums\CodeLanguage;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\ExerciseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'organization_id', 'lesson_id', 'language', 'statement', 'starter_code', 'solution_code',
    'time_limit_ms', 'memory_limit_mb',
])]
#[Hidden(['solution_code'])]
class Exercise extends Model
{
    /** @use HasFactory<ExerciseFactory> */
    use BelongsToOrganization, HasFactory, HasUuids;

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function tests(): HasMany
    {
        return $this->hasMany(ExerciseTest::class)->orderBy('position');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }

    protected function casts(): array
    {
        return [
            'language' => CodeLanguage::class,
            'time_limit_ms' => 'integer',
            'memory_limit_mb' => 'integer',
        ];
    }
}
