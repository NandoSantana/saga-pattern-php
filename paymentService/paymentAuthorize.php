<?php
// POST /payments/authorize
require 'db.php';

$body = json_decode(file_get_contents('php://input'), true);
$requestId = $body['request_id'];

$pdo = pdo();

// request já processado?
$stmt = $pdo->prepare("SELECT response FROM payment_requests WHERE request_id = :id");
$stmt->execute(['id' => $requestId]);
$existing = $stmt->fetchColumn();
if ($existing) {
    echo $existing;
    exit;
}

// Simulação de autorização…
$authorized = true;

$response = json_encode([
    'authorized' => $authorized,
    'order_ref' => $body['order_ref']
]);

$stmt = $pdo->prepare("
    INSERT INTO payment_requests (request_id, order_ref, amount, status, response)
    VALUES (:r, :o, :a, :s, :resp)
");
$stmt->execute([
    'r' => $requestId,
    'o' => $body['order_ref'],
    'a' => $body['amount'],
    's' => $authorized ? 'authorized' : 'denied',
    'resp' => $response
]);

echo $response;
