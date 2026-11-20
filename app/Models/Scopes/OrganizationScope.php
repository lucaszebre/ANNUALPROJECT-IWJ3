<?php

namespace App\Models\Scopes;

use App\Support\CurrentOrganization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Limits every query to the current organisation. A signed-in user without one sees nothing.
 * Visitors, console commands and queued jobs are not filtered; a public page that lists
 * several organisations' data (the catalogue) must not rely on a signed-in user's scope.
 *
 * @implements Scope<Model>
 */
class OrganizationScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $organization = app(CurrentOrganization::class);

        if ($organization->id() !== null) {
            $builder->where($model->qualifyColumn('organization_id'), $organization->id());
        } elseif ($organization->isLocked()) {
            $builder->whereRaw('1 = 0');
        }
    }
}
