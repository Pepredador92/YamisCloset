<x-admin::layouts>
    <x-slot:title>@lang('installments.title') #{{ $order->increment_id }}</x-slot>

    @php
        $mayEdit = bouncer()->hasPermission('sales.installments.edit');
        $active = ! in_array($order->status, ['canceled', 'closed', 'fraud'], true);
        $balance = $plan ? $plan->total_cents - $paid : null;
    @endphp

    <div class="mb-5 flex flex-wrap items-center justify-between gap-3 text-gray-800 dark:text-white">
        <div>
            <h1 class="text-xl font-bold">@lang('installments.title') · @lang('installments.labels.order') #{{ $order->increment_id }}</h1>
            <p class="mt-1 text-sm">{{ $order->customer_full_name }} · {{ $order->customer_email }}</p>
        </div>
        <div class="flex gap-2">
            @if (bouncer()->hasPermission('sales.orders.view'))
                <a class="secondary-button" href="{{ route('admin.sales.orders.view', $order->id) }}">@lang('installments.labels.order')</a>
            @endif
            <a class="secondary-button" href="{{ route('admin.sales.installments.index') }}">@lang('installments.labels.back')</a>
        </div>
    </div>

    @if ($errors->any())
        <ul role="alert" class="mb-4 rounded bg-red-50 p-4 text-sm text-red-800">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    <div class="mb-4 grid gap-3 sm:grid-cols-3">
        @foreach (['total' => $plan ? $plan->total_cents / 100 : $order->grand_total, 'paid' => ($paid ?? 0) / 100, 'balance' => $plan ? $balance / 100 : $order->grand_total] as $label => $amount)
            <div class="box-shadow rounded bg-white p-4 text-gray-800 dark:bg-gray-900 dark:text-white">
                <p class="text-sm">@lang('installments.labels.'.$label)</p>
                <p class="text-xl font-bold">{{ core()->formatPrice((float) $amount, $plan->currency ?? $order->order_currency_code) }}</p>
            </div>
        @endforeach
    </div>

    @if ($plan || ($mayEdit && $active))
        <section class="box-shadow mb-4 rounded bg-white p-4 text-gray-800 dark:bg-gray-900 dark:text-white">
            <h2 class="mb-3 font-semibold">@lang('installments.labels.terms')</h2>
            @if ($mayEdit && $active)
                <form method="POST" action="{{ $plan ? route('admin.sales.installments.update', $plan->id) : route('admin.sales.installments.store', $order->id) }}" class="flex flex-wrap items-end gap-3">
                    @csrf
                    @if ($plan)
                        @method('PATCH')
                    @endif
                    <label class="grid gap-1 text-sm">
                        @lang('installments.labels.delivery')
                        <select name="delivery" class="rounded border border-gray-300 px-2 py-2 dark:bg-gray-800" required>
                            @foreach (['held', 'delivered'] as $delivery)
                                <option value="{{ $delivery }}" @selected(old('delivery', $plan->delivery ?? 'held') === $delivery)>@lang('installments.delivery.'.$delivery)</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="grid gap-1 text-sm">
                        @lang('installments.labels.due')
                        <input type="date" name="due_on" value="{{ old('due_on', $plan->due_on ?? '') }}" class="rounded border border-gray-300 px-2 py-2 dark:bg-gray-800" required>
                    </label>
                    <button class="primary-button" type="submit">@lang($plan ? 'installments.labels.save' : 'installments.labels.setup')</button>
                </form>
            @elseif ($plan)
                <p>@lang('installments.delivery.'.$plan->delivery) · @lang('installments.labels.due') {{ $plan->due_on }}</p>
            @endif
        </section>
    @endif

    @if ($plan && $mayEdit && $active && $balance > 0)
        <section class="box-shadow mb-4 rounded bg-white p-4 text-gray-800 dark:bg-gray-900 dark:text-white">
            <h2 class="font-semibold">@lang('installments.labels.new_payment')</h2>
            <p class="mb-3 mt-1 text-sm">@lang('installments.labels.manual')</p>
            <form method="POST" action="{{ route('admin.sales.installments.payment', $plan->id) }}" class="flex flex-wrap items-end gap-3">
                @csrf
                <input type="hidden" name="request_key" value="{{ old('request_key', (string) Illuminate\Support\Str::uuid()) }}">
                <label class="grid gap-1 text-sm">
                    @lang('installments.labels.amount')
                    <input type="number" name="amount" value="{{ old('amount') }}" min="0.01" max="{{ number_format($balance / 100, 2, '.', '') }}" step="0.01" class="w-32 rounded border border-gray-300 px-2 py-2 dark:bg-gray-800" required>
                </label>
                <label class="grid gap-1 text-sm">
                    @lang('installments.labels.date')
                    <input type="date" name="paid_on" value="{{ old('paid_on', today()->toDateString()) }}" max="{{ today()->toDateString() }}" class="rounded border border-gray-300 px-2 py-2 dark:bg-gray-800" required>
                </label>
                <label class="grid gap-1 text-sm">
                    @lang('installments.labels.method')
                    <select name="method" class="rounded border border-gray-300 px-2 py-2 dark:bg-gray-800" required>
                        @foreach (['cash', 'transfer', 'card', 'other'] as $method)
                            <option value="{{ $method }}" @selected(old('method') === $method)>@lang('installments.method.'.$method)</option>
                        @endforeach
                    </select>
                </label>
                <label class="grid gap-1 text-sm">
                    @lang('installments.labels.note')
                    <input type="text" name="note" value="{{ old('note') }}" maxlength="255" class="rounded border border-gray-300 px-2 py-2 dark:bg-gray-800">
                </label>
                <button class="primary-button" type="submit">@lang('installments.labels.new_payment')</button>
            </form>
        </section>
    @endif

    @if ($plan)
        <section class="box-shadow overflow-x-auto rounded bg-white p-4 text-gray-800 dark:bg-gray-900 dark:text-white">
            <h2 class="mb-3 font-semibold">@lang('installments.labels.history')</h2>
            <table class="w-full text-left text-sm">
                <thead class="border-b border-gray-200 dark:border-gray-800">
                    <tr>
                        @foreach (['date', 'amount', 'method', 'note', 'admin', 'status'] as $label)
                            <th scope="col" class="whitespace-nowrap px-3 py-2">@lang('installments.labels.'.$label)</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse ($payments as $payment)
                        <tr class="border-b border-gray-100 dark:border-gray-800">
                            <td class="whitespace-nowrap px-3 py-2">{{ $payment->paid_on }}</td>
                            <td class="whitespace-nowrap px-3 py-2">{{ core()->formatPrice($payment->amount_cents / 100, $plan->currency) }}</td>
                            <td class="px-3 py-2">@lang('installments.method.'.$payment->method)</td>
                            <td class="px-3 py-2">{{ $payment->note ?: '—' }}</td>
                            <td class="px-3 py-2">{{ $payment->admin_name ?: '—' }}</td>
                            <td class="px-3 py-2">
                                @if ($payment->voided_at)
                                    <p>@lang('installments.labels.voided') · {{ $payment->void_reason }}</p>
                                @elseif ($mayEdit)
                                    <details>
                                        <summary class="cursor-pointer text-blue-600 dark:text-blue-400">@lang('installments.labels.void')</summary>
                                        <form method="POST" action="{{ route('admin.sales.installments.void', [$plan->id, $payment->id]) }}" class="mt-2 grid gap-2">
                                            @csrf
                                            @method('PATCH')
                                            <label class="grid gap-1">
                                                @lang('installments.labels.reason')
                                                <input name="reason" maxlength="255" class="rounded border border-gray-300 px-2 py-1 dark:bg-gray-800" required>
                                            </label>
                                            <button class="secondary-button w-fit" type="submit">@lang('installments.labels.void')</button>
                                        </form>
                                    </details>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="p-4 text-center">@lang('installments.labels.no_payments')</td></tr>
                    @endforelse
                </tbody>
            </table>
        </section>
    @endif
</x-admin::layouts>
