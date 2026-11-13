<?php

namespace App\Http\Controllers\Admin;

use App\Repositories\SalesInstallmentRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Webkul\Sales\Models\OrderProxy;

class SalesInstallmentController
{
    /** Resolve the repository for installment plans. */
    public function __construct(protected SalesInstallmentRepository $plans) {}

    /** Show the list of plans and current balances. */
    public function index(): View
    {
        return view('admin::sales.installments.index', ['plans' => $this->plans->paginate()]);
    }

    /** Show the plan or the setup form for an existing order. */
    public function order(int $order): View|RedirectResponse
    {
        if ($plan = $this->plans->forOrder($order)) {
            return redirect()->route('admin.sales.installments.show', $plan->id);
        }

        return view('admin::sales.installments.show', [
            'order' => OrderProxy::query()->findOrFail($order),
            'plan' => null,
        ]);
    }

    /** Show the plan and its complete payment history. */
    public function show(int $plan): View
    {
        $record = $this->plans->find($plan);
        $payments = $this->plans->payments($plan);

        return view('admin::sales.installments.show', [
            'order' => OrderProxy::query()->findOrFail($record->order_id),
            'plan' => $record,
            'payments' => $payments,
            'paid' => $payments->whereNull('voided_at')->sum('amount_cents'),
        ]);
    }

    /** Start a plan for an existing order. */
    public function store(Request $request, int $order): RedirectResponse
    {
        $plan = $this->plans->create($order, $this->terms($request));

        return $this->saved($plan);
    }

    /** Update the due date and delivery status. */
    public function update(Request $request, int $plan): RedirectResponse
    {
        $this->plans->update($plan, $this->terms($request));

        return $this->saved($plan);
    }

    /** Record a payment collected by the store. */
    public function payment(Request $request, int $plan): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'regex:/^\d{1,8}(\.\d{1,2})?$/'],
            'paid_on' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'method' => ['required', 'in:cash,transfer,card,other'],
            'note' => ['nullable', 'string', 'max:255'],
            'request_key' => ['required', 'uuid'],
        ]);

        $this->plans->addPayment($plan, (int) auth('admin')->id(), $data);

        return $this->saved($plan);
    }

    /** Void an entry to correct the balance with a visible audit trail. */
    public function voidPayment(Request $request, int $plan, int $payment): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:255']]);

        $this->plans->voidPayment($plan, $payment, (int) auth('admin')->id(), $data['reason']);

        return $this->saved($plan);
    }

    /** Validate the terms shared by the setup and edit forms. */
    protected function terms(Request $request): array
    {
        return $request->validate([
            'delivery' => ['required', 'in:held,delivered'],
            'due_on' => ['required', 'date_format:Y-m-d'],
        ]);
    }

    /** Return to the detail page after saving a change. */
    protected function saved(int $plan): RedirectResponse
    {
        session()->flash('success', __('installments.saved'));

        return redirect()->route('admin.sales.installments.show', $plan);
    }
}
