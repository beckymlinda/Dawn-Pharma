<?php

namespace App\Validation;

use Illuminate\Validation\DatabasePresenceVerifier;

/**
 * Makes exists: and unique: validation rules look only at the current shop's rows,
 * so a shop cannot refer to another shop's products, and a SKU or invoice number
 * only has to be unique within the shop.
 */
class TenantPresenceVerifier extends DatabasePresenceVerifier
{
    protected function table($table)
    {
        $query = parent::table($table);

        if (($tenant = tenant()) && in_array($table, config('tenancy.tables'), true)) {
            $query->where($table . '.tenant_id', $tenant->id);
        }

        return $query;
    }
}
