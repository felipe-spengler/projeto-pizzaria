<?php
session_start();
// Validação básica de admin (ajuste conforme seu sistema de auth)
if (!isset($_SESSION['user_id']) && !isset($_SESSION['admin_logged'])) {
    // header('Location: /login.php'); // Descomente em produção
}

require_once __DIR__ . '/../../vendor/autoload.php';
// Database Connection
use App\Config\Database;
$db = Database::getInstance()->getConnection();

// --- Queries de Métricas ---

// 1. Visitas por Dia (Últimos 7 dias)
$dailyVisits = $db->query("
    SELECT DATE_FORMAT(created_at, '%d/%m') as date, COUNT(*) as count 
    FROM access_logs 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) 
    GROUP BY date 
    ORDER BY MIN(created_at) ASC
")->fetchAll(PDO::FETCH_ASSOC);

// 2. Por Dispositivo
$devices = $db->query("
    SELECT device_type, COUNT(*) as count 
    FROM access_logs 
    GROUP BY device_type
")->fetchAll(PDO::FETCH_ASSOC);

// 3. Top Cidades
$cities = $db->query("
    SELECT city, COUNT(*) as count 
    FROM access_logs 
    WHERE city IS NOT NULL 
    GROUP BY city 
    ORDER BY count DESC 
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/../../views/admin/layouts/header.php';
?>

<div class="p-6">
    <h1 class="text-3xl font-display font-bold text-gray-800 mb-8">Métricas de Acesso 📊</h1>

    <!-- KPI Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
            <h3 class="text-gray-500 text-sm font-bold uppercase">Total Visitas (Hoje)</h3>
            <p class="text-3xl font-bold text-brand-600 mt-2">
                <?php
                $stmt = $db->query("SELECT COUNT(*) FROM access_logs WHERE DATE(created_at) = CURDATE()");
                echo $stmt->fetchColumn();
                ?>
            </p>
        </div>
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
            <h3 class="text-gray-500 text-sm font-bold uppercase">Total Geral</h3>
            <p class="text-3xl font-bold text-gray-800 mt-2">
                <?php
                $stmt = $db->query("SELECT COUNT(*) FROM access_logs");
                echo number_format($stmt->fetchColumn());
                ?>
            </p>
        </div>
    </div>

    <!-- Charts Row 1 -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
        <!-- Daily Visits -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
            <h3 class="font-bold text-lg mb-4 text-gray-700">Acessos da Semana</h3>
            <canvas id="dailyChart"></canvas>
        </div>

        <!-- Devices -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
            <h3 class="font-bold text-lg mb-4 text-gray-700">Dispositivos</h3>
            <div class="h-64 flex justify-center">
                <canvas id="deviceChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Charts Row 2 -->
    <div class="grid grid-cols-1 gap-8">
        <!-- Top Cities -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
            <h3 class="font-bold text-lg mb-4 text-gray-700">Top Cidades</h3>
            <canvas id="cityChart" height="100"></canvas>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    // Configurações Globais
    Chart.defaults.font.family = "'Inter', sans-serif";
    Chart.defaults.color = '#64748b';

    // 1. Gráfico de Visitas
    const dailyData = <?php echo json_encode($dailyVisits); ?>;
    new Chart(document.getElementById('dailyChart'), {
        type: 'line',
        data: {
            labels: dailyData.map(d => d.date),
            datasets: [{
                label: 'Visitas',
                data: dailyData.map(d => d.count),
                borderColor: '#FF6B00', // Brand Orange
                backgroundColor: 'rgba(255, 107, 0, 0.1)',
                fill: true,
                tension: 0.4
            }]
        },
        options: { plugins: { legend: { display: false } } }
    });

    // 2. Gráfico de Dispositivos
    const deviceData = <?php echo json_encode($devices); ?>;
    new Chart(document.getElementById('deviceChart'), {
        type: 'doughnut',
        data: {
            labels: deviceData.map(d => d.device_type.charAt(0).toUpperCase() + d.device_type.slice(1)),
            datasets: [{
                data: deviceData.map(d => d.count),
                backgroundColor: ['#3b82f6', '#10b981', '#f59e0b'],
                borderWidth: 0
            }]
        }
    });

    // 3. Gráfico de Cidades
    const cityData = <?php echo json_encode($cities); ?>;
    new Chart(document.getElementById('cityChart'), {
        type: 'bar',
        data: {
            labels: cityData.map(d => d.city),
            datasets: [{
                label: 'Usuários',
                data: cityData.map(d => d.count),
                backgroundColor: '#cbd5e1',
                borderRadius: 4
            }]
        },
        options: { indexAxis: 'y' }
    });
</script>

<?php include __DIR__ . '/../../views/admin/layouts/footer.php'; ?>