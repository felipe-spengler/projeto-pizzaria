<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Config\Database;
use App\Config\Session;

Session::start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$controller = new App\Controllers\ProductController();

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['id'])) {
    if ($_POST['action'] === 'delete') {
        $controller->delete($_POST['id']);
        header('Location: products.php?msg=deleted');
        exit;
    } elseif ($_POST['action'] === 'toggle_active') {
        $controller->toggleActive($_POST['id']);
        header('Location: products.php');
        exit;
    }
}

// Fetch Products
$products = $controller->index();

include __DIR__ . '/../../views/admin/layouts/header.php';
?>

<div class="flex justify-between items-center mb-8">
    <div>
        <h1 class="font-display font-bold text-3xl text-gray-900">Cardápio - Produtos</h1>
        <p class="text-gray-500 text-lg">Gerencie os produtos do cardápio.</p>
    </div>
    <a href="products_form.php"
        class="bg-brand-600 hover:bg-brand-700 text-white px-6 py-3 rounded-xl font-bold transition-all shadow-lg hover:shadow-xl flex items-center gap-2">
        <i class="fas fa-plus"></i> Novo Produto
    </a>
</div>

<?php if (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
    <div class="bg-green-100 text-green-700 p-4 rounded-xl mb-6">Produto removido com sucesso!</div>
<?php endif; ?>

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead>
                <tr class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider">
                    <th class="px-6 py-4 font-semibold">Produto</th>
                    <th class="px-6 py-4 font-semibold">Categoria</th>
                    <th class="px-6 py-4 font-semibold">Preço</th>
                    <th class="px-6 py-4 font-semibold">Customização</th>
                    <th class="px-6 py-4 font-semibold">Status</th>
                    <th class="px-6 py-4 font-semibold text-right">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php foreach ($products as $product): ?>
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-4">
                                <img src="<?= $product['image_url'] ?>" alt=""
                                    class="w-12 h-12 rounded-lg object-cover bg-gray-100">
                                <div>
                                    <div class="font-bold text-gray-900"><?= $product['name'] ?></div>
                                    <div class="text-xs text-gray-500 line-clamp-1 w-48"><?= $product['description'] ?>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span
                                class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-gray-100 text-gray-700 text-xs font-medium">
                                <i class="fas fa-<?= $product['category_icon'] ?? 'tag' ?>"></i>
                                <?= $product['category_name'] ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 font-bold text-gray-900">
                            R$ <?= number_format($product['price'], 2, ',', '.') ?>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            <?php if ($product['is_customizable']): ?>
                                <div class="flex flex-col gap-1">
                                    <span
                                        class="text-xs bg-blue-50 text-blue-600 px-2 py-0.5 rounded border border-blue-100 w-fit">
                                        Até <?= $product['max_flavors'] ?> sabores
                                    </span>
                                    <span class="text-xs text-gray-400"><?= $product['allowed_flavor_types'] ?></span>
                                </div>
                            <?php else: ?>
                                <span class="text-gray-400 text-xs">Fixo</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4">
                            <form method="POST" class="inline">
                                <input type="hidden" name="action" value="toggle_active">
                                <input type="hidden" name="id" value="<?= $product['id'] ?>">
                                <button type="submit"
                                    class="relative inline-flex items-center cursor-pointer transition-colors duration-200 focus:outline-none">
                                    <div
                                        class="<?= $product['active'] ? 'bg-green-500' : 'bg-gray-200' ?> w-11 h-6 rounded-full peer-focus:ring-2 peer-focus:ring-brand-300">
                                        <div
                                            class="absolute top-[2px] left-[2px] bg-white w-5 h-5 rounded-full transition-transform <?= $product['active'] ? 'translate-x-full' : '' ?>">
                                        </div>
                                    </div>
                                    <span
                                        class="ml-2 text-sm text-gray-900 font-medium"><?= $product['active'] ? 'Ativo' : 'Inativo' ?></span>
                                </button>
                            </form>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <a href="products_form.php?id=<?= $product['id'] ?>"
                                    class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form method="POST" class="inline"
                                    onsubmit="return confirm('Tem certeza que deseja excluir este produto?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $product['id'] ?>">
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