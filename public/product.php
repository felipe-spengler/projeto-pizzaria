<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\Config\Database;

$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: menu.php');
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


// --- Combo Logic Definition ---
$isCombo = str_starts_with($product['name'], 'COMBO');
$comboSteps = [];

if ($isCombo) {
    if ($product['name'] === 'COMBO P') {
        $comboSteps = [
            ['title' => 'Pizza P (até 2 sabores)', 'type' => 'salgado', 'max' => 2],
            ['title' => 'Broto Doce (1 sabor)', 'type' => 'doce', 'max' => 1],
            ['title' => 'Bebida', 'type' => 'refrigerante', 'max' => 1] // User can change from default
        ];
    } elseif ($product['name'] === 'COMBO G') {
        $comboSteps = [
            ['title' => 'Pizza G (até 3 sabores)', 'type' => 'salgado', 'max' => 3], // G is usually 3 or 4, user said G
            ['title' => 'Broto Doce (1 sabor)', 'type' => 'doce', 'max' => 1],
            ['title' => 'Bebida', 'type' => 'refrigerante', 'max' => 1]
        ];
    } elseif ($product['name'] === 'COMBO GG') {
        $comboSteps = [
            ['title' => 'Pizza GG (até 4 sabores)', 'type' => 'salgado', 'max' => 4],
            ['title' => 'Broto Doce (1 sabor)', 'type' => 'doce', 'max' => 1],
            ['title' => 'Bebida', 'type' => 'refrigerante', 'max' => 1]
        ];
    } elseif ($product['name'] === 'COMBO 2 PIZZA G') {
        $comboSteps = [
            ['title' => 'Pizza G #1 (até 3 sabores)', 'type' => 'salgado', 'max' => 3],
            ['title' => 'Pizza G #2 (até 3 sabores)', 'type' => 'salgado', 'max' => 3],
            ['title' => 'Bebida', 'type' => 'refrigerante', 'max' => 1]
        ];
    }
} else {
    // Regular Product Logic (Single Step)
    if ($product['is_customizable']) {
        $types = explode(',', $product['allowed_flavor_types'] ?? 'salgado');
        $comboSteps[] = [
            'title' => 'Escolha os sabores',
            'type' => implode(',', $types), // Pass all types for query
            'max' => $product['max_flavors']
        ];
    }
}

// Fetch all necessary flavors for these steps
$allFlavors = [];
if (!empty($comboSteps)) {
    // Collect all unique types needed
    $neededTypes = [];
    foreach ($comboSteps as $step) {
        $stepTypes = explode(',', $step['type']);
        foreach ($stepTypes as $t)
            $neededTypes[] = trim($t);
    }
    $neededTypes = array_unique($neededTypes);

    // Fetch from DB
    $in = str_repeat('?,', count($neededTypes) - 1) . '?';
    $stmt = $db->prepare("SELECT * FROM flavors WHERE is_available = 1 AND type IN ($in) ORDER BY name ASC");
    $stmt->execute($neededTypes);
    $rawFlavors = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Group by type for easy access
    foreach ($rawFlavors as $f) {
        $allFlavors[$f['type']][] = $f;
    }
}
?>

<?php include __DIR__ . '/../views/layouts/header.php'; ?>

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
            <div class="p-8 lg:p-12">
                <a href="menu.php"
                    class="inline-flex items-center text-gray-500 hover:text-brand-600 mb-6 transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i> Voltar ao cardápio
                </a>

                <h1 class="font-display font-bold text-4xl text-gray-900 mb-2"><?= $product['name'] ?></h1>
                <p class="text-2xl text-brand-600 font-bold mb-6">R$
                    <?= number_format($product['price'], 2, ',', '.') ?>
                </p>
                <p class="text-gray-600 mb-8 leading-relaxed"><?= $product['description'] ?></p>

                <form action="cart.php" method="POST" id="addToCartForm">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="product_id" value="<?= $product['id'] ?>">

                    <?php foreach ($comboSteps as $index => $step):
                        // Determine which flavors available for this step
                        // If type contains comma 'salgado,doce', we merge arrays
                        $stepTypes = explode(',', $step['type']);
                        $availableFlavors = [];
                        foreach ($stepTypes as $t) {
                            if (isset($allFlavors[trim($t)])) {
                                $availableFlavors = array_merge($availableFlavors, $allFlavors[trim($t)]);
                            }
                        }
                        ?>
                        <div class="mb-8 p-6 bg-gray-50 rounded-2xl border border-gray-100 step-container"
                            data-max="<?= $step['max'] ?>" data-step-index="<?= $index ?>">
                            <h3 class="font-bold text-gray-900 text-lg mb-4 flex items-center justify-between">
                                <span>
                                    <span
                                        class="bg-brand-600 text-white w-6 h-6 rounded-full inline-flex items-center justify-center text-xs mr-2"><?= $index + 1 ?></span>
                                    <?= $step['title'] ?>
                                </span>
                                <span
                                    class="text-xs font-normal text-gray-500 bg-white px-2 py-1 rounded-lg border border-gray-200">
                                    Máx: <?= $step['max'] ?>
                                </span>
                            </h3>

                            <div class="space-y-3 max-h-64 overflow-y-auto pr-2 custom-scrollbar">
                                <?php foreach ($availableFlavors as $flavor): ?>
                                    <label
                                        class="flex items-start p-3 rounded-xl border border-gray-200 cursor-pointer hover:border-brand-300 hover:bg-white transition-all select-none bg-white">
                                        <input type="checkbox" name="flavors[<?= $index ?>][]" value="<?= $flavor['id'] ?>"
                                            class="mt-1 w-5 h-5 text-brand-600 rounded border-gray-300 focus:ring-brand-500 flavor-checkbox"
                                            data-step="<?= $index ?>">
                                        <div class="ml-3 flex-grow">
                                            <div class="flex justify-between w-full">
                                                <span class="font-medium text-gray-900 text-sm"><?= $flavor['name'] ?></span>
                                                <?php if ($flavor['additional_price'] > 0): ?>
                                                    <span
                                                        class="text-xs font-bold text-green-600 bg-green-50 px-2 py-0.5 rounded-full">+
                                                        R$
                                                        <?= number_format($flavor['additional_price'], 2, ',', '.') ?></span>
                                                <?php endif; ?>
                                            </div>
                                            <?php if ($flavor['description']): ?>
                                                <p class="text-xs text-gray-500 mt-0.5 line-clamp-1"><?= $flavor['description'] ?>
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                            <p class="step-error text-red-500 text-xs mt-2 hidden font-medium">
                                <i class="fas fa-exclamation-circle mr-1"></i> Selecione pelo menos 1 sabor neste item.
                            </p>
                        </div>
                    <?php endforeach; ?>

                    <div class="flex flex-col sm:flex-row gap-4 pt-6 border-t border-gray-100">
                        <!-- Quantity Input ... -->
                        <!-- Submit Button ... -->
                        <!-- (Reuse existing HTML for quantity/submit) -->
                        <div class="w-32">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Quantidade</label>
                            <div class="flex items-center border border-gray-300 rounded-xl overflow-hidden">
                                <button type="button" class="px-4 py-3 hover:bg-gray-100 transition-colors"
                                    onclick="this.nextElementSibling.stepDown()">-</button>
                                <input type="number" name="quantity" value="1" min="1" max="10"
                                    class="w-full text-center border-none focus:ring-0 p-0 text-gray-900 font-bold">
                                <button type="button" class="px-4 py-3 hover:bg-gray-100 transition-colors"
                                    onclick="this.previousElementSibling.stepUp()">+</button>
                            </div>
                        </div>
                        <button type="submit"
                            class="flex-grow btn-primary flex items-center justify-center gap-3 text-lg shadow-lg shadow-brand-200">
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
    document.addEventListener('DOMContentLoaded', () => {
        const form = document.getElementById('addToCartForm');
        const steps = document.querySelectorAll('.step-container');

        // Checkbox Logic per Step
        steps.forEach(step => {
            const max = parseInt(step.dataset.max);
            const stepIndex = step.dataset.stepIndex;
            const checkboxes = step.querySelectorAll(`.flavor-checkbox[data-step="${stepIndex}"]`);

            checkboxes.forEach(cb => {
                cb.addEventListener('change', () => {
                    const checkedCount = step.querySelectorAll(`.flavor-checkbox[data-step="${stepIndex}"]:checked`).length;

                    if (checkedCount > max) {
                        cb.checked = false;
                        alert(`Você só pode selecionar ${max} item(ns) nesta etapa.`); // Simple alert for now
                    }
                });
            });
        });

        // Form Validation
        form.addEventListener('submit', (e) => {
            let isValid = true;

            steps.forEach(step => {
                const stepIndex = step.dataset.stepIndex;
                const checkedCount = step.querySelectorAll(`.flavor-checkbox[data-step="${stepIndex}"]:checked`).length;
                const errorMsg = step.querySelector('.step-error');

                if (checkedCount === 0) {
                    isValid = false;
                    errorMsg.classList.remove('hidden');
                    // Scroll to error
                    step.scrollIntoView({ behavior: 'smooth', block: 'center' });
                } else {
                    errorMsg.classList.add('hidden');
                }
            });

            if (!isValid) {
                e.preventDefault();
            }
        });
    });
</script>

<?php include __DIR__ . '/../views/layouts/footer.php'; ?>