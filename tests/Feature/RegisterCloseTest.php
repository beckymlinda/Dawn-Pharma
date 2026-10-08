<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesPosData;
use Tests\TestCase;

class RegisterCloseTest extends TestCase
{
    use RefreshDatabase, CreatesPosData;

    private function cashierWithRegister()
    {
        $cashier = $this->userWithPermissions(['add_pos_sell', 'view_pos_sell', 'close_cash_register'], 'cashier');
        $register = $this->openRegisterFor($cashier);

        return [$cashier, $register];
    }

    public function test_close_page_shows_count_and_close_without_a_popup(): void
    {
        [$cashier] = $this->cashierWithRegister();

        $this->actingAs($cashier)->get('/pos/register/close')->assertOk()
            ->assertSee('Count &amp; close', false)
            ->assertSee('id="closeRegisterForm"', false)
            ->assertDontSee('confirmCloseModal');
    }

    public function test_closing_goes_to_the_dashboard(): void
    {
        [$cashier, $register] = $this->cashierWithRegister();
        Setting::create(['key' => 'lp_blind_close', 'value' => '1']);

        $this->actingAs($cashier)->patch("/pos/register/{$register->id}", ['counted_cash' => 0])
            ->assertRedirect(route('dashboard'));

        $this->assertNotNull($register->fresh()->closed_at);
    }

    public function test_close_page_after_closing_does_not_redirect_to_itself(): void
    {
        [$cashier, $register] = $this->cashierWithRegister();
        $register->update(['closed_at' => now()]);

        // The browser's previous page is the close page itself (the old loop)
        $this->actingAs($cashier)
            ->from('/pos/register/close')
            ->get('/pos/register/close')
            ->assertRedirect(route('register.create'));
    }

    public function test_pressing_close_twice_is_harmless(): void
    {
        [$cashier, $register] = $this->cashierWithRegister();

        $this->actingAs($cashier)->patch("/pos/register/{$register->id}", ['counted_cash' => 0])->assertRedirect(route('dashboard'));
        $closedAt = $register->fresh()->closed_at;

        $this->actingAs($cashier)->from("/pos/register/close")
            ->patch("/pos/register/{$register->id}", ['counted_cash' => 0])
            ->assertRedirect(route('dashboard'));
        $this->assertEquals($closedAt, $register->fresh()->closed_at);
    }
}
