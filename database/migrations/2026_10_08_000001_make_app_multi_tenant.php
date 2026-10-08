<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // The tables this migration gave a tenant_id (fixed here: tables added later, such as
    // sms_recipients, are created with tenant_id and listed only in config/tenancy.php)
    private const TABLES = [
        'users', 'units', 'categories', 'brands', 'products', 'contacts', 'purchases', 'purchase_items',
        'sales', 'sale_items', 'pos', 'locations', 'registers', 'stock_histories', 'roles',
        'stock_adjustments', 'payments', 'activity_logs', 'settings', 'notification_recipients',
        'notification_logs', 'integrity_events', 'expenses',
    ];

    /** Values that must be unique within a shop (were unique across the whole database) */
    private const PER_SHOP_UNIQUE = [
        'users' => 'email',
        'products' => 'sku',
        'purchases' => 'reference_no',
        'sales' => 'invoice_no',
        'roles' => 'name',
        'settings' => 'key',
        'notification_recipients' => 'email',
    ];

    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug', 63)->unique();             // shop link: /{slug}
            $table->string('status', 20)->default('active');      // active | suspended
            $table->string('plan', 30)->nullable();
            $table->date('paid_until')->nullable();               // null = no end date
            $table->unsignedSmallInteger('grace_days')->default(7);
            $table->string('contact_name')->nullable();
            $table->string('contact_phone', 50)->nullable();
            $table->string('contact_email')->nullable();
            $table->string('suspended_reason')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            // The software owner's staff: no shop, use the admin portal
            $table->boolean('is_platform_admin')->default(false)->after('password');
        });

        $withForeignKeys = in_array(DB::getDriverName(), ['mysql', 'mariadb']);
        foreach (self::TABLES as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($withForeignKeys) {
                $table->unsignedBigInteger('tenant_id')->nullable()->after('id');
                $table->index('tenant_id');
                if ($withForeignKeys) {
                    $table->foreign('tenant_id')->references('id')->on('tenants')->restrictOnDelete();
                }
            });
        }

        // The data already in this installation becomes the first shop
        if (DB::table('users')->exists() || DB::table('products')->exists() || DB::table('sales')->exists()) {
            $name = DB::table('settings')->where('key', 'system_name')->value('value') ?: config('app.name', 'My Shop');
            $tenantId = DB::table('tenants')->insertGetId([
                'name' => $name,
                'slug' => env('TENANCY_FIRST_SLUG', 'area25'),
                'status' => 'active',
                'grace_days' => config('tenancy.default_grace_days', 7),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach (self::TABLES as $tableName) {
                DB::table($tableName)->whereNull('tenant_id')->update(['tenant_id' => $tenantId]);
            }
        }

        foreach (self::PER_SHOP_UNIQUE as $tableName => $column) {
            Schema::table($tableName, function (Blueprint $table) use ($column) {
                $table->dropUnique([$column]);
                $table->unique(['tenant_id', $column]);
            });
        }
    }

    public function down(): void
    {
        foreach (self::PER_SHOP_UNIQUE as $tableName => $column) {
            Schema::table($tableName, function (Blueprint $table) use ($column) {
                $table->dropUnique(['tenant_id', $column]);
                $table->unique([$column]);
            });
        }

        $withForeignKeys = in_array(DB::getDriverName(), ['mysql', 'mariadb']);
        foreach (self::TABLES as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($withForeignKeys) {
                if ($withForeignKeys) {
                    $table->dropForeign(['tenant_id']);
                }
                $table->dropIndex(['tenant_id']);
                $table->dropColumn('tenant_id');
            });
        }

        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('is_platform_admin'));
        Schema::dropIfExists('tenants');
    }
};
