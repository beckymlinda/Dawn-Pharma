<?php

namespace Tests\Concerns;

use App\Models\Permission;
use App\Models\Product;
use App\Models\Register;
use App\Models\Role;
use App\Models\User;

trait CreatesPosData
{
    protected function userWithPermissions(array $permissions, string $roleName = 'tester'): User
    {
        $role = Role::firstOrCreate(['name' => $roleName], ['display_name' => ucfirst($roleName)]);

        foreach ($permissions as $name) {
            $permission = Permission::firstOrCreate(['name' => $name], ['display_name' => $name, 'group' => 'Test']);
            $role->permissions()->syncWithoutDetaching([$permission->id]);
        }

        $user = User::factory()->create();
        $user->roles()->attach($role);

        return $user;
    }

    protected function cashier(): User
    {
        return $this->userWithPermissions(['add_pos_sell', 'view_pos_sell'], 'cashier');
    }

    protected function product(array $attributes = []): Product
    {
        static $n = 0;
        $n++;

        return Product::create(array_merge([
            'name' => "Test Product {$n}",
            'business_location' => 'Main',
            'purchase_price' => 600,
            'selling_price' => 1000,
            'current_stock' => 10,
            'product_type' => 'Single',
            'sku' => "TEST-{$n}-" . uniqid(),
        ], $attributes));
    }

    protected function openRegisterFor(User $user): Register
    {
        return Register::create([
            'user_id' => $user->id,
            'opened_at' => now(),
            'opening_cash' => 0,
        ]);
    }
}
