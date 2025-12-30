<?php
require_once __DIR__ . '/src/Config/Database.php';

use App\Config\Database;

try {
    $db = Database::getInstance()->getConnection();

    $sql = "CREATE TABLE IF NOT EXISTS access_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ip_address VARCHAR(45) NOT NULL,
        city VARCHAR(100) DEFAULT NULL,
        region VARCHAR(100) DEFAULT NULL,
        country VARCHAR(100) DEFAULT NULL,
        device_type ENUM('desktop', 'mobile', 'tablet') DEFAULT 'desktop',
        os VARCHAR(50) DEFAULT NULL,
        browser VARCHAR(50) DEFAULT NULL,
        page_url VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (created_at),
        INDEX (ip_address)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    $db->exec($sql);
    echo "<h1>Sucesso!</h1> Tabela 'access_logs' criada (ou já existia). <br> Pode apagar este arquivo agora.";

} catch (PDOException $e) {
    echo "Erro ao criar tabela: " . $e->getMessage();
}
