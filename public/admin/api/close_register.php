<?php
require_once __DIR__ . '/../../../vendor/autoload.php';

use App\Config\Database;
use App\Config\Session;

Session::start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$db = Database::getInstance()->getConnection();

// 1. Check for open orders
// Open statuses: pending, preparing, out_for_delivery, ready_for_pickup
$stmt = $db->query("SELECT COUNT(*) FROM orders WHERE status IN ('pending', 'preparing', 'out_for_delivery', 'ready_for_pickup')");
$openOrders = $stmt->fetchColumn();

if ($openOrders > 0) {
    echo json_encode(['success' => false, 'message' => "Não é possível fechar o caixa pois existem $openOrders pedidos em aberto."]);
    exit;
}

// 2. "Close" the register
// We simply save the current timestamp as the new "start" time for the next session.
// We store this in a JSON file.
$storageDir = __DIR__ . '/../../../storage';
if (!is_dir($storageDir)) {
    mkdir($storageDir, 0755, true);
}
$registerFile = $storageDir . '/register_state.json';

// Calculate stats for the session being closed
// Get previous start time
$lastOpen = null;
if (file_exists($registerFile)) {
    $data = json_decode(file_get_contents($registerFile), true);
    $lastOpen = $data['last_open'] ?? null;
}

// Default to beginning of time if no last open
$startTimeQuery = $lastOpen ? $lastOpen : '1970-01-01 00:00:00';

$stmt = $db->prepare("SELECT SUM(total_amount) as total, COUNT(*) as count FROM orders WHERE status = 'completed' AND created_at >= ?");
$stmt->execute([$startTimeQuery]);
$stats = $stmt->fetch();

// Save NEW start time (NOW)
$now = date('Y-m-d H:i:s');
file_put_contents($registerFile, json_encode(['last_open' => $now]));

echo json_encode([
    'success' => true,
    'message' => 'Caixa fechado com sucesso!',
    'revenue' => $stats['total'] ?? 0,
    'count' => $stats['count'] ?? 0,
    'closed_at' => $now
]);
