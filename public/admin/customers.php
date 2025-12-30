<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Config\Database;
use App\Config\Session;

Session::start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$controller = new App\Controllers\CustomerController();

$filters = [];
if (isset($_GET['filter']) && $_GET['filter'] === 'inactive') {
    $filters['inactive_days'] = 60;
}

$customers = $controller->index($filters);

include __DIR__ . '/../../views/admin/layouts/header.php';
?>

<div class="mb-8">
    <h1 class="font-display font-bold text-3xl text-gray-900">Gerenciamento de Clientes</h1>
    <p class="text-gray-500 text-lg">Visualize os melhores clientes e os inativos.</p>
</div>

<!-- Stats / Highlights -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex items-center justify-between">
        <div>
            <p class="text-gray-500 text-xs font-semibold uppercase tracking-wider mb-1">Total Clientes</p>
            <h3 class="text-3xl font-bold text-gray-900"><?= count($customers) ?></h3>
        </div>
        <div class="w-16 h-16 bg-blue-50 rounded-2xl flex items-center justify-center text-blue-600">
            <i class="fas fa-users text-3xl"></i>
        </div>
    </div>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="p-6 border-b border-gray-100 flex justify-between items-center">
        <h2 class="font-display font-bold text-lg text-gray-900">Lista de Clientes</h2>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead>
                <tr class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider">
                    <th class="px-6 py-4 font-semibold">Cliente</th>
                    <th class="px-6 py-4 font-semibold">Contato</th>
                    <th class="px-6 py-4 font-semibold">Total Gasto</th>
                    <th class="px-6 py-4 font-semibold">Pedidos</th>
                    <th class="px-6 py-4 font-semibold">Último Pedido</th>
                    <th class="px-6 py-4 font-semibold text-right">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php foreach ($customers as $customer): ?>
                    <?php
                    $lastOrder = $customer['last_order_date'] ? strtotime($customer['last_order_date']) : null;
                    $daysSinceOrder = $lastOrder ? floor((time() - $lastOrder) / (60 * 60 * 24)) : 999;
                    $isInactive = $daysSinceOrder > 60;
                    ?>
                    <tr class="hover:bg-gray-50 transition-colors <?= $isInactive ? 'bg-red-50/50' : '' ?>">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div
                                    class="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center text-gray-500 font-bold">
                                    <?= substr($customer['name'], 0, 1) ?>
                                </div>
                                <div>
                                    <div class="font-medium text-gray-900"><?= $customer['name'] ?></div>
                                    <div class="text-xs text-gray-500">Desde
                                        <?= date('d/m/Y', strtotime($customer['created_at'])) ?>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            <div><i class="fas fa-envelope w-4"></i> <?= $customer['email'] ?></div>
                            <?php if ($customer['phone']): ?>
                                <div><i class="fas fa-phone w-4"></i> <?= $customer['phone'] ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 font-bold text-gray-900">
                            R$ <?= number_format($customer['total_spent'], 2, ',', '.') ?>
                        </td>
                        <td class="px-6 py-4 text-gray-700">
                            <?= $customer['orders_count'] ?> pedidos
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            <?php if ($lastOrder): ?>
                                <div><?= date('d/m/Y', $lastOrder) ?></div>
                                <div class="text-xs"><?= $daysSinceOrder ?> dias atrás</div>
                            <?php else: ?>
                                <span class="text-gray-400">Nunca pediu</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <?php if ($isInactive && $lastOrder): ?>
                                <span
                                    class="px-3 py-1 bg-red-100 text-red-600 rounded-full text-xs font-bold whitespace-nowrap">
                                    <i class="fas fa-exclamation-circle mr-1"></i> Inativo +60d
                                </span>
                            <?php elseif ($customer['total_spent'] > 500): ?>
                                <span
                                    class="px-3 py-1 bg-yellow-100 text-yellow-700 rounded-full text-xs font-bold whitespace-nowrap">
                                    <i class="fas fa-star mr-1"></i> VIP
                                </span>
                            <?php else: ?>
                                <span class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-xs font-bold">Ativo</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../../views/admin/layouts/footer.php'; ?>