<?php
// start_order.php
require 'OrderSagaOrchestrator.php';

$data = [
    'order_id' => 'local-123', // id local antes do serviço
    'items' => [
        ['sku' => 'ABC', 'qty' => 2],
        ['sku' => 'XYZ', 'qty' => 1],
    ],
    'amount' => 199.90,
    'payment_method' => ['type' => 'card', 'token' => 'tok_123'],
    'customer' => ['id' => 10, 'name' => 'Fernando']
];

$orchestrator = new OrderSagaOrchestrator();
$result = $orchestrator->start($data);

header('Content-Type: application/json; charset=utf-8');
echo json_encode($result, JSON_UNESCAPED_UNICODE);
