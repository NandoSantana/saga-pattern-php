<?php
require 'db.php';

$body = json_decode(file_get_contents('php://input'), true);
$requestId = $body['request_id']; // obrigatório

$pdo = pdo();

// 1. Verificar se já processou
$stmt = $pdo->prepare("SELECT response FROM order_requests WHERE request_id = :id");
$stmt->execute(['id' => $requestId]);
$existing = $stmt->fetchColumn();

if ($existing) {
    echo $existing; // idempotência: devolve o mesmo retorno
    exit;
}

// 2. Criar pedido
$serviceOrderId = bin2hex(random_bytes(16));

$stmt = $pdo->prepare("
    INSERT INTO orders (service_order_id, status, payload)
    VALUES (:id, 'created', :payload)
");
$stmt->execute([
    'id' => $serviceOrderId,
    'payload' => json_encode($body)
]);

$response = json_encode([
    'order_id' => $serviceOrderId,
    'status' => 'created'
], JSON_UNESCAPED_UNICODE);

// 3. Registrar o request id
$stmt = $pdo->prepare("
    INSERT INTO order_requests (request_id, service_order_id, response)
    VALUES (:request, :order, :response)
");
$stmt->execute([
    'request' => $requestId,
    'order'   => $serviceOrderId,
    'response'=> $response
]);

echo $response;
