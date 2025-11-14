<?php
// HttpClient.php
class HttpClientRetry {
    private int $maxRetries;
    private int $initialDelayMs;

    public function __construct(int $maxRetries = 3, int $initialDelayMs = 200) {
        $this->maxRetries = $maxRetries;
        $this->initialDelayMs = $initialDelayMs; // delay base em ms
    }

    public function postJson(string $url, array $data, int $timeout = 5) {
        $attempt = 0;

        while (true) {
            $attempt++;

            try {
                $response = $this->doRequest($url, $data, $timeout);

                // Se recebeu 2xx: OK
                if ($response['status'] >= 200 && $response['status'] < 300) {
                    return $response;
                }

                // Se erro não transitório => não tenta novamente
                if ($response['status'] >= 400 && $response['status'] < 500) {
                    throw new Exception("Erro 4xx: " . $response['status']);
                }

            } catch (Exception $e) {
                if ($attempt > $this->maxRetries) {
                    throw new Exception("Máximo de tentativas alcançado: {$e->getMessage()}");
                }

                // cálculo do backoff exponencial com jitter
                $delay = $this->calculateBackoff($attempt);
                usleep($delay * 1000); // ms -> microsegundos
            }
        }
    }

    private function doRequest(string $url, array $data, int $timeout) {
        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT => $timeout,
        ]);

        $resp = curl_exec($ch);
        $err  = curl_error($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if ($err) {
            throw new Exception("Erro de conexão: $err");
        }

        return [
            'status' => $code,
            'body' => json_decode($resp, true)
        ];
    }

    private function calculateBackoff(int $attempt): int {
        // base * (2^(attempt - 1))
        $base = $this->initialDelayMs * (2 ** ($attempt - 1));

        // jitter aleatório 0–100ms
        $jitter = rand(0, 100);

        return $base + $jitter;
    }
}
