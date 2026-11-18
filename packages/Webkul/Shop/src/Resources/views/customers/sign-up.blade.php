<!-- SEO Meta Content -->
@push('meta')
    <meta
        name="description"
        content="@lang('shop::app.customers.signup-form.page-title')"
    />

    <meta
        name="keywords"
        content="@lang('shop::app.customers.signup-form.page-title')"
    />
@endPush

<x-shop::layouts
    :has-header="false"
    :has-feature="false"
    :has-footer="false"
>
    <!-- Page Title -->
    <x-slot:title>
        @lang('shop::app.customers.signup-form.page-title')
    </x-slot>

	<div class="container mt-20 max-1180:px-5 max-md:mt-12">
        {!! view_render_event('bagisto.shop.customers.sign-up.logo.before') !!}

        <!-- Company Logo -->
        <div class="flex items-center gap-x-14 max-1180:gap-x-9">
            <a
                href="{{ route('shop.home.index') }}"
                class="mx-auto mb-5"
                aria-label="{{ config('app.name') }}"
            >
                <img
                    src="{{ core()->getCurrentChannel()->logo_url ?? bagisto_asset('images/logo.svg') }}"
                    alt="{{ core()->getCurrentChannel()->logo_alt ?: config('app.name') }}"
                    width="131"
                    height="29"
                >
            </a>
        </div>

        {!! view_render_event('bagisto.shop.customers.sign-up.logo.before') !!}

        <!-- Form Container -->
		<div class="m-auto w-full max-w-217.5 rounded-xl border border-zinc-200 p-16 px-22.5 max-md:px-8 max-md:py-8 max-sm:border-none max-sm:p-0">
			<h1 class="font-dmserif text-4xl max-md:text-3xl max-sm:text-xl">
                @lang('shop::app.customers.signup-form.page-title')
            </h1>

			<p class="mt-4 text-xl text-zinc-500 max-sm:mt-0 max-sm:text-sm">
                @lang('shop::app.customers.signup-form.form-signup-text')
            </p>

            @if ($socialRegistration)
                <p class="mt-4 rounded-lg bg-blue-50 p-4 text-sm text-blue-800">
                    @lang('shop::app.customers.signup-form.social-completion')
                </p>
            @endif

            <div class="mt-14 rounded-sm max-sm:mt-8">
                <x-shop::form :action="route('shop.customers.register.store')">
                    {!! view_render_event('bagisto.shop.customers.signup_form_controls.before') !!}

                    <!-- First Name -->
                    <x-shop::form.control-group>
                        <x-shop::form.control-group.label class="required">
                            @lang('storefront.name')
                        </x-shop::form.control-group.label>

                        <x-shop::form.control-group.control
                            type="text"
                            class="px-6 py-4 max-md:py-3 max-sm:py-2"
                            name="first_name"
                            rules="required"
                            :value="old('first_name', $socialRegistration['first_name'] ?? '')"
                            :label="trans('storefront.name')"
                            :placeholder="trans('storefront.name')"
                            :aria-label="trans('storefront.name')"
                            aria-required="true"
                        />

                        <x-shop::form.control-group.error control-name="first_name" />
                    </x-shop::form.control-group>

                    {!! view_render_event('bagisto.shop.customers.signup_form.first_name.after') !!}

                    <!-- Phone Number -->
                    <x-shop::form.control-group>
                        <x-shop::form.control-group.label class="required">
                            @lang('shop::app.customers.signup-form.phone-number')
                        </x-shop::form.control-group.label>

                        <div class="flex gap-2 max-sm:flex-col">
                            <select
                                name="phone_country"
                                id="phone_country"
                                class="w-full rounded-lg border border-zinc-200 bg-white px-4 py-4 text-sm text-zinc-700 max-sm:py-3 md:max-w-64"
                                aria-label="@lang('shop::app.customers.signup-form.phone-country')"
                                required
                            >
                                @foreach ($phoneCountries as $country)
                                    <option
                                        value="{{ $country['code'] }}"
                                        data-calling-code="{{ $country['calling_code'] }}"
                                        @selected(old('phone_country', 'MX') === $country['code'])
                                    >
                                        {{ $country['name'] }} (+{{ $country['calling_code'] }})
                                    </option>
                                @endforeach
                            </select>

                            <input
                                type="tel"
                                id="phone_number"
                                name="phone_number"
                                class="w-full rounded-lg border border-zinc-200 px-6 py-4 text-sm text-zinc-700 max-sm:py-3"
                                value="{{ old('phone_number') }}"
                                placeholder="55 1234 5678"
                                autocomplete="tel-national"
                                inputmode="tel"
                                aria-label="@lang('shop::app.customers.signup-form.phone-number')"
                                required
                            />
                        </div>

                        <p class="mt-2 text-xs text-zinc-500">
                            @lang('shop::app.customers.signup-form.phone-help')
                        </p>

                        <x-shop::form.control-group.error control-name="phone_country" />
                        <x-shop::form.control-group.error control-name="phone_number" />
                        <x-shop::form.control-group.error control-name="phone" />
                    </x-shop::form.control-group>

                    <div class="mb-6 flex items-center gap-2">
                        <input
                            type="checkbox"
                            id="has_whatsapp"
                            name="has_whatsapp"
                            value="1"
                            class="h-4 w-4 rounded border-zinc-300 text-navyBlue focus:ring-navyBlue"
                            @checked(old('has_whatsapp'))
                        />
                        <label for="has_whatsapp" class="cursor-pointer text-sm text-zinc-600">
                            @lang('shop::app.customers.signup-form.has-whatsapp')
                        </label>
                    </div>

                    {!! view_render_event('bagisto.shop.customers.signup_form.phone.after') !!}

                    <!-- Email -->
                    <x-shop::form.control-group>
                        <x-shop::form.control-group.label>
                            @lang('storefront.email')
                        </x-shop::form.control-group.label>

                        <x-shop::form.control-group.control
                            type="email"
                            class="px-6 py-4 max-md:py-3 max-sm:py-2"
                            name="email"
                            rules="email"
                            :value="old('email', $socialRegistration['email'] ?? '')"
                            :label="trans('storefront.email')"
                            placeholder="email@example.com"
                            :aria-label="trans('storefront.email')"

                            :readonly="! empty($socialRegistration['email'])"
                        />

                        <x-shop::form.control-group.error control-name="email" />
                    </x-shop::form.control-group>

                    {!! view_render_event('bagisto.shop.customers.signup_form.email.after') !!}

                    @if (! $socialRegistration)
                    <!-- Password -->
                    <x-shop::form.control-group class="mb-6">
                        <x-shop::form.control-group.label class="required">
                            @lang('shop::app.customers.signup-form.password')
                        </x-shop::form.control-group.label>

                        <x-shop::form.control-group.control
                            type="password"
                            class="px-6 py-4 max-md:py-3 max-sm:py-2"
                            name="password"
                            rules="required|min:8"
                            value=""
                            :label="trans('shop::app.customers.signup-form.password')"
                            :placeholder="trans('shop::app.customers.signup-form.password')"
                            ref="password"
                            :aria-label="trans('shop::app.customers.signup-form.password')"
                            aria-required="true"
                        />

                        <x-shop::form.control-group.error control-name="password" />
                    </x-shop::form.control-group>

                    {!! view_render_event('bagisto.shop.customers.signup_form.password.after') !!}

                    <!-- Confirm Password -->
                    <x-shop::form.control-group>
                        <x-shop::form.control-group.label>
                            @lang('shop::app.customers.signup-form.confirm-pass')
                        </x-shop::form.control-group.label>

                        <x-shop::form.control-group.control
                            type="password"
                            class="px-6 py-4 max-md:py-3 max-sm:py-2"
                            name="password_confirmation"
                            rules="confirmed:@password"
                            value=""
                            :label="trans('shop::app.customers.signup-form.password')"
                            :placeholder="trans('shop::app.customers.signup-form.confirm-pass')"
                            :aria-label="trans('shop::app.customers.signup-form.confirm-pass')"
                            aria-required="true"
                        />

                        <x-shop::form.control-group.error control-name="password_confirmation" />
                    </x-shop::form.control-group>

                    {!! view_render_event('bagisto.shop.customers.signup_form.password_confirmation.after') !!}
                    @endif

                    <!-- Captcha -->
                    @if (core()->getConfigData('customer.captcha.credentials.status'))
                        <x-shop::form.control-group>
                            {!! \Webkul\Customer\Facades\Captcha::render() !!}

                            <x-shop::form.control-group.error control-name="recaptcha_token" />
                        </x-shop::form.control-group>
                    @endif

                    <!-- Subscribed Button -->
                    @if (core()->getConfigData('customer.settings.create_new_account_options.news_letter'))
                        <div class="mb-5 flex select-none items-center gap-1.5">
                            <input
                                type="checkbox"
                                name="is_subscribed"
                                id="is-subscribed"
                                class="peer hidden"
                            />

                            <label
                                class="icon-uncheck peer-checked:icon-check-box cursor-pointer text-2xl text-navyBlue peer-checked:text-navyBlue"
                                for="is-subscribed"
                            ></label>

                            <label
                                class="cursor-pointer select-none text-base text-zinc-500 max-sm:text-sm ltr:pl-0 rtl:pr-0"
                                for="is-subscribed"
                            >
                                @lang('shop::app.customers.signup-form.subscribe-to-newsletter')
                            </label>
                        </div>
                    @endif

                    {!! view_render_event('bagisto.shop.customers.signup_form.newsletter_subscription.after') !!}

                    @if(
                        core()->getConfigData('general.gdpr.settings.enabled')
                        && core()->getConfigData('general.gdpr.agreement.enabled')
                    )
                        <div class="mb-2 flex select-none items-center gap-1.5">
                            <x-shop::form.control-group.control
                                type="checkbox"
                                name="agreement"
                                id="agreement"
                                value="0"
                                rules="required"
                                for="agreement"
                            />

                            <label
                                class="cursor-pointer select-none text-base text-zinc-500 max-sm:text-sm"
                                for="agreement"
                                v-pre
                            >
                                {{ core()->getConfigData('general.gdpr.agreement.agreement_label') }}
                            </label>

                            @if (core()->getConfigData('general.gdpr.agreement.agreement_content'))
                                <span
                                    class="cursor-pointer text-base text-navyBlue max-sm:text-sm"
                                    @click="$refs.termsModal.open()"
                                >
                                    @lang('shop::app.customers.signup-form.click-here')
                                </span>
                            @endif
                        </div>

                        <x-shop::form.control-group.error control-name="agreement" />
                    @endif

                    <div class="mt-8 flex flex-wrap items-center gap-9 max-sm:justify-center max-sm:gap-5">
                        <!-- Save Button -->
                        <button
                            class="primary-button m-0 mx-auto block w-full max-w-93.5 rounded-2xl px-11 py-4 text-center text-base max-md:max-w-full max-md:rounded-lg max-md:py-3 max-sm:py-1.5 ltr:ml-0 rtl:mr-0"
                            type="submit"
                        >
                            @lang('shop::app.customers.signup-form.button-title')
                        </button>

                        <div class="flex flex-wrap gap-4">
                            {!! view_render_event('bagisto.shop.customers.login_form_controls.after') !!}
                        </div>
                    </div>

                    {!! view_render_event('bagisto.shop.customers.signup_form_controls.after') !!}

                </x-shop::form>
            </div>

			<p class="mt-5 font-medium text-zinc-500 max-sm:text-center max-sm:text-sm">
                @lang('shop::app.customers.signup-form.account-exists')

                <a class="text-navyBlue"
                    href="{{ route('shop.customer.session.index') }}"
                >
                    @lang('shop::app.customers.signup-form.sign-in-button')
                </a>
            </p>
		</div>

        <p class="mb-4 mt-8 text-center text-xs text-zinc-500">
            <x-shop::layouts.footer.copyright>
                @lang('shop::app.customers.signup-form.footer', ['current_year'=> date('Y') ])
            </x-shop::layouts.footer.copyright>
        </p>
	</div>

    @push('scripts')
        {!! \Webkul\Customer\Facades\Captcha::renderJS() !!}

        <script>
            const phoneCountrySelect = document.getElementById('phone_country');
            const phoneNumberInput = document.getElementById('phone_number');

            phoneNumberInput?.addEventListener('input', () => {
                const pastedValue = phoneNumberInput.value.trim();
                const isInternational = pastedValue.startsWith('+') || pastedValue.startsWith('00');
                let digits = pastedValue.replace(/\D/g, '');

                if (isInternational && phoneCountrySelect) {
                    if (pastedValue.startsWith('00')) {
                        digits = digits.slice(2);
                    }

                    const selectedOption = phoneCountrySelect.selectedOptions[0];
                    const options = [...phoneCountrySelect.options].sort((left, right) => {
                        return right.dataset.callingCode.length - left.dataset.callingCode.length;
                    });
                    const match = options.find((option) => {
                        return digits.startsWith(option.dataset.callingCode)
                            && option.dataset.callingCode === selectedOption?.dataset.callingCode;
                    }) ?? options.find((option) => digits.startsWith(option.dataset.callingCode));

                    if (match) {
                        phoneCountrySelect.value = match.value;
                        digits = digits.slice(match.dataset.callingCode.length);
                    }
                }

                phoneNumberInput.value = digits;
            });
        </script>
    @endpush

    <!-- Terms & Conditions Modal -->
    <x-shop::modal ref="termsModal">
        <x-slot:toggle></x-slot>

        <x-slot:header class="p-5!">
            <p>@lang('shop::app.customers.signup-form.terms-conditions')</p>
        </x-slot>

        <x-slot:content class="p-5!">
            <div class="max-h-125 overflow-auto">
                {!! core()->getConfigData('general.gdpr.agreement.agreement_content') !!}
            </div>
        </x-slot>
    </x-shop::modal>
</x-shop::layouts>
