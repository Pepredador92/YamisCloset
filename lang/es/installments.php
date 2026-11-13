<?php

return [
    'title' => 'Abonos',
    'saved' => 'Cambios guardados.',
    'ineligible' => 'El pedido debe estar vigente, tener un total mayor a cero y no tener importes facturados ni reembolsados para iniciar un plan.',
    'invalid_amount' => 'El abono debe ser mayor a cero y no superar el saldo pendiente.',
    'delivery' => ['held' => 'Apartado en tienda', 'delivered' => 'Entregado al cliente'],
    'method' => ['cash' => 'Efectivo', 'transfer' => 'Transferencia', 'card' => 'Tarjeta', 'other' => 'Otro'],
    'status' => ['paid' => 'Liquidado', 'overdue' => 'Vencido', 'open' => 'Pendiente', 'inactive' => 'Pedido inactivo'],
    'labels' => [
        'order' => 'Pedido', 'customer' => 'Cliente', 'delivery' => 'Mercancía', 'due' => 'Fecha límite',
        'total' => 'Total', 'paid' => 'Abonado', 'balance' => 'Saldo', 'status' => 'Estado',
        'orders' => 'Ver pedidos', 'empty' => 'Abre un pedido y selecciona Abonos para crear el primer plan.',
        'setup' => 'Crear plan', 'terms' => 'Condiciones del plan', 'save' => 'Guardar condiciones',
        'new_payment' => 'Registrar abono', 'amount' => 'Importe', 'date' => 'Fecha de pago',
        'method' => 'Forma de pago', 'note' => 'Nota o referencia', 'history' => 'Historial de abonos',
        'no_payments' => 'Todavía no hay abonos.', 'void' => 'Anular registro', 'reason' => 'Motivo de corrección',
        'voided' => 'Anulado', 'admin' => 'Registró', 'back' => 'Volver a Abonos',
        'manual' => 'Registra aquí los pagos ya recibidos. Anular un registro corrige el saldo sin enviar un reembolso.',
    ],
];
