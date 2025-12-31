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

$categories = $db->query("SELECT * FROM categories ORDER BY name")->fetchAll();
$product = null;
$error = null;

if (isset($_GET['id'])) {
    $stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $product = $stmt->fetch();
    
    if (!$product) {
        header('Location: products.php');
        exit;
    }
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $categoryId = $_POST['category_id'];
    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $imageUrl = $_POST['image_url'];

    // Handle Image Upload
    if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../../public/assets/images/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $fileInfo = pathinfo($_FILES['image_file']['name']);
        $extension = strtolower($fileInfo['extension']);
        $validExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

        if (in_array($extension, $validExtensions)) {
            // Sanitize filename
            $cleanName = preg_replace('/[^a-zA-Z0-9_-]/', '', pathinfo($fileInfo['filename'], PATHINFO_FILENAME));
            $newFilename = $cleanName . '-' . uniqid() . '.' . $extension;
            $destination = $uploadDir . $newFilename;

            if (move_uploaded_file($_FILES['image_file']['tmp_name'], $destination)) {
                $imageUrl = 'assets/images/' . $newFilename;
            }
        }
    }
    $isCustomizable = isset($_POST['is_customizable']) ? 1 : 0;
    $maxFlavors = $_POST['max_flavors'];
    $active = isset($_POST['active']) ? 1 : 0;
    
    // Handle flavor types checkboxes
    $flavorTypes = isset($_POST['flavor_types']) ? implode(',', $_POST['flavor_types']) : 'salgado';

    if (empty($name) || empty($price)) {
        $error = "Nome e preço são obrigatórios.";
    } else {
        if ($product) {
            // Update
            $sql = "UPDATE products SET category_id=?, name=?, description=?, price=?, image_url=?, is_customizable=?, allowed_flavor_types=?, max_flavors=?, active=? WHERE id=?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$categoryId, $name, $description, $price, $imageUrl, $isCustomizable, $flavorTypes, $maxFlavors, $active, $product['id']]);
        } else {
            // Create
            $sql = "INSERT INTO products (category_id, name, description, price, image_url, is_customizable, allowed_flavor_types, max_flavors, active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $db->prepare($sql);
            $stmt->execute([$categoryId, $name, $description, $price, $imageUrl, $isCustomizable, $flavorTypes, $maxFlavors, $active]);
        }
        
        header('Location: products.php');
        exit;
    }
}

include __DIR__ . '/../../views/admin/layouts/header.php';
?>

<div class="mb-8">
    <a href="products.php" class="text-gray-500 hover:text-gray-700 flex items-center gap-2 mb-4">
        <i class="fas fa-arrow-left"></i> Voltar para Produtos
    </a>
    <h1 class="font-display font-bold text-3xl text-gray-900"><?= $product ? 'Editar Produto' : 'Novo Produto' ?></h1>
</div>

<?php if ($error): ?>
    <div class="bg-red-100 text-red-700 p-4 rounded-xl mb-6"><?= $error ?></div>
<?php endif; ?>

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden max-w-4xl">
    <form method="POST" enctype="multipart/form-data" class="p-8 space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Name -->
            <div class="col-span-2 md:col-span-1">
                <label class="block text-sm font-bold text-gray-700 mb-2">Nome do Produto</label>
                <input type="text" name="name" value="<?= $product['name'] ?? '' ?>" required
                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-brand-500 focus:border-brand-500 text-gray-900 placeholder-gray-400 transition-all">
            </div>

            <!-- Category -->
            <div class="col-span-2 md:col-span-1">
                <label class="block text-sm font-bold text-gray-700 mb-2">Categoria</label>
                <select name="category_id" required
                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-brand-500 focus:border-brand-500 text-gray-900 bg-white transition-all">
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= ($product['category_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>>
                            <?= $cat['name'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Description -->
            <div class="col-span-2">
                <label class="block text-sm font-bold text-gray-700 mb-2">Descrição</label>
                <textarea name="description" rows="3"
                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-brand-500 focus:border-brand-500 text-gray-900 placeholder-gray-400 transition-all"><?= $product['description'] ?? '' ?></textarea>
            </div>

            <!-- Price -->
            <div class="col-span-2 md:col-span-1">
                <label class="block text-sm font-bold text-gray-700 mb-2">Preço (R$)</label>
                <input type="number" step="0.01" name="price" value="<?= $product['price'] ?? '0.00' ?>" required
                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-brand-500 focus:border-brand-500 text-gray-900 placeholder-gray-400 transition-all">
            </div>

            <!-- Image -->
            <div class="col-span-2 md:col-span-1">
                <label class="block text-sm font-bold text-gray-700 mb-2">Imagem do Produto</label>
                
                <!-- File Input -->
                <div class="flex items-center gap-4 mb-3">
                    <?php if (!empty($product['image_url'])): ?>
                        <div class="relative w-16 h-16 rounded-lg overflow-hidden border border-gray-200 group bg-gray-100 flex-shrink-0">
                            <img src="../../public/<?= $product['image_url'] ?>" class="w-full h-full object-cover">
                        </div>
                    <?php endif; ?>
                    
                    <div class="flex-grow">
                        <label class="cursor-pointer bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 font-medium py-2 px-4 rounded-lg shadow-sm transition-all flex items-center gap-2 justify-center w-full">
                            <i class="fas fa-cloud-upload-alt text-brand-600"></i>
                            <span>Escolher Arquivo</span>
                            <input type="file" name="image_file" accept="image/*" class="hidden" onchange="document.getElementById('fileNameDisplay').textContent = this.files[0] ? this.files[0].name : '';">
                        </label>
                        <span id="fileNameDisplay" class="text-xs text-gray-500 truncate block mt-1 text-center h-4"></span>
                    </div>
                </div>

                <!-- Fallback URL Input -->
                <label class="block text-xs text-gray-500 mb-1">Ou cole uma URL externa:</label>
                <input type="url" name="image_url" value="<?= $product['image_url'] ?? '' ?>" placeholder="https://..."
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-500 focus:border-brand-500 text-gray-900 transition-all">
            </div>

            <div class="col-span-2 border-t border-gray-100 my-4 pt-6"></div>

            <!-- Customization Settings -->
            <div class="col-span-2">
                <h3 class="font-bold text-gray-900 text-lg mb-4">Configurações de Sabor</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="flex items-center">
                        <input type="checkbox" id="is_cust" name="is_customizable" value="1" <?= ($product['is_customizable'] ?? 0) ? 'checked' : '' ?>
                            class="w-5 h-5 text-brand-600 border-gray-300 rounded focus:ring-brand-500">
                        <label for="is_cust" class="ml-2 text-gray-700 font-medium select-none">Permitir escolha de sabores?</label>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Máximo de Sabores</label>
                        <input type="number" name="max_flavors" value="<?= $product['max_flavors'] ?? '1' ?>" min="1"
                            class="w-24 px-4 py-2 border border-gray-300 rounded-xl text-center">
                    </div>

                    <div class="col-span-2">
                        <label class="block text-sm font-bold text-gray-700 mb-2">Tipos de Sabor Permitidos</label>
                        <div class="flex flex-wrap gap-4">
                            <?php 
                            $types = ['salgado', 'doce', 'calzone', 'refrigerante', 'cerveja'];
                            $currentTypes = explode(',', $product['allowed_flavor_types'] ?? 'salgado');
                            foreach ($types as $type): 
                            ?>
                                <label class="inline-flex items-center bg-gray-50 px-3 py-2 rounded-lg border border-gray-200 cursor-pointer hover:bg-gray-100">
                                    <input type="checkbox" name="flavor_types[]" value="<?= $type ?>" 
                                        <?= in_array($type, $currentTypes) ? 'checked' : '' ?>
                                        class="w-4 h-4 text-brand-600 border-gray-300 rounded focus:ring-brand-500">
                                    <span class="ml-2 text-sm text-gray-700 capitalize"><?= $type ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-span-2 border-t border-gray-100 my-4 pt-6"></div>

            <div class="col-span-2">
                <label class="inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="active" value="1" <?= ($product['active'] ?? 1) ? 'checked' : '' ?> class="sr-only peer">
                    <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-brand-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-600"></div>
                    <span class="ml-3 text-sm font-medium text-gray-900">Produto Ativo (Visível no Cardápio)</span>
                </label>
            </div>
        </div>

        <div class="flex items-center justify-end gap-4 pt-6">
            <a href="products.php" class="px-6 py-3 text-gray-700 font-bold hover:bg-gray-100 rounded-xl transition-colors">Cancelar</a>
            <button type="submit" class="px-8 py-3 bg-brand-600 text-white font-bold rounded-xl shadow-lg hover:bg-brand-700 transition-all hover:shadow-xl transform hover:-translate-y-0.5">
                Salvar Produto
            </button>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../../views/admin/layouts/footer.php'; ?>
