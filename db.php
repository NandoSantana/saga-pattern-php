<?php
// db.php
function pdo() {
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO(
            "mysql:host=localhost;port=8889;dbname=assistencia;charset=utf8mb4",
            "root",
            "root",
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    }
    return $pdo;
}
