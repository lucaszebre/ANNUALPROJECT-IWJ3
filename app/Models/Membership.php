<?php

namespace App\Models;

use App\Enums\MemberRole;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\MembershipFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * The link between a user and an organisation, with the user's role in it.
 *
 * @property MemberRole $role
 */
#[Fillable(['organization_id', 'user_id', 'role'])]
class Membership extends Pivot
{
    /** @use HasFactory<MembershipFactory> */
    use BelongsToOrganization, HasFactory, HasUuids;

    protected $table = 'memberships';

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
