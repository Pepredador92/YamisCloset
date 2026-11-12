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
        'facebook' => 'enable_facebook',
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
            return Socialite::driver($provider)->redirect('shop.customers.account.profile.index');
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());

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

        $channelId = core()->getCurrentChannel()->id;

        $customer = $this->customerSocialAccountRepository->findCustomerByProvider(
            $user->getId(),
            $provider,
            $channelId
        );

        if (! $customer) {
            if ($this->customerSocialAccountRepository->emailIsTaken($user->getEmail(), $channelId)) {
                session()->flash('error', trans('shop::app.customers.login-form.social-account-exists'));

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

        Event::dispatch('customer.after.login', $customer);

        if ($intended = session()->pull('shop.url.intended')) {
            return redirect()->to($intended);
        }

        return redirect()->route('shop.customers.account.profile.index');
    }

    /**
     * Abort unless the provider is one a customer may sign in with and the store has enabled it.
     */
    protected function abortUnlessEnabled(string $provider): void
    {
        if (
            ! isset(self::PROVIDERS[$provider])
            || ! core()->getConfigData('customer.settings.social_login.'.self::PROVIDERS[$provider])
        ) {
            abort(404);
        }
    }
}
