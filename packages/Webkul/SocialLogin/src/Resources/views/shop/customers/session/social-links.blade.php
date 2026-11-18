<div class="mt-6">
    @if (\Webkul\SocialLogin\Http\Controllers\LoginController::googleIsConfigured())
        <a href="{{ route('customer.social_login.index', 'google') }}" class="secondary-button flex items-center gap-3">
            @include('social_login::icons.google')
            @lang('storefront.google')
        </a>
    @else
        <button type="button" disabled class="secondary-button flex cursor-not-allowed items-center gap-3 opacity-50">
            @include('social_login::icons.google')
            @lang('storefront.google')
        </button>
        <p class="mt-2 text-sm text-zinc-500">@lang('storefront.google-unavailable')</p>
    @endif
</div>
