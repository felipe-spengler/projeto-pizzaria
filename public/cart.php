<?php
session_start();
require_once __DIR__ . '/../vendor/autoload.php';

use App\Config\Database;

$db = Database::getInstance()->getConnection();

// --- Action Handlers ---

// 1. Add to Cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $productId = $_POST['product_id'];
    $quantity = (int) $_POST['quantity'];
    $rawFlavors = $_POST['flavors'] ?? [];
    $flavors = [];

    // Flatten multidimensional array from steps if necessary
    foreach ($rawFlavors as $item) {
        if (is_array($item)) {
            foreach ($item as $subItem)
                $flavors[] = $subItem;
        } else {
            $flavors[] = $item;
        }
    }

    // Verify Product
    $stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$productId]);
    $product = $stmt->fetch();

    if ($product) {
        $cartItem = [
            'product_id' => $product['id'],
            'name' => $product['name'],
            'price' => (float) $product['price'],
            'quantity' => $quantity,
            'flavors' => []
        ];

        // Fetch Flavor Details
        $totalFlavorPrice = 0;
        if (!empty($flavors)) {
            $in = str_repeat('?,', count($flavors) - 1) . '?';
            $stmt = $db->prepare("SELECT * FROM flavors WHERE id IN ($in)");
            $stmt->execute($flavors);
            $flavorData = $stmt->fetchAll();

            foreach ($flavorData as $f) {
                $cartItem['flavors'][] = [
                    'id' => $f['id'], // Added ID for DB insertion
                    'name' => $f['name'],
                    'price' => (float) $f['additional_price']
                ];
                $totalFlavorPrice += (float) $f['additional_price'];
            }
        }

        // Calculate unit price with flavors
        $cartItem['unit_total'] = $cartItem['price'] + $totalFlavorPrice;
        $cartItem['total'] = $cartItem['unit_total'] * $quantity;

        $_SESSION['cart'][] = $cartItem;
    }

    header('Location: cart.php');
    exit;
}

// 2. Remove from Cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'remove') {
    $index = (int) $_POST['index'];
    if (isset($_SESSION['cart'][$index])) {
        unset($_SESSION['cart'][$index]);
        $_SESSION['cart'] = array_values($_SESSION['cart']); // Reindex
    }
    header('Location: cart.php');
    exit;
}

// 3. Checkout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'checkout') {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }

    $cart = $_SESSION['cart'] ?? [];
    if (empty($cart)) {
        header('Location: cart.php');
        exit;
    }

    $userId = $_SESSION['user_id'];
    $totalAmount = array_sum(array_column($cart, 'total'));

    // Inputs
    $deliveryMethod = $_POST['delivery_method'] ?? 'pickup';
    $paymentMethod = $_POST['payment_method'] ?? 'cash';
    $notes = $_POST['notes'] ?? '';
    $changeFor = null;
    $address = '';

    if ($deliveryMethod === 'delivery') {
        $addressOption = $_POST['address_option'] ?? 'new';

        if ($addressOption === 'new') {
            // Simple address for now (will be updated with modal later)
            $address = trim($_POST['delivery_address'] ?? '');
            if (empty($address)) {
                $address = "Endereço não informado";
            }
        } else {
            // Existing address ID
            $addrId = (int) $addressOption;
            $stmtGet = $db->prepare("SELECT * FROM addresses WHERE id = ? AND user_id = ?");
            $stmtGet->execute([$addrId, $userId]);
            $addrRow = $stmtGet->fetch();

            if ($addrRow) {
                $address = "{$addrRow['street']}, {$addrRow['number']} - {$addrRow['neighborhood']}";
                if ($addrRow['complement'])
                    $address .= " ({$addrRow['complement']})";
                $address .= " - {$addrRow['city']}/{$addrRow['state']}";
            } else {
                $address = 'Endereço inválido';
            }
        }

        // Change (troco) - only for cash payment
        if ($paymentMethod === 'cash') {
            $changeFor = !empty($_POST['change_for']) ? (float) $_POST['change_for'] : null;
        }
    } else {
        $address = "Retirada no Balcão";
    }

    try {
        $db->beginTransaction();

        // Insert Order
        $stmt = $db->prepare("INSERT INTO orders (user_id, status, total_amount, delivery_address, notes, delivery_method, payment_method, change_for) VALUES (?, 'pending', ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$userId, $totalAmount, $address, $notes, $deliveryMethod, $paymentMethod, $changeFor]);
        $orderId = $db->lastInsertId();

        // Insert Items
        $stmtItem = $db->prepare("INSERT INTO order_items (order_id, product_id, quantity, unit_price, subtotal) VALUES (?, ?, ?, ?, ?)");

        foreach ($cart as $item) {
            $stmtItem->execute([$orderId, $item['product_id'], $item['quantity'], $item['unit_total'], $item['total']]);
            $itemId = $db->lastInsertId();

            // Insert Flavors
            if (!empty($item['flavors'])) {
                $stmtFlavor = $db->prepare("INSERT INTO order_item_flavors (order_item_id, flavor_id) VALUES (?, ?)");
                foreach ($item['flavors'] as $flav) {
                    $fId = is_array($flav) ? ($flav['id'] ?? null) : $flav;
                    if ($fId)
                        $stmtFlavor->execute([$itemId, $fId]);
                }
            }
        }

        $db->commit();
        unset($_SESSION['cart']);
        header("Location: cart.php?success=$orderId");
        exit;

    } catch (Exception $e) {
        $db->rollBack();
        die("Erro ao processar pedido: " . $e->getMessage());
    }
}

// --- View Rendering ---

include __DIR__ . '/../views/layouts/header.php';

// Fetch User Addresses
$userAddresses = [];
if (isset($_SESSION['user_id'])) {
    $stmtAddr = $db->prepare("SELECT * FROM addresses WHERE user_id = ? ORDER BY id DESC");
    $stmtAddr->execute([$_SESSION['user_id']]);
    $userAddresses = $stmtAddr->fetchAll();
}

// Success View with WhatsApp Button
if (isset($_GET['success'])):
    $orderId = $_GET['success'];
    // Re-fetch order details for the receipt
    $stmt = $db->prepare("SELECT o.*, u.name as user_name, u.phone FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = ?");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();

    // Fetch Items with Product Name
    $stmt = $db->prepare("SELECT oi.*, p.name as product_name FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
    $stmt->execute([$orderId]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Build Complete WhatsApp Message
    $msg = "*🍕 NOVO PEDIDO #{$orderId}*\n";
    $msg .= "================================\n";
    $msg .= "*📅 Data:* " . date('d/m/Y H:i', strtotime($order['created_at'])) . "\n";
    $msg .= "*👤 Cliente:* {$order['user_name']}\n";
    if ($order['phone']) {
        $msg .= "*📱 Telefone:* {$order['phone']}\n";
    }
    $msg .= "================================\n\n";

    // Delivery Info
    $msg .= "*📦 ENTREGA:*\n";
    if ($order['delivery_method'] == 'delivery') {
        $msg .= "🏍️ *Delivery*\n";
        $msg .= "📍 " . $order['delivery_address'] . "\n";
    } else {
        $msg .= "🏪 *RETIRADA NO BALCÃO*\n";
    }
    $msg .= "\n";

    // Payment Info (only for delivery)
    if ($order['delivery_method'] == 'delivery') {
        $msg .= "*💳 PAGAMENTO:*\n";
        $paymentLabels = [
            'pix' => '💰 PIX',
            'credit_card' => '💳 Cartão de Crédito',
            'debit_card' => '💳 Cartão de Débito',
            'cash' => '💵 Dinheiro'
        ];
        $msg .= ($paymentLabels[$order['payment_method']] ?? ucfirst($order['payment_method'])) . "\n\n";
    }

    // Items
    $msg .= "*🛒 ITENS DO PEDIDO:*\n";
    $msg .= "--------------------------------\n";
    foreach ($items as $item) {
        $msg .= "• *{$item['quantity']}x {$item['product_name']}*\n";

        // Fetch Flavors
        $stmtF = $db->prepare("SELECT f.name FROM order_item_flavors oif JOIN flavors f ON oif.flavor_id = f.id WHERE oif.order_item_id = ?");
        $stmtF->execute([$item['id']]);
        $flavors = $stmtF->fetchAll(PDO::FETCH_COLUMN);

        if ($flavors) {
            $msg .= "  _(" . implode(', ', $flavors) . ")_\n";
        }
        $msg .= "  R$ " . number_format($item['subtotal'], 2, ',', '.') . "\n\n";
    }

    // Notes
    if ($order['notes']) {
        $msg .= "--------------------------------\n";
        $msg .= "*📝 OBSERVAÇÕES:*\n";
        $msg .= $order['notes'] . "\n";
    }

    // Total
    $msg .= "================================\n";
    $msg .= "*💰 TOTAL: R$ " . number_format($order['total_amount'], 2, ',', '.') . "*\n";
    $msg .= "================================\n\n";
    $msg .= "Gostaria de confirmar meu pedido! 😋";

    $waLink = "https://wa.me/5549999459490?text=" . urlencode($msg);
    ?>
    <div class="min-h-screen bg-gray-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl shadow-xl p-8 max-w-md w-full text-center">
            <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
                <i class="fas fa-check text-4xl text-green-600"></i>
            </div>
            <h1 class="font-display font-bold text-2xl text-gray-900 mb-2">Pedido Recebido!</h1>
            <p class="text-gray-600 mb-8">Seu pedido #<?= $orderId ?> foi registrado com sucesso.</p>

            <a href="<?= $waLink ?>" target="_blank"
                class="w-full btn-primary bg-green-500 hover:bg-green-600 flex items-center justify-center gap-2 py-4 text-lg mb-4">
                <i class="fab fa-whatsapp text-2xl"></i>
                Finalizar no WhatsApp
            </a>
            <a href="menu.php" class="text-brand-600 font-medium hover:underline">Voltar ao Cardápio</a>
        </div>
    </div>
<?php else: ?>

    <!-- Default Cart View -->
    <div class="bg-gray-50 min-h-screen py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h1 class="font-display font-bold text-3xl text-gray-900 mb-8">Seu Carrinho</h1>

            <?php if (empty($_SESSION['cart'])): ?>
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-12 text-center">
                    <i class="fas fa-shopping-basket text-4xl text-gray-300 mb-4"></i>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Seu carrinho está vazio</h3>
                    <a href="menu.php" class="btn-primary mt-4">Ver Cardápio</a>
                </div>
            <?php else: ?>
                <div class="flex flex-col lg:flex-row gap-8">
                    <!-- Cart Items List -->
                    <div class="flex-grow space-y-4">
                        <?php
                        $total = 0;
                        foreach ($_SESSION['cart'] as $index => $item):
                            $total += $item['total'];
                            ?>
                            <div
                                class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex items-center justify-between group">
                                <div class="flex-grow">
                                    <h3 class="font-bold text-gray-900 text-lg"><?= $item['name'] ?></h3>
                                    <p class="text-sm text-gray-500">Qtd: <?= $item['quantity'] ?></p>
                                    <?php if (!empty($item['flavors'])): ?>
                                        <p class="text-xs text-brand-600 mt-1">
                                            + <?= implode(', ', array_column($item['flavors'], 'name')) ?>
                                        </p>
                                    <?php endif; ?>
                                </div>

                                <div class="flex items-center gap-6">
                                    <span class="font-bold text-brand-600 text-lg">R$
                                        <?= number_format($item['total'], 2, ',', '.') ?></span>

                                    <form action="cart.php" method="POST">
                                        <input type="hidden" name="action" value="remove">
                                        <input type="hidden" name="index" value="<?= $index ?>">
                                        <button type="submit"
                                            class="w-10 h-10 rounded-full bg-red-50 text-red-500 hover:bg-red-100 hover:text-red-700 transition-colors flex items-center justify-center"
                                            title="Remover item">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Checkout Summary -->
                    <div class="w-full lg:w-96">
                        <div
                            class="bg-gradient-to-br from-white to-gray-50 rounded-2xl shadow-lg border border-gray-200 overflow-hidden sticky top-24">
                            <!-- Header -->
                            <div class="bg-gradient-to-r from-brand-600 to-orange-600 p-6 text-white">
                                <h2 class="font-display font-bold text-2xl mb-2">Finalizar Pedido</h2>
                                <p class="text-brand-100 text-sm">Complete as informações abaixo</p>
                            </div>

                            <div class="p-6">
                                <!-- Total Display -->
                                <div class="bg-white rounded-xl p-4 mb-6 border-2 border-brand-100 shadow-sm">
                                    <div class="flex justify-between items-center">
                                        <span class="text-gray-600 font-medium">Total do Pedido</span>
                                        <span class="font-display font-bold text-2xl text-brand-600">R$
                                            <?= number_format($total, 2, ',', '.') ?></span>
                                    </div>
                                </div>

                                <form action="cart.php" method="POST" id="checkoutForm">
                                    <input type="hidden" name="action" value="checkout">

                                    <!-- Step 1: Delivery Method -->
                                    <div class="mb-6">
                                        <div class="flex items-center gap-2 mb-4">
                                            <div
                                                class="w-8 h-8 rounded-full bg-brand-600 text-white flex items-center justify-center font-bold text-sm">
                                                1</div>
                                            <h3 class="font-bold text-gray-900 text-lg">Como você prefere?</h3>
                                        </div>
                                        <div class="grid grid-cols-2 gap-3">
                                            <!-- Pickup Option (Default) -->
                                            <label class="relative cursor-pointer group">
                                                <input type="radio" name="delivery_method" value="pickup" checked
                                                    class="peer sr-only" onchange="updateCheckoutFlow()">
                                                <div
                                                    class="p-4 rounded-xl border-2 border-gray-200 peer-checked:border-brand-600 peer-checked:bg-brand-50 transition-all hover:border-brand-300 text-center">
                                                    <i
                                                        class="fas fa-store text-3xl text-gray-400 peer-checked:text-brand-600 mb-2 block group-hover:scale-110 transition-transform"></i>
                                                    <span
                                                        class="font-bold text-gray-700 peer-checked:text-brand-600 block text-sm">Retirar</span>
                                                    <span class="text-xs text-gray-500 block mt-1">No balcão</span>
                                                </div>
                                                <div
                                                    class="absolute -top-2 -right-2 w-6 h-6 bg-brand-600 rounded-full items-center justify-center text-white text-xs hidden peer-checked:flex">
                                                    <i class="fas fa-check"></i>
                                                </div>
                                            </label>

                                            <!-- Delivery Option -->
                                            <label class="relative cursor-pointer group">
                                                <input type="radio" name="delivery_method" value="delivery" class="peer sr-only"
                                                    onchange="updateCheckoutFlow()">
                                                <div
                                                    class="p-4 rounded-xl border-2 border-gray-200 peer-checked:border-brand-600 peer-checked:bg-brand-50 transition-all hover:border-brand-300 text-center">
                                                    <i
                                                        class="fas fa-motorcycle text-3xl text-gray-400 peer-checked:text-brand-600 mb-2 block group-hover:scale-110 transition-transform"></i>
                                                    <span
                                                        class="font-bold text-gray-700 peer-checked:text-brand-600 block text-sm">Entrega</span>
                                                    <span class="text-xs text-gray-500 block mt-1">Em casa</span>
                                                </div>
                                                <div
                                                    class="absolute -top-2 -right-2 w-6 h-6 bg-brand-600 rounded-full items-center justify-center text-white text-xs hidden peer-checked:flex">
                                                    <i class="fas fa-check"></i>
                                                </div>
                                            </label>
                                        </div>
                                    </div>

                                    <!-- Step 2: Address & Payment (Only for Delivery) -->
                                    <div id="deliveryOnlySection" class="hidden">
                                        <!-- Address -->
                                        <div class="mb-6">
                                            <div class="flex items-center gap-2 mb-4">
                                                <div
                                                    class="w-8 h-8 rounded-full bg-brand-600 text-white flex items-center justify-center font-bold text-sm">
                                                    2</div>
                                                <h3 class="font-bold text-gray-900 text-lg">Endereço de Entrega</h3>
                                            </div>

                                            <?php if (!empty($userAddresses)): ?>
                                                <div class="space-y-2 mb-3">
                                                    <?php foreach ($userAddresses as $addr): ?>
                                                        <label class="relative cursor-pointer">
                                                            <input type="radio" name="address_option" value="<?= $addr['id'] ?>"
                                                                class="peer sr-only">
                                                            <div
                                                                class="p-3 rounded-lg border-2 border-gray-200 peer-checked:border-brand-600 peer-checked:bg-brand-50 transition-all hover:border-brand-300">
                                                                <div class="flex items-start gap-3">
                                                                    <i class="fas fa-map-marker-alt text-brand-600 mt-1"></i>
                                                                    <span
                                                                        class="text-sm text-gray-800 flex-grow"><?= htmlspecialchars($addr['full_address']) ?></span>
                                                                </div>
                                                            </div>
                                                            <div
                                                                class="absolute top-3 right-3 w-5 h-5 bg-brand-600 rounded-full items-center justify-center text-white text-xs hidden peer-checked:flex">
                                                                <i class="fas fa-check"></i>
                                                            </div>
                                                        </label>
                                                    <?php endforeach; ?>

                                                    <label class="relative cursor-pointer">
                                                        <input type="radio" name="address_option" value="new" checked
                                                            class="peer sr-only"
                                                            onclick="document.getElementById('newAddressField').classList.remove('hidden')">
                                                        <div
                                                            class="p-3 rounded-lg border-2 border-dashed border-brand-300 peer-checked:border-brand-600 peer-checked:bg-brand-50 transition-all hover:border-brand-400">
                                                            <div class="flex items-center gap-3">
                                                                <i class="fas fa-plus-circle text-brand-600"></i>
                                                                <span class="text-sm font-semibold text-brand-600">Usar outro
                                                                    endereço</span>
                                                            </div>
                                                        </div>
                                                    </label>
                                                </div>
                                            <?php else: ?>
                                                <input type="hidden" name="address_option" value="new">
                                            <?php endif; ?>

                                            <div id="newAddressField" class="mt-3">
                                                <textarea name="delivery_address" rows="3"
                                                    class="w-full border-2 border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 focus:border-brand-500 p-3 transition-all"
                                                    placeholder="Rua, Número, Bairro, Complemento..."></textarea>
                                            </div>
                                        </div>

                                        <!-- Payment Method -->
                                        <div class="mb-6">
                                            <div class="flex items-center gap-2 mb-4">
                                                <div
                                                    class="w-8 h-8 rounded-full bg-brand-600 text-white flex items-center justify-center font-bold text-sm">
                                                    3</div>
                                                <h3 class="font-bold text-gray-900 text-lg">Forma de Pagamento</h3>
                                            </div>
                                            <div class="grid grid-cols-2 gap-2">
                                                <label class="relative cursor-pointer">
                                                    <input type="radio" name="payment_method" value="pix" checked
                                                        class="peer sr-only">
                                                    <div
                                                        class="p-3 rounded-lg border-2 border-gray-200 peer-checked:border-brand-600 peer-checked:bg-brand-50 transition-all hover:border-brand-300 text-center">
                                                        <i class="fas fa-qrcode text-xl text-brand-600 mb-1 block"></i>
                                                        <span class="text-xs font-bold text-gray-700 block">PIX</span>
                                                    </div>
                                                </label>
                                                <label class="relative cursor-pointer">
                                                    <input type="radio" name="payment_method" value="credit_card"
                                                        class="peer sr-only">
                                                    <div
                                                        class="p-3 rounded-lg border-2 border-gray-200 peer-checked:border-brand-600 peer-checked:bg-brand-50 transition-all hover:border-brand-300 text-center">
                                                        <i class="fas fa-credit-card text-xl text-brand-600 mb-1 block"></i>
                                                        <span class="text-xs font-bold text-gray-700 block">Crédito</span>
                                                    </div>
                                                </label>
                                                <label class="relative cursor-pointer">
                                                    <input type="radio" name="payment_method" value="debit_card"
                                                        class="peer sr-only">
                                                    <div
                                                        class="p-3 rounded-lg border-2 border-gray-200 peer-checked:border-brand-600 peer-checked:bg-brand-50 transition-all hover:border-brand-300 text-center">
                                                        <i class="fas fa-credit-card text-xl text-brand-600 mb-1 block"></i>
                                                        <span class="text-xs font-bold text-gray-700 block">Débito</span>
                                                    </div>
                                                </label>
                                                <label class="relative cursor-pointer">
                                                    <input type="radio" name="payment_method" value="cash" class="peer sr-only">
                                                    <div
                                                        class="p-3 rounded-lg border-2 border-gray-200 peer-checked:border-brand-600 peer-checked:bg-brand-50 transition-all hover:border-brand-300 text-center">
                                                        <i class="fas fa-money-bill-wave text-xl text-brand-600 mb-1 block"></i>
                                                        <span class="text-xs font-bold text-gray-700 block">Dinheiro</span>
                                                    </div>
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Observations (Always visible) -->
                                    <div class="mb-6">
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                                            <i class="fas fa-comment-dots text-brand-600 mr-1"></i>
                                            Observações (opcional)
                                        </label>
                                        <textarea name="notes" rows="3"
                                            class="w-full border-2 border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 focus:border-brand-500 p-3 transition-all"
                                            placeholder="Ex: Troco para R$ 50, sem cebola, etc..."></textarea>
                                    </div>

                                    <!-- Submit Button -->
                                    <?php if (isset($_SESSION['user_id'])): ?>
                                        <button type="submit"
                                            class="w-full bg-gradient-to-r from-brand-600 to-orange-600 hover:from-brand-500 hover:to-orange-500 text-white font-bold py-4 rounded-xl transition-all transform hover:scale-105 shadow-lg hover:shadow-xl mb-3 flex items-center justify-center gap-2">
                                            <i class="fas fa-check-circle"></i>
                                            <span>Confirmar Pedido</span>
                                            <i class="fas fa-arrow-right"></i>
                                        </button>
                                    <?php else: ?>
                                        <a href="login.php"
                                            class="block w-full text-center bg-gray-600 hover:bg-gray-700 text-white font-bold py-4 rounded-xl transition-all mb-3">
                                            <i class="fas fa-sign-in-alt mr-2"></i>
                                            Faça Login para Continuar
                                        </a>
                                    <?php endif; ?>

                                    <a href="menu.php"
                                        class="block w-full text-center border-2 border-brand-200 text-brand-600 hover:bg-brand-50 font-semibold py-3 rounded-xl transition-all">
                                        <i class="fas fa-arrow-left mr-2"></i>
                                        Continuar Comprando
                                    </a>
                                </form>
                            </div>
                        </div>
                    </div>

                    <script>
                        function updateCheckoutFlow() {
                            const deliveryMethod = document.querySelector('input[name="delivery_method"]:checked').value;
                            const deliverySection = document.getElementById('deliveryOnlySection');

                            if (deliveryMethod === 'delivery') {
                                deliverySection.classList.remove('hidden');
                            } else {
                                deliverySection.classList.add('hidden');
                            }
                        }
                    </script>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/../views/layouts/footer.php'; ?>