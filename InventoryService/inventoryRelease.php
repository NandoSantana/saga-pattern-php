<?php
// POST /inventory/release
echo json_encode([
    'status' => 'released',
    'order_ref' => $_POST['order_ref'] ?? null
]);
