<?php

use Illuminate\Support\Facades\Mail;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as GoogleIdentity;
use Webkul\Customer\Models\Customer;
use Webkul\Shop\Support\PhoneNumberNormalizer;

// ============================================================================
// Public Shopping
// ============================================================================

it('shows the catalog directly after the carousel without requiring an account', function () {
    $this->get(route('shop.home.index'))
        ->assertOk()
        ->assertSeeInOrder(['<v-carousel', '<v-search'], false)
        ->assertSeeText(trans('storefront.catalog'));
});

it('renders registration fields without leaving uncompiled Blade components', function () {
    $this->get(route('shop.customers.register.index'))
        ->assertOk()
        ->assertSee('name="first_name"', false)
        ->assertSee('name="phone_number"', false)
        ->assertDontSee('<x-shop::', false);
});

it('renders a telephone input in the sign-in form', function () {
    $this->get(route('shop.customer.session.index'))
        ->assertOk()
        ->assertSee('type="tel"', false)
        ->assertSee('name="phone"', false);
});

it('keeps guest items when a phone-only customer registers and continues to checkout', function () {
    Mail::fake();

    $product = $this->createSimpleProduct(['price' => ['float_value' => 100]]);
    $this->addProductToCart($product->id, 2)->assertOk();

    $this->get(route('shop.checkout.onepage.index'))
        ->assertRedirect(route('shop.customer.session.index'));

    $this->post(route('shop.customers.register.store'), [
        'first_name' => 'María',
        'phone_country' => 'MX',
        'phone_number' => '492 123 4567',
        'has_whatsapp' => 1,
        'password' => 'storefront-password',
        'password_confirmation' => 'storefront-password',
    ])->assertSessionHasNoErrors()->assertRedirect(route('shop.checkout.onepage.index'));

    $customer = auth('customer')->user();

    expect($customer->phone)->toBe('+524921234567')
        ->and($customer->email)->toBeNull()
        ->and($customer->has_whatsapp)->toBeTrue();

    $this->getJson(route('shop.api.checkout.cart.index'))
        ->assertOk()
        ->assertJsonPath('data.items_qty', 2)
        ->assertJsonPath('data.is_guest', false);
});

it('requires authentication even when guest checkout was enabled in admin settings', function () {
    $this->setConfig('sales.checkout.shopping_cart.allow_guest_checkout', 1);
    $product = $this->createSimpleProduct();
    $this->addProductToCart($product->id)->assertOk();

    $this->get(route('shop.checkout.onepage.index'))->assertRedirect(route('shop.customer.session.index'));
    $this->postJson(route('shop.checkout.onepage.orders.store'))
        ->assertOk()
        ->assertJsonPath('data.redirect_url', route('shop.customer.session.index'));
});

// ============================================================================
// Phone Authentication
// ============================================================================

it('normalizes national and international pasted phone numbers', function (mixed $input, ?string $expected) {
    expect(PhoneNumberNormalizer::normalize($input))->toBe($expected);
})->with([
    ['492 123 4567', '+524921234567'],
    ['+52 (492) 123-4567', '+524921234567'],
    ['0052 492 123 4567', '+524921234567'],
    ['+1 (202) 555-0123', '+12025550123'],
    ['492ABC1234567', null],
    [['4921234567'], null],
]);

it('logs in using the canonical phone number and rejects the wrong password', function () {
    $customer = Customer::factory()->create([
        'phone' => '+524921234567',
        'password' => bcrypt('storefront-password'),
        'status' => 1,
        'is_verified' => 1,
        'channel_id' => core()->getCurrentChannel()->id,
    ]);

    $this->post(route('shop.customer.session.create'), [
        'phone' => '492 123 4567',
        'password' => 'wrong-password',
    ])->assertSessionHas('error');

    $this->assertGuest('customer');

    $this->post(route('shop.customer.session.create'), [
        'phone' => '+52 (492) 123-4567',
        'password' => 'storefront-password',
    ])->assertSessionHasNoErrors();

    $this->assertAuthenticatedAs($customer, 'customer');
});

it('rejects duplicate normalized phone numbers at registration', function () {
    Customer::factory()->create(['phone' => '+524921234567']);

    $this->postJson(route('shop.customers.register.store'), [
        'first_name' => 'Otra cliente',
        'phone_country' => 'MX',
        'phone_number' => '+52 492 123 4567',
        'password' => 'storefront-password',
        'password_confirmation' => 'storefront-password',
    ])->assertUnprocessable()->assertJsonValidationErrors('phone');
});

it('validates malicious registration fields without raising a server error', function () {
    $this->postJson(route('shop.customers.register.store'), [
        'phone_country' => ['MX'],
        'phone_number' => ['4921234567'],
        'email' => ['example@example.test'],
    ])->assertUnprocessable();
});

// ============================================================================
// Google Completion
// ============================================================================

it('refuses providers other than Google', function (string $provider) {
    $this->get(route('customer.social_login.index', $provider))->assertNotFound();
    $this->get(route('customer.social_login.callback', $provider))->assertNotFound();
})->with(['facebook', 'github', 'linkedin-openid', 'twitter']);

it('requests a phone after Google verifies a new identity and creates one linked customer', function () {
    Mail::fake();

    $this->setConfig([
        'customer.settings.social_login.enable_google' => 1,
        'customer.settings.social_login.google_client_id' => 'test-client',
        'customer.settings.social_login.google_client_secret' => 'test-secret',
    ]);

    $identity = (new GoogleIdentity)->setRaw(['email_verified' => true])->map([
        'id' => 'google-storefront-test',
        'name' => 'María',
        'email' => 'google@example.test',
    ]);

    Socialite::shouldReceive('driver')->with('google')->once()->andReturnSelf();
    Socialite::shouldReceive('user')->once()->andReturn($identity);

    $this->get(route('customer.social_login.callback', 'google'))
        ->assertRedirect(route('shop.customers.register.index'));

    $this->assertGuest('customer');

    $this->post(route('shop.customers.register.store'), [
        'phone_country' => 'MX',
        'phone_number' => '492 123 4567',
    ])->assertSessionHasNoErrors()->assertRedirect(route('shop.home.index'));

    $this->assertAuthenticated('customer');
    $this->assertDatabaseHas('customer_social_accounts', [
        'customer_id' => auth('customer')->id(),
        'provider_id' => 'google-storefront-test',
        'provider_name' => 'google',
    ]);
});

// ============================================================================
// Account And Checkout Integration
// ============================================================================

it('preserves an optional email and WhatsApp choice when editing a phone-only profile', function () {
    $customer = Customer::factory()->create([
        'phone' => '+524921234567',
        'email' => null,
        'last_name' => '',
    ]);
    $this->actingAs($customer, 'customer');

    $this->post(route('shop.customers.account.profile.update'), [
        'first_name' => 'María',
        'phone' => '+52 (492) 123-4567',
        'has_whatsapp' => 1,
    ])->assertSessionHasNoErrors()->assertRedirect(route('shop.customers.account.profile.index'));

    expect($customer->fresh()->has_whatsapp)->toBeTrue()
        ->and($customer->fresh()->email)->toBeNull();
});

it('places an order for an identified customer without requiring an email', function () {
    Mail::fake();

    $customer = Customer::factory()->create([
        'phone' => '+524921234567',
        'email' => null,
        'last_name' => '',
    ]);
    $this->actingAs($customer, 'customer');

    $product = $this->createSimpleProduct();
    $this->prepareCartForCheckout($product, [
        'first_name' => $customer->first_name,
        'last_name' => '',
        'email' => null,
        'phone' => $customer->phone,
    ]);

    $this->postJson(route('shop.checkout.onepage.orders.store'))
        ->assertOk()
        ->assertJsonPath('data.redirect_url', route('shop.checkout.onepage.success'));

    $this->assertDatabaseHas('orders', [
        'customer_id' => $customer->id,
        'customer_email' => null,
        'is_guest' => 0,
    ]);
});

it('throttles the same phone even when formatting changes', function () {
    Customer::factory()->create([
        'phone' => '+524921234567',
        'password' => bcrypt('storefront-password'),
        'channel_id' => core()->getCurrentChannel()->id,
    ]);

    foreach (range(1, 8) as $attempt) {
        $response = $this->post(route('shop.customer.session.create'), [
            'phone' => $attempt % 2 ? '492 123 4567' : '+52 (492) 123-4567',
            'password' => 'wrong-password',
        ]);
    }

    $response->assertSessionHasErrors('phone');
    $this->assertGuest('customer');
});

// ============================================================================
// Optional Email Uniqueness
// ============================================================================

it('allows separate accounts to submit an empty email from a browser form', function () {
    Mail::fake();

    foreach (['4921234568', '4921234569'] as $phone) {
        auth('customer')->logout();

        $this->post(route('shop.customers.register.store'), [
            'first_name' => 'Cliente',
            'phone_country' => 'MX',
            'phone_number' => $phone,
            'email' => '',
            'password' => 'storefront-password',
            'password_confirmation' => 'storefront-password',
        ])->assertSessionHasNoErrors()->assertRedirect(route('shop.home.index'));
    }

    expect(Customer::query()->whereIn('phone', ['+524921234568', '+524921234569'])->whereNull('email')->count())->toBe(2);
});

// ============================================================================
// Cart Login API
// ============================================================================

it('authenticates the cart login modal with a phone number', function () {
    $customer = Customer::factory()->create([
        'phone' => '+524921234567',
        'password' => bcrypt('storefront-password'),
        'status' => 1,
        'is_verified' => 1,
        'channel_id' => core()->getCurrentChannel()->id,
    ]);

    $this->postJson(route('shop.api.customers.session.create'), [
        'phone' => '492 123 4567',
        'password' => 'storefront-password',
    ])->assertOk();

    $this->assertAuthenticatedAs($customer, 'customer');
});
