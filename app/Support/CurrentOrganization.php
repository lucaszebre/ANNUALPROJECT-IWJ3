<?php

namespace App\Support;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

/**
 * The organisation the current request works in (docs/data-model.md, decision 4.1).
 *
 * Set by ResolveCurrentOrganization from the session, only once the user is checked to be a
 * member of it. A signed-in user without one sees no organisation's data (`isLocked`).
 * Visitors, console commands and queued jobs have none and are not filtered.
 * Registered as a scoped singleton: reset between requests and jobs.
 */
final class CurrentOrganization
{
    private ?string $id = null;

    private bool $locked = false;

    public function id(): ?string
    {
        return $this->id;
    }

    public function set(string $id): void
    {
        $this->id = $id;
        $this->locked = false;
    }

    /**
     * No organisation, and no access to any: for a signed-in user who has not picked one.
     */
    public function lock(): void
    {
        $this->id = null;
        $this->locked = true;
    }

    public function isLocked(): bool
    {
        return $this->locked;
    }

    public function forget(): void
    {
        $this->id = null;
        $this->locked = false;
    }

    /**
     * `exists` validation limited to the current organisation, so an id of another
     * organisation cannot be submitted in a form. Fails for every id when none is set.
     */
    public function exists(string $table, string $column = 'id'): Exists
    {
        return Rule::exists($table, $column)->where('organization_id', $this->id);
    }
}
