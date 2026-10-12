<?php

namespace Tests\Feature;

use App\Http\Controllers\SetupController;
use App\Models\Product;
use App\Models\Role;
use App\Models\Sale;
use App\Models\Setting;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesPosData;
use Tests\TestCase;

class TenancyTest extends TestCase
{
    use RefreshDatabase, CreatesPosData;

    private function otherShop(string $slug = 'cnc'): Tenant
    {
        $tenant = app(TenantProvisioner::class)->create(
            ['name' => strtoupper($slug) . ' Shop', 'slug' => $slug, 'grace_days' => 7],
            ['name' => 'Owner ' . $slug, 'email' => "owner@{$slug}.test", 'password' => 'secret-password']
        );
        set_tenant($this->tenant);

        return $tenant;
    }

    private function platformAdmin(): User
    {
        return SetupController::makePlatformAdmin('Vendor', 'vendor@example.test', 'vendor-password');
    }

    private function inShop(Tenant $tenant, callable $callback)
    {
        return with_tenant($tenant, $callback);
    }

    /** Requests behave as if no shop has been chosen in this browser */
    private function withoutDefaultShop(): void
    {
        config(['tenancy.fallback_slug' => '']);
    }

    // --- Isolation -----------------------------------------------------------

    public function test_each_shop_only_sees_its_own_data(): void
    {
        $mine = $this->product(['name' => 'Panadol Area25']);
        $other = $this->otherShop();
        $theirs = $this->inShop($other, fn () => $this->product(['name' => 'Panadol CNC']));

        $this->assertSame([$mine->id], Product::pluck('id')->all());
        $this->assertSame($other->id, $theirs->tenant_id);
        $this->assertNull(Product::find($theirs->id));

        $owner = $this->userWithPermissions(['view_product', 'edit_product'], 'super_admin');
        $this->actingAs($owner)->get('/products')->assertOk()->assertSee('Panadol Area25')->assertDontSee('Panadol CNC');
        $this->actingAs($owner)->get("/products/{$theirs->id}/edit")->assertNotFound();
    }

    public function test_a_shop_cannot_sell_another_shops_product(): void
    {
        $other = $this->otherShop();
        $theirs = $this->inShop($other, fn () => $this->product(['current_stock' => 10]));
        $cashier = $this->cashier();
        $this->openRegisterFor($cashier);

        $this->actingAs($cashier)->postJson('/pos/finalize', [
            'items' => [['product_id' => $theirs->id, 'quantity' => 1]],
            'invoice_no' => 'INV-X', 'payment_method' => 'cash', 'payment_status' => 'paid',
        ])->assertStatus(400);

        $this->assertEquals(10, $this->inShop($other, fn () => Product::find($theirs->id)->current_stock));
        $this->assertSame(0, Sale::withoutTenantScope()->count());
    }

    public function test_same_sku_and_role_names_are_allowed_in_different_shops(): void
    {
        $this->product(['sku' => 'PAN-500']);
        $this->cashier();                                  // this shop's "cashier" role
        $other = $this->otherShop();                       // provisioning creates CNC's own roles
        $theirs = $this->inShop($other, fn () => $this->product(['sku' => 'PAN-500']));

        $this->assertSame('PAN-500', $theirs->sku);
        $this->assertSame(1, Role::where('name', 'cashier')->count());
        $this->assertSame(2, Role::withoutTenantScope()->where('name', 'cashier')->count());

        // Validation only checks within the shop
        $owner = $this->userWithPermissions(['add_product'], 'super_admin');
        $this->actingAs($owner)->post('/products', ['name' => 'Dup', 'sku' => 'PAN-500', 'purchase_price' => 1, 'selling_price' => 2])
            ->assertSessionHasErrors('sku');
        $this->inShop($other, fn () => $this->product(['sku' => 'ONLY-IN-CNC']));
        $this->actingAs($owner)->post('/products', ['name' => 'New', 'sku' => 'ONLY-IN-CNC', 'purchase_price' => 1, 'selling_price' => 2])
            ->assertSessionDoesntHaveErrors('sku');
    }

    public function test_settings_and_branding_are_per_shop(): void
    {
        Setting::create(['key' => 'system_name', 'value' => 'Area 25 Pharmacy']);
        $other = $this->otherShop();

        $this->withSession(['tenant_id' => $other->id])->get('/login')->assertOk()->assertSee('CNC Shop')->assertDontSee('Area 25 Pharmacy');
        $this->withSession(['tenant_id' => $this->tenant->id])->get('/login')->assertOk()->assertSee('Area 25 Pharmacy');
    }

    // --- Choosing a shop -----------------------------------------------------

    public function test_shop_link_selects_the_shop_and_opens_its_login(): void
    {
        $other = $this->otherShop();
        $this->withoutDefaultShop();

        $this->get('/cnc')->assertRedirect(route('login'))->assertSessionHas('tenant_id', $other->id);
    }

    public function test_unknown_shop_link_is_not_found_and_real_pages_are_not_hijacked(): void
    {
        $this->get('/nosuchshop')->assertNotFound();
        $this->get('/login')->assertOk();
        $this->get('/a/b')->assertNotFound();
    }

    public function test_login_with_shop_code_when_no_shop_is_selected(): void
    {
        $other = $this->otherShop();
        $this->withoutDefaultShop();

        $this->get('/login')->assertOk()->assertSee('Shop code');

        $this->post('/login', ['email' => 'owner@cnc.test', 'password' => 'secret-password'])->assertSessionHasErrors('shop');
        $this->post('/login', ['shop' => 'nope', 'email' => 'owner@cnc.test', 'password' => 'secret-password'])->assertSessionHasErrors('shop');
        $this->assertGuest();

        $this->post('/login', ['shop' => 'CNC', 'email' => 'owner@cnc.test', 'password' => 'secret-password'])
            ->assertRedirect(route('dashboard', absolute: false))
            ->assertSessionHas('tenant_id', $other->id);
        $this->assertAuthenticated();
    }

    public function test_a_login_only_works_in_its_own_shop(): void
    {
        $this->otherShop();

        // This browser is on the "test" shop: CNC's owner is unknown here
        $this->post('/login', ['email' => 'owner@cnc.test', 'password' => 'secret-password'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_a_user_from_another_shop_is_rejected_even_with_a_session(): void
    {
        $other = $this->otherShop();
        $theirOwner = $this->inShop($other, fn () => User::first());

        $this->actingAs($theirOwner)->withSession(['tenant_id' => $this->tenant->id])->get('/dashboard')->assertForbidden();
    }

    public function test_opening_another_shops_link_logs_out_of_the_current_one(): void
    {
        $this->otherShop();
        $owner = $this->userWithPermissions([], 'super_admin');

        $this->actingAs($owner)->withSession(['tenant_id' => $this->tenant->id])->get('/cnc')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_logged_in_without_a_shop_is_sent_to_login(): void
    {
        $owner = $this->userWithPermissions([], 'super_admin');
        $this->withoutDefaultShop();

        $this->actingAs($owner)->get('/dashboard')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    // --- Admin portal --------------------------------------------------------

    public function test_platform_admin_logs_in_and_sees_all_shops(): void
    {
        $this->platformAdmin();
        $this->otherShop();

        $this->get('/platform')->assertRedirect('/platform/login');
        $this->post('/platform/login', ['email' => 'vendor@example.test', 'password' => 'vendor-password'])
            ->assertRedirect(route('platform.index'));

        $this->get('/platform')->assertOk()->assertSee('Your income this month');
        $this->get('/platform/tenants')->assertOk()->assertSee('Test Shop')->assertSee('CNC Shop');
    }

    public function test_portal_shows_your_income_per_pharmacy_not_their_sales(): void
    {
        $vendor = $this->platformAdmin();
        $other = $this->otherShop();
        $this->tenant->update(['plan' => 'basic']);
        $other->update(['plan' => 'standard']);

        // The pharmacies' own sales must not appear in the portal
        $cashier = $this->cashier();
        $this->openRegisterFor($cashier);
        $product = $this->product(['selling_price' => 987654, 'current_stock' => 5]);
        $this->actingAs($cashier)->postJson('/pos/finalize', ['items' => [['product_id' => $product->id, 'quantity' => 1]],
            'invoice_no' => 'INV-BIG', 'payment_method' => 'cash', 'payment_status' => 'paid'])->assertOk();

        // What they paid you: Test Shop twice this month, CNC once
        $this->tenant->recordPayment(1, 25000, 'Vendor');
        $this->tenant->recordPayment(2, 50000, 'Vendor');
        $other->recordPayment(1, 45000, 'Vendor');
        $other->paymentSubmissions()->create(['months' => 1, 'amount' => 45000, 'account' => 'mpamba', 'paid_on' => now(),
            'proof_path' => 'x.jpg', 'status' => 'pending']);

        $month = now()->month;
        $this->actingAs($vendor)->get('/platform')->assertOk()
            ->assertSee('120,000.00')           // your income this month
            ->assertSee('45,000.00')            // pending approval
            ->assertDontSee('987,654');

        $income = $this->actingAs($vendor)->get('/platform/income')->assertOk();
        $rows = collect($income->viewData('tenants'))->keyBy(fn ($r) => $r['tenant']->slug);
        $this->assertEquals(75000, $rows['test']['months'][$month]);
        $this->assertEquals(45000, $rows['cnc']['months'][$month]);
        $this->assertEquals(45000, $rows['cnc']['pending']);
        $this->assertEquals(120000, $income->viewData('monthTotals')[$month]);
        $income->assertDontSee('987,654');

        $this->actingAs($vendor)->get('/platform/tenants')->assertOk()->assertSee('75,000.00')->assertDontSee('987,654');
        $this->actingAs($vendor)->get("/platform/tenants/{$this->tenant->id}")->assertOk()->assertDontSee('987,654');
    }

    public function test_shop_users_cannot_use_the_admin_portal(): void
    {
        $owner = $this->userWithPermissions([], 'super_admin');

        $this->post('/platform/login', ['email' => $owner->email, 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->actingAs($owner)->get('/platform')->assertForbidden();
    }

    public function test_platform_admin_is_not_a_user_of_any_shop(): void
    {
        $this->platformAdmin();

        $this->post('/login', ['email' => 'vendor@example.test', 'password' => 'vendor-password'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_creating_a_shop_sets_up_roles_location_and_owner(): void
    {
        $vendor = $this->platformAdmin();

        $this->actingAs($vendor)->post('/platform/tenants', [
            'name' => 'Mzuzu Pharmacy', 'slug' => 'mzuzu', 'plan' => 'standard', 'paid_until' => now()->addMonth()->toDateString(),
            'grace_days' => 7, 'owner_name' => 'Chikondi', 'owner_email' => 'chikondi@mzuzu.test',
            'owner_password' => 'mzuzu-pass', 'owner_password_confirmation' => 'mzuzu-pass',
        ])->assertRedirect();

        $shop = Tenant::where('slug', 'mzuzu')->firstOrFail();
        $this->inShop($shop, function () {
            $this->assertEqualsCanonicalizing(['super_admin', 'admin', 'cashier', 'manager'], Role::pluck('name')->all());
            $owner = User::sole();
            $this->assertTrue($owner->hasRole('super_admin'));
            $this->assertTrue($owner->hasPermission('add_pos_sell'));
            $this->assertSame('Mzuzu Pharmacy', business_settings(true)['system_name']);
        });

        auth()->logout();
        $this->withoutDefaultShop();
        $this->post('/login', ['shop' => 'mzuzu', 'email' => 'chikondi@mzuzu.test', 'password' => 'mzuzu-pass'])
            ->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_reserved_taken_or_badly_formed_shop_codes_are_refused(): void
    {
        $vendor = $this->platformAdmin();
        $base = ['name' => 'X', 'grace_days' => 7, 'owner_name' => 'X', 'owner_email' => 'x@x.test',
            'owner_password' => 'password1', 'owner_password_confirmation' => 'password1'];

        foreach (['platform', 'sales', 'dashboard', 'test', 'Bad Name!'] as $slug) {
            $this->actingAs($vendor)->post('/platform/tenants', $base + ['slug' => $slug])->assertSessionHasErrors('slug');
        }
    }

    // --- Subscriptions -------------------------------------------------------

    public function test_suspended_shop_is_blocked_and_can_be_reactivated(): void
    {
        $vendor = $this->platformAdmin();
        $owner = $this->userWithPermissions([], 'super_admin');

        $this->actingAs($vendor)->post("/platform/tenants/{$this->tenant->id}/suspend", ['reason' => 'Unpaid invoice']);
        $this->actingAs($owner)->get('/dashboard')->assertStatus(402)->assertSee('Unpaid invoice');

        $this->actingAs($vendor)->post("/platform/tenants/{$this->tenant->id}/reactivate");
        $this->actingAs($owner)->get('/dashboard')->assertOk();
    }

    public function test_unpaid_shop_gets_a_warning_then_is_blocked_after_the_grace_period(): void
    {
        $owner = $this->userWithPermissions([], 'super_admin');

        $this->tenant->update(['paid_until' => now()->subDays(3)->toDateString(), 'grace_days' => 7]);
        $this->actingAs($owner)->get('/dashboard')->assertOk()->assertSee('Your subscription ended on');

        $this->tenant->update(['paid_until' => now()->subDays(10)->toDateString()]);
        $this->actingAs($owner)->get('/dashboard')->assertStatus(402);
    }

    public function test_recording_a_payment_extends_paid_until(): void
    {
        $vendor = $this->platformAdmin();
        $this->tenant->update(['paid_until' => now()->addDays(5)->toDateString()]);

        $this->actingAs($vendor)->post("/platform/tenants/{$this->tenant->id}/extend", ['months' => 1]);

        $this->assertSame(now()->addDays(5)->addMonthNoOverflow()->toDateString(), $this->tenant->fresh()->paid_until->toDateString());
    }

    // --- Billing (shop side) -------------------------------------------------

    public function test_shop_owner_sees_days_left_on_billing_page_and_in_the_sidebar(): void
    {
        $owner = $this->userWithPermissions([], 'super_admin');
        $this->tenant->update(['plan' => 'standard', 'paid_until' => now()->addDays(20)->toDateString()]);

        $this->actingAs($owner)->get('/billing')->assertOk()
            ->assertSee('days left')
            ->assertSee('Standard')
            ->assertSee(now()->addDays(20)->format('d M Y'));

        $this->actingAs($owner)->get('/dashboard')->assertSee('20d left');
    }

    public function test_cashiers_do_not_see_billing(): void
    {
        $this->actingAs($this->cashier())->get('/billing')->assertForbidden();
    }

    public function test_recorded_payment_appears_in_the_shops_billing_history(): void
    {
        $vendor = $this->platformAdmin();
        $owner = $this->userWithPermissions([], 'super_admin');
        $this->tenant->update(['plan' => 'basic', 'paid_until' => now()->addDays(5)->toDateString()]);

        $this->actingAs($vendor)->post("/platform/tenants/{$this->tenant->id}/extend",
            ['months' => 3, 'method' => 'Airtel Money', 'reference' => 'TX12345'])->assertRedirect();

        $payment = $this->tenant->payments()->sole();
        $this->assertEquals(75000, $payment->amount);                       // 3 x Basic 25,000
        $this->assertSame(now()->addDays(5)->toDateString(), $payment->period_from->toDateString());
        $this->assertSame(now()->addDays(5)->addMonthsNoOverflow(3)->toDateString(), $this->tenant->fresh()->paid_until->toDateString());

        $this->actingAs($owner)->get('/billing')->assertSee('TX12345')->assertSee('Airtel Money')->assertSee('75,000.00');
    }

    public function test_renewing_a_lapsed_shop_starts_from_today(): void
    {
        $vendor = $this->platformAdmin();
        $this->tenant->update(['paid_until' => now()->subDays(20)->toDateString()]);

        $this->actingAs($vendor)->post("/platform/tenants/{$this->tenant->id}/extend", ['months' => 1, 'amount' => 40000]);

        $this->assertSame(now()->addMonthNoOverflow()->toDateString(), $this->tenant->fresh()->paid_until->toDateString());
        $this->assertEquals(40000, $this->tenant->payments()->sole()->amount);
    }

    // --- Proof of payment ----------------------------------------------------

    private function sendProof(User $owner, array $overrides = [])
    {
        return $this->actingAs($owner)->post('/billing/proof', $overrides + [
            'months' => 3, 'amount' => 75000, 'account' => 'airtel_money', 'reference' => 'CI241008.1523.H12345',
            'paid_on' => now()->toDateString(), 'proof' => \Illuminate\Http\UploadedFile::fake()->image('airtel-sms.jpg'),
        ]);
    }

    public function test_billing_page_lists_where_to_pay(): void
    {
        $owner = $this->userWithPermissions([], 'super_admin');

        $this->actingAs($owner)->get('/billing')->assertOk()
            ->assertSee('TNM Mpamba')->assertSee('+265 882 446 802')
            ->assertSee('National Bank')->assertSee('1012249353')->assertSee('Rabecca Mulinda')
            ->assertSee('Airtel Money')->assertSee('0990 516 395')->assertSee('Daniel Pandor')
            ->assertSee('Upload proof of payment');
    }

    public function test_uploading_proof_stores_it_privately_and_emails_the_owner(): void
    {
        \Illuminate\Support\Facades\Storage::fake('local');
        \Illuminate\Support\Facades\Mail::fake();
        $this->tenant->update(['plan' => 'basic']);
        $owner = $this->userWithPermissions([], 'super_admin');

        $this->sendProof($owner)->assertRedirect(route('billing.index'));

        $submission = \App\Models\PaymentSubmission::sole();
        $this->assertSame('pending', $submission->status);
        $this->assertSame($this->tenant->id, $submission->tenant_id);
        \Illuminate\Support\Facades\Storage::disk('local')->assertExists($submission->proof_path);

        \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\PaymentProofSubmitted::class, function ($mail) use ($submission) {
            $html = $mail->render();
            return $mail->hasTo('beckymlinda@gmail.com')
                && $mail->submission->is($submission)
                && str_contains($html, 'Test Shop')
                && str_contains($html, '75,000.00')
                && str_contains($html, 'CI241008.1523.H12345')
                && str_contains($html, 'Airtel Money')
                && count($mail->attachments()) === 1;
        });

        $this->actingAs($owner)->get('/billing')->assertSee('Waiting for approval');
        $this->actingAs($owner)->get("/billing/proof/{$submission->id}")->assertOk();
    }

    private ?object $realMailer = null;

    /** Make the mailer refuse the first $failures sends like Gmail's "421 Server busy" */
    private function busyMailServer(int $failures): object
    {
        \App\Services\PaymentProofNotifier::$retryDelays = [0, 0];
        $this->realMailer = $this->app->make('mail.manager');
        $state = (object) ['failures' => $failures, 'sent' => 0];
        \Illuminate\Support\Facades\Mail::shouldReceive('to')->andReturnUsing(fn () => new class($state) {
            public function __construct(private object $state) {}
            public function send($mail): void
            {
                if ($this->state->failures-- > 0) {
                    throw new \RuntimeException('Expected response code "220" but got code "421": Server busy, try again later.');
                }
                $this->state->sent++;
            }
        });

        return $state;
    }

    public function test_proof_email_is_retried_when_the_mail_server_is_busy(): void
    {
        \Illuminate\Support\Facades\Storage::fake('local');
        $mail = $this->busyMailServer(2);
        $owner = $this->userWithPermissions([], 'super_admin');

        $this->sendProof($owner)->assertRedirect();

        $submission = \App\Models\PaymentSubmission::sole();
        $this->assertSame(1, $mail->sent);
        $this->assertNotNull($submission->notified_at);
        $this->assertSame(3, $submission->notify_attempts);
    }

    public function test_unsent_proof_email_is_kept_and_resent_later(): void
    {
        \Illuminate\Support\Facades\Storage::fake('local');
        $this->busyMailServer(99);
        $owner = $this->userWithPermissions([], 'super_admin');
        $vendor = $this->platformAdmin();

        $this->sendProof($owner)->assertRedirect();
        $submission = \App\Models\PaymentSubmission::sole();
        $this->assertNull($submission->notified_at);
        $this->assertStringContainsString('421', $submission->notify_error);
        $this->actingAs($vendor)->get('/platform/payments')->assertSee('Email not sent yet');

        // The mail server is fine again: the scheduled resend delivers it
        $this->app->instance('mail.manager', $this->realMailer);
        \Illuminate\Support\Facades\Mail::swap($this->realMailer);
        \Illuminate\Support\Facades\Mail::fake();
        $this->artisan('payments:resend-proof-emails')->assertSuccessful();

        \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\PaymentProofSubmitted::class);
        $this->assertNotNull($submission->fresh()->notified_at);
    }

    public function test_approving_proof_extends_the_subscription_and_unblocks_the_shop(): void
    {
        \Illuminate\Support\Facades\Storage::fake('local');
        \Illuminate\Support\Facades\Mail::fake();
        $vendor = $this->platformAdmin();
        $owner = $this->userWithPermissions([], 'super_admin');
        $this->tenant->update(['plan' => 'basic', 'paid_until' => now()->subDays(30)->toDateString(), 'grace_days' => 7]);

        // Blocked: the dashboard is locked but Billing still works, so they can pay
        $this->actingAs($owner)->get('/dashboard')->assertStatus(402)->assertSee('upload proof of payment');
        $this->actingAs($owner)->get('/billing')->assertOk();
        $this->sendProof($owner)->assertRedirect();
        $submission = \App\Models\PaymentSubmission::sole();

        $this->actingAs($vendor)->get('/platform/payments')->assertOk()->assertSee('Test Shop')->assertSee('CI241008.1523.H12345');
        $this->actingAs($vendor)->get("/platform/payments/{$submission->id}/proof")->assertOk();
        $this->actingAs($vendor)->post("/platform/payments/{$submission->id}/approve", ['months' => 3, 'amount' => 75000])->assertRedirect();

        $this->assertSame('approved', $submission->fresh()->status);
        $this->assertSame(now()->addMonthsNoOverflow(3)->toDateString(), $this->tenant->fresh()->paid_until->toDateString());
        $payment = $this->tenant->payments()->sole();
        $this->assertSame('Airtel Money', $payment->method);
        $this->assertSame('CI241008.1523.H12345', $payment->reference);

        $this->actingAs($owner)->get('/dashboard')->assertOk();
        $this->actingAs($owner)->get('/billing')->assertSee('Approved');

        // Cannot be approved twice
        $this->actingAs($vendor)->post("/platform/payments/{$submission->id}/approve", ['months' => 3, 'amount' => 75000])->assertStatus(409);
        $this->assertSame(1, $this->tenant->payments()->count());
    }

    public function test_rejected_proof_shows_the_reason_to_the_shop(): void
    {
        \Illuminate\Support\Facades\Storage::fake('local');
        \Illuminate\Support\Facades\Mail::fake();
        $vendor = $this->platformAdmin();
        $owner = $this->userWithPermissions([], 'super_admin');
        $this->sendProof($owner);
        $submission = \App\Models\PaymentSubmission::sole();

        $this->actingAs($vendor)->post("/platform/payments/{$submission->id}/reject", ['review_note' => 'Not received on Airtel Money'])->assertRedirect();

        $this->actingAs($owner)->get('/billing')->assertSee('Not approved')->assertSee('Not received on Airtel Money');
        $this->assertSame(0, $this->tenant->payments()->count());
    }

    public function test_proof_upload_rules(): void
    {
        \Illuminate\Support\Facades\Storage::fake('local');
        \Illuminate\Support\Facades\Mail::fake();
        $owner = $this->userWithPermissions([], 'super_admin');

        $this->sendProof($owner, ['proof' => \Illuminate\Http\UploadedFile::fake()->create('virus.exe', 10)])->assertSessionHasErrors('proof');
        $this->sendProof($owner, ['account' => 'bitcoin'])->assertSessionHasErrors('account');
        $this->sendProof($owner, ['paid_on' => now()->addDay()->toDateString()])->assertSessionHasErrors('paid_on');
        $this->actingAs($this->cashier())->post('/billing/proof', [])->assertForbidden();

        $this->assertSame(0, \App\Models\PaymentSubmission::count());
        \Illuminate\Support\Facades\Mail::assertNothingSent();
    }

    public function test_a_shop_cannot_open_another_shops_proof(): void
    {
        \Illuminate\Support\Facades\Storage::fake('local');
        \Illuminate\Support\Facades\Mail::fake();
        $other = $this->otherShop();
        $theirs = $other->paymentSubmissions()->create([
            'months' => 1, 'amount' => 1, 'account' => 'mpamba', 'paid_on' => now(), 'proof_path' => 'payment-proofs/x.jpg', 'status' => 'pending',
        ]);
        \Illuminate\Support\Facades\Storage::disk('local')->put('payment-proofs/x.jpg', 'secret');

        $owner = $this->userWithPermissions([], 'super_admin');
        $this->actingAs($owner)->get("/billing/proof/{$theirs->id}")->assertNotFound();
    }

    // --- Support login -------------------------------------------------------

    public function test_log_in_as_shop_owner_and_back_to_the_portal(): void
    {
        $vendor = $this->platformAdmin();
        $owner = $this->userWithPermissions([], 'super_admin');
        Role::where('name', 'super_admin')->update(['is_superadmin' => true]);

        $this->actingAs($vendor)->post("/platform/tenants/{$this->tenant->id}/impersonate")
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('tenant_id', $this->tenant->id)
            ->assertSessionHas('impersonated_by');
        $this->assertAuthenticatedAs($owner);

        $session = ['tenant_id' => $this->tenant->id, 'impersonated_by' => 'Vendor (vendor@example.test)'];
        $this->withSession($session)->get('/dashboard')->assertSee('Support session');

        $this->withSession($session)->post('/logout')->assertRedirect(route('platform.login'));
    }

    public function test_platform_admin_can_change_a_shop_owners_password(): void
    {
        $vendor = $this->platformAdmin();
        $other = $this->otherShop();
        $owner = $this->inShop($other, fn () => User::first());
        $url = "/platform/tenants/{$other->id}/users/{$owner->id}/password";

        $this->actingAs($vendor)->get("/platform/tenants/{$other->id}")->assertOk()->assertSee('Change password');

        $this->actingAs($vendor)->put($url, ['password' => 'short', 'password_confirmation' => 'short'])
            ->assertSessionHasErrors('password', null, "password_{$owner->id}");

        $this->actingAs($vendor)->put($url, ['password' => 'brand-new-pass', 'password_confirmation' => 'brand-new-pass'])
            ->assertSessionHas('success');
        $this->assertTrue(\Hash::check('brand-new-pass', $owner->fresh()->password));
        $this->assertTrue($this->inShop($other, fn () => \App\Models\ActivityLog::where('action', 'password_reset')->exists()));

        // The account must belong to the shop in the address
        $this->actingAs($vendor)->put("/platform/tenants/{$this->tenant->id}/users/{$owner->id}/password",
            ['password' => 'brand-new-pass', 'password_confirmation' => 'brand-new-pass'])->assertNotFound();
    }

    public function test_shop_users_cannot_change_passwords_through_the_portal(): void
    {
        $other = $this->otherShop();
        $owner = $this->inShop($other, fn () => User::first());
        $shopOwner = $this->userWithPermissions([], 'super_admin');

        $this->actingAs($shopOwner)->put("/platform/tenants/{$other->id}/users/{$owner->id}/password",
            ['password' => 'brand-new-pass', 'password_confirmation' => 'brand-new-pass']);
        $this->assertTrue(\Hash::check('secret-password', $owner->fresh()->password));
    }

    // --- Setup ---------------------------------------------------------------

    public function test_setup_page_creates_the_first_platform_admin_once(): void
    {
        $url = '/setup/admin?token=test-setup-token-0123456789';
        $data = ['name' => 'Vendor', 'email' => 'vendor@example.test', 'password' => 'long-password', 'password_confirmation' => 'long-password'];

        $this->post($url, $data)->assertRedirect();
        $this->assertTrue(User::withoutTenantScope()->where('email', 'vendor@example.test')->whereNull('tenant_id')->value('is_platform_admin'));

        $this->post($url, ['email' => 'second@example.test'] + $data)->assertStatus(409);
    }
}
