<?php

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Webkul\Customer\Models\Customer;

// ============================================================================
// Phone Customer Administration
// ============================================================================

it('creates a customer with phone and password without requiring email or surnames', function () {
    Mail::fake();
    $this->loginAsAdmin();

    $this->postJson(route('admin.customers.customers.store'), [
        'first_name' => 'Cliente de tienda',
        'phone' => '+52 (492) 123-4567',
        'has_whatsapp' => 1,
        'channel_id' => core()->getCurrentChannel()->id,
        'password' => 'storefront-password',
        'password_confirmation' => 'storefront-password',
    ])->assertOk();

    $customer = Customer::query()->where('phone', '+524921234567')->firstOrFail();

    expect($customer->email)->toBeNull()
        ->and($customer->has_whatsapp)->toBeTrue()
        ->and(Hash::check('storefront-password', $customer->password))->toBeTrue();
});

it('updates a phone-only customer and allows an authorized administrator to reset their password', function () {
    Mail::fake();
    $customer = Customer::factory()->create([
        'phone' => '+524921234567',
        'last_name' => '',
        'email' => null,
    ]);
    $this->loginAsAdmin();

    $this->putJson(route('admin.customers.customers.update', $customer->id), [
        'first_name' => 'Cliente',
        'phone' => '492 123 4567',
        'has_whatsapp' => 1,
        'status' => 1,
        'password' => 'replacement-password',
        'password_confirmation' => 'replacement-password',
    ])->assertOk();

    expect($customer->fresh()->has_whatsapp)->toBeTrue()
        ->and($customer->fresh()->email)->toBeNull()
        ->and(Hash::check('replacement-password', $customer->fresh()->password))->toBeTrue();
});
