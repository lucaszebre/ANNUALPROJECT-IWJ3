<?php

namespace App\Support;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

/**
 * The organisation the current request works in (docs/data-model.md, decision 4.1).
 *
 * Set by ResolveCurrentOrganization from the session, only once the user is checked to be a
 * member of it. Null for visitors, users who have not picked an organisation yet, console
 * commands and queued jobs. Registered as a scoped singleton: reset between requests and jobs.
 */
final class CurrentOrganization
{
    private ?string $id = null;

    public function id(): ?string
    {
        return $this->id;
    }

    public function set(?string $id): void
    {
        $this->id = $id;
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
