<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Permission;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Super Admin role with all permissions
        $superAdmin = Role::create([
            'name' => 'super_admin',
            'display_name' => 'Super Admin',
            'description' => 'Full system access',
            'is_superadmin' => true
        ]);

        // Give super admin all permissions
        $allPermissions = Permission::all()->pluck('id');
        $superAdmin->permissions()->sync($allPermissions);

        // Create Admin role with most permissions
        $admin = Role::create([
            'name' => 'admin',
            'display_name' => 'Admin',
            'description' => 'Administrative access'
        ]);

        // Give admin most permissions (excluding superadmin specific ones)
        $adminPermissions = Permission::where('group', '!=', 'Superadmin')->pluck('id');
        $admin->permissions()->sync($adminPermissions);

        // Create Cashier role with limited permissions
        $cashier = Role::create([
            'name' => 'cashier',
            'display_name' => 'Cashier',
            'description' => 'POS and basic sales access'
        ]);

        $cashierPermissions = [
            'view_pos_sell',
            'add_pos_sell',
            'edit_pos_sell',
            'print_invoice',
            'view_product',
            'view_all_customer',
            'add_customer',
            'edit_customer',
            'view_all_supplier',
            'add_supplier',
            'view_cash_register',
            'close_cash_register',
            'view_home_data'
        ];

        $cashierPermissionIds = Permission::whereIn('name', $cashierPermissions)->pluck('id');
        $cashier->permissions()->sync($cashierPermissionIds);

        // Create Manager role
        $manager = Role::create([
            'name' => 'manager',
            'display_name' => 'Manager',
            'description' => 'Management access with reporting'
        ]);

        $managerPermissions = [
            'view_pos_sell',
            'add_pos_sell',
            'edit_pos_sell',
            'delete_pos_sell',
            'print_invoice',
            'view_product',
            'add_product',
            'edit_product',
            'view_all_customer',
            'add_customer',
            'edit_customer',
            'delete_customer',
            'view_all_supplier',
            'add_supplier',
            'edit_supplier',
            'delete_supplier',
            'view_all_purchase',
            'add_purchase',
            'edit_purchase',
            'view_purchase_sell_report',
            'view_stock_report',
            'view_register_report',
            'view_cash_register',
            'close_cash_register',
            'view_home_data',
            'view_export_buttons'
        ];

        $managerPermissionIds = Permission::whereIn('name', $managerPermissions)->pluck('id');
        $manager->permissions()->sync($managerPermissionIds);
    }
}
