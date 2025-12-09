<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\Config\Database;
use App\Config\Session;

// Inicia sessão com configurações otimizadas
Session::start();

// Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// Logout Logic
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: login.php');
    exit;
}

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

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'], $_POST['status'])) {
    $status = $_POST['status'];
    $orderId = $_POST['order_id'];
    // Mark as viewed when interacting
    $stmt = $db->prepare("UPDATE orders SET status = ?, viewed = TRUE WHERE id = ?");
    $stmt->execute([$status, $orderId]);
    header('Location: admin.php');
    exit;
}

// Mark as Viewed
if (isset($_GET['mark_viewed'])) {
    $orderId = $_GET['mark_viewed'];
    $stmt = $db->prepare("UPDATE orders SET viewed = TRUE WHERE id = ?");
    $stmt->execute([$orderId]);
    header('Location: admin.php');
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

include __DIR__ . '/../views/admin/layouts/header.php';
?>

<!-- Your existing Dashboard HTML here -->
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

    <!-- ... (Rest of your dashboard stats) ... -->
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
                            onclick="markViewed(<?= $order['id'] ?>)" style="cursor: pointer;">
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
                            <td class="px-6 py-4 text-right" onclick="event.stopPropagation();">
                                <!-- Print Button -->
                                <a href="print_receipt.php?id=<?= $order['id'] ?>" target="_blank"
                                    class="text-gray-600 hover:text-gray-800 font-medium text-sm mr-3" title="Imprimir Pedido">
                                    <i class="fas fa-print"></i>
                                </a>

                                <form action="admin.php" method="POST" class="inline-block">
                                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                    <?php if ($order['status'] === 'pending'): ?>
                                        <button type="submit" name="status" value="preparing"
                                            class="text-blue-600 hover:text-blue-800 font-medium text-sm mr-2"
                                            title="Aceitar e Preparar">
                                            <i class="fas fa-play"></i> Preparar
                                        </button>
                                    <?php elseif ($order['status'] === 'preparing'): ?>
                                        <button type="submit" name="status" value="out_for_delivery"
                                            class="text-orange-600 hover:text-orange-800 font-medium text-sm mr-2"
                                            title="Despachar">
                                            <i class="fas fa-motorcycle"></i> Despachar
                                        </button>
                                    <?php elseif ($order['status'] === 'out_for_delivery'): ?>
                                        <button type="submit" name="status" value="delivered"
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
    <!-- Áudio de campainha de CDN público confiável -->
    <source src="https://assets.mixkit.co/sfx/preview/mixkit-doorbell-single-press-569.mp3" type="audio/mpeg">
    <!-- Fallback: áudio gerado via Web Audio API se CDN falhar -->
</audio>

<script>
    // Configuração de áudio melhorada
    let audio = document.getElementById('notificationSound');
    let audioInitialized = false;
    let lastOrderId = <?= $orders[0]['id'] ?? 0 ?>;
    
    // Função para inicializar áudio (requer interação do usuário primeiro)
    function initAudio() {
        if (audioInitialized) return;
        
        // Tenta carregar o áudio
        audio.load();
        
        // Cria um áudio de campainha simples usando Web Audio API como fallback
        if (!audio.canPlayType('audio/mpeg')) {
            createFallbackBellSound();
        }
        
        audioInitialized = true;
    }
    
    // Função para criar som de campainha usando Web Audio API
    function createFallbackBellSound() {
        try {
            const audioContext = new (window.AudioContext || window.webkitAudioContext)();
            const oscillator = audioContext.createOscillator();
            const gainNode = audioContext.createGain();
            
            oscillator.connect(gainNode);
            gainNode.connect(audioContext.destination);
            
            oscillator.frequency.value = 800; // Frequência da campainha
            oscillator.type = 'sine';
            
            gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
            gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.5);
            
            oscillator.start(audioContext.currentTime);
            oscillator.stop(audioContext.currentTime + 0.5);
            
            // Segundo toque (ding-dong)
            setTimeout(() => {
                const oscillator2 = audioContext.createOscillator();
                const gainNode2 = audioContext.createGain();
                
                oscillator2.connect(gainNode2);
                gainNode2.connect(audioContext.destination);
                
                oscillator2.frequency.value = 600;
                oscillator2.type = 'sine';
                
                gainNode2.gain.setValueAtTime(0.3, audioContext.currentTime);
                gainNode2.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.5);
                
                oscillator2.start(audioContext.currentTime);
                oscillator2.stop(audioContext.currentTime + 0.5);
            }, 300);
        } catch (e) {
            console.log('Web Audio API não disponível:', e);
        }
    }
    
    // Função para tocar o som
    function playNotificationSound() {
        if (!audioInitialized) {
            initAudio();
        }
        
        // Reseta o áudio para o início
        audio.currentTime = 0;
        
        // Tenta tocar o áudio do arquivo
        const playPromise = audio.play();
        
        if (playPromise !== undefined) {
            playPromise
                .then(() => {
                    console.log('Áudio de notificação tocado com sucesso!');
                })
                .catch(err => {
                    console.log('Tentando fallback de áudio:', err);
                    // Se falhar, usa Web Audio API
                    createFallbackBellSound();
                });
        } else {
            // Fallback para navegadores antigos
            createFallbackBellSound();
        }
    }
    
    // Inicializa áudio quando a página carrega (requer interação do usuário)
    // Vamos tentar inicializar quando o usuário interagir com a página pela primeira vez
    let userInteracted = false;
    
    function handleUserInteraction() {
        if (!userInteracted) {
            userInteracted = true;
            initAudio();
            // Toca um som silencioso para "desbloquear" o áudio
            audio.volume = 0.01;
            audio.play().then(() => {
                audio.volume = 0.8; // Volume normal
                audio.pause();
                audio.currentTime = 0;
            }).catch(() => {});
        }
    }
    
    document.addEventListener('click', handleUserInteraction, { once: true });
    document.addEventListener('touchstart', handleUserInteraction, { once: true });
    document.addEventListener('keydown', handleUserInteraction, { once: true });
    
    // Tenta inicializar automaticamente após um pequeno delay
    setTimeout(() => {
        if (!userInteracted) {
            initAudio();
        }
    }, 1000);

    function checkOrders() {
        fetch(`api/check_orders.php?last_id=${lastOrderId}`)
            .then(response => response.json())
            .then(data => {
                if (data.new_orders_count > 0 && data.max_id > lastOrderId) {
                    // Atualiza o último ID
                    lastOrderId = data.max_id;
                    
                    // Toca o som de notificação
                    playNotificationSound();
                    
                    // Mostra alerta visual
                    const alertDiv = document.createElement('div');
                    alertDiv.className = 'fixed top-4 right-4 bg-gradient-to-r from-green-500 to-green-600 text-white px-6 py-4 rounded-xl shadow-2xl z-50 animate-bounce cursor-pointer border-2 border-white';
                    alertDiv.innerHTML = '<div class="flex items-center gap-3"><i class="fas fa-bell text-2xl animate-pulse"></i><div><div class="font-bold text-lg">🔔 Novo Pedido!</div><div class="text-sm opacity-90">Clique para atualizar</div></div></div>';
                    alertDiv.onclick = () => location.reload();
                    document.body.appendChild(alertDiv);
                    
                    // Remove o alerta após 5 segundos se não clicado
                    setTimeout(() => {
                        if (alertDiv.parentNode) {
                            alertDiv.style.transition = 'opacity 0.5s';
                            alertDiv.style.opacity = '0';
                            setTimeout(() => alertDiv.remove(), 500);
                        }
                    }, 5000);

                    // Auto reload após 3 segundos (dá tempo do som tocar)
                    setTimeout(() => {
                        location.reload();
                    }, 3000);
                }
            })
            .catch(err => console.error('Error checking orders:', err));
    }

    // Poll every 10 seconds
    setInterval(checkOrders, 10000);

    // Mark as Viewed function
    function markViewed(orderId) {
        fetch(`admin.php?mark_viewed=${orderId}`, { method: 'GET' })
            .then(() => {
                // Reload to update UI
                location.reload();
            });
    }
</script>

<?php include __DIR__ . '/../views/admin/layouts/footer.php'; ?>