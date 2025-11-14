<?php
// POST /payments/cancel
echo json_encode([
    'status' => 'payment_canceled',
    'order_ref' => $_POST['order_ref'] ?? null
]);
