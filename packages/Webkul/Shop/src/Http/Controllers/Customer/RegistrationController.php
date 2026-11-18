<?php

namespace Webkul\Shop\Http\Controllers\Customer;

use Cookie;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\View\View;
use libphonenumber\PhoneNumberUtil;
use Webkul\Core\Repositories\SubscribersListRepository;
use Webkul\Customer\Repositories\CustomerGroupRepository;
use Webkul\Customer\Repositories\CustomerRepository;
use Webkul\Shop\Http\Controllers\Controller;
use Webkul\Shop\Http\Requests\Customer\RegistrationRequest;
use Webkul\Shop\Mail\Customer\EmailVerificationNotification;
use Webkul\Shop\Mail\Customer\RegistrationNotification;
use Webkul\SocialLogin\Repositories\CustomerSocialAccountRepository;

class RegistrationController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        protected CustomerRepository $customerRepository,
        protected CustomerGroupRepository $customerGroupRepository,
        protected SubscribersListRepository $subscriptionRepository,
        protected CustomerSocialAccountRepository $customerSocialAccountRepository
    ) {}

    /**
     * Opens up the user's sign up form.
     *
     * @return View
     */
    public function index()
    {
        $socialRegistration = session('social_registration');

        if ($socialRegistration && ($socialRegistration['expires_at'] ?? 0) < now()->timestamp) {
            session()->forget('social_registration');

            $socialRegistration = null;
        }

        $phoneUtil = PhoneNumberUtil::getInstance();
        $supportedRegions = array_fill_keys($phoneUtil->getSupportedRegions(), true);

        $phoneCountries = core()->countries()
            ->filter(fn ($country) => isset($supportedRegions[strtoupper($country->code)]))
            ->map(function ($country) use ($phoneUtil) {
                $code = strtoupper($country->code);
                $name = \Locale::getDisplayRegion('und_'.$code, app()->getLocale());

                return [
                    'code' => $code,
                    'name' => $name ?: $country->name,
                    'calling_code' => $phoneUtil->getCountryCodeForRegion($code),
                ];
            })
            ->filter(fn ($country) => $country['calling_code'] > 0)
            ->values()
            ->all();

        usort($phoneCountries, fn ($left, $right) => $left['code'] === 'MX'
            ? -1
            : ($right['code'] === 'MX' ? 1 : strnatcasecmp($left['name'], $right['name'])));

        return view('shop::customers.sign-up', compact('phoneCountries', 'socialRegistration'));
    }

    /**
     * Method to store user's sign up form data to DB.
     *
     * @return Response
     */
    public function store(RegistrationRequest $registrationRequest)
    {
        $customerGroup = core()->getConfigData('customer.settings.create_new_account_options.default_group');

        $socialRegistration = session('social_registration');
        $subscription = request()->filled('email') ? $this->subscriptionRepository->findOneWhere(['email' => request()->input('email')]) : null;

        $data = array_merge($registrationRequest->only([
            'first_name',
            'last_name',
            'email',
            'phone',
            'has_whatsapp',
            'is_subscribed',
        ]), [
            'password' => $socialRegistration ? null : bcrypt(request()->input('password')),
            'api_token' => Str::random(80),
            'is_verified' => true,
            'status' => true,
            'customer_group_id' => $this->customerGroupRepository->findOneWhere(['code' => $customerGroup])->id,
            'channel_id' => core()->getCurrentChannel()->id,
            'token' => Str::random(64),
            'subscribed_to_news_letter' => (bool) (request()->input('is_subscribed') ?? $subscription?->is_subscribed),
        ]);

        Event::dispatch('customer.registration.before');

        $data['last_name'] = $data['last_name'] ?: '';

        $customer = DB::transaction(function () use ($data, $socialRegistration) {
            $customer = $this->customerRepository->create($data);

            if ($socialRegistration) {
                $this->customerSocialAccountRepository->create([
                    'customer_id' => $customer->id,
                    'provider_id' => $socialRegistration['provider_id'],
                    'provider_name' => $socialRegistration['provider'],
                ]);
            }

            return $customer;
        });

        session()->forget('social_registration');

        if ($subscription) {
            $this->subscriptionRepository->update([
                'customer_id' => $customer->id,
            ], $subscription->id);
        }

        if (
            ! empty($data['email'])
            && ! empty($data['is_subscribed'])
            && ! $subscription
        ) {
            Event::dispatch('customer.subscription.before');

            $subscription = $this->subscriptionRepository->create([
                'email' => $data['email'],
                'customer_id' => $customer->id,
                'channel_id' => core()->getCurrentChannel()->id,
                'is_subscribed' => 1,
                'token' => Str::random(64),
            ]);

            Event::dispatch('customer.subscription.after', $subscription);
        }

        Event::dispatch('customer.create.after', $customer);

        Event::dispatch('customer.registration.after', $customer);

        auth()->guard('customer')->login($customer);
        session()->regenerate();

        Event::dispatch('customer.after.login', $customer);

        return redirect()->to(session()->pull('shop.url.intended', route('shop.home.index')));
    }

    /**
     * Method to verify account.
     *
     * @param  string  $token
     * @return Response
     */
    public function verifyAccount($token)
    {
        $customer = $this->customerRepository->findOneByField('token', $token);

        if ($customer) {
            $this->customerRepository->update([
                'is_verified' => 1,
                'token' => null,
            ], $customer->id);

            if ((bool) core()->getConfigData('emails.general.notifications.emails.general.notifications.registration')) {
                Mail::queue(new RegistrationNotification($customer));
            }

            $this->customerRepository->syncNewRegisteredCustomerInformation($customer);

            session()->flash('success', trans('shop::app.customers.signup-form.verified'));
        } else {
            session()->flash('warning', trans('shop::app.customers.signup-form.verify-failed'));
        }

        return redirect()->route('shop.customer.session.index');
    }

    /**
     * Resend verification email.
     *
     * @param  string  $email
     * @return Response
     */
    public function resendVerificationEmail($email)
    {
        $customer = $this->customerRepository->findOneByField('email', $email);

        if (! $customer) {
            session()->flash('success', trans('shop::app.customers.signup-form.verification-sent'));

            return redirect()->back();
        }

        $customer = $this->customerRepository->update(
            ['token' => Str::random(64)],
            $customer->id
        );

        try {
            Mail::queue(new EmailVerificationNotification($customer));

            if (Cookie::has('enable-resend')) {
                Cookie::queue(Cookie::forget('enable-resend'));
            }

            if (Cookie::has('email-for-resend')) {
                Cookie::queue(Cookie::forget('email-for-resend'));
            }
        } catch (\Exception $e) {
            report($e);

            session()->flash('error', trans('shop::app.customers.signup-form.verification-not-sent'));

            return redirect()->back();
        }

        session()->flash('success', trans('shop::app.customers.signup-form.verification-sent'));

        return redirect()->back();
    }
}
