<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Webkul\Shop\Support\PhoneNumberNormalizer;

return new class extends Migration
{
    /**
     * Normalize existing valid phone identifiers while rejecting collisions.
     */
    public function up(): void
    {
        DB::transaction(function () {
            DB::table('customers')->where('email', '')->update(['email' => null]);
            DB::table('core_config')->where('code', 'sales.checkout.shopping_cart.allow_guest_checkout')->update(['value' => '0']);

            $customers = DB::table('customers')->whereNotNull('phone')->lockForUpdate()->get(['id', 'phone']);
            $normalized = [];
            $owners = [];

            foreach ($customers as $customer) {
                $phone = PhoneNumberNormalizer::normalize($customer->phone) ?? $customer->phone;

                if (isset($owners[$phone])) {
                    throw new RuntimeException('Customer phone numbers need deduplication before migration.');
                }

                $owners[$phone] = $customer->id;
                $normalized[$customer->id] = $phone;
            }

            foreach ($normalized as $id => $phone) {
                DB::table('customers')->where('id', $id)->update(['phone' => $phone]);
            }
        });
    }

    /**
     * Keep canonical phone numbers when rolling back the application.
     */
    public function down(): void {}
};
