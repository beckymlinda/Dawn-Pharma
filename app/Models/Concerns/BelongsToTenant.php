<?php

namespace App\Models\Concerns;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Builder;

/**
 * Rows owned by a shop. Every query is limited to the current shop and new
 * rows are stamped with it, so one shop can never read or change another's data.
 *
 *  - a shop is selected:       only that shop's rows
 *  - on the admin portal:       only rows with no shop (e.g. platform admins);
 *                               portal code reads shop data with withoutTenantScope()
 *  - web request, no shop chosen: nothing (until the shop is selected, and also
 *                               if code ever runs before the shop is identified)
 *  - command line (no request): all rows
 */
trait BelongsToTenant
{
    protected static function bootBelongsToTenant(): void
    {
        static::addGlobalScope('tenant', function (Builder $query) {
            $column = $query->getModel()->getTable() . '.tenant_id';

            if ($tenant = tenant()) {
                $query->where($column, $tenant->id);
            } elseif (is_central()) {
                $query->whereNull($column);
            } elseif (tenant_unresolved() || (!app()->bound('tenancy.tenant') && !app()->runningInConsole())) {
                // Safety net: a web request where no shop has been decided yet sees nothing,
                // never every shop's rows
                $query->whereRaw('1 = 0');
            }
        });

        static::creating(function ($model) {
            if (empty($model->tenant_id) && ($tenant = tenant())) {
                $model->tenant_id = $tenant->id;
            }
        });
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public static function withoutTenantScope(): Builder
    {
        return static::withoutGlobalScope('tenant');
    }
}
