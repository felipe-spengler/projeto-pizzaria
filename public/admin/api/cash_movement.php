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

// Check Active Register
$stmt = $db->query("SELECT id FROM cash_registers WHERE status = 'open' ORDER BY id DESC LIMIT 1");
$register = $stmt->fetch();

if (!$register) {
    echo json_encode(['success' => false, 'message' => 'Nenhum caixa aberto.']);
    exit;
}

$type = $_POST['type'] ?? ''; // 'supply' or 'bleed'
$amount = (float) ($_POST['amount'] ?? 0);
$description = trim($_POST['description'] ?? '');

if (!in_array($type, ['supply', 'bleed'])) {
    echo json_encode(['success' => false, 'message' => 'Tipo inválido.']);
    exit;
}

if ($amount <= 0) {
    echo json_encode(['success' => false, 'message' => 'Valor deve ser positivo.']);
    exit;
}

if (empty($description)) {
    echo json_encode(['success' => false, 'message' => 'Descrição obrigatória.']);
    exit;
}

// Insert Movement
$stmt = $db->prepare("INSERT INTO cash_movements (register_id, user_id, type, amount, description) VALUES (?, ?, ?, ?, ?)");
try {
    $stmt->execute([$register['id'], $_SESSION['user_id'], $type, $amount, $description]);

    // Update Register Totals (Optional, if we want real-time columns in register table, but we calculate on close usually)
    // But let's verify if we need to update `total_supply` / `total_bleed` on the fly?
    // cash_registers table has `total_supply` and `total_bleed`. It is good practice to update them.

    if ($type === 'supply') {
        $db->prepare("UPDATE cash_registers SET total_supply = total_supply + ? WHERE id = ?")->execute([$amount, $register['id']]);
    } else {
        $db->prepare("UPDATE cash_registers SET total_bleed = total_bleed + ? WHERE id = ?")->execute([$amount, $register['id']]);
    }

    echo json_encode(['success' => true, 'message' => 'Movimentação registrada com sucesso!']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erro: ' . $e->getMessage()]);
}
