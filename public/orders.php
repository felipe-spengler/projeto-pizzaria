<?php
session_start();
require_once __DIR__ . '/../vendor/autoload.php';

use App\Config\Database;

// Redirect to login if not authenticated
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$db = Database::getInstance()->getConnection();
$userId = $_SESSION['user_id'];

// Fetch user's orders
$stmt = $db->prepare("
    SELECT o.*, 
           COUNT(oi.id) as item_count
    FROM orders o
    LEFT JOIN order_items oi ON o.id = oi.order_id
    WHERE o.user_id = ?
    GROUP BY o.id
    ORDER BY o.created_at DESC
");
$stmt->execute([$userId]);
$orders = $stmt->fetchAll();

include __DIR__ . '/../views/layouts/header.php';
?>

<div class="bg-gray-50 min-h-screen py-12">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-8">
            <h1 class="font-display font-bold text-3xl text-gray-900 mb-2">Meus Pedidos</h1>
            <p class="text-gray-600">Acompanhe o status dos seus pedidos</p>
        </div>

        <?php if (empty($orders)): ?>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-12 text-center">
                <i class="fas fa-shopping-bag text-5xl text-gray-300 mb-4"></i>
                <h3 class="text-xl font-bold text-gray-900 mb-2">Nenhum pedido ainda</h3>
                <p class="text-gray-600 mb-6">Que tal fazer seu primeiro pedido?</p>
                <a href="menu.php" class="btn-primary inline-block">
                    <i class="fas fa-pizza-slice mr-2"></i>
                    Ver Cardápio
                </a>
            </div>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($orders as $order):
                    // Fetch order items for this order
                    $stmtItems = $db->prepare("
                        SELECT oi.*, p.name as product_name, p.image_url
                        FROM order_items oi
                        JOIN products p ON oi.product_id = p.id
                        WHERE oi.order_id = ?
                    ");
                    $stmtItems->execute([$order['id']]);
                    $items = $stmtItems->fetchAll();

                    // Status styling
                    $statusConfig = [
                        'pending' => ['label' => 'Aguardando Confirmação', 'color' => 'yellow', 'icon' => 'clock'],
                        'preparing' => ['label' => 'Preparando', 'color' => 'blue', 'icon' => 'fire'],
                        'out_for_delivery' => ['label' => 'Saiu para Entrega', 'color' => 'purple', 'icon' => 'motorcycle'],
                        'delivered' => ['label' => 'Entregue', 'color' => 'green', 'icon' => 'check-circle'],
                        'cancelled' => ['label' => 'Cancelado', 'color' => 'red', 'icon' => 'times-circle'],
                    ];
                    $status = $statusConfig[$order['status']] ?? ['label' => $order['status'], 'color' => 'gray', 'icon' => 'question'];
                    ?>
                    <div
                        class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition-shadow">
                        <!-- Order Header -->
                        <div class="p-6 border-b border-gray-100 bg-gray-50">
                            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                                <div>
                                    <h3 class="font-bold text-lg text-gray-900">Pedido #<?= $order['id'] ?></h3>
                                    <p class="text-sm text-gray-500">
                                        <?= date('d/m/Y às H:i', strtotime($order['created_at'])) ?>
                                    </p>
                                </div>
                                <div class="flex items-center gap-4">
                                    <span
                                        class="px-4 py-2 rounded-full text-sm font-bold bg-<?= $status['color'] ?>-100 text-<?= $status['color'] ?>-700 inline-flex items-center gap-2">
                                        <i class="fas fa-<?= $status['icon'] ?>"></i>
                                        <?= $status['label'] ?>
                                    </span>
                                    <span class="font-bold text-xl text-gray-900">
                                        R$ <?= number_format($order['total_amount'], 2, ',', '.') ?>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Order Items -->
                        <div class="p-6">
                            <div class="space-y-3 mb-4">
                                <?php foreach ($items as $item):
                                    // Fetch flavors for this item
                                    $stmtFlavors = $db->prepare("
                                        SELECT f.name 
                                        FROM order_item_flavors oif
                                        JOIN flavors f ON oif.flavor_id = f.id
                                        WHERE oif.order_item_id = ?
                                    ");
                                    $stmtFlavors->execute([$item['id']]);
                                    $flavors = $stmtFlavors->fetchAll(PDO::FETCH_COLUMN);
                                    ?>
                                    <div class="flex items-center gap-4">
                                        <div
                                            class="w-16 h-16 rounded-lg bg-gray-100 flex items-center justify-center overflow-hidden">
                                            <?php if ($item['image_url']): ?>
                                                <img src="<?= $item['image_url'] ?>" alt="<?= $item['product_name'] ?>"
                                                    class="w-full h-full object-cover">
                                            <?php else: ?>
                                                <i class="fas fa-pizza-slice text-2xl text-gray-400"></i>
                                            <?php endif; ?>
                                        </div>
                                        <div class="flex-grow">
                                            <h4 class="font-semibold text-gray-900">
                                                <?= $item['quantity'] ?>x <?= $item['product_name'] ?>
                                            </h4>
                                            <?php if (!empty($flavors)): ?>
                                                <p class="text-sm text-brand-600">
                                                    <i class="fas fa-utensils mr-1"></i>
                                                    <?= implode(', ', $flavors) ?>
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                        <div class="text-gray-600 font-medium">
                                            R$ <?= number_format($item['subtotal'], 2, ',', '.') ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <!-- Order Details -->
                            <div class="border-t border-gray-100 pt-4 mt-4">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                                    <div>
                                        <span class="text-gray-500 font-medium">Forma de Entrega:</span>
                                        <span class="text-gray-900 ml-2">
                                            <?php if ($order['delivery_method'] == 'delivery'): ?>
                                                <i class="fas fa-motorcycle text-brand-600"></i> Entrega
                                            <?php else: ?>
                                                <i class="fas fa-store text-brand-600"></i> Retirada
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                    <div>
                                        <span class="text-gray-500 font-medium">Pagamento:</span>
                                        <span class="text-gray-900 ml-2">
                                            <?php
                                            $paymentLabels = [
                                                'pix' => 'PIX',
                                                'credit_card' => 'Cartão de Crédito',
                                                'debit_card' => 'Cartão de Débito',
                                                'cash' => 'Dinheiro'
                                            ];
                                            echo $paymentLabels[$order['payment_method']] ?? ucfirst($order['payment_method']);
                                            ?>
                                        </span>
                                    </div>
                                    <?php if ($order['delivery_method'] == 'delivery' && $order['delivery_address']): ?>
                                        <div class="md:col-span-2">
                                            <span class="text-gray-500 font-medium">Endereço:</span>
                                            <span
                                                class="text-gray-900 ml-2"><?= htmlspecialchars($order['delivery_address']) ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($order['notes']): ?>
                                        <div class="md:col-span-2">
                                            <span class="text-gray-500 font-medium">Observações:</span>
                                            <span class="text-gray-900 ml-2"><?= htmlspecialchars($order['notes']) ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <?php if ($order['status'] == 'pending'): ?>
                                <div class="border-t border-gray-100 pt-4 mt-4">
                                    <p class="text-sm text-gray-600 mb-3">
                                        <i class="fas fa-info-circle text-blue-500"></i>
                                        Aguardando confirmação da pizzaria via WhatsApp
                                    </p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../views/layouts/footer.php'; ?>