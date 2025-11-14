<?php
// SagaRepository.php
class SagaRepository {
    public function save(array $saga) {
        $pdo = pdo();
        $stmt = $pdo->prepare("
            INSERT INTO sagas (id, type, state, step, payload) 
            VALUES (:id, :type, :state, :step, :payload)
            ON DUPLICATE KEY UPDATE state = :state2, step = :step2, payload = :payload2, updated_at = NOW()
        ");
        $stmt->execute([
            ':id' => $saga['id'],
            ':type' => $saga['type'],
            ':state' => $saga['state'],
            ':step' => $saga['step'],
            ':payload' => json_encode($saga['payload']),
            ':state2' => $saga['state'],
            ':step2' => $saga['step'],
            ':payload2' => json_encode($saga['payload']),
        ]);
    }

    public function find(string $id) {
        $pdo = pdo();
        $stmt = $pdo->prepare("SELECT * FROM sagas WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return null;
        $row['payload'] = json_decode($row['payload'], true);
        return $row;
    }
}
