<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            // Others
            ['name' => 'view_export_buttons', 'display_name' => 'View export to buttons (csv/excel/print/pdf) on tables', 'group' => 'Others'],
            
            // User
            ['name' => 'view_user', 'display_name' => 'View user', 'group' => 'User'],
            ['name' => 'add_user', 'display_name' => 'Add user', 'group' => 'User'],
            ['name' => 'edit_user', 'display_name' => 'Edit user', 'group' => 'User'],
            ['name' => 'delete_user', 'display_name' => 'Delete user', 'group' => 'User'],
            
            // Roles
            ['name' => 'view_role', 'display_name' => 'View role', 'group' => 'Roles'],
            ['name' => 'add_role', 'display_name' => 'Add Role', 'group' => 'Roles'],
            ['name' => 'edit_role', 'display_name' => 'Edit Role', 'group' => 'Roles'],
            ['name' => 'delete_role', 'display_name' => 'Delete role', 'group' => 'Roles'],
            
            // Supplier
            ['name' => 'view_all_supplier', 'display_name' => 'View all supplier', 'group' => 'Supplier'],
            ['name' => 'view_own_supplier', 'display_name' => 'View own supplier', 'group' => 'Supplier'],
            ['name' => 'add_supplier', 'display_name' => 'Add supplier', 'group' => 'Supplier'],
            ['name' => 'edit_supplier', 'display_name' => 'Edit supplier', 'group' => 'Supplier'],
            ['name' => 'delete_supplier', 'display_name' => 'Delete supplier', 'group' => 'Supplier'],
            
            // Customer
            ['name' => 'view_all_customer', 'display_name' => 'View all customer', 'group' => 'Customer'],
            ['name' => 'view_own_customer', 'display_name' => 'View own customer', 'group' => 'Customer'],
            ['name' => 'view_customers_no_sell_one_month', 'display_name' => 'View customers with no sell from one month only', 'group' => 'Customer'],
            ['name' => 'view_customers_no_sell_three_months', 'display_name' => 'View customers with no sell from three months only', 'group' => 'Customer'],
            ['name' => 'view_customers_no_sell_six_months', 'display_name' => 'View customers with no sell from six months only', 'group' => 'Customer'],
            ['name' => 'view_customers_no_sell_one_year', 'display_name' => 'View customers with no sell from one year only', 'group' => 'Customer'],
            ['name' => 'view_customers_irrespective', 'display_name' => 'View customers irrespective of their sell', 'group' => 'Customer'],
            ['name' => 'add_customer', 'display_name' => 'Add customer', 'group' => 'Customer'],
            ['name' => 'edit_customer', 'display_name' => 'Edit customer', 'group' => 'Customer'],
            ['name' => 'delete_customer', 'display_name' => 'Delete customer', 'group' => 'Customer'],
            
            // Product
            ['name' => 'view_product', 'display_name' => 'View product', 'group' => 'Product'],
            ['name' => 'add_product', 'display_name' => 'Add product', 'group' => 'Product'],
            ['name' => 'edit_product', 'display_name' => 'Edit product', 'group' => 'Product'],
            ['name' => 'delete_product', 'display_name' => 'Delete product', 'group' => 'Product'],
            ['name' => 'add_opening_stock', 'display_name' => 'Add Opening Stock', 'group' => 'Product'],
            ['name' => 'view_purchase_price', 'display_name' => 'View Purchase Price', 'group' => 'Product'],
            
            // Purchase & Stock Adjustment
            ['name' => 'view_all_purchase', 'display_name' => 'View all Purchase & Stock Adjustment', 'group' => 'Purchase & Stock Adjustment'],
            ['name' => 'view_own_purchase', 'display_name' => 'View own Purchase & Stock Adjustment', 'group' => 'Purchase & Stock Adjustment'],
            ['name' => 'add_purchase', 'display_name' => 'Add purchase & Stock Adjustment', 'group' => 'Purchase & Stock Adjustment'],
            ['name' => 'edit_purchase', 'display_name' => 'Edit purchase & Stock Adjustment', 'group' => 'Purchase & Stock Adjustment'],
            ['name' => 'delete_purchase', 'display_name' => 'Delete purchase & Stock Adjustment', 'group' => 'Purchase & Stock Adjustment'],
            ['name' => 'add_purchase_payment', 'display_name' => 'Add purchase payment', 'group' => 'Purchase & Stock Adjustment'],
            ['name' => 'edit_purchase_payment', 'display_name' => 'Edit purchase payment', 'group' => 'Purchase & Stock Adjustment'],
            ['name' => 'delete_purchase_payment', 'display_name' => 'Delete purchase payment', 'group' => 'Purchase & Stock Adjustment'],
            ['name' => 'update_purchase_status', 'display_name' => 'Update Status', 'group' => 'Purchase & Stock Adjustment'],
            
            // POS
            ['name' => 'view_pos_sell', 'display_name' => 'View POS sell', 'group' => 'POS'],
            ['name' => 'add_pos_sell', 'display_name' => 'Add POS sell', 'group' => 'POS'],
            ['name' => 'edit_pos_sell', 'display_name' => 'Edit POS sell', 'group' => 'POS'],
            ['name' => 'delete_pos_sell', 'display_name' => 'Delete POS sell', 'group' => 'POS'],
            ['name' => 'edit_product_price_pos', 'display_name' => 'Edit product price from POS screen', 'group' => 'POS'],
            ['name' => 'edit_product_discount_pos', 'display_name' => 'Edit product discount from POS screen', 'group' => 'POS'],
            ['name' => 'add_edit_payment', 'display_name' => 'Add/Edit Payment', 'group' => 'POS'],
            ['name' => 'print_invoice', 'display_name' => 'Print Invoice', 'group' => 'POS'],
            ['name' => 'disable_multiple_pay', 'display_name' => 'Disable Multiple Pay', 'group' => 'POS'],
            ['name' => 'disable_draft', 'display_name' => 'Disable Draft', 'group' => 'POS'],
            ['name' => 'disable_express_checkout', 'display_name' => 'Disable Express Checkout', 'group' => 'POS'],
            ['name' => 'disable_discount', 'display_name' => 'Disable Discount', 'group' => 'POS'],
            ['name' => 'disable_suspend_sale', 'display_name' => 'Disable Suspend Sale', 'group' => 'POS'],
            ['name' => 'disable_credit_sale', 'display_name' => 'Disable credit sale button', 'group' => 'POS'],
            ['name' => 'disable_quotation', 'display_name' => 'Disable Quotation', 'group' => 'POS'],
            ['name' => 'disable_card', 'display_name' => 'Disable Card', 'group' => 'POS'],
            
            // Sell
            ['name' => 'commission_agent_own_sell', 'display_name' => 'Commission agent can view their own sell', 'group' => 'Sell'],
            ['name' => 'add_sell_payment', 'display_name' => 'Add sell payment', 'group' => 'Sell'],
            ['name' => 'edit_sell_payment', 'display_name' => 'Edit sell payment', 'group' => 'Sell'],
            ['name' => 'delete_sell_payment', 'display_name' => 'Delete sell payment', 'group' => 'Sell'],
            ['name' => 'edit_product_price_sales', 'display_name' => 'Edit product price from sales screen', 'group' => 'Sell'],
            ['name' => 'edit_product_discount_sales', 'display_name' => 'Edit product discount from Sale screen', 'group' => 'Sell'],
            ['name' => 'add_edit_delete_discount', 'display_name' => 'Add/Edit/Delete Discount', 'group' => 'Sell'],
            ['name' => 'access_all_sell_return', 'display_name' => 'Access all sell return', 'group' => 'Sell'],
            ['name' => 'access_own_sell_return', 'display_name' => 'Access own sell return', 'group' => 'Sell'],
            ['name' => 'add_edit_invoice_number', 'display_name' => 'Add edit invoice number', 'group' => 'Sell'],
            
            // Draft
            ['name' => 'view_all_drafts', 'display_name' => 'View all drafts', 'group' => 'Draft'],
            ['name' => 'view_own_drafts', 'display_name' => 'View own drafts', 'group' => 'Draft'],
            ['name' => 'edit_draft', 'display_name' => 'Edit draft', 'group' => 'Draft'],
            ['name' => 'delete_draft', 'display_name' => 'Delete draft', 'group' => 'Draft'],
            
            // Quotation
            ['name' => 'view_all_quotations', 'display_name' => 'View all quotations', 'group' => 'Quotation'],
            ['name' => 'view_own_quotations', 'display_name' => 'View own quotations', 'group' => 'Quotation'],
            ['name' => 'edit_quotation', 'display_name' => 'Edit quotation', 'group' => 'Quotation'],
            ['name' => 'delete_quotation', 'display_name' => 'Delete quotation', 'group' => 'Quotation'],
            
            // Shipments
            ['name' => 'access_all_shipments', 'display_name' => 'Access all shipments', 'group' => 'Shipments'],
            ['name' => 'access_own_shipments', 'display_name' => 'Access own shipments', 'group' => 'Shipments'],
            ['name' => 'access_pending_shipments', 'display_name' => 'Access pending shipments only', 'group' => 'Shipments'],
            ['name' => 'commission_agent_own_shipments', 'display_name' => 'Commission agent can access their own shipments', 'group' => 'Shipments'],
            
            // Cash Register
            ['name' => 'view_cash_register', 'display_name' => 'View cash register', 'group' => 'Cash Register'],
            ['name' => 'close_cash_register', 'display_name' => 'Close cash register', 'group' => 'Cash Register'],
            
            // Brand
            ['name' => 'view_brand', 'display_name' => 'View brand', 'group' => 'Brand'],
            ['name' => 'add_brand', 'display_name' => 'Add brand', 'group' => 'Brand'],
            ['name' => 'edit_brand', 'display_name' => 'Edit brand', 'group' => 'Brand'],
            ['name' => 'delete_brand', 'display_name' => 'Delete brand', 'group' => 'Brand'],
            
            // Tax rate
            ['name' => 'view_tax_rate', 'display_name' => 'View tax rate', 'group' => 'Tax rate'],
            ['name' => 'add_tax_rate', 'display_name' => 'Add tax rate', 'group' => 'Tax rate'],
            ['name' => 'edit_tax_rate', 'display_name' => 'Edit tax rate', 'group' => 'Tax rate'],
            ['name' => 'delete_tax_rate', 'display_name' => 'Delete tax rate', 'group' => 'Tax rate'],
            
            // Unit
            ['name' => 'view_unit', 'display_name' => 'View unit', 'group' => 'Unit'],
            ['name' => 'add_unit', 'display_name' => 'Add unit', 'group' => 'Unit'],
            ['name' => 'edit_unit', 'display_name' => 'Edit unit', 'group' => 'Unit'],
            ['name' => 'delete_unit', 'display_name' => 'Delete unit', 'group' => 'Unit'],
            
            // Category
            ['name' => 'view_category', 'display_name' => 'View category', 'group' => 'Category'],
            ['name' => 'add_category', 'display_name' => 'Add category', 'group' => 'Category'],
            ['name' => 'edit_category', 'display_name' => 'Edit category', 'group' => 'Category'],
            ['name' => 'delete_category', 'display_name' => 'Delete category', 'group' => 'Category'],
            
            // Report
            ['name' => 'view_purchase_sell_report', 'display_name' => 'View purchase & sell report', 'group' => 'Report'],
            ['name' => 'view_tax_report', 'display_name' => 'View Tax report', 'group' => 'Report'],
            ['name' => 'view_supplier_customer_report', 'display_name' => 'View Supplier & Customer report', 'group' => 'Report'],
            ['name' => 'view_expense_report', 'display_name' => 'View expense report', 'group' => 'Report'],
            ['name' => 'view_profit_loss_report', 'display_name' => 'View profit/loss report', 'group' => 'Report'],
            ['name' => 'view_stock_report', 'display_name' => 'View stock report, stock adjustment report & stock expiry report', 'group' => 'Report'],
            ['name' => 'view_trending_product_report', 'display_name' => 'View trending product report', 'group' => 'Report'],
            ['name' => 'view_register_report', 'display_name' => 'View register report', 'group' => 'Report'],
            ['name' => 'view_sales_representative_report', 'display_name' => 'View sales representative report', 'group' => 'Report'],
            ['name' => 'view_product_stock_value', 'display_name' => 'View product stock value', 'group' => 'Report'],
            
            // Settings
            ['name' => 'access_business_settings', 'display_name' => 'Access business settings', 'group' => 'Settings'],
            ['name' => 'access_barcode_settings', 'display_name' => 'Access barcode settings', 'group' => 'Settings'],
            ['name' => 'access_invoice_settings', 'display_name' => 'Access invoice settings', 'group' => 'Settings'],
            ['name' => 'access_printers', 'display_name' => 'Access printers', 'group' => 'Settings'],
            
            // Expense
            ['name' => 'access_all_expenses', 'display_name' => 'Access all expenses', 'group' => 'Expense'],
            ['name' => 'view_own_expense', 'display_name' => 'View own expense only', 'group' => 'Expense'],
            ['name' => 'add_expense', 'display_name' => 'Add Expense', 'group' => 'Expense'],
            ['name' => 'edit_expense', 'display_name' => 'Edit Expense', 'group' => 'Expense'],
            ['name' => 'delete_expense', 'display_name' => 'Delete Expense', 'group' => 'Expense'],
            
            // Home
            ['name' => 'view_home_data', 'display_name' => 'View Home data', 'group' => 'Home'],
            
            // Account
            ['name' => 'access_accounts', 'display_name' => 'Access Accounts', 'group' => 'Account'],
            ['name' => 'edit_account_transaction', 'display_name' => 'Edit account transaction', 'group' => 'Account'],
            ['name' => 'delete_account_transaction', 'display_name' => 'Delete account transaction', 'group' => 'Account'],
            ['name' => 'access_selling_price_groups', 'display_name' => 'Access selling price groups', 'group' => 'Account'],
            ['name' => 'default_selling_price', 'display_name' => 'Default Selling Price', 'group' => 'Account'],
            
            // Accounting
            ['name' => 'access_accounting_module', 'display_name' => 'Access Accounting Module', 'group' => 'Accounting'],
            ['name' => 'manage_accounts', 'display_name' => 'Manage Accounts', 'group' => 'Accounting'],
            ['name' => 'view_journal', 'display_name' => 'View Journal', 'group' => 'Accounting'],
            ['name' => 'add_journal', 'display_name' => 'Add Journal', 'group' => 'Accounting'],
            ['name' => 'edit_journal', 'display_name' => 'Edit Journal', 'group' => 'Accounting'],
            ['name' => 'delete_journal', 'display_name' => 'Delete Journal', 'group' => 'Accounting'],
            ['name' => 'map_transactions', 'display_name' => 'Map Transactions', 'group' => 'Accounting'],
            ['name' => 'view_transfer', 'display_name' => 'View Transfer', 'group' => 'Accounting'],
            ['name' => 'add_transfer', 'display_name' => 'Add Transfer', 'group' => 'Accounting'],
            ['name' => 'edit_transfer', 'display_name' => 'Edit Transfer', 'group' => 'Accounting'],
            ['name' => 'delete_transfer', 'display_name' => 'Delete Transfer', 'group' => 'Accounting'],
            ['name' => 'manage_budget', 'display_name' => 'Manage Budget', 'group' => 'Accounting'],
            
            // Stock Management Permissions
            ['name' => 'view_stock_report', 'display_name' => 'View Stock Report', 'group' => 'Stock'],
            ['name' => 'export_stock_report', 'display_name' => 'Export Stock Report', 'group' => 'Stock'],
            ['name' => 'view_stock_expiry_report', 'display_name' => 'View Stock Expiry Report', 'group' => 'Stock'],
            ['name' => 'create_stock_adjustment', 'display_name' => 'Create Stock Adjustment', 'group' => 'Stock'],
            ['name' => 'view_stock_adjustments', 'display_name' => 'View Stock Adjustments', 'group' => 'Stock'],
            
            // Superadmin
            ['name' => 'access_package_subscriptions', 'display_name' => 'Access package subscriptions', 'group' => 'Superadmin'],
        ];

        foreach ($permissions as $permission) {
            Permission::create($permission);
        }
    }
}
