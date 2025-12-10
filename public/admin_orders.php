<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\Config\Database;
use App\Config\Session;

Session::start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$db = Database::getInstance()->getConnection();

// Filter Logic
$startDate = $_GET['start_date'] ?? date('Y-m-01'); // FIRST day of current month
$endDate = $_GET['end_date'] ?? date('Y-m-d'); // Today
$statusFilter = $_GET['status'] ?? 'all';

// Prepare base query clauses
$whereClause = "WHERE DATE(o.created_at) BETWEEN ? AND ?";
$params = [$startDate, $endDate];

if ($statusFilter !== 'all') {
    $whereClause .= " AND o.status = ?";
    $params[] = $statusFilter;
}

// Fetch Orders
$sql = "
    SELECT o.*, u.name as customer_name 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    $whereClause
    ORDER BY o.created_at DESC
";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

// Reports (Only valid transactions)
// Total Revenue in Period
$revenueSql = "SELECT SUM(total_amount) FROM orders o $whereClause AND o.status != 'cancelled'";
$stmtRev = $db->prepare($revenueSql);
$stmtRev->execute($params); // Note: reusing params might be tricky if I added status filter. 
// Actually, revenue should probably ignore the status filter from the UI unless specifically desired, 
// usually revenue reports want "completed/valid" orders.
// Let's make the report independent of status filter, but dependent on "not cancelled".
$reportParams = [$startDate, $endDate];
$revenue = $db->prepare("SELECT SUM(total_amount) FROM orders o WHERE DATE(created_at) BETWEEN ? AND ? AND status != 'cancelled'")->execute($reportParams);

// Better way:
$stmtRevenue = $db->prepare("SELECT SUM(total_amount) FROM orders o WHERE DATE(created_at) BETWEEN ? AND ? AND status != 'cancelled'");
$stmtRevenue->execute([$startDate, $endDate]);
$totalRevenue = $stmtRevenue->fetchColumn() ?: 0;

// Top Products
$topProductsSql = "
    SELECT p.name, SUM(oi.quantity) as total_qty, SUM(oi.subtotal) as total_rev
    FROM order_items oi
    JOIN orders o ON oi.order_id = o.id
    JOIN products p ON oi.product_id = p.id
    WHERE DATE(o.created_at) BETWEEN ? AND ?
    AND o.status != 'cancelled'
    GROUP BY p.id, p.name
    ORDER BY total_qty DESC
    LIMIT 5
";
$stmtTop = $db->prepare($topProductsSql);
$stmtTop->execute([$startDate, $endDate]);
$topProducts = $stmtTop->fetchAll();

include __DIR__ . '/../views/admin/layouts/header.php';
?>

<div class="mb-8 flex flex-col md:flex-row justify-between items-end gap-4">
    <div>
        <h1 class="font-display font-bold text-3xl text-gray-900">Relatório de Pedidos</h1>
        <p class="text-gray-500 text-lg">Gerencie pedidos e visualize métricas.</p>
    </div>

    <form class="flex flex-col md:flex-row gap-4 bg-white p-4 rounded-xl shadow-sm border border-gray-100 items-end">
        <div>
            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Data Início</label>
            <input type="date" name="start_date" value="<?= $startDate ?>"
                class="px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-brand-500">
        </div>
        <div>
            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Data Fim</label>
            <input type="date" name="end_date" value="<?= $endDate ?>"
                class="px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-brand-500">
        </div>
        <div>
            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Status</label>
            <select name="status"
                class="px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-brand-500 bg-white">
                <option value="all" <?= $statusFilter === 'all' ? 'selected' : '' ?>>Todos</option>
                <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pendente</option>
                <option value="preparing" <?= $statusFilter === 'preparing' ? 'selected' : '' ?>>Preparando</option>
                <option value="delivery" <?= $statusFilter === 'delivery' ? 'selected' : '' ?>>Em Entrega</option>
                <option value="completed" <?= $statusFilter === 'completed' ? 'selected' : '' ?>>Concluído</option>
                <option value="cancelled" <?= $statusFilter === 'cancelled' ? 'selected' : '' ?>>Cancelado</option>
            </select>
        </div>
        <button type="submit"
            class="px-6 py-2 bg-brand-600 text-white rounded-lg font-bold hover:bg-brand-700 transition-colors">
            Filtrar
        </button>
    </form>
</div>

<!-- Stats Grid -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
    <!-- Revenue Card -->
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex items-center justify-between">
        <div>
            <p class="text-gray-500 text-xs font-semibold uppercase tracking-wider mb-1">Faturamento no Período</p>
            <h3 class="text-3xl font-bold text-gray-900">R$ <?= number_format($totalRevenue, 2, ',', '.') ?></h3>
        </div>
        <div class="w-16 h-16 bg-green-50 rounded-2xl flex items-center justify-center text-green-600">
            <i class="fas fa-dollar-sign text-3xl"></i>
        </div>
    </div>

    <!-- Top Products Card -->
    <div class="lg:col-span-2 bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
        <h3 class="font-display font-bold text-gray-900 mb-4">Produtos Mais Vendidos</h3>
        <div class="space-y-4">
            <?php foreach ($topProducts as $idx => $prod): ?>
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <span
                            class="w-6 h-6 rounded-full bg-brand-100 text-brand-700 flex items-center justify-center text-xs font-bold">
                            <?= $idx + 1 ?>
                        </span>
                        <span class="font-medium text-gray-700"><?= $prod['name'] ?></span>
                    </div>
                    <div class="text-sm font-semibold text-gray-900">
                        <?= $prod['total_qty'] ?> un. <span class="text-gray-400 font-normal ml-2">(R$
                            <?= number_format($prod['total_rev'], 2, ',', '.') ?>)</span>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php if (empty($topProducts)): ?>
                <p class="text-gray-400 text-sm">Nenhum dado no período.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Orders Table -->
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="p-6 border-b border-gray-100">
        <h2 class="font-display font-bold text-lg text-gray-900">Histórico de Pedidos</h2>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead>
                <tr class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider">
                    <th class="px-6 py-4 font-semibold">ID</th>
                    <th class="px-6 py-4 font-semibold">Cliente</th>
                    <th class="px-6 py-4 font-semibold">Status</th>
                    <th class="px-6 py-4 font-semibold">Total</th>
                    <th class="px-6 py-4 font-semibold">Data</th>
                    <th class="px-6 py-4 font-semibold text-right">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php if (count($orders) > 0): ?>
                    <?php foreach ($orders as $order): ?>
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 text-gray-900 font-medium">#<?= $order['id'] ?></td>
                            <td class="px-6 py-4">
                                <span class="text-gray-700 font-medium"><?= $order['customer_name'] ?></span>
                            </td>
                            <td class="px-6 py-4">
                                <?php
                                $statusClasses = [
                                    'pending' => 'bg-yellow-100 text-yellow-700',
                                    'preparing' => 'bg-blue-100 text-blue-700',
                                    'delivery' => 'bg-orange-100 text-orange-700',
                                    'completed' => 'bg-green-100 text-green-700',
                                    'cancelled' => 'bg-red-100 text-red-700',
                                ];
                                $lbls = [
                                    'pending' => 'Pendente',
                                    'preparing' => 'Preparando',
                                    'delivery' => 'Em Entrega',
                                    'completed' => 'Concluído',
                                    'cancelled' => 'Cancelado',
                                ];
                                ?>
                                <span
                                    class="px-3 py-1 rounded-full text-xs font-bold <?= $statusClasses[$order['status']] ?? '' ?>">
                                    <?= $lbls[$order['status']] ?? $order['status'] ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-gray-900">R$ <?= number_format($order['total_amount'], 2, ',', '.') ?>
                            </td>
                            <td class="px-6 py-4 text-gray-500 text-sm">
                                <?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></td>
                            <td class="px-6 py-4 text-right">
                                <a href="print_receipt.php?id=<?= $order['id'] ?>" target="_blank"
                                    class="text-gray-600 hover:text-gray-800 font-medium text-sm" title="Imprimir/Ver Detalhes">
                                    <i class="fas fa-eye"></i> Detalhes
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                            Nenhum pedido encontrado com os filtros selecionados.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../views/admin/layouts/footer.php'; ?>