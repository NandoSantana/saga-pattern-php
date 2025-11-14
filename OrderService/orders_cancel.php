<?php
// POST /orders/cancel
$body = json_decode(file_get_contents('php://input'), true);
$orderId = $body['order_id'];

$pdo = pdo();

$stmt = $pdo->prepare("UPDATE orders SET status = 'canceled' WHERE service_order_id = :id");
$stmt->execute(['id' => $orderId]);

// idempotente: cancelar 10x => mesmo resultado
echo json_encode(['order_id' => $orderId, 'status' => 'canceled']);
