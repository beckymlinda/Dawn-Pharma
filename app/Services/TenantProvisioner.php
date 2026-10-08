<?php

namespace App\Services;

use App\Models\Location;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Setting;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\DB;

/**
 * Sets up a new shop: the shop record, its standard roles (super admin, admin,
 * manager, cashier), a main location, its business details and the owner's login.
 */
class TenantProvisioner
{
    public function create(array $shop, array $owner): Tenant
    {
        return DB::transaction(function () use ($shop, $owner) {
            $tenant = Tenant::create($shop + ['status' => 'active']);

            // Permissions are shared by all shops; make sure they exist
            if (!Permission::exists()) {
                (new PermissionSeeder())->run();
            }

            // Everything below is created inside the new shop
            with_tenant($tenant, function () use ($tenant, $shop, $owner) {
                (new RoleSeeder())->run();

                Location::create(['name' => 'Main Shop', 'phone' => $shop['contact_phone'] ?? null]);

                foreach (['system_name' => $tenant->name, 'telephone' => $tenant->contact_phone] as $key => $value) {
                    if ($value) {
                        Setting::create(['key' => $key, 'value' => $value]);
                    }
                }

                $user = User::create([
                    'name' => $owner['name'],
                    'email' => strtolower($owner['email']),
                    'password' => $owner['password'],
                ]);
                $user->roles()->attach(Role::where('name', 'super_admin')->value('id'));
            });

            return $tenant;
        });
    }
}
