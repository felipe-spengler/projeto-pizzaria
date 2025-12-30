<?php
require_once __DIR__ . '/../../vendor/autoload.php';
use App\Config\Database;
use App\Config\Session;

// Inicia sessão com configurações otimizadas
Session::start();

header('Content-Type: application/json');

// Check admin auth (optional but recommended)
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$lastId = $_GET['last_id'] ?? 0;

$db = Database::getInstance()->getConnection();
$stmt = $db->prepare("SELECT COUNT(*) as count, MAX(id) as max_id FROM orders WHERE id > ?");
$stmt->execute([$lastId]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode([
    'new_orders_count' => $result['count'],
    'max_id' => $result['max_id']
]);
