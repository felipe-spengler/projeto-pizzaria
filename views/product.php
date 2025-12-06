<?php
use App\Config\Database;

$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: /menu');
    exit;
}

$db = Database::getInstance()->getConnection();
$stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    echo "Produto não encontrado";
    exit;
}

$flavors = [];
if ($product['is_customizable']) {
    $flavors = $db->query("SELECT * FROM flavors WHERE is_available = 1")->fetchAll();
}

include __DIR__ . '/layouts/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <div class="bg-white rounded-3xl shadow-xl overflow-hidden border border-gray-100">
        <div class="grid grid-cols-1 lg:grid-cols-2">
            <!-- Image Side -->
            <div class="h-96 lg:h-auto relative bg-gray-100">
                <?php if ($product['image_url']): ?>
                    <img src="<?= $product['image_url'] ?>" alt="<?= $product['name'] ?>"
                        class="w-full h-full object-cover">
                <?php else: ?>
                    <div class="flex items-center justify-center h-full text-gray-400">
                        <i class="fas fa-image text-6xl"></i>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Details Side -->
            <div class="p-8 lg:p-12">
                <a href="/menu"
                    class="inline-flex items-center text-gray-500 hover:text-brand-600 mb-6 transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i> Voltar ao cardápio
                </a>

                <h1 class="font-display font-bold text-4xl text-gray-900 mb-2"><?= $product['name'] ?></h1>
                <p class="text-2xl text-brand-600 font-bold mb-6">R$
                    <?= number_format($product['price'], 2, ',', '.') ?></p>
                <p class="text-gray-600 mb-8 leading-relaxed"><?= $product['description'] ?></p>

                <form action="/cart/add" method="POST" id="addToCartForm">
                    <input type="hidden" name="product_id" value="<?= $product['id'] ?>">

                    <?php if ($product['is_customizable']): ?>
                        <div class="mb-8">
                            <h3 class="font-bold text-gray-900 text-lg mb-4 flex items-center">
                                <i class="fas fa-utensils text-brand-500 mr-2"></i>
                                Escolha os sabores
                                <span class="ml-2 text-sm font-normal text-gray-500">(Máximo:
                                    <?= $product['max_flavors'] ?>)</span>
                            </h3>

                            <div class="space-y-4 max-h-96 overflow-y-auto pr-2 custom-scrollbar">
                                <?php foreach ($flavors as $flavor): ?>
                                    <label
                                        class="flex items-start p-4 rounded-xl border border-gray-200 cursor-pointer hover:border-brand-300 hover:bg-brand-50 transition-all select-none">
                                        <input type="checkbox" name="flavors[]" value="<?= $flavor['id'] ?>"
                                            class="mt-1 w-5 h-5 text-brand-600 rounded border-gray-300 focus:ring-brand-500 flavor-checkbox"
                                            data-price="<?= $flavor['additional_price'] ?>">
                                        <div class="ml-3">
                                            <div class="flex justify-between w-full">
                                                <span class="font-semibold text-gray-900"><?= $flavor['name'] ?></span>
                                                <?php if ($flavor['additional_price'] > 0): ?>
                                                    <span class="text-sm font-medium text-brand-600">+ R$
                                                        <?= number_format($flavor['additional_price'], 2, ',', '.') ?></span>
                                                <?php endif; ?>
                                            </div>
                                            <p class="text-sm text-gray-500 mt-1"><?= $flavor['description'] ?></p>
                                        </div>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                            <p id="flavor-error" class="text-red-500 text-sm mt-2 hidden">Por favor selecione pelo menos 1
                                sabor.</p>
                        </div>
                    <?php endif; ?>

                    <div class="flex flex-col sm:flex-row gap-4 pt-6 border-t border-gray-100">
                        <div class="w-32">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Quantidade</label>
                            <div class="flex items-center border border-gray-300 rounded-xl overflow-hidden">
                                <button type="button" class="px-4 py-3 hover:bg-gray-100 transition-colors"
                                    onclick="this.nextElementSibling.stepDown()">-</button>
                                <input type="number" name="quantity" value="1" min="1" max="10"
                                    class="w-full text-center border-none focus:ring-0 p-0">
                                <button type="button" class="px-4 py-3 hover:bg-gray-100 transition-colors"
                                    onclick="this.previousElementSibling.stepUp()">+</button>
                            </div>
                        </div>
                        <button type="submit"
                            class="flex-grow btn-primary flex items-center justify-center gap-3 text-lg">
                            <span>Adicionar ao Pedido</span>
                            <i class="fas fa-shopping-bag"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    const maxFlavors = <?= $product['max_flavors'] ?? 0 ?>;
    const isCustomizable = <?= $product['is_customizable'] ? 'true' : 'false' ?>;
    const checkboxes = document.querySelectorAll('.flavor-checkbox');

    if (isCustomizable) {
        checkboxes.forEach(cb => {
            cb.addEventListener('change', () => {
                const checked = document.querySelectorAll('.flavor-checkbox:checked');
                if (checked.length > maxFlavors) {
                    cb.checked = false;
                    alert(`Você pode escolher no máximo ${maxFlavors} sabores.`);
                }
            });
        });

        document.getElementById('addToCartForm').addEventListener('submit', (e) => {
            const checked = document.querySelectorAll('.flavor-checkbox:checked');
            if (checked.length === 0) {
                e.preventDefault();
                document.getElementById('flavor-error').classList.remove('hidden');
            }
        });
    }
</script>

<?php include __DIR__ . '/layouts/footer.php'; ?>