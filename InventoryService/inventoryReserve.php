<?php
//POST /inventory/reserve
require 'db.php';

$body = json_decode(file_get_contents('php://input'), true);
$requestId = $body['request_id'];

$pdo = pdo();

// já processado?
$stmt = $pdo->prepare("SELECT response FROM inventory_reservations WHERE request_id = :id");
$stmt->execute(['id' => $requestId]);
$existing = $stmt->fetchColumn();

if ($existing) {
    echo $existing;
    exit;
}

// Aqui faria a lógica real de reserva de estoque…
// Exemplo simplificado:

$response = json_encode([
    'status' => 'reserved',
    'order_ref' => $body['order_ref']
]);

$stmt = $pdo->prepare("
    INSERT INTO inventory_reservations (request_id, order_ref, items, status, response)
    VALUES (:r, :o, :i, 'reserved', :resp)
");
$stmt->execute([
    'r' => $requestId,
    'o' => $body['order_ref'],
    'i' => json_encode($body['items']),
    'resp' => $response
]);

echo $response;
