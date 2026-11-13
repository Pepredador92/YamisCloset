<x-admin::layouts>
    <x-slot:title>@lang('installments.title')</x-slot>

    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-xl font-bold text-gray-800 dark:text-white">@lang('installments.title')</h1>
        @if (bouncer()->hasPermission('sales.orders'))
            <a class="secondary-button" href="{{ route('admin.sales.orders.index') }}">@lang('installments.labels.orders')</a>
        @endif
    </div>

    <div class="box-shadow overflow-x-auto rounded bg-white dark:bg-gray-900">
        <table class="w-full text-left text-sm text-gray-800 dark:text-gray-200">
            <thead class="border-b border-gray-200 dark:border-gray-800">
                <tr>
                    @foreach (['order', 'customer', 'delivery', 'due', 'total', 'paid', 'balance', 'status'] as $label)
                        <th scope="col" class="whitespace-nowrap px-3 py-3">@lang('installments.labels.'.$label)</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse ($plans as $plan)
                    @php
                        $balance = $plan->total_cents - $plan->paid_cents;
                        $status = match (true) {
                            $balance === 0 => 'paid',
                            in_array($plan->order_status, ['canceled', 'closed', 'fraud'], true) => 'inactive',
                            $plan->due_on < today()->toDateString() => 'overdue',
                            default => 'open',
                        };
                    @endphp
                    <tr class="border-b border-gray-100 dark:border-gray-800">
                        <td class="px-3 py-3"><a class="font-semibold text-blue-600 dark:text-blue-400" href="{{ route('admin.sales.installments.show', $plan->id) }}">#{{ $plan->increment_id }}</a></td>
                        <td class="px-3 py-3">{{ trim($plan->customer_first_name.' '.$plan->customer_last_name) ?: '—' }}</td>
                        <td class="px-3 py-3">@lang('installments.delivery.'.$plan->delivery)</td>
                        <td class="whitespace-nowrap px-3 py-3">{{ $plan->due_on }}</td>
                        @foreach ([$plan->total_cents, $plan->paid_cents, $balance] as $amount)
                            <td class="whitespace-nowrap px-3 py-3">{{ core()->formatPrice($amount / 100, $plan->currency) }}</td>
                        @endforeach
                        <td class="px-3 py-3">@lang('installments.status.'.$status)</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="p-6 text-center">@lang('installments.labels.empty')</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $plans->links() }}</div>
</x-admin::layouts>
