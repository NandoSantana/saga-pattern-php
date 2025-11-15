<?php
// OrderSagaOrchestrator.php
require_once 'db.php';
require_once 'SagaRepository.php';
require_once 'HttpClient.php';

class OrderSagaOrchestrator {
    private $repo;
    private $http;

    public function __construct() {
        $this->repo = new SagaRepository();
        $this->http = new HttpClient();
    }

    public function start(array $orderData) {
        $sagaId = $this->uuid();
        $saga = [
            'id' => $sagaId,
            'type' => 'OrderSaga',
            'state' => 'pending',
            'step' => 'create_order',
            'payload' => $orderData
        ];
        $this->repo->save($saga);

        try {
            $this->createOrder($saga);
            $this->reserveInventory($saga);
            $this->authorizePayment($saga);

            // tudo ok
            $saga['state'] = 'completed';
            $saga['step'] = 'done';
            $this->repo->save($saga);

            return ['ok' => true, 'saga_id' => $sagaId];

        } catch (Exception $e) {
            // entra em compensação
            $saga['state'] = 'compensating';
            $this->repo->save($saga);
            $this->compensate($saga, $e->getMessage());
            return ['ok' => false, 'error' => $e->getMessage(), 'saga_id' => $sagaId];
        }
    }

    private function createOrder(&$saga) {
        // exemplo: chama order service
        $payload = $saga['payload'];
        $resp = $this->http->postJson('http://localhost:9001/orders', [
            'order_id' => $payload['order_id'],
            'items' => $payload['items'],
            'customer' => $payload['customer']
        ]);

        if ($resp['status'] !== 201) {
            throw new Exception("Falha ao criar pedido");
        }

        // salvar resposta (ex: order_id gerado pelo serviço)
        $saga['payload']['service_order_id'] = $resp['body']['order_id'] ?? null;
        $saga['step'] = 'order_created';
        $this->repo->save($saga);
    }

    private function reserveInventory(&$saga) {
        $payload = $saga['payload'];
        $resp = $this->http->postJson('http://localhost:9002/inventory/reserve', [
            'items' => $payload['items'],
            'order_ref' => $payload['service_order_id']
        ]);

        if ($resp['status'] !== 200) {
            throw new Exception("Falha ao reservar estoque");
        }

        $saga['step'] = 'inventory_reserved';
        $this->repo->save($saga);
    }

    private function authorizePayment(&$saga) {
        $payload = $saga['payload'];
        $resp = $this->http->postJson('http://localhost:9003/payments/authorize', [
            'amount' => $payload['amount'],
            'order_ref' => $payload['service_order_id'],
            'payment_method' => $payload['payment_method']
        ]);

        if ($resp['status'] !== 200 || ($resp['body']['authorized'] ?? false) !== true) {
            throw new Exception("Pagamento não autorizado");
        }

        $saga['step'] = 'payment_authorized';
        $this->repo->save($saga);
    }

    private function compensate(&$saga, $reason) {
        // exemplo de compensações na ordem inversa
        // 1) se payment_authorized => cancelar pagamento (se aplicável)
        // 2) se inventory_reserved => liberar estoque
        // 3) se order_created => cancelar pedido

        $step = $saga['step'];
        $payload = $saga['payload'];

        try {
            if ($step === 'payment_authorized') {
                // cancelar pagamento
                $this->http->postJson('http://localhost:9003/payments/cancel', [
                    'order_ref' => $payload['service_order_id']
                ]);
            }

            if (in_array($step, ['payment_authorized', 'inventory_reserved', 'order_created'])) {
                // liberar estoque
                $this->http->postJson('http://localhost:9002/inventory/release', [
                    'order_ref' => $payload['service_order_id']
                ]);
            }

            if (in_array($step, ['payment_authorized', 'inventory_reserved', 'order_created'])) {
                // cancelar pedido
                $this->http->postJson('http://localhost:9001/orders/cancel', [
                    'order_id' => $payload['service_order_id']
                ]);
            }

            $saga['state'] = 'failed';
            $saga['step'] = 'compensated';
            $saga['payload']['error'] = $reason;
            $this->repo->save($saga);
        } catch (Exception $e) {
            // compensa parcialmente — grave em log e marque como failed_with_compensation_error
            $saga['state'] = 'failed_with_compensation_error';
            $saga['payload']['comp_error'] = $e->getMessage();
            $this->repo->save($saga);
        }
    }

    private function uuid() {
        // gera uuid simples
        return bin2hex(random_bytes(16));
    }
}
