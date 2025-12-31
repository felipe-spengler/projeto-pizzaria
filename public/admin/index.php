<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Config\Database;
use App\Config\Session;

// Inicia sessão com configurações otimizadas
Session::start();

// Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// Logout Logic
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ../login.php');
    exit;
}

$db = Database::getInstance()->getConnection();

// Quick Stats (Mock logic until we have real orders populated)
// Check Active Register
$stmtReg = $db->query("SELECT * FROM cash_registers WHERE status = 'open' ORDER BY id DESC LIMIT 1");
$activeRegister = $stmtReg->fetch();

$stats = [
    'revenue' => 0,
    'orders_count' => 0,
    'pending_orders' => $db->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn() ?: 0,
    'payment_method_breakdown' => []
];

if ($activeRegister) {
    // Calculate Stats for Current Session
    $regStart = $activeRegister['opened_at'];

    $sqlStats = "SELECT 
                    SUM(total_amount) as total, 
                    COUNT(*) as count,
                    SUM(CASE WHEN payment_method = 'credit_card' OR payment_method = 'debit_card' THEN total_amount ELSE 0 END) as card,
                    SUM(CASE WHEN payment_method = 'pix' THEN total_amount ELSE 0 END) as pix,
                    SUM(CASE WHEN payment_method = 'cash' THEN total_amount ELSE 0 END) as cash
                 FROM orders 
                 WHERE status = 'completed' AND created_at >= ?";

    $stmtStats = $db->prepare($sqlStats);
    $stmtStats->execute([$regStart]);
    $resStats = $stmtStats->fetch();

    $stats['revenue'] = $resStats['total'] ?? 0;
    $stats['orders_count'] = $resStats['count'] ?? 0;
    $stats['payment_method_breakdown'] = [
        'card' => $resStats['card'] ?? 0,
        'pix' => $resStats['pix'] ?? 0,
        'cash' => $resStats['cash'] ?? 0
    ];
}

// Fetch Recent Orders
$orders = $db->query("
    SELECT o.*, u.name as customer_name 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    ORDER BY o.created_at DESC 
    LIMIT 10
")->fetchAll();

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'], $_POST['status'])) {
    $status = $_POST['status'];
    $orderId = $_POST['order_id'];
    // Mark as viewed when interacting
    $stmt = $db->prepare("UPDATE orders SET status = ?, viewed = TRUE WHERE id = ?");
    $stmt->execute([$status, $orderId]);
    header('Location: ./');
    exit;
}

// Mark as Viewed
if (isset($_GET['mark_viewed'])) {
    $orderId = $_GET['mark_viewed'];
    $stmt = $db->prepare("UPDATE orders SET viewed = TRUE WHERE id = ?");
    $stmt->execute([$orderId]);
    header('Location: ./');
    exit;
}

// Print Receipt
if (isset($_GET['print'])) {
    $orderId = $_GET['print'];
    // Mark as viewed
    $stmt = $db->prepare("UPDATE orders SET viewed = TRUE WHERE id = ?");
    $stmt->execute([$orderId]);
    // Fetch order details
    include __DIR__ . '/print_receipt.php';
    exit;
}

include __DIR__ . '/../../views/admin/layouts/header.php';
?>

<!-- Your existing Dashboard HTML here -->
<div class="mb-4 flex flex-col md:flex-row justify-between items-center gap-4">
    <div class="flex items-center gap-4">
        <?php if ($activeRegister): ?>
            <span class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-xs font-bold flex items-center gap-2">
                <i class="fas fa-circle text-[8px]"></i> Caixa Aberto
            </span>
            <span class="text-xs text-gray-500">Início:
                <?= date('d/m H:i', strtotime($activeRegister['opened_at'])) ?></span>
        <?php else: ?>
            <span
                class="px-3 py-1 bg-red-100 text-red-700 rounded-full text-xs font-bold flex items-center gap-2 animate-pulse">
                <i class="fas fa-circle text-[8px]"></i> Caixa Fechado
            </span>
        <?php endif; ?>
    </div>

    <div class="flex items-center gap-2">
        <?php if ($activeRegister): ?>
            <button onclick="openMovementsModal()"
                class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg shadow-sm flex items-center gap-2 transition-all text-sm">
                <i class="fas fa-exchange-alt"></i>
                <span>Sangria/Suprimento</span>
            </button>
            <button onclick="closeRegister()"
                class="bg-gray-900 hover:bg-gray-800 text-white font-bold py-2 px-6 rounded-lg shadow-md flex items-center gap-2 transition-all">
                <i class="fas fa-door-closed"></i>
                <span>Fechar Caixa</span>
            </button>
        <?php else: ?>
            <button onclick="document.getElementById('openRegisterModal').classList.remove('hidden')"
                class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-6 rounded-lg shadow-md flex items-center gap-2 transition-all animate-bounce">
                <i class="fas fa-door-open"></i>
                <span>Abrir Caixa</span>
            </button>
        <?php endif; ?>
    </div>
</div>

<!-- Open Register Modal -->
<div id="openRegisterModal"
    class="fixed inset-0 bg-black/80 z-[70] <?= $activeRegister ? 'hidden' : 'flex' ?> items-center justify-center backdrop-blur-sm">
    <div class="bg-white rounded-2xl p-8 max-w-sm w-full mx-4 shadow-2xl">
        <h2 class="text-2xl font-bold text-gray-900 mb-2 text-center">Abrir Caixa</h2>
        <p class="text-gray-500 mb-6 text-center text-sm">Informe o valor inicial na gaveta (Troco).</p>

        <form onsubmit="event.preventDefault(); openRegister();">
            <div class="mb-6">
                <label class="block text-sm font-bold text-gray-700 mb-2">Fundo de Troco (R$)</label>
                <input type="number" id="initialBalance" step="0.01" value="0.00"
                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 text-xl font-bold text-center">
            </div>

            <button type="submit"
                class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-4 rounded-xl transition-all flex items-center justify-center gap-2">
                <i class="fas fa-check"></i>
                Confirmar Abertura
            </button>
        </form>
    </div>
</div>

<!-- Movements Modal -->
<div id="movementsModal" class="fixed inset-0 bg-black/50 z-[70] hidden items-center justify-center backdrop-blur-sm">
    <div class="bg-white rounded-2xl p-8 max-w-sm w-full mx-4 shadow-xl relative">
        <button onclick="document.getElementById('movementsModal').classList.add('hidden')"
            class="absolute top-4 right-4 text-gray-400 hover:text-gray-600">
            <i class="fas fa-times"></i>
        </button>
        <h2 class="text-xl font-bold text-gray-900 mb-6 text-center">Sangria / Suprimento</h2>

        <div class="flex gap-2 mb-6 bg-gray-100 p-1 rounded-xl">
            <button onclick="setMovType('supply')" id="btnSupply"
                class="flex-1 py-2 rounded-lg text-sm font-bold transition-all bg-white shadow text-green-600">Suprimento</button>
            <button onclick="setMovType('bleed')" id="btnBleed"
                class="flex-1 py-2 rounded-lg text-sm font-bold text-gray-500 hover:bg-white/50 transition-all">Sangria</button>
        </div>

        <form onsubmit="event.preventDefault(); submitMovement();">
            <input type="hidden" id="movType" value="supply">

            <div class="mb-4">
                <label class="block text-sm font-bold text-gray-700 mb-2">Valor (R$)</label>
                <input type="number" id="movAmount" step="0.01" required
                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 text-xl font-bold">
            </div>

            <div class="mb-6">
                <label class="block text-sm font-bold text-gray-700 mb-2">Descrição</label>
                <input type="text" id="movDesc" required placeholder="Ex: Compra de gelo"
                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 text-sm">
            </div>

            <button type="submit"
                class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-xl transition-all shadow-lg">
                Confirmar
            </button>
        </form>
    </div>
</div>

<!-- Stats Grid -->
<div
    class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8 <?= !$activeRegister ? 'opacity-50 pointer-events-none filter blur-[2px]' : '' ?>">
    <!-- Faturamento Card Expandido -->
    <div
        class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex flex-col justify-between group hover:border-brand-200 transition-all relative overflow-hidden">
        <div class="flex justify-between items-start mb-4">
            <div>
                <p class="text-gray-500 text-xs font-semibold uppercase tracking-wider mb-1">Faturamento (Sessão)</p>
                <h3 class="text-2xl font-bold text-gray-900">R$ <?= number_format($stats['revenue'], 2, ',', '.') ?>
                </h3>
            </div>
            <div class="w-10 h-10 bg-green-50 rounded-lg flex items-center justify-center text-green-600">
                <i class="fas fa-dollar-sign"></i>
            </div>
        </div>

        <!-- Mini Breakdown -->
        <div class="grid grid-cols-3 gap-2 text-xs border-t border-gray-100 pt-3">
            <div class="text-center">
                <span class="block text-gray-400 mb-1"><i class="fas fa-credit-card"></i> Card</span>
                <span
                    class="font-bold text-gray-700">R$<?= number_format($stats['payment_method_breakdown']['card'], 0, ',', '.') ?></span>
            </div>
            <div class="text-center border-l border-r border-gray-100 px-1">
                <span class="block text-gray-400 mb-1"><i class="fas fa-qrcode"></i> Pix</span>
                <span
                    class="font-bold text-gray-700">R$<?= number_format($stats['payment_method_breakdown']['pix'], 0, ',', '.') ?></span>
            </div>
            <div class="text-center">
                <span class="block text-gray-400 mb-1"><i class="fas fa-money-bill"></i> Din</span>
                <span
                    class="font-bold text-gray-700">R$<?= number_format($stats['payment_method_breakdown']['cash'], 0, ',', '.') ?></span>
            </div>
        </div>
    </div>

    <!-- ... (Rest of your dashboard stats) ... -->
    <div
        class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex items-center justify-between group hover:border-brand-200 transition-all">
        <div>
            <p class="text-gray-500 text-xs font-semibold uppercase tracking-wider mb-1">Pedidos no Caixa</p>
            <h3 class="text-2xl font-bold text-gray-900"><?= $stats['orders_count'] ?></h3>
        </div>
        <div
            class="w-12 h-12 bg-blue-50 rounded-xl flex items-center justify-center text-blue-600 group-hover:scale-110 transition-transform">
            <i class="fas fa-shopping-bag text-xl"></i>
        </div>
    </div>
</div>

<!-- Recent Orders Table -->
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <!-- ... (Table Content) ... -->
    <div class="p-6 border-b border-gray-100 flex justify-between items-center">
        <h2 class="font-display font-bold text-lg text-gray-900">Pedidos Recentes</h2>
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
                        <tr class="hover:bg-gray-50 transition-colors <?= !$order['viewed'] ? 'bg-yellow-50' : '' ?>"
                            onclick="markViewed(<?= $order['id'] ?>)" style="cursor: pointer;"
                            data-created-at="<?= $order['created_at'] ?>" data-status="<?= $order['status'] ?>"
                            id="order-row-<?= $order['id'] ?>">
                            <td class="px-6 py-4 text-gray-900 <?= !$order['viewed'] ? 'font-bold' : 'font-medium' ?>">
                                #<?= $order['id'] ?></td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center text-gray-500 text-xs font-bold">
                                        <?= substr($order['customer_name'], 0, 1) ?>
                                    </div>
                                    <span
                                        class="text-gray-700 <?= !$order['viewed'] ? 'font-bold' : 'font-medium' ?>"><?= $order['customer_name'] ?></span>
                                    <?php if (!$order['viewed']): ?>
                                        <span
                                            class="px-2 py-0.5 bg-red-500 text-white text-xs rounded-full font-bold animate-pulse">NOVO</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <?php
                                $statusClasses = [
                                    'pending' => 'bg-yellow-100 text-yellow-700',
                                    'preparing' => 'bg-blue-100 text-blue-700',
                                    'delivery' => 'bg-orange-100 text-orange-700',
                                    'out_for_delivery' => 'bg-orange-100 text-orange-700',
                                    'ready_for_pickup' => 'bg-purple-100 text-purple-700',
                                    'completed' => 'bg-green-100 text-green-700',
                                    'cancelled' => 'bg-red-100 text-red-700',
                                ];
                                $statusLabels = [
                                    'pending' => 'Pendente',
                                    'preparing' => 'Preparando',
                                    'delivery' => 'Em Entrega',
                                    'out_for_delivery' => 'Saiu p/ Entrega',
                                    'ready_for_pickup' => 'Aguardando Retirada',
                                    'delivered' => 'Entregue',
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
                            <td class="px-6 py-4 text-right" onclick="event.stopPropagation();">
                                <!-- Print Button -->
                                <a href="print_receipt.php?id=<?= $order['id'] ?>" target="_blank"
                                    class="text-gray-600 hover:text-gray-800 font-medium text-sm mr-3" title="Imprimir Pedido">
                                    <i class="fas fa-print"></i>
                                </a>

                                <form action="" method="POST" class="inline-block">
                                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                    <?php if ($order['status'] === 'pending'): ?>
                                        <button type="submit" name="status" value="preparing"
                                            class="text-blue-600 hover:text-blue-800 font-medium text-sm mr-2"
                                            title="Aceitar e Preparar">
                                            <i class="fas fa-play"></i> Preparar
                                        </button>
                                    <?php elseif ($order['status'] === 'preparing'): ?>
                                        <?php if ($order['delivery_method'] === 'pickup'): ?>
                                            <button type="submit" name="status" value="ready_for_pickup"
                                                class="text-purple-600 hover:text-purple-800 font-medium text-sm mr-2"
                                                title="Pronto para Retirada">
                                                <i class="fas fa-shopping-bag"></i> Pronto
                                            </button>
                                        <?php else: ?>
                                            <button type="submit" name="status" value="out_for_delivery"
                                                class="text-orange-600 hover:text-orange-800 font-medium text-sm mr-2"
                                                title="Despachar">
                                                <i class="fas fa-motorcycle"></i> Despachar
                                            </button>
                                        <?php endif; ?>
                                    <?php elseif ($order['status'] === 'out_for_delivery' || $order['status'] === 'ready_for_pickup'): ?>
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

<!-- Audio Alert - Campainha de Notificação -->
<audio id="notificationSound" preload="auto" volume="0.8">
    <source src="/sounds/bell.mp3" type="audio/mpeg">
</audio>

<div class="fixed bottom-4 left-4 z-50">
    <button onclick="playNotificationSound(false)"
        class="bg-gray-800 text-white p-3 rounded-full shadow-lg hover:bg-brand-600 transition-colors"
        title="Testar Som da Campainha">
        <i class="fas fa-volume-up"></i>
    </button>
</div>

<script>
    // Configuração de áudio simplificada
    let audio = document.getElementById('notificationSound');
    let audioInitialized = false;
    let lastOrderId = <?= $orders[0]['id'] ?? 0 ?>;
    let isAlarmActive = false;
    let pollingInterval;

    // Função para inicializar áudio (requer interação do usuário primeiro)
    function initAudio() {
        if (audioInitialized) return Promise.resolve();

        // Tenta tocar e parar imediatamente para liberar o audio context do navegador
        // Isso é crucial para navegadores mobile e políticas de autoplay rigorosas
        return audio.play().then(() => {
            audio.pause();
            audio.currentTime = 0;
            audioInitialized = true;
            console.log("Audio Audio Context desbloqueado com sucesso!");
        }).catch(error => {
            console.log("Autoplay bloqueado ou interação necessária:", error);
            // Não marcamos como inicializado para tentar novamente na próxima interação
        });
    }

    // Função para tocar o som
    function playNotificationSound(loop = false) {
        // Se já foi inicializado ou estamos num evento de clique (confiamos que initAudio vai resolver ou já resolveu)

        // Primeiro garantimos a inicialização
        initAudio().then(() => {
            // Configura Loop
            audio.loop = loop;
            audio.currentTime = 0;

            const playPromise = audio.play();
            if (playPromise !== undefined) {
                playPromise.catch(err => {
                    console.log('Erro ao tocar áudio:', err);
                });
            }
        });
    }

    function stopNotificationSound() {
        audio.pause();
        audio.currentTime = 0;
        audio.loop = false;
    }

    // Inicializa áudio quando a página carrega (requer interação do usuário)
    // Vamos tentar inicializar quando o usuário interagir com a página pela primeira vez
    let userInteracted = false;

    function handleUserInteraction() {
        if (!userInteracted) {
            userInteracted = true;
            initAudio(); // Tenta desbloquear o audio na primeira interação global
        }
    }

    document.addEventListener('click', handleUserInteraction, { once: true });
    document.addEventListener('touchstart', handleUserInteraction, { once: true });
    document.addEventListener('keydown', handleUserInteraction, { once: true });

    function checkOrders() {
        if (isAlarmActive) return; // Não verifica se já estiver tocando

        const now = new Date();
        const timeString = now.toLocaleTimeString('pt-BR', { hour12: false });
        const lastUpdatedEl = document.getElementById('lastUpdatedTime');
        if (lastUpdatedEl) lastUpdatedEl.innerText = timeString;

        fetch(`/api/check_orders.php?last_id=${lastOrderId}`)
            .then(response => response.json())
            .then(data => {
                if (data.new_orders_count > 0 && data.max_id > lastOrderId) {
                    // Atualiza o último ID
                    lastOrderId = data.max_id;
                    isAlarmActive = true;

                    // Toca o som de notificação EM LOOP
                    playNotificationSound(true);

                    // Stop polling while alerting
                    clearInterval(pollingInterval);

                    // Cria Overlay de Tela Inteira
                    const overlay = document.createElement('div');
                    overlay.id = 'newOrderOverlay';
                    overlay.className = 'fixed inset-0 bg-black/80 z-[60] flex items-center justify-center backdrop-blur-sm animate-pulse';
                    overlay.innerHTML = `
                        <div class="bg-white rounded-3xl p-8 max-w-sm mx-4 text-center shadow-2xl transform scale-100 transition-transform">
                            <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
                                <i class="fas fa-bell text-4xl text-green-600 animate-swing"></i>
                            </div>
                            <h2 class="text-3xl font-bold text-gray-900 mb-2">Novo Pedido!</h2>
                            <p class="text-gray-500 mb-8">Um novo pedido acabou de chegar na cozinha.</p>
                            <button onclick="acknowledgeOrder()" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-4 px-6 rounded-xl shadow-lg shadow-green-500/30 transition-all transform hover:scale-105 active:scale-95 flex items-center justify-center gap-3">
                                <i class="fas fa-check-circle text-xl"></i>
                                <span>Aceitar e Ver Pedido</span>
                            </button>
                        </div>
                    `;

                    document.body.appendChild(overlay);

                    // Adiciona style blink global para chamar atencao no titulo da aba
                    let originalTitle = document.title;
                    window.blinkInterval = setInterval(() => {
                        document.title = document.title === "🔔 NOVO PEDIDO!" ? originalTitle : "🔔 NOVO PEDIDO!";
                    }, 1000);
                }
            })
            .catch(err => console.error('Error checking orders:', err));
    }

    // Função global para aceitar o pedido
    window.acknowledgeOrder = function () {
        stopNotificationSound();
        const overlay = document.getElementById('newOrderOverlay');
        if (overlay) overlay.remove();

        // Limpa intervalo do titulo
        if (window.blinkInterval) clearInterval(window.blinkInterval);

        // Reload para mostrar o novo pedido
        location.reload();
    };

    // Poll every 10 seconds
    pollingInterval = setInterval(checkOrders, 10000);

    // Mark as Viewed function
    function markViewed(orderId) {
        fetch(`admin.php?mark_viewed=${orderId}`, { method: 'GET' })
            .then(() => {
                // Reload to update UI
                location.reload();
            });
    }
</script>
<script>
    // Late Order Alerts Logic
    function checkLateOrders() {
        const rows = document.querySelectorAll('tr[data-created-at]');
        const now = new Date();

        rows.forEach(row => {
            const createdAtStr = row.dataset.createdAt; // YYYY-MM-DD HH:mm:ss
            const status = row.dataset.status;

            // Only alert for active orders (not completed/cancelled)
            if (['completed', 'cancelled', 'delivered'].includes(status)) return;

            // Parse Date (Compatibilidade Safari/Legacy: repalce space with T)
            const createdAt = new Date(createdAtStr.replace(' ', 'T'));
            const diffMinutes = (now - createdAt) / 1000 / 60;

            // Remove existing alert classes first
            row.classList.remove('bg-yellow-100', 'bg-orange-100', 'bg-red-100', 'animate-pulse');

            // Apply Alerts
            if (diffMinutes >= 90) {
                // Red Alert + Pulse (Every 10 mins notification logic handled by pulse visual for now)
                row.classList.add('bg-red-100');
                // Ensure specific cells don't override background heavily or add border
                row.style.borderLeft = "4px solid #ef4444";
            } else if (diffMinutes >= 75) {
                // Orange Alert
                row.classList.add('bg-orange-100');
                row.style.borderLeft = "4px solid #f97316";
            } else if (diffMinutes >= 60) {
                // Yellow Alert
                row.classList.add('bg-yellow-50');
                row.style.borderLeft = "4px solid #eab308";
            } else {
                row.style.borderLeft = "none";
            }
        });
    }

    // Run late check every minute
    setInterval(checkLateOrders, 60000);
    // Run immediately
    checkLateOrders();

    function closeRegister() {
        if (!confirm('Deseja realmente fechar o caixa? Isso irá consolidar o faturamento da sessão.')) return;

        fetch('/admin/api/close_register.php')
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    let msg = `✅ Caixa fechado com sucesso!\n\n`;
                    msg += `💰 Fat. Total: R$ ${parseFloat(data.summary.total_sales).toFixed(2)}\n`;
                    msg += `💵 Saldo Final (Gaveta): R$ ${parseFloat(data.summary.final_balance).toFixed(2)}\n\n`;
                    msg += `Detalhes:\n`;
                    msg += `💳 Cartão: R$ ${parseFloat(data.summary.details.card).toFixed(2)}\n`;
                    msg += `💠 Pix: R$ ${parseFloat(data.summary.details.pix).toFixed(2)}\n`;
                    msg += `💵 Dinheiro: R$ ${parseFloat(data.summary.details.cash).toFixed(2)}`;

                    alert(msg);
                    location.reload();
                } else {
                    alert('Erro: ' + data.message);
                }
            })
            .catch(err => {
                console.error(err);
                alert('Erro ao conectar com o servidor.');
            });
    }

    function openRegister() {
        const initial = document.getElementById('initialBalance').value;
        const formData = new FormData();
        formData.append('initial_balance', initial);

        fetch('/admin/api/open_register.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message);
                }
            });
    }

    // Movements Logic
    function openMovementsModal() {
        document.getElementById('movementsModal').classList.remove('hidden');
    }

    function setMovType(type) {
        document.getElementById('movType').value = type;
        const btnSupply = document.getElementById('btnSupply');
        const btnBleed = document.getElementById('btnBleed');

        if (type === 'supply') {
            btnSupply.className = 'flex-1 py-2 rounded-lg text-sm font-bold transition-all bg-white shadow text-green-600';
            btnBleed.className = 'flex-1 py-2 rounded-lg text-sm font-bold text-gray-500 hover:bg-white/50 transition-all';
        } else {
            btnBleed.className = 'flex-1 py-2 rounded-lg text-sm font-bold transition-all bg-white shadow text-red-600';
            btnSupply.className = 'flex-1 py-2 rounded-lg text-sm font-bold text-gray-500 hover:bg-white/50 transition-all';
        }
    }

    function submitMovement() {
        const type = document.getElementById('movType').value;
        const amount = document.getElementById('movAmount').value;
        const desc = document.getElementById('movDesc').value;

        const formData = new FormData();
        formData.append('type', type);
        formData.append('amount', amount);
        formData.append('description', desc);

        fetch('/admin/api/cash_movement.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert('Movimentação registrada!');
                    location.reload();
                } else {
                    alert('Erro: ' + data.message);
                }
            });
    }
</script>

<?php include __DIR__ . '/../../views/admin/layouts/footer.php'; ?>