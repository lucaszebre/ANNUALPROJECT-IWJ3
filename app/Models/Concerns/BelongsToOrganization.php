<?php

namespace App\Models\Concerns;

use App\Models\Organization;
use App\Models\Scopes\OrganizationScope;
use App\Support\CurrentOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * For models whose table has an organization_id column (data model, decision 4.1):
 * queries only see the current organisation, and new rows get its id.
 */
trait BelongsToOrganization
{
    public static function bootBelongsToOrganization(): void
    {
        static::addGlobalScope(new OrganizationScope);

        static::creating(function (Model $model): void {
            $current = app(CurrentOrganization::class)->id();

            if ($current === null) {
                return;
            }

            if ($model->getAttribute('organization_id') === null) {
                $model->setAttribute('organization_id', $current);
            }

            // organization_id is fillable: never let a request write into another organisation.
            if ($model->getAttribute('organization_id') !== $current) {
                throw new LogicException(sprintf('Cannot create a %s in another organisation.', class_basename($model)));
            }
        });
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
