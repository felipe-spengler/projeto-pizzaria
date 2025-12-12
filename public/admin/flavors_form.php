<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Config\Database;
use App\Config\Session;

Session::start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$controller = new App\Controllers\FlavorController();
$flavor = null;
$error = null;

if (isset($_GET['id'])) {
    $flavor = $controller->show($_GET['id']);
    if (!$flavor) {
        header('Location: flavors.php');
        exit;
    }
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];

    if (empty($name)) {
        $error = "Nome é obrigatório.";
    } else {
        $data = [
            'name' => $_POST['name'],
            'description' => $_POST['description'],
            'type' => $_POST['type'],
            'additional_price' => $_POST['additional_price'],
            'is_available' => isset($_POST['is_available'])
        ];

        if ($flavor) {
            $controller->update($flavor['id'], $data);
        } else {
            $controller->store($data);
        }

        header('Location: flavors.php');
        exit;
    }
}

include __DIR__ . '/../../views/admin/layouts/header.php';
?>

<div class="mb-8">
    <a href="flavors.php" class="text-gray-500 hover:text-gray-700 flex items-center gap-2 mb-4">
        <i class="fas fa-arrow-left"></i> Voltar para Sabores
    </a>
    <h1 class="font-display font-bold text-3xl text-gray-900"><?= $flavor ? 'Editar Sabor' : 'Novo Sabor' ?></h1>
</div>

<?php if ($error): ?>
    <div class="bg-red-100 text-red-700 p-4 rounded-xl mb-6"><?= $error ?></div>
<?php endif; ?>

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden max-w-2xl">
    <form method="POST" class="p-8 space-y-6">
        <!-- Name -->
        <div>
            <label class="block text-sm font-bold text-gray-700 mb-2">Nome do Sabor</label>
            <input type="text" name="name" value="<?= $flavor['name'] ?? '' ?>" required
                class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-brand-500 focus:border-brand-500 text-gray-900 placeholder-gray-400 transition-all">
        </div>

        <!-- Type -->
        <div>
            <label class="block text-sm font-bold text-gray-700 mb-2">Tipo</label>
            <select name="type" required
                class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-brand-500 focus:border-brand-500 text-gray-900 bg-white transition-all">
                <?php $types = ['salgado', 'doce', 'calzone', 'refrigerante', 'cerveja']; ?>
                <?php foreach ($types as $t): ?>
                    <option value="<?= $t ?>" <?= ($flavor['type'] ?? '') == $t ? 'selected' : '' ?>>
                        <?= ucfirst($t) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Description -->
        <div>
            <label class="block text-sm font-bold text-gray-700 mb-2">Descrição / Ingredientes</label>
            <textarea name="description" rows="3"
                class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-brand-500 focus:border-brand-500 text-gray-900 placeholder-gray-400 transition-all"><?= $flavor['description'] ?? '' ?></textarea>
        </div>

        <!-- Additional Price -->
        <div>
            <label class="block text-sm font-bold text-gray-700 mb-2">Preço Adicional (R$)</label>
            <div class="relative">
                <span class="absolute left-4 top-3 text-gray-500">R$</span>
                <input type="number" step="0.01" name="additional_price"
                    value="<?= $flavor['additional_price'] ?? '0.00' ?>"
                    class="w-full pl-12 pr-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-brand-500 focus:border-brand-500 text-gray-900 placeholder-gray-400 transition-all">
            </div>
            <p class="text-xs text-gray-500 mt-1">Este valor será somado ao preço base do produto.</p>
        </div>

        <div class="border-t border-gray-100 my-4 pt-6"></div>

        <!-- Availability -->
        <div>
            <label class="inline-flex items-center cursor-pointer">
                <input type="checkbox" name="is_available" value="1" <?= ($flavor['is_available'] ?? 1) ? 'checked' : '' ?> class="sr-only peer">
                <div
                    class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-brand-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-600">
                </div>
                <span class="ml-3 text-sm font-medium text-gray-900">Disponível para Pedidos</span>
            </label>
        </div>

        <div class="flex items-center justify-end gap-4 pt-6">
            <a href="flavors.php"
                class="px-6 py-3 text-gray-700 font-bold hover:bg-gray-100 rounded-xl transition-colors">Cancelar</a>
            <button type="submit"
                class="px-8 py-3 bg-brand-600 text-white font-bold rounded-xl shadow-lg hover:bg-brand-700 transition-all hover:shadow-xl transform hover:-translate-y-0.5">
                Salvar Sabor
            </button>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../../views/admin/layouts/footer.php'; ?>