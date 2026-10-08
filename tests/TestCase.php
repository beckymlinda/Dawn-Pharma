<?php

namespace Tests;

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /** The shop tests run in (requests without a selected shop fall back to it) */
    protected ?Tenant $tenant = null;

    protected function setUp(): void
    {
        parent::setUp();

        if (in_array(RefreshDatabase::class, class_uses_recursive($this), true)) {
            $this->tenant = Tenant::create(['name' => 'Test Shop', 'slug' => config('tenancy.fallback_slug')]);
            set_tenant($this->tenant);
        }
    }
}
