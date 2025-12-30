<?php
use App\Config\Database;

$db = Database::getInstance()->getConnection();

// Quick Stats (Mock logic until we have real orders populated)
$today = date('Y-m-d');
$stats = [
    'revenue' => $db->query("SELECT SUM(total_amount) FROM orders WHERE DATE(created_at) = '$today' AND status != 'cancelled'")->fetchColumn() ?: 0,
    'orders_count' => $db->query("SELECT COUNT(*) FROM orders WHERE DATE(created_at) = '$today'")->fetchColumn() ?: 0,
    'pending_orders' => $db->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn() ?: 0,
    'active_dishes' => 0 // Mock
];

// Fetch Recent Orders
$orders = $db->query("
    SELECT o.*, u.name as customer_name 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    ORDER BY o.created_at DESC 
    LIMIT 10
")->fetchAll();

include __DIR__ . '/layouts/header.php';
?>

<!-- Stats Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div
        class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex items-center justify-between group hover:border-brand-200 transition-all">
        <div>
            <p class="text-gray-500 text-xs font-semibold uppercase tracking-wider mb-1">Faturamento Hoje</p>
            <h3 class="text-2xl font-bold text-gray-900">R$ <?= number_format($stats['revenue'], 2, ',', '.') ?></h3>
        </div>
        <div
            class="w-12 h-12 bg-green-50 rounded-xl flex items-center justify-center text-green-600 group-hover:scale-110 transition-transform">
            <i class="fas fa-dollar-sign text-xl"></i>
        </div>
    </div>

    <div
        class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex items-center justify-between group hover:border-brand-200 transition-all">
        <div>
            <p class="text-gray-500 text-xs font-semibold uppercase tracking-wider mb-1">Pedidos Hoje</p>
            <h3 class="text-2xl font-bold text-gray-900"><?= $stats['orders_count'] ?></h3>
        </div>
        <div
            class="w-12 h-12 bg-blue-50 rounded-xl flex items-center justify-center text-blue-600 group-hover:scale-110 transition-transform">
            <i class="fas fa-shopping-bag text-xl"></i>
        </div>
    </div>

    <div
        class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex items-center justify-between group hover:border-brand-200 transition-all">
        <div>
            <p class="text-gray-500 text-xs font-semibold uppercase tracking-wider mb-1">Pendentes</p>
            <h3 class="text-2xl font-bold text-gray-900"><?= $stats['pending_orders'] ?></h3>
        </div>
        <div
            class="w-12 h-12 bg-orange-50 rounded-xl flex items-center justify-center text-orange-600 group-hover:scale-110 transition-transform">
            <i class="fas fa-clock text-xl"></i>
        </div>
    </div>

    <div
        class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex items-center justify-between group hover:border-brand-200 transition-all">
        <div>
            <p class="text-gray-500 text-xs font-semibold uppercase tracking-wider mb-1">Ticket Médio</p>
            <h3 class="text-2xl font-bold text-gray-900">
                R$
                <?= $stats['orders_count'] > 0 ? number_format($stats['revenue'] / $stats['orders_count'], 2, ',', '.') : '0,00' ?>
            </h3>
        </div>
        <div
            class="w-12 h-12 bg-purple-50 rounded-xl flex items-center justify-center text-purple-600 group-hover:scale-110 transition-transform">
            <i class="fas fa-chart-line text-xl"></i>
        </div>
    </div>
</div>

<!-- Recent Orders Table -->
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="p-6 border-b border-gray-100 flex justify-between items-center">
        <h2 class="font-display font-bold text-lg text-gray-900">Pedidos Recentes</h2>
        <a href="#" class="text-sm font-medium text-brand-600 hover:text-brand-700">Ver Todos <i
                class="fas fa-arrow-right ml-1"></i></a>
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
                                <div class="flex items-center gap-3">
                                    <div
                                        class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center text-gray-500 text-xs font-bold">
                                        <?= substr($order['customer_name'], 0, 1) ?>
                                    </div>
                                    <span class="text-gray-700 font-medium"><?= $order['customer_name'] ?></span>
                                </div>
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
                                $statusLabels = [
                                    'pending' => 'Pendente',
                                    'preparing' => 'Preparando',
                                    'delivery' => 'Em Entrega',
                                    'completed' => 'Concluído',
                                    'cancelled' => 'Cancelado',
                                ];
                                $cls = $statusClasses[$order['status']] ?? 'bg-gray-100 text-gray-700';
                                $lbl = $statusLabels[$order['status']] ?? $order['status'];
                                ?>
                                <span class="px-3 py-1 rounded-full text-xs font-bold <?= $cls ?>">
                                    <?= $lbl ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-gray-900">R$ <?= number_format($order['total_amount'], 2, ',', '.') ?>
                            </td>
                            <td class="px-6 py-4 text-gray-500 text-sm"><?= date('H:i', strtotime($order['created_at'])) ?></td>
                            <td class="px-6 py-4 text-right">
                                <form action="/admin/orders/update" method="POST" class="inline-block">
                                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                    <?php if ($order['status'] === 'pending'): ?>
                                        <button type="submit" name="status" value="preparing"
                                            class="text-blue-600 hover:text-blue-800 font-medium text-sm mr-2"
                                            title="Aceitar e Preparar">
                                            <i class="fas fa-play"></i> Preparar
                                        </button>
                                    <?php elseif ($order['status'] === 'preparing'): ?>
                                        <button type="submit" name="status" value="delivery"
                                            class="text-orange-600 hover:text-orange-800 font-medium text-sm mr-2"
                                            title="Despachar">
                                            <i class="fas fa-motorcycle"></i> Despachar
                                        </button>
                                    <?php elseif ($order['status'] === 'delivery'): ?>
                                        <button type="submit" name="status" value="completed"
                                            class="text-green-600 hover:text-green-800 font-medium text-sm mr-2" title="Concluir">
                                            <i class="fas fa-check"></i> Concluir
                                        </button>
                                    <?php endif; ?>

                                    <?php if (!in_array($order['status'], ['completed', 'cancelled'])): ?>
                                        <button type="submit" name="status" value="cancelled"
                                            class="text-red-500 hover:text-red-700 text-sm" title="Cancelar Pedido"
                                            onclick="return confirm('Tem certeza que deseja cancelar?')">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    <?php endif; ?>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                            <div class="flex flex-col items-center justify-center">
                                <i class="fas fa-clipboard-list text-4xl text-gray-300 mb-4"></i>
                                <p class="text-lg font-medium text-gray-900">Nenhum pedido hoje</p>
                                <p class="text-sm">Os pedidos aparecerão aqui assim que forem realizados.</p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/layouts/footer.php'; ?>