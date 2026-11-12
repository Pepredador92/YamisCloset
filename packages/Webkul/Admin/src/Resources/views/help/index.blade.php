<x-admin::layouts>
    <x-slot:title>
        @if (app()->isLocale('es'))
            Ayuda · Yamis Closet
        @else
            Help · Yamis Closet
        @endif
    </x-slot>

    {!! view_render_event('bagisto.admin.help.index.before') !!}

    <div class="mb-8 grid max-w-3xl gap-1.5">
        <h1 class="text-2xl font-bold leading-snug! text-gray-800 dark:text-white">
            @if (app()->isLocale('es'))
                Ayuda de Yamis Closet
            @else
                Yamis Closet help
            @endif
        </h1>

        <p class="leading-relaxed! text-gray-600 dark:text-gray-300">
            @if (app()->isLocale('es'))
                Accede a la tienda pública para revisar la experiencia de compra y confirma los cambios desde este panel.
            @else
                Open the public storefront to review the shopping experience and manage your store from this panel.
            @endif
        </p>
    </div>

    <a
        href="{{ route('shop.home.index') }}"
        target="_blank"
        rel="noopener noreferrer"
        class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-5 py-2.5 font-semibold text-white transition hover:bg-blue-700"
    >
        @if (app()->isLocale('es'))
            Abrir la tienda
        @else
            Open storefront
        @endif
        <span aria-hidden="true">↗</span>
    </a>

    {!! view_render_event('bagisto.admin.help.index.after') !!}
</x-admin::layouts>
