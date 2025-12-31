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

// 2. Find Active Register
$stmt = $db->query("SELECT * FROM cash_registers WHERE status = 'open' ORDER BY id DESC LIMIT 1");
$register = $stmt->fetch();

if (!$register) {
    // If no register found in DB, maybe fallback or error?
    // For migration, if no register is found, we might assume one started at "beginning of time" or create one on the fly?
    // Let's return error to force user to "Open" strictly contextually, but since we are migrating, maybe we just close "nothing" or auto-create a closed one?
    // Better: If no open register, say "Nenhum caixa aberto.".
    echo json_encode(['success' => false, 'message' => "Nenhum caixa aberto para fechar."]);
    exit;
}

$registerId = $register['id'];
$openedAt = $register['opened_at'];

// 3. Calculate Totals
$sql = "SELECT 
            SUM(total_amount) as total_sales,
            SUM(CASE WHEN payment_method = 'pix' THEN total_amount ELSE 0 END) as total_pix,
            SUM(CASE WHEN payment_method = 'credit_card' THEN total_amount ELSE 0 END) as total_card,
            SUM(CASE WHEN payment_method = 'debit_card' THEN total_amount ELSE 0 END) as total_debit, -- Assuming card field merges credit/debit or separate?
            SUM(CASE WHEN payment_method = 'cash' THEN total_amount ELSE 0 END) as total_cash
        FROM orders 
        WHERE status = 'completed' AND created_at >= ?";

$stmt = $db->prepare($sql);
$stmt->execute([$openedAt]);
$totals = $stmt->fetch(PDO::FETCH_ASSOC);

// Merge Debit into Card or keep separate? Table has 'total_card'. Let's sum credit+debit for 'total_card'.
$totalCard = ($totals['total_card'] ?? 0) + ($totals['total_debit'] ?? 0);
$totalPix = $totals['total_pix'] ?? 0;
$totalCash = $totals['total_cash'] ?? 0;
$totalSales = $totals['total_sales'] ?? 0;

// 4. Calculate Final Balance
// Balance = Initial + Cash Sales + Supplies - Bleeds
// We need to fetch Supplies and Bleeds
$stmtMov = $db->prepare("SELECT 
                            SUM(CASE WHEN type='supply' THEN amount ELSE 0 END) as supply,
                            SUM(CASE WHEN type='bleed' THEN amount ELSE 0 END) as bleed
                         FROM cash_movements WHERE register_id = ?");
$stmtMov->execute([$registerId]);
$moves = $stmtMov->fetch(PDO::FETCH_ASSOC);

$totalSupply = $moves['supply'] ?? 0;
$totalBleed = $moves['bleed'] ?? 0;

// Final Cash in Drawer (Teórico)
$finalBalance = $register['initial_balance'] + $totalCash + $totalSupply - $totalBleed;

// 5. Update Register
$closeTime = date('Y-m-d H:i:s');
$updateSql = "UPDATE cash_registers SET 
                closed_at = ?, 
                final_balance = ?, 
                total_sales = ?, 
                total_pix = ?, 
                total_card = ?, 
                total_cash = ?,
                total_supply = ?, 
                total_bleed = ?, 
                status = 'closed' 
              WHERE id = ?";
$stmtUp = $db->prepare($updateSql);
$stmtUp->execute([
    $closeTime,
    $finalBalance,
    $totalSales,
    $totalPix,
    $totalCard,
    $totalCash,
    $totalSupply,
    $totalBleed,
    $registerId
]);

echo json_encode([
    'success' => true,
    'message' => 'Caixa fechado com sucesso!',
    'summary' => [
        'total_sales' => $totalSales,
        'final_balance' => $finalBalance,
        'details' => [
            'pix' => $totalPix,
            'card' => $totalCard,
            'cash' => $totalCash
        ]
    ]
]);
