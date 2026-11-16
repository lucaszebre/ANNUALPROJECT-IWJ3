<?php

namespace App\Models;

use App\Enums\SubmissionStatus;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\SubmissionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['organization_id', 'exercise_id', 'user_id', 'code', 'status', 'tests_passed', 'tests_total', 'finished_at'])]
class Submission extends Model
{
    /** @use HasFactory<SubmissionFactory> */
    use BelongsToOrganization, HasFactory, HasUuids;

    public function exercise(): BelongsTo
    {
        return $this->belongsTo(Exercise::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function results(): HasMany
    {
        return $this->hasMany(SubmissionResult::class);
    }

    protected function casts(): array
    {
        return [
            'status' => SubmissionStatus::class,
            'tests_passed' => 'integer',
            'tests_total' => 'integer',
            'finished_at' => 'datetime',
        ];
    }
}
