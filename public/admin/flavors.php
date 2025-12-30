<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Config\Database;
use App\Config\Session;

Session::start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$db = Database::getInstance()->getConnection();

$controller = new App\Controllers\FlavorController();

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['id'])) {
    if ($_POST['action'] === 'delete') {
        $controller->delete($_POST['id']);
        header('Location: flavors.php?msg=deleted');
        exit;
    } elseif ($_POST['action'] === 'toggle') {
        $controller->toggleAvailability($_POST['id']);
        header('Location: flavors.php');
        exit;
    }
}

// Filter by Type
$typeFilter = $_GET['type'] ?? 'all';
$params = [];
$sql = "SELECT * FROM flavors";

if ($typeFilter !== 'all') {
    $sql .= " WHERE type = ?";
    $params[] = $typeFilter;
}

$sql .= " ORDER BY type, name";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$flavors = $stmt->fetchAll();

include __DIR__ . '/../../views/admin/layouts/header.php';
?>

<div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
    <div>
        <h1 class="font-display font-bold text-3xl text-gray-900">Cardápio - Sabores</h1>
        <p class="text-gray-500 text-lg">Gerencie os sabores e adicionais.</p>
    </div>
    <div class="flex gap-4">
        <form class="flex items-center">
            <select name="type" onchange="this.form.submit()"
                class="px-4 py-3 border border-gray-300 rounded-xl bg-white text-gray-700 font-medium focus:ring-2 focus:ring-brand-500 outline-none">
                <option value="all">Todos os Tipos</option>
                <option value="salgado" <?= $typeFilter === 'salgado' ? 'selected' : '' ?>>Salgados</option>
                <option value="doce" <?= $typeFilter === 'doce' ? 'selected' : '' ?>>Doces</option>
                <option value="calzone" <?= $typeFilter === 'calzone' ? 'selected' : '' ?>>Calzones</option>
                <option value="refrigerante" <?= $typeFilter === 'refrigerante' ? 'selected' : '' ?>>Bebidas</option>
            </select>
        </form>
        <a href="flavors_form.php"
            class="bg-brand-600 hover:bg-brand-700 text-white px-6 py-3 rounded-xl font-bold transition-all shadow-lg hover:shadow-xl flex items-center gap-2 whitespace-nowrap">
            <i class="fas fa-plus"></i> Novo Sabor
        </a>
    </div>
</div>

<?php if (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
    <div class="bg-green-100 text-green-700 p-4 rounded-xl mb-6">Sabor removido com sucesso!</div>
<?php endif; ?>

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead>
                <tr class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider">
                    <th class="px-6 py-4 font-semibold">Sabor</th>
                    <th class="px-6 py-4 font-semibold">Tipo</th>
                    <th class="px-6 py-4 font-semibold">Preço Adicional</th>
                    <th class="px-6 py-4 font-semibold">Disponibilidade</th>
                    <th class="px-6 py-4 font-semibold text-right">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php foreach ($flavors as $flavor): ?>
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4">
                            <div class="font-bold text-gray-900"><?= $flavor['name'] ?></div>
                            <div class="text-xs text-gray-500 line-clamp-1"><?= $flavor['description'] ?></div>
                        </td>
                        <td class="px-6 py-4">
                            <?php
                            $typeColors = [
                                'salgado' => 'bg-orange-100 text-orange-700',
                                'doce' => 'bg-pink-100 text-pink-700',
                                'calzone' => 'bg-yellow-100 text-yellow-700',
                                'refrigerante' => 'bg-blue-100 text-blue-700',
                                'cerveja' => 'bg-amber-100 text-amber-800',
                            ];
                            ?>
                            <span
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium capitalize <?= $typeColors[$flavor['type']] ?? 'bg-gray-100 text-gray-800' ?>">
                                <?= $flavor['type'] ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 text-gray-900 font-medium">
                            <?= $flavor['additional_price'] > 0 ? '+ R$ ' . number_format($flavor['additional_price'], 2, ',', '.') : '-' ?>
                        </td>
                        <td class="px-6 py-4">
                            <form method="POST" class="inline">
                                <input type="hidden" name="action" value="toggle_available">
                                <input type="hidden" name="id" value="<?= $flavor['id'] ?>">
                                <button type="submit"
                                    class="relative inline-flex items-center cursor-pointer transition-colors duration-200 focus:outline-none">
                                    <div
                                        class="<?= $flavor['is_available'] ? 'bg-green-500' : 'bg-gray-200' ?> w-11 h-6 rounded-full peer-focus:ring-2 peer-focus:ring-brand-300">
                                        <div
                                            class="absolute top-[2px] left-[2px] bg-white w-5 h-5 rounded-full transition-transform <?= $flavor['is_available'] ? 'translate-x-full' : '' ?>">
                                        </div>
                                    </div>
                                    <span
                                        class="ml-2 text-sm text-gray-900 font-medium"><?= $flavor['is_available'] ? 'Disponível' : 'Indisponível' ?></span>
                                </button>
                            </form>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <a href="flavors_form.php?id=<?= $flavor['id'] ?>"
                                    class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form method="POST" class="inline"
                                    onsubmit="return confirm('Tem certeza que deseja excluir este sabor?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $flavor['id'] ?>">
                                    <button type="submit"
                                        class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                                        title="Excluir">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../../views/admin/layouts/footer.php'; ?>