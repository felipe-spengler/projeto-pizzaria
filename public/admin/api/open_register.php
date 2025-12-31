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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

$initialBalance = isset($_POST['initial_balance']) ? (float) $_POST['initial_balance'] : 0.00;

// Check if already open
$stmt = $db->query("SELECT id FROM cash_registers WHERE status = 'open' LIMIT 1");
if ($stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Já existe um caixa aberto.']);
    exit;
}

$stmt = $db->prepare("INSERT INTO cash_registers (user_id, opened_at, initial_balance, status) VALUES (?, NOW(), ?, 'open')");
try {
    $stmt->execute([$_SESSION['user_id'], $initialBalance]);
    echo json_encode(['success' => true, 'message' => 'Caixa aberto com sucesso!']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erro ao abrir caixa: ' . $e->getMessage()]);
}
