<?php

namespace Tests\Feature;

use App\Models\NotificationLog;
use App\Models\SmsRecipient;
use App\Services\Sms\TumaSend;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\CreatesPosData;
use Tests\TestCase;

class ZReportSmsTest extends TestCase
{
    use RefreshDatabase, CreatesPosData;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        config(['services.tumasend.key' => 'test-key', 'services.tumasend.from' => 'TumaSend']);
    }

    private function fakeTumaSend(int $status = 201, array $body = ['success' => true, 'invalid_recipients' => [], 'credits_remaining' => 98]): void
    {
        Http::fake(['gateway.tumasend.com/*' => Http::response($body, $status)]);
    }

    public function test_phone_numbers_are_stored_in_international_form(): void
    {
        $this->assertSame('+265991234567', TumaSend::normalizePhone('0991 234 567'));
        $this->assertSame('+265881234567', TumaSend::normalizePhone('265-881-234-567'));
        $this->assertSame('+265881234567', TumaSend::normalizePhone('+265 881 234 567'));
        $this->assertSame('+265991234567', TumaSend::normalizePhone('991234567'));
        $this->assertSame('+27821234567', TumaSend::normalizePhone('+27 82 123 4567'));
        $this->assertNull(TumaSend::normalizePhone('12345'));
        $this->assertNull(TumaSend::normalizePhone('0123 456 789'));
    }

    public function test_notifications_page_prompts_for_a_phone_number(): void
    {
        $admin = $this->userWithPermissions([], 'admin');
        \App\Models\Setting::create(['key' => 'telephone', 'value' => '+265 991 234 567']);

        // Offers the pharmacy's own number, shown the local way
        $this->actingAs($admin)->get('/notifications')->assertOk()
            ->assertSee('Get the Z report on your phone')
            ->assertSee('Use the pharmacy number 0991 234 567');
    }

    public function test_admin_adds_pauses_and_removes_a_phone_number(): void
    {
        $admin = $this->userWithPermissions([], 'admin');

        $this->actingAs($admin)->post('/notifications/phones', ['phone' => '0991 234 567', 'name' => 'Owner'])->assertSessionHasNoErrors();
        $phone = SmsRecipient::firstOrFail();
        $this->assertSame('+265991234567', $phone->phone);

        // The same number typed differently is a duplicate
        $this->actingAs($admin)->post('/notifications/phones', ['phone' => '+265991234567'])->assertSessionHasErrors('phone');
        $this->actingAs($admin)->post('/notifications/phones', ['phone' => 'abc'])->assertSessionHasErrors('phone');

        $this->actingAs($admin)->get('/notifications')->assertSee('0991 234 567')->assertDontSee('Get the Z report on your phone');

        $this->actingAs($admin)->patch("/notifications/phones/{$phone->id}");
        $this->assertFalse($phone->fresh()->is_active);

        $this->actingAs($admin)->delete("/notifications/phones/{$phone->id}");
        $this->assertSame(0, SmsRecipient::count());
    }

    public function test_cashiers_cannot_manage_phone_numbers(): void
    {
        $cashier = $this->userWithPermissions(['add_pos_sell'], 'cashier');

        $this->actingAs($cashier)->post('/notifications/phones', ['phone' => '0991234567'])->assertForbidden();
        $this->assertSame(0, SmsRecipient::count());
    }

    public function test_closing_the_register_texts_the_z_report(): void
    {
        $this->fakeTumaSend();
        $cashier = $this->userWithPermissions(['add_pos_sell', 'view_pos_sell', 'close_cash_register'], 'cashier');
        $register = $this->openRegisterFor($cashier);
        SmsRecipient::create(['phone' => '+265991234567']);
        SmsRecipient::create(['phone' => '+265881234567', 'is_active' => false]);

        $this->actingAs($cashier)->patch("/pos/register/{$register->id}", ['counted_cash' => 0])->assertRedirect(route('dashboard'));
        // Runs after the response in the app; run it here
        dispatch_sync(new \App\Jobs\SendZReportEmail($register->id, $cashier->id));

        Http::assertSent(function (Request $request) {
            return $request->url() === 'https://gateway.tumasend.com/api/v1/send/sms'
                && $request->hasHeader('x-api-key', 'test-key')
                && $request['from'] === 'TumaSend'
                && $request['recipients'] === ['+265991234567']   // paused number left out
                && str_contains($request['message'], 'Z' . \App\Models\Register::max('id') . ' ')
                && str_contains($request['message'], 'Total MWK ')
                && !str_contains($request['message'], 'Drawer')
                && mb_strlen($request['message']) <= 160;
        });
        $this->assertSame('sent', NotificationLog::where('type', 'z_report_sms')->latest('id')->value('status'));
    }

    public function test_sms_text_is_one_sms_with_each_method_and_the_total(): void
    {
        $register = new \App\Models\Register(['opening_cash' => 0]);
        $register->id = 32;
        $z = fn (array $payments, string $name = 'Area 25 Pharmacy') => [
            'business' => ['name' => $name], 'register' => $register, 'closed_at' => now()->setDate(2026, 10, 8),
            'sales_count' => 12, 'gross_sales' => array_sum(array_column($payments, 'amount')), 'payments' => $payments,
        ];
        $notifier = app(\App\Services\ZReportNotifier::class);

        $text = $notifier->smsText($z([
            'cash' => ['label' => 'Cash', 'amount' => 30000], 'airtel_money' => ['label' => 'Airtel Money', 'amount' => 10000],
            'mpamba' => ['label' => 'TNM Mpamba', 'amount' => 5000],
        ]));
        $this->assertSame("Area 25 Pharmacy Z32 08/10\nCash 30,000\nAirtel 10,000\nMpamba 5,000\nTotal MWK 45,000 (12 sales)", $text);

        // Every method, big amounts and a long accented name still fit in one SMS
        $all = [];
        foreach (['cash', 'airtel_money', 'mpamba', 'card', 'bank_transfer', 'national_bank', 'standard_bank', 'cheques', 'advance', 'cash_and_card'] as $i => $key) {
            $all[$key] = ['label' => $key, 'amount' => 12345678 - $i];
        }
        $text = $notifier->smsText($z($all, 'Pharmacie Générale de Lilongwe Area 25 et Kanengo Branch'));
        $this->assertLessThanOrEqual(160, mb_strlen($text));
        $this->assertMatchesRegularExpression('/^[\x20-\x7E\n]+$/', $text);   // plain characters only
        $this->assertStringContainsString('Other ', $text);
        $this->assertStringEndsWith('(12 sales)', $text);
    }

    public function test_clear_all_empties_only_this_shops_sent_list(): void
    {
        $admin = $this->userWithPermissions([], 'admin');
        NotificationLog::create(['type' => 'z_report', 'recipients' => 'a@b.c', 'status' => 'sent']);
        NotificationLog::create(['type' => 'z_report_sms', 'recipients' => '+265991234567', 'status' => 'failed']);
        $other = \App\Models\Tenant::create(['name' => 'Other Shop', 'slug' => 'other-shop']);
        with_tenant($other, fn () => NotificationLog::create(['type' => 'z_report', 'recipients' => 'x@y.z', 'status' => 'sent']));

        $this->actingAs($admin)->get('/notifications')->assertSee('Clear all');
        $this->actingAs($admin)->delete('/notifications/logs')->assertSessionHas('success', 'Cleared 2 sent entries.');

        $this->assertSame(0, NotificationLog::count());
        $this->assertSame(1, NotificationLog::withoutGlobalScopes()->count());
    }

    public function test_sent_history_older_than_30_days_is_pruned(): void
    {
        $old = NotificationLog::create(['type' => 'z_report', 'recipients' => 'a@b.c', 'status' => 'sent']);
        $old->forceFill(['created_at' => now()->subDays(31)])->save();
        NotificationLog::create(['type' => 'z_report', 'recipients' => 'a@b.c', 'status' => 'sent']);

        $this->artisan('model:prune', ['--model' => [NotificationLog::class]])->assertSuccessful();

        $this->assertSame(1, NotificationLog::withoutGlobalScopes()->count());
    }

    public function test_no_sms_is_sent_without_phone_numbers(): void
    {
        $this->fakeTumaSend();
        $cashier = $this->userWithPermissions(['add_pos_sell', 'view_pos_sell', 'close_cash_register'], 'cashier');
        $register = $this->openRegisterFor($cashier);

        $this->actingAs($cashier)->patch("/pos/register/{$register->id}", ['counted_cash' => 0]);
        dispatch_sync(new \App\Jobs\SendZReportEmail($register->id, $cashier->id));

        Http::assertNothingSent();
        $this->assertSame(0, NotificationLog::where('type', 'z_report_sms')->count());
    }

    public function test_out_of_credits_is_logged_with_a_clear_reason(): void
    {
        $this->fakeTumaSend(402, ['error' => 'insufficient_credits', 'message' => 'Not enough SMS credits']);
        $admin = $this->userWithPermissions([], 'admin');
        $this->openRegisterFor($admin);
        SmsRecipient::create(['phone' => '+265991234567']);

        $this->actingAs($admin)->post('/notifications/z-report/sms')
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'Not enough SMS credits'));
        $this->assertSame('failed', NotificationLog::where('type', 'z_report_sms')->value('status'));
    }
}
