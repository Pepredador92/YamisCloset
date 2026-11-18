<?php

namespace Webkul\SocialLogin\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Event;
use Laravel\Socialite\Facades\Socialite;
use Webkul\SocialLogin\Repositories\CustomerSocialAccountRepository;

class LoginController extends Controller
{
    use DispatchesJobs, ValidatesRequests;

    /**
     * The providers a customer may sign in with, each keyed to the setting that enables it.
     */
    public const PROVIDERS = [
        'google' => 'enable_google',
    ];

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(protected CustomerSocialAccountRepository $customerSocialAccountRepository) {}

    /**
     * Redirects to the social provider.
     *
     * @param  string  $provider
     * @return Response
     */
    public function redirectToProvider($provider)
    {
        $this->abortUnlessEnabled($provider);

        try {
            return Socialite::driver($provider)->redirect();
        } catch (\Exception $e) {
            report($e);

            session()->flash('error', trans('storefront.google-unavailable'));

            return redirect()->route('shop.customer.session.index');
        }
    }

    /**
     * Sign in the customer the provider identifies, unless their email belongs to another account or they are inactive.
     *
     * @param  string  $provider
     * @return Response
     */
    public function handleProviderCallback($provider)
    {
        $this->abortUnlessEnabled($provider);

        try {
            $user = Socialite::driver($provider)->user();
        } catch (\Exception $e) {
            return redirect()->route('shop.customer.session.index');
        }

        if (
            ! $user->getEmail()
            || ! filter_var($user->user['email_verified'] ?? $user->user['verified_email'] ?? false, FILTER_VALIDATE_BOOLEAN)
        ) {
            session()->flash('error', trans('storefront.google-unavailable'));

            return redirect()->route('shop.customer.session.index');
        }

        $channelId = core()->getCurrentChannel()->id;

        $customer = $this->customerSocialAccountRepository->findCustomerByProvider(
            $user->getId(),
            $provider,
            $channelId
        );

        if ($signedIn = auth()->guard('customer')->user()) {
            if (
                ($customer && $customer->id !== $signedIn->id)
                || ($signedIn->email && strcasecmp($signedIn->email, $user->getEmail()) !== 0)
                || (! $signedIn->email && $this->customerSocialAccountRepository->emailIsTaken($user->getEmail(), $channelId))
            ) {
                session()->flash('error', trans('storefront.google-link-conflict'));

                return redirect()->route('shop.customers.account.profile.index');
            }

            if (! $customer) {
                $this->customerSocialAccountRepository->create([
                    'customer_id' => $signedIn->id,
                    'provider_id' => (string) $user->getId(),
                    'provider_name' => $provider,
                ]);
            }

            return redirect()->route('shop.customers.account.profile.index');
        }

        if (! $customer) {
            if ($this->customerSocialAccountRepository->emailIsTaken($user->getEmail(), $channelId)) {
                session()->flash('error', trans('storefront.google-link-conflict'));

                return redirect()->route('shop.customer.session.index');
            }

            $names = $this->customerSocialAccountRepository->getFirstLastName($user->getName() ?? '');

            session(['social_registration' => [
                'provider' => $provider,
                'provider_id' => (string) $user->getId(),
                'email' => $user->getEmail(),
                'first_name' => $names['first_name'],
                'last_name' => $names['last_name'],
                'expires_at' => now()->addMinutes(20)->timestamp,
            ]]);

            return redirect()->route('shop.customers.register.index');
        }

        if (! $customer->status) {
            session()->flash('warning', trans('shop::app.customers.login-form.not-activated'));

            return redirect()->route('shop.customer.session.index');
        }

        auth()->guard('customer')->login($customer);

        session()->regenerate();

        Event::dispatch('customer.after.login', $customer);

        if (! $customer->phone) {
            return redirect()->route('shop.customers.account.profile.edit');
        }

        if ($intended = session()->pull('shop.url.intended')) {
            return redirect()->to($intended);
        }

        return redirect()->route('shop.customers.account.profile.index');
    }

    /**
     * Whether both Google OAuth credentials have been configured for this channel.
     */
    public static function googleIsConfigured(): bool
    {
        return (bool) core()->getConfigData('customer.settings.social_login.enable_google')
            && filled(core()->getConfigData('customer.settings.social_login.google_client_id') ?: config('services.google.client_id'))
            && filled(core()->getConfigData('customer.settings.social_login.google_client_secret') ?: config('services.google.client_secret'));
    }

    /**
     * Abort unless the provider is one a customer may sign in with and the store has enabled it.
     */
    protected function abortUnlessEnabled(string $provider): void
    {
        if (
            ! isset(self::PROVIDERS[$provider])
            || ! self::googleIsConfigured()
        ) {
            abort(404);
        }
    }
}
