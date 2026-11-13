<?php

namespace App\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use stdClass;
use Webkul\Sales\Models\OrderProxy;

class SalesInstallmentRepository
{
    /** Return plans with their balances and order contact details. */
    public function paginate(): LengthAwarePaginator
    {
        return DB::table('sales_installment_plans as plans')
            ->join('orders', 'orders.id', '=', 'plans.order_id')
            ->leftJoinSub(
                DB::table('sales_installment_payments')
                    ->selectRaw('plan_id, SUM(amount_cents) AS paid_cents')
                    ->whereNull('voided_at')
                    ->groupBy('plan_id'),
                'payments',
                'payments.plan_id',
                '=',
                'plans.id'
            )
            ->select('plans.*', 'orders.increment_id', 'orders.customer_first_name', 'orders.customer_last_name', 'orders.status as order_status')
            ->selectRaw('COALESCE(payments.paid_cents, 0) AS paid_cents')
            ->latest('plans.id')
            ->paginate(25);
    }

    /** Return the plan for a particular order if it exists. */
    public function forOrder(int $orderId): ?stdClass
    {
        return DB::table('sales_installment_plans')->where('order_id', $orderId)->first();
    }

    /** Return a plan or respond with a missing resource. */
    public function find(int $id): stdClass
    {
        return DB::table('sales_installment_plans')->where('id', $id)->first() ?? abort(404);
    }

    /** Return the complete payment history, including corrections. */
    public function payments(int $id): Collection
    {
        return DB::table('sales_installment_payments as payments')
            ->leftJoin('admins', 'admins.id', '=', 'payments.admin_id')
            ->select('payments.*', 'admins.name as admin_name')
            ->where('plan_id', $id)
            ->orderByDesc('paid_on')
            ->orderByDesc('payments.id')
            ->get();
    }

    /** Create one plan per order, using the order amount and currency. */
    public function create(int $orderId, array $data): int
    {
        return DB::transaction(function () use ($orderId, $data) {
            $order = OrderProxy::query()->lockForUpdate()->findOrFail($orderId);
            $this->validateOrder($order);

            if ($plan = $this->forOrder($orderId)) {
                return $plan->id;
            }

            $total = (int) round((float) $order->grand_total * 100);

            if ($total < 1 || ! preg_match('/^[A-Z]{3}$/', (string) $order->order_currency_code)
                || (float) $order->grand_total_invoiced > 0 || (float) $order->grand_total_refunded > 0) {
                throw ValidationException::withMessages(['order_id' => __('installments.ineligible')]);
            }

            return DB::table('sales_installment_plans')->insertGetId([
                'order_id' => $orderId,
                'total_cents' => $total,
                'currency' => $order->order_currency_code,
                'delivery' => $data['delivery'],
                'due_on' => $data['due_on'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });
    }

    /** Save the delivery status and agreed due date. */
    public function update(int $id, array $data): void
    {
        $this->find($id);

        DB::table('sales_installment_plans')->where('id', $id)->update([
            'delivery' => $data['delivery'],
            'due_on' => $data['due_on'],
            'updated_at' => now(),
        ]);
    }

    /** Record a payment once and prevent overpayments across concurrent requests. */
    public function addPayment(int $id, int $adminId, array $data): void
    {
        DB::transaction(function () use ($id, $adminId, $data) {
            $plan = $this->lockPlan($id);
            $order = OrderProxy::query()->lockForUpdate()->findOrFail($plan->order_id);
            $this->validateOrder($order);

            if (DB::table('sales_installment_payments')->where('plan_id', $id)->where('request_key', $data['request_key'])->exists()) {
                return;
            }

            $paid = DB::table('sales_installment_payments')->where('plan_id', $id)->whereNull('voided_at')->sum('amount_cents');
            $amount = (int) round((float) $data['amount'] * 100);

            if ($amount < 1 || $amount > $plan->total_cents - $paid) {
                throw ValidationException::withMessages(['amount' => __('installments.invalid_amount')]);
            }

            DB::table('sales_installment_payments')->insert([
                'plan_id' => $id,
                'request_key' => $data['request_key'],
                'amount_cents' => $amount,
                'paid_on' => $data['paid_on'],
                'method' => $data['method'],
                'note' => $data['note'] ?? null,
                'admin_id' => $adminId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });
    }

    /** Correct a payment while preserving its history and the reason. */
    public function voidPayment(int $id, int $paymentId, int $adminId, string $reason): void
    {
        DB::transaction(function () use ($id, $paymentId, $adminId, $reason) {
            $this->lockPlan($id);

            $payment = DB::table('sales_installment_payments')->where('plan_id', $id)->where('id', $paymentId);
            abort_unless($payment->exists(), 404);

            $payment->whereNull('voided_at')->update([
                'voided_at' => now(),
                'voided_by' => $adminId,
                'void_reason' => $reason,
                'updated_at' => now(),
            ]);
        });
    }

    /** Lock a plan for changes to its payment history. */
    protected function lockPlan(int $id): stdClass
    {
        return DB::table('sales_installment_plans')->where('id', $id)->lockForUpdate()->first() ?? abort(404);
    }

    /** Reject payments for canceled, closed, or fraudulent orders. */
    protected function validateOrder($order): void
    {
        if (in_array($order->status, ['canceled', 'closed', 'fraud'], true)) {
            throw ValidationException::withMessages(['order_id' => __('installments.ineligible')]);
        }
    }
}
