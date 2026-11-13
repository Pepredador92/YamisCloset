<?php

return [
    'title' => 'Installments',
    'saved' => 'Changes saved.',
    'ineligible' => 'The order must be active, have a positive total, and have no invoiced or refunded amounts to start a plan.',
    'invalid_amount' => 'The payment must be positive and cannot exceed the remaining balance.',
    'delivery' => ['held' => 'Held in store', 'delivered' => 'Delivered to customer'],
    'method' => ['cash' => 'Cash', 'transfer' => 'Transfer', 'card' => 'Card', 'other' => 'Other'],
    'status' => ['paid' => 'Paid', 'overdue' => 'Overdue', 'open' => 'Open', 'inactive' => 'Inactive order'],
    'labels' => [
        'order' => 'Order', 'customer' => 'Customer', 'delivery' => 'Merchandise', 'due' => 'Due date',
        'total' => 'Total', 'paid' => 'Paid', 'balance' => 'Balance', 'status' => 'Status',
        'orders' => 'View orders', 'empty' => 'Open an order and select Installments to create the first plan.',
        'setup' => 'Create plan', 'terms' => 'Plan terms', 'save' => 'Save terms',
        'new_payment' => 'Record payment', 'amount' => 'Amount', 'date' => 'Payment date',
        'method' => 'Payment method', 'note' => 'Note or reference', 'history' => 'Payment history',
        'no_payments' => 'No payments yet.', 'void' => 'Void entry', 'reason' => 'Reason for correction',
        'voided' => 'Voided', 'admin' => 'Recorded by', 'back' => 'Back to installments',
        'manual' => 'Record payments already received. Voiding an entry adjusts the balance without issuing a refund.',
    ],
];
