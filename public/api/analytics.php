<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../src/Config/Database.php';

use App\Config\Database;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['error' => 'Method not allowed']));
}

// Receber dados do JSON enviado pelo JS
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    exit(json_encode(['status' => 'ignored']));
}

$ip = $_SERVER['REMOTE_ADDR'];
// Se estiver atrás de proxy (Coolify/Docker), tenta pegar o real
if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $parts = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
    $ip = trim($parts[0]);
}

$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

// Detecção simples de dispositivo no Backend tb (backup)
$deviceType = 'desktop';
if (preg_match('/mobile|android|iphone|ipad/i', $userAgent)) {
    $deviceType = 'mobile';
}

try {
    $db = Database::getInstance()->getConnection();

    // Inserir no banco
    $stmt = $db->prepare("INSERT INTO access_logs (ip_address, city, region, country, device_type, os, browser, page_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

    $stmt->execute([
        $ip,
        $data['city'] ?? null,
        $data['region'] ?? null,
        $data['country'] ?? null,
        $deviceType, // Usamos o do PHP que é mais seguro/padronizado ou o do JS se quiser
        $data['os'] ?? 'Unknown',
        $data['browser'] ?? 'Unknown',
        $data['url'] ?? '/'
    ]);

    echo json_encode(['status' => 'success']);

} catch (\Exception $e) {
    // Analytics não pode dar erro visível
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
