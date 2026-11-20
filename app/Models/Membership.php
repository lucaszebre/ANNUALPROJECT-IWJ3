<?php

namespace App\Models;

use App\Enums\MemberRole;
use Database\Factories\MembershipFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * The link between a user and an organisation, with the user's role in it.
 * Not limited to the current organisation: the profile and the role checks read all of a
 * user's memberships.
 */
#[Fillable(['organization_id', 'user_id', 'role'])]
class Membership extends Pivot
{
    /** @use HasFactory<MembershipFactory> */
    use HasFactory, HasUuids;

    protected $table = 'memberships';

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function casts(): array
    {
        return [
            'role' => MemberRole::class,
        ];
    }
}
