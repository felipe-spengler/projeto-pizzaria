<?php
require_once __DIR__ . '/../../vendor/autoload.php';
use App\Config\Database;
use App\Config\Session;

Session::start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$db = Database::getInstance()->getConnection();

$stmt = $db->query("SELECT * FROM cash_registers ORDER BY created_at DESC");
$registers = $stmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/../../views/admin/layouts/header.php';
?>

<div class="mb-8">
    <h1 class="font-display font-bold text-3xl text-gray-900">Histórico de Caixas</h1>
    <p class="text-gray-500">Relatório financeiro por sessão.</p>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider">
                <tr>
                    <th class="px-6 py-4 font-semibold">ID</th>
                    <th class="px-6 py-4 font-semibold">Status</th>
                    <th class="px-6 py-4 font-semibold">Abertura</th>
                    <th class="px-6 py-4 font-semibold">Fechamento</th>
                    <th class="px-6 py-4 font-semibold">Faturamento</th>
                    <th class="px-6 py-4 font-semibold">Troco Inicial</th>
                    <th class="px-6 py-4 font-semibold">Saldo Final (Gaveta)</th>
                    <th class="px-6 py-4 font-semibold">Detalhes</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php if (count($registers) > 0): ?>
                    <?php foreach ($registers as $reg): ?>
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 font-medium text-gray-900">#<?= $reg['id'] ?></td>
                            <td class="px-6 py-4">
                                <?php if ($reg['status'] === 'open'): ?>
                                    <span
                                        class="px-2 py-1 bg-green-100 text-green-700 rounded-full text-xs font-bold animate-pulse">ABERTO</span>
                                <?php else: ?>
                                    <span class="px-2 py-1 bg-gray-100 text-gray-600 rounded-full text-xs font-bold">FECHADO</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4"><?= date('d/m/Y H:i', strtotime($reg['opened_at'])) ?></td>
                            <td class="px-6 py-4">
                                <?= $reg['closed_at'] ? date('d/m/Y H:i', strtotime($reg['closed_at'])) : '-' ?></td>
                            <td class="px-6 py-4 font-bold text-green-600">R$
                                <?= number_format($reg['total_sales'], 2, ',', '.') ?></td>
                            <td class="px-6 py-4 text-gray-500">R$ <?= number_format($reg['initial_balance'] ?? 0, 2, ',', '.') ?>
                            </td>
                            <td class="px-6 py-4 font-mono font-bold">R$
                                <?= number_format($reg['final_balance'] ?? 0, 2, ',', '.') ?></td>
                            <td class="px-6 py-4 text-xs">
                                <?php if ($reg['status'] === 'closed'): ?>
                                    <div class="flex gap-2">
                                        <div class="text-gray-600"><span class="font-bold">Pix:</span>
                                            <?= number_format($reg['total_pix'], 2, ',', '.') ?></div>
                                        <div class="text-gray-600"><span class="font-bold">Card:</span>
                                            <?= number_format($reg['total_card'], 2, ',', '.') ?></div>
                                        <div class="text-gray-600"><span class="font-bold">Din:</span>
                                            <?= number_format($reg['total_cash'], 2, ',', '.') ?></div>
                                    </div>
                                    <div class="text-xs text-gray-400 mt-1">
                                        Supp: <?= number_format($reg['total_supply'] ?? 0, 2, ',', '.') ?> | Sangria:
                                        <?= number_format($reg['total_bleed'] ?? 0, 2, ',', '.') ?>
                                    </div>
                                <?php else: ?>
                                    <span class="text-gray-400 italic">Em andamento...</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="px-6 py-8 text-center text-gray-500">Nenhum registro de caixa encontrado.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../../views/admin/layouts/footer.php'; ?>