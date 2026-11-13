<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_installment_plans', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('order_id')->unique();
            $table->foreign('order_id')->references('id')->on('orders')->restrictOnDelete();
            $table->unsignedBigInteger('total_cents');
            $table->string('currency', 3);
            $table->string('delivery', 16);
            $table->date('due_on')->index();
            $table->timestamps();
        });

        Schema::create('sales_installment_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained('sales_installment_plans')->restrictOnDelete();
            $table->uuid('request_key');
            $table->unique(['plan_id', 'request_key']);
            $table->unsignedBigInteger('amount_cents');
            $table->date('paid_on');
            $table->string('method', 16);
            $table->string('note', 255)->nullable();
            $table->unsignedInteger('admin_id')->nullable();
            $table->foreign('admin_id')->references('id')->on('admins')->nullOnDelete();
            $table->timestamp('voided_at')->nullable();
            $table->unsignedInteger('voided_by')->nullable();
            $table->foreign('voided_by')->references('id')->on('admins')->nullOnDelete();
            $table->string('void_reason', 255)->nullable();
            $table->timestamps();
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE sales_installment_plans ENABLE ROW LEVEL SECURITY');
            DB::statement('ALTER TABLE sales_installment_payments ENABLE ROW LEVEL SECURITY');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_installment_payments');
        Schema::dropIfExists('sales_installment_plans');
    }
};
