<?php
session_start();
require_once __DIR__ . '/../vendor/autoload.php';

use App\Config\Database;

$db = Database::getInstance()->getConnection();
$errors = [];
$guestName = trim($_POST['guest_name'] ?? '');
$guestPhone = trim($_POST['guest_phone'] ?? '');
$isLoggedIn = isset($_SESSION['user_id']);

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
    $cart = $_SESSION['cart'] ?? [];
    if (empty($cart)) {
        $errors[] = 'Seu carrinho está vazio.';
    }

    if (!$isLoggedIn && empty($guestName)) {
        $errors[] = 'Informe seu nome para continuar sem login.';
    }

    // Identify user (creates um "guest" vinculado à sessão)
    if (empty($errors)) {
        if ($isLoggedIn) {
            $userId = $_SESSION['user_id'];
        } else {
            if (!isset($_SESSION['guest_user_id'])) {
                $generatedEmail = 'guest+' . session_id() . '-' . time() . '@pedido.local';
                $stmtUser = $db->prepare("INSERT INTO users (name, email, phone, password, role) VALUES (?, ?, ?, NULL, 'customer')");
                $stmtUser->execute([$guestName, $generatedEmail, $guestPhone]);
                $_SESSION['guest_user_id'] = $db->lastInsertId();
                $_SESSION['guest_user_name'] = $guestName;
            } else {
                // Atualiza nome/telefone do convidado caso tenha sido preenchido agora
                $stmtUser = $db->prepare("UPDATE users SET name = ?, phone = ? WHERE id = ?");
                $stmtUser->execute([$guestName, $guestPhone, $_SESSION['guest_user_id']]);
            }
            $userId = $_SESSION['guest_user_id'];
        }

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
            $errors[] = "Erro ao processar pedido: " . $e->getMessage();
        }
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
        $msg .= ($paymentLabels[$order['payment_method']] ?? ucfirst($order['payment_method'])) . "\n";
        
        // Add change info if payment is cash and change_for is set
        if ($order['payment_method'] == 'cash' && $order['change_for']) {
            $msg .= "💸 Troco para: R$ " . number_format($order['change_for'], 2, ',', '.') . "\n";
        }
        
        $msg .= "\n";
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

            <?php if (!empty($errors)): ?>
                <div class="mb-6 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-xl">
                    <ul class="list-disc list-inside space-y-1">
                        <?php foreach ($errors as $err): ?>
                            <li><?= htmlspecialchars($err) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

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
                                    <input type="hidden" name="delivery_method" id="deliveryMethodInput" value="pickup">
                                    <input type="hidden" name="address_option" id="addressOptionInput" value="">
                                    <input type="hidden" name="street" id="streetInput" value="">
                                    <input type="hidden" name="number" id="numberInput" value="">
                                    <input type="hidden" name="neighborhood" id="neighborhoodInput" value="">
                                    <input type="hidden" name="complement" id="complementInput" value="">
                                    <input type="hidden" name="payment_method" id="paymentMethodInput" value="">
                                    <input type="hidden" name="change_for" id="changeForInput" value="">

                                    <?php if (!$isLoggedIn): ?>
                                        <div class="mb-6 bg-brand-50 border border-brand-200 rounded-xl p-4">
                                            <div class="flex items-center justify-between mb-3">
                                                <div class="flex items-center gap-2">
                                                    <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-brand-600 text-white text-xs font-bold">0</span>
                                                    <h3 class="font-bold text-gray-900 text-base">Continuar sem login</h3>
                                                </div>
                                                <a class="text-sm font-semibold text-brand-600 hover:text-brand-700" href="login.php?redirect=cart.php">Preferir fazer login</a>
                                            </div>
                                            <div class="space-y-3">
                                                <div>
                                                    <label class="block text-sm font-semibold text-gray-700 mb-1">Nome completo *</label>
                                                    <input type="text" name="guest_name" value="<?= htmlspecialchars($guestName) ?>" required
                                                        class="w-full border-2 border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-500 focus:border-brand-500"
                                                        placeholder="Ex: João da Silva">
                                                </div>
                                                <div>
                                                    <label class="block text-sm font-semibold text-gray-700 mb-1">Telefone (opcional)</label>
                                                    <input type="text" name="guest_phone" value="<?= htmlspecialchars($guestPhone) ?>"
                                                        class="w-full border-2 border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-500 focus:border-brand-500"
                                                        placeholder="(XX) XXXXX-XXXX">
                                                </div>
                                                <p class="text-xs text-gray-600">Usaremos seus dados apenas para identificar o pedido.</p>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <div class="mb-6 bg-green-50 border border-green-200 rounded-xl p-4">
                                            <p class="text-sm text-green-800"><i class="fas fa-user-check mr-2"></i>Você está logado. Pedido será vinculado à sua conta.</p>
                                        </div>
                                    <?php endif; ?>

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
                                                <input type="radio" name="delivery_type" value="pickup" checked
                                                    class="peer sr-only" onchange="selectDeliveryType('pickup')">
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
                                                <input type="radio" name="delivery_type" value="delivery" class="peer sr-only"
                                                    onchange="selectDeliveryType('delivery')">
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

                                    <!-- Delivery Summary (shows after modal completion) -->
                                    <div id="deliverySummary" class="hidden mb-6">
                                        <div class="bg-brand-50 border border-brand-200 rounded-lg p-4">
                                            <div class="flex justify-between items-start mb-2">
                                                <h4 class="font-bold text-brand-900 flex items-center gap-2">
                                                    <i class="fas fa-map-marker-alt"></i>
                                                    Endereço de Entrega
                                                </h4>
                                                <button type="button" onclick="openDeliveryModal()"
                                                    class="text-brand-600 hover:text-brand-700 text-sm font-semibold">
                                                    Alterar
                                                </button>
                                            </div>
                                            <p id="addressSummaryText" class="text-sm text-gray-700 mb-3"></p>

                                            <div class="border-t border-brand-200 pt-3 mt-3">
                                                <h4 class="font-bold text-brand-900 flex items-center gap-2 mb-2">
                                                    <i class="fas fa-credit-card"></i>
                                                    Pagamento
                                                </h4>
                                                <p id="paymentSummaryText" class="text-sm text-gray-700"></p>
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
                                            placeholder="Ex: sem cebola, tirar azeitona, etc..."></textarea>
                                    </div>

                                    <!-- Submit Button -->
                                    <button type="submit" id="submitBtn"
                                        class="w-full bg-gradient-to-r from-brand-600 to-orange-600 hover:from-brand-500 hover:to-orange-500 text-white font-bold py-4 rounded-xl transition-all transform hover:scale-105 shadow-lg hover:shadow-xl mb-3 flex items-center justify-center gap-2">
                                        <i class="fas fa-check-circle"></i>
                                        <span><?= $isLoggedIn ? 'Confirmar Pedido' : 'Confirmar pedido sem login' ?></span>
                                        <i class="fas fa-arrow-right"></i>
                                    </button>

                                    <a href="menu.php"
                                        class="block w-full text-center border-2 border-brand-200 text-brand-600 hover:bg-brand-50 font-semibold py-3 rounded-xl transition-all">
                                        <i class="fas fa-arrow-left mr-2"></i>
                                        Continuar Comprando
                                    </a>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Delivery Modal Wizard -->
                    <div id="deliveryModal"
                        class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4 hidden">
                        <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                            <!-- Modal Header -->
                            <div class="bg-gradient-to-r from-brand-600 to-orange-600 p-6 text-white sticky top-0 z-10">
                                <div class="flex justify-between items-center">
                                    <div>
                                        <h2 class="font-display font-bold text-2xl">Dados da Entrega</h2>
                                        <p class="text-brand-100 text-sm mt-1">Passo <span id="modalStep">1</span> de 2</p>
                                    </div>
                                    <button type="button" onclick="closeDeliveryModal()"
                                        class="text-white hover:text-gray-200 transition-colors">
                                        <i class="fas fa-times text-2xl"></i>
                                    </button>
                                </div>
                                <!-- Progress Bar -->
                                <div class="mt-4 bg-white bg-opacity-20 rounded-full h-2">
                                    <div id="progressBar" class="bg-white h-2 rounded-full transition-all duration-300"
                                        style="width: 50%"></div>
                                </div>
                            </div>

                            <!-- Step 1: Address -->
                            <div id="step1" class="p-6">
                                <h3 class="font-bold text-xl text-gray-900 mb-4 flex items-center gap-2">
                                    <i class="fas fa-map-marker-alt text-brand-600"></i>
                                    Endereço de Entrega
                                </h3>

                                <!-- Saved Addresses -->
                                <?php if (!empty($userAddresses)): ?>
                                    <div class="mb-6">
                                        <h4 class="font-semibold text-gray-700 mb-3">Endereços Salvos</h4>
                                        <div class="space-y-2">
                                            <?php foreach ($userAddresses as $addr): ?>
                                                <label class="relative cursor-pointer block">
                                                    <input type="radio" name="modal_address_option" value="<?= $addr['id'] ?>"
                                                        class="peer sr-only" onchange="selectSavedAddress(this)"
                                                        data-street="<?= htmlspecialchars($addr['street']) ?>"
                                                        data-number="<?= htmlspecialchars($addr['number']) ?>"
                                                        data-neighborhood="<?= htmlspecialchars($addr['neighborhood']) ?>"
                                                        data-complement="<?= htmlspecialchars($addr['complement'] ?? '') ?>"
                                                        data-city="<?= htmlspecialchars($addr['city']) ?>"
                                                        data-state="<?= htmlspecialchars($addr['state']) ?>">
                                                    <div
                                                        class="p-4 rounded-lg border-2 border-gray-200 peer-checked:border-brand-600 peer-checked:bg-brand-50 transition-all hover:border-brand-300">
                                                        <div class="flex items-start gap-3">
                                                            <i class="fas fa-map-marker-alt text-brand-600 mt-1"></i>
                                                            <div class="flex-grow">
                                                                <p class="font-semibold text-gray-900">
                                                                    <?= htmlspecialchars($addr['street']) ?>,
                                                                    <?= htmlspecialchars($addr['number']) ?></p>
                                                                <p class="text-sm text-gray-600">
                                                                    <?= htmlspecialchars($addr['neighborhood']) ?> -
                                                                    <?= htmlspecialchars($addr['city']) ?>/<?= htmlspecialchars($addr['state']) ?>
                                                                </p>
                                                                <?php if ($addr['complement']): ?>
                                                                    <p class="text-xs text-gray-500 italic">
                                                                        <?= htmlspecialchars($addr['complement']) ?></p>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div
                                                        class="absolute top-4 right-4 w-6 h-6 bg-brand-600 rounded-full items-center justify-center text-white text-xs hidden peer-checked:flex">
                                                        <i class="fas fa-check"></i>
                                                    </div>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>

                                        <div class="my-4 flex items-center gap-3">
                                            <div class="flex-grow border-t border-gray-300"></div>
                                            <span class="text-gray-500 text-sm font-medium">ou</span>
                                            <div class="flex-grow border-t border-gray-300"></div>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <!-- New Address Form -->
                                <div>
                                    <h4 class="font-semibold text-gray-700 mb-3">Novo Endereço</h4>
                                    <div class="space-y-4">
                                        <div class="grid grid-cols-2 gap-4">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Cidade *</label>
                                                <input type="text" id="modalCity" value="Toledo" readonly
                                                    class="w-full border-2 border-gray-300 bg-gray-100 rounded-lg px-3 py-2 text-sm">
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Estado *</label>
                                                <input type="text" id="modalState" value="PR" readonly
                                                    class="w-full border-2 border-gray-300 bg-gray-100 rounded-lg px-3 py-2 text-sm">
                                            </div>
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Bairro *</label>
                                            <input type="text" id="modalNeighborhood" required
                                                class="w-full border-2 border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-500 focus:border-brand-500"
                                                placeholder="Ex: Centro">
                                        </div>

                                        <div class="grid grid-cols-3 gap-4">
                                            <div class="col-span-2">
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Rua *</label>
                                                <input type="text" id="modalStreet" required
                                                    class="w-full border-2 border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-500 focus:border-brand-500"
                                                    placeholder="Ex: Av. Paraná">
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Número *</label>
                                                <input type="text" id="modalNumber" required
                                                    class="w-full border-2 border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-500 focus:border-brand-500"
                                                    placeholder="123">
                                            </div>
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Ponto de
                                                Referência</label>
                                            <input type="text" id="modalComplement"
                                                class="w-full border-2 border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-brand-500 focus:border-brand-500"
                                                placeholder="Ex: Próximo ao supermercado">
                                        </div>
                                    </div>
                                </div>

                                <button type="button" onclick="goToStep2()"
                                    class="mt-6 w-full bg-gradient-to-r from-brand-600 to-orange-600 hover:from-brand-500 hover:to-orange-500 text-white font-bold py-3 rounded-xl transition-all">
                                    Próximo: Forma de Pagamento
                                    <i class="fas fa-arrow-right ml-2"></i>
                                </button>
                            </div>

                            <!-- Step 2: Payment -->
                            <div id="step2" class="p-6 hidden">
                                <button type="button" onclick="backToStep1()"
                                    class="text-brand-600 hover:text-brand-700 font-semibold mb-4 flex items-center gap-2">
                                    <i class="fas fa-arrow-left"></i>
                                    Voltar
                                </button>

                                <h3 class="font-bold text-xl text-gray-900 mb-4 flex items-center gap-2">
                                    <i class="fas fa-credit-card text-brand-600"></i>
                                    Forma de Pagamento
                                </h3>

                                <div class="grid grid-cols-2 gap-3 mb-6">
                                    <label class="relative cursor-pointer">
                                        <input type="radio" name="modal_payment" value="pix" class="peer sr-only"
                                            onchange="updatePaymentSelection()">
                                        <div
                                            class="p-4 rounded-lg border-2 border-gray-200 peer-checked:border-brand-600 peer-checked:bg-brand-50 transition-all hover:border-brand-300 text-center">
                                            <i class="fas fa-qrcode text-2xl text-brand-600 mb-2 block"></i>
                                            <span class="text-sm font-bold text-gray-700 block">PIX</span>
                                        </div>
                                    </label>
                                    <label class="relative cursor-pointer">
                                        <input type="radio" name="modal_payment" value="credit_card" class="peer sr-only"
                                            onchange="updatePaymentSelection()">
                                        <div
                                            class="p-4 rounded-lg border-2 border-gray-200 peer-checked:border-brand-600 peer-checked:bg-brand-50 transition-all hover:border-brand-300 text-center">
                                            <i class="fas fa-credit-card text-2xl text-brand-600 mb-2 block"></i>
                                            <span class="text-sm font-bold text-gray-700 block">Crédito</span>
                                        </div>
                                    </label>
                                    <label class="relative cursor-pointer">
                                        <input type="radio" name="modal_payment" value="debit_card" class="peer sr-only"
                                            onchange="updatePaymentSelection()">
                                        <div
                                            class="p-4 rounded-lg border-2 border-gray-200 peer-checked:border-brand-600 peer-checked:bg-brand-50 transition-all hover:border-brand-300 text-center">
                                            <i class="fas fa-credit-card text-2xl text-brand-600 mb-2 block"></i>
                                            <span class="text-sm font-bold text-gray-700 block">Débito</span>
                                        </div>
                                    </label>
                                    <label class="relative cursor-pointer">
                                        <input type="radio" name="modal_payment" value="cash" class="peer sr-only"
                                            onchange="updatePaymentSelection()">
                                        <div
                                            class="p-4 rounded-lg border-2 border-gray-200 peer-checked:border-brand-600 peer-checked:bg-brand-50 transition-all hover:border-brand-300 text-center">
                                            <i class="fas fa-money-bill-wave text-2xl text-brand-600 mb-2 block"></i>
                                            <span class="text-sm font-bold text-gray-700 block">Dinheiro</span>
                                        </div>
                                    </label>
                                </div>

                                <!-- Change Field (only for cash) -->
                                <div id="changeField" class="hidden mb-6">
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        <i class="fas fa-hand-holding-usd text-brand-600 mr-1"></i>
                                        Precisa de troco para quanto? *
                                    </label>
                                    <div class="relative">
                                        <span
                                            class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 font-medium">R$</span>
                                        <input type="number" id="modalChange" step="0.01" min="0"
                                            class="w-full border-2 border-gray-200 rounded-lg pl-12 pr-3 py-2 text-sm focus:ring-2 focus:ring-brand-500 focus:border-brand-500"
                                            placeholder="50,00">
                                    </div>
                                    <p class="text-xs text-gray-500 mt-1">Deixe em branco se não precisar de troco</p>
                                </div>

                                <button type="button" onclick="confirmDelivery()"
                                    class="w-full bg-gradient-to-r from-green-600 to-green-500 hover:from-green-500 hover:to-green-400 text-white font-bold py-3 rounded-xl transition-all flex items-center justify-center gap-2">
                                    <i class="fas fa-check-circle"></i>
                                    Confirmar Dados
                                </button>
                            </div>
                        </div>
                    </div>

                    <script>
                        let currentModalStep = 1;
                        let selectedAddressType = 'new'; // 'new' or addressId

                        function selectDeliveryType(type) {
                            if (type === 'delivery') {
                                openDeliveryModal();
                            } else {
                                document.getElementById('deliveryMethodInput').value = 'pickup';
                                document.getElementById('deliverySummary').classList.add('hidden');
                            }
                        }

                        function openDeliveryModal() {
                            document.getElementById('deliveryModal').classList.remove('hidden');
                            currentModalStep = 1;
                            updateModalStep();
                        }

                        function closeDeliveryModal() {
                            // If closing without completing, revert to pickup
                            if (document.getElementById('deliveryMethodInput').value !== 'delivery') {
                                document.querySelector('input[name="delivery_type"][value="pickup"]').checked = true;
                            }
                            document.getElementById('deliveryModal').classList.add('hidden');
                        }

                        function selectSavedAddress(radio) {
                            selectedAddressType = radio.value;
                            document.getElementById('addressOptionInput').value = radio.value;

                            // Clear new address fields when selecting saved address
                            document.getElementById('modalNeighborhood').value = '';
                            document.getElementById('modalStreet').value = '';
                            document.getElementById('modalNumber').value = '';
                            document.getElementById('modalComplement').value = '';
                        }

                        function goToStep2() {
                            // Validate address
                            if (selectedAddressType === 'new') {
                                const neighborhood = document.getElementById('modalNeighborhood').value.trim();
                                const street = document.getElementById('modalStreet').value.trim();
                                const number = document.getElementById('modalNumber').value.trim();

                                if (!neighborhood || !street || !number) {
                                    alert('Por favor, preencha todos os campos obrigatórios do endereço.');
                                    return;
                                }

                                document.getElementById('addressOptionInput').value = 'new';
                                document.getElementById('streetInput').value = street;
                                document.getElementById('numberInput').value = number;
                                document.getElementById('neighborhoodInput').value = neighborhood;
                                document.getElementById('complementInput').value = document.getElementById('modalComplement').value.trim();
                            }

                            currentModalStep = 2;
                            updateModalStep();
                        }

                        function backToStep1() {
                            currentModalStep = 1;
                            updateModalStep();
                        }

                        function updateModalStep() {
                            document.getElementById('modalStep').textContent = currentModalStep;
                            document.getElementById('progressBar').style.width = (currentModalStep * 50) + '%';

                            if (currentModalStep === 1) {
                                document.getElementById('step1').classList.remove('hidden');
                                document.getElementById('step2').classList.add('hidden');
                            } else {
                                document.getElementById('step1').classList.add('hidden');
                                document.getElementById('step2').classList.remove('hidden');
                            }
                        }

                        function updatePaymentSelection() {
                            const paymentMethod = document.querySelector('input[name="modal_payment"]:checked')?.value;
                            const changeField = document.getElementById('changeField');

                            if (paymentMethod === 'cash') {
                                changeField.classList.remove('hidden');
                                document.getElementById('modalChange').required = false;
                            } else {
                                changeField.classList.add('hidden');
                                document.getElementById('modalChange').value = '';
                            }
                        }

                        function confirmDelivery() {
                            const paymentMethod = document.querySelector('input[name="modal_payment"]:checked')?.value;

                            if (!paymentMethod) {
                                alert('Por favor, selecione a forma de pagamento.');
                                return;
                            }

                            // Save payment data
                            document.getElementById('paymentMethodInput').value = paymentMethod;
                            document.getElementById('changeForInput').value = document.getElementById('modalChange').value || '';

                            // Update delivery method
                            document.getElementById('deliveryMethodInput').value = 'delivery';

                            // Build summary text
                            let addressText = '';
                            if (selectedAddressType === 'new') {
                                const street = document.getElementById('modalStreet').value;
                                const number = document.getElementById('modalNumber').value;
                                const neighborhood = document.getElementById('modalNeighborhood').value;
                                const complement = document.getElementById('modalComplement').value;
                                const city = document.getElementById('modalCity').value;
                                const state = document.getElementById('modalState').value;

                                addressText = `${street}, ${number} - ${neighborhood}, ${city}/${state}`;
                                if (complement) addressText += ` (${complement})`;
                            } else {
                                const selectedRadio = document.querySelector('input[name="modal_address_option"]:checked');
                                if (selectedRadio) {
                                    const street = selectedRadio.dataset.street;
                                    const number = selectedRadio.dataset.number;
                                    const neighborhood = selectedRadio.dataset.neighborhood;
                                    const city = selectedRadio.dataset.city;
                                    const state = selectedRadio.dataset.state;
                                    const complement = selectedRadio.dataset.complement;

                                    addressText = `${street}, ${number} - ${neighborhood}, ${city}/${state}`;
                                    if (complement) addressText += ` (${complement})`;
                                }
                            }

                            document.getElementById('addressSummaryText').textContent = addressText;

                            // Payment summary
                            const paymentLabels = {
                                'pix': '💰 PIX',
                                'credit_card': '💳 Cartão de Crédito',
                                'debit_card': '💳 Cartão de Débito',
                                'cash': '💵 Dinheiro'
                            };
                            let paymentText = paymentLabels[paymentMethod] || paymentMethod;
                            const changeValue = document.getElementById('modalChange').value;
                            if (paymentMethod === 'cash' && changeValue) {
                                paymentText += ` - Troco para R$ ${parseFloat(changeValue).toFixed(2).replace('.', ',')}`;
                            }
                            document.getElementById('paymentSummaryText').textContent = paymentText;

                            // Show summary and close modal
                            document.getElementById('deliverySummary').classList.remove('hidden');
                            closeDeliveryModal();

                            // Check delivery radio
                            document.querySelector('input[name="delivery_type"][value="delivery"]').checked = true;
                        }
                    </script>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/../views/layouts/footer.php'; ?>