<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\Config\Database;
use App\Config\Session;

Session::start();

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

// --- Image Override Logic (Sync with menu.php) ---
$pNameNorm = mb_strtoupper($product['name'] ?? '', 'UTF-8');
if ($product['category_id'] == 2) { // Calzones
    $product['image_url'] = 'assets/images/calzone.jpg';
} elseif ($pNameNorm === 'COMBO 2 PIZZA G') {
    $product['image_url'] = 'assets/images/combo-2-pizzas.png';
} elseif ($pNameNorm === 'REFRIGERANTE 2L' || $pNameNorm === 'REFRIGERANTE 1L') {
    $product['image_url'] = 'assets/images/coca-cola-2l.png';
} elseif ($pNameNorm === 'REFRIGERANTE LATA') {
    $product['image_url'] = 'https://images.unsplash.com/photo-1622483767028-3f66f32aef97?auto=format&fit=crop&w=800&q=80';
} elseif (str_contains($pNameNorm, 'PIZZA PEQUENA')) {
    $product['image_url'] = 'https://images.unsplash.com/photo-1574071318508-1cdbab80d002?auto=format&fit=crop&w=800&q=80';
} elseif (str_contains($pNameNorm, 'PIZZA MÉDIA')) {
    $product['image_url'] = 'https://images.unsplash.com/photo-1590947132387-155cc02f3212?auto=format&fit=crop&w=800&q=80';
} elseif (str_contains($pNameNorm, 'PIZZA GRANDE')) {
    $product['image_url'] = 'https://images.unsplash.com/photo-1594007654729-407eedc4be65?auto=format&fit=crop&w=800&q=80';
} elseif (str_contains($pNameNorm, 'PIZZA GIGANTE')) {
    $product['image_url'] = 'https://images.unsplash.com/photo-1513104890138-7c749659a591?auto=format&fit=crop&w=800&q=80';
} elseif (str_contains($pNameNorm, 'PIZZA BROTO')) {
    $product['image_url'] = 'https://images.unsplash.com/photo-1588315029754-2dd089d39a1a?auto=format&fit=crop&w=800&q=80';
}
// ------------------------------------------------


// --- Combo Logic Definition ---
$isCombo = str_starts_with($product['name'], 'COMBO');
$comboSteps = [];

if ($isCombo) {
    if ($product['name'] === 'COMBO P') {
        $comboSteps = [
            ['title' => 'Pizza P (até 2 sabores)', 'type' => 'salgado,doce', 'max' => 2],
            ['title' => 'Broto Doce (1 sabor)', 'type' => 'doce', 'max' => 1],
            ['title' => 'Bebida', 'type' => 'refrigerante', 'max' => 1] // User can change from default
        ];
    } elseif ($product['name'] === 'COMBO G') {
        $comboSteps = [
            ['title' => 'Pizza G (até 3 sabores)', 'type' => 'salgado,doce', 'max' => 3], // G is usually 3 or 4, user said G
            ['title' => 'Broto Doce (1 sabor)', 'type' => 'doce', 'max' => 1],
            ['title' => 'Bebida', 'type' => 'refrigerante', 'max' => 1]
        ];
    } elseif ($product['name'] === 'COMBO GG') {
        $comboSteps = [
            ['title' => 'Pizza GG (até 4 sabores)', 'type' => 'salgado,doce', 'max' => 4],
            ['title' => 'Broto Doce (1 sabor)', 'type' => 'doce', 'max' => 1],
            ['title' => 'Bebida', 'type' => 'refrigerante', 'max' => 1]
        ];
    } elseif ($product['name'] === 'COMBO 2 PIZZA G') {
        $comboSteps = [
            ['title' => 'Pizza G #1 (até 3 sabores)', 'type' => 'salgado,doce', 'max' => 3],
            ['title' => 'Pizza G #2 (até 3 sabores)', 'type' => 'salgado,doce', 'max' => 3],
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
    $neededTypes = array_values(array_unique($neededTypes));

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

include __DIR__ . '/../views/layouts/header.php';
?>

<div class="max-w-7xl mx-auto px-0 sm:px-4 lg:px-8 py-0 sm:py-8 lg:py-12 pb-32 sm:pb-8">
    <div class="bg-white rounded-none sm:rounded-3xl shadow-xl border-0 sm:border border-gray-100">
        <div class="grid grid-cols-1 lg:grid-cols-2">
            <!-- Image Side -->
            <div
                class="h-64 sm:h-80 lg:h-auto relative bg-gray-100 sm:rounded-t-3xl lg:rounded-tr-none lg:rounded-l-3xl overflow-hidden">
                <?php if ($product['image_url']): ?>
                    <img src="<?= $product['image_url'] ?>" alt="<?= $product['name'] ?>"
                        class="w-full h-full object-cover">
                <?php else: ?>
                    <div class="flex items-center justify-center h-full text-gray-400">
                        <i class="fas fa-image text-4xl sm:text-6xl"></i>
                    </div>
                <?php endif; ?>
            </div>
            <div class="p-4 sm:p-6 lg:p-8 xl:p-12 relative">
                <a href="menu.php"
                    class="inline-flex items-center text-gray-500 hover:text-brand-600 mb-4 sm:mb-6 transition-colors text-sm sm:text-base">
                    <i class="fas fa-arrow-left mr-2"></i> Voltar ao cardápio
                </a>

                <h1 class="font-display font-bold text-2xl sm:text-3xl lg:text-4xl text-gray-900 mb-2">
                    <?= $product['name'] ?>
                </h1>
                <p class="text-xl sm:text-2xl text-brand-600 font-bold mb-4 sm:mb-6">R$
                    <?= number_format($product['price'], 2, ',', '.') ?>
                </p>
                <p class="text-gray-600 mb-4 leading-relaxed text-sm sm:text-base">
                    <?= $product['description'] ?>
                </p>

                <form action="cart.php" method="POST" id="addToCartForm" class="relative">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="product_id" value="<?= $product['id'] ?>">

                    <!-- Wizard Progress -->
                    <?php if (count($comboSteps) > 1): ?>
                        <div class="mb-6 sm:mb-8 bg-gray-50 p-4 rounded-xl border border-gray-100">
                            <div class="flex justify-between text-xs sm:text-sm font-bold text-gray-700 mb-2">
                                <span id="step-counter">Etapa 1 de <?= count($comboSteps) ?></span>
                                <span id="step-progress-text" class="text-brand-600">0% Completo</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2.5 sm:h-3 overflow-hidden">
                                <div id="progress-bar"
                                    class="bg-brand-600 h-2.5 sm:h-3 rounded-full transition-all duration-500 ease-out shadow-lg shadow-brand-500/30"
                                    style="width: 0%"></div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div id="wizard-steps" class="mb-4 min-h-[300px]">
                        <?php foreach ($comboSteps as $index => $step):
                            // Determine which flavors available for this step
                            $stepTypes = explode(',', $step['type']);
                            $availableFlavors = [];
                            foreach ($stepTypes as $t) {
                                if (isset($allFlavors[trim($t)])) {
                                    $availableFlavors = array_merge($availableFlavors, $allFlavors[trim($t)]);
                                }
                            }
                            ?>
                            <div class="step-container transition-all duration-300 <?= $index === 0 ? 'block' : 'hidden' ?>"
                                data-max="<?= $step['max'] ?>" data-step-index="<?= $index ?>">

                                <h3
                                    class="sticky top-20 z-40 bg-white/95 backdrop-blur-sm font-bold text-gray-900 text-lg sm:text-xl mb-4 sm:mb-6 flex items-center gap-3 py-4 border-b border-gray-100 shadow-sm">
                                    <span
                                        class="bg-brand-100 text-brand-600 w-8 h-8 sm:w-10 sm:h-10 rounded-xl inline-flex items-center justify-center text-sm sm:text-base font-bold shadow-sm ring-4 ring-white">
                                        <?= $index + 1 ?>
                                    </span>
                                    <div class="flex flex-col">
                                        <span class="flex items-center gap-2">
                                            <?= $step['title'] ?>
                                            <span id="counter-display-<?= $index ?>"
                                                class="text-sm font-bold text-brand-600 bg-brand-50 px-2 py-0.5 rounded-full">
                                                (0/<?= $step['max'] ?>)
                                            </span>
                                        </span>
                                        <span
                                            class="text-xs sm:text-sm font-normal text-gray-500 tracking-wide uppercase">Escolha
                                            até <?= $step['max'] ?> sabor(es)</span>
                                    </div>
                                </h3>

                                <div class="grid grid-cols-1 gap-2 sm:gap-3 pr-1 sm:pr-2 pb-2">
                                    <?php foreach ($availableFlavors as $flavor): ?>
                                        <label
                                            class="group relative flex items-start p-3 sm:p-4 rounded-xl border-2 border-transparent bg-gray-50 cursor-pointer hover:bg-white hover:border-brand-200 hover:shadow-md transition-all select-none">
                                            <input type="checkbox" name="flavors[<?= $index ?>][]" value="<?= $flavor['id'] ?>"
                                                class="peer sr-only flavor-checkbox" data-step="<?= $index ?>">

                                            <div
                                                class="w-5 h-5 sm:w-6 sm:h-6 rounded-lg border-2 border-gray-300 peer-checked:bg-brand-600 peer-checked:border-brand-600 peer-checked:text-white flex items-center justify-center flex-shrink-0 transition-all mr-3 sm:mr-4 mt-0.5">
                                                <i
                                                    class="fas fa-check text-xs sm:text-sm transform scale-0 peer-checked:scale-100 transition-transform"></i>
                                            </div>

                                            <div class="flex-grow min-w-0">
                                                <div class="flex justify-between items-start gap-2 flex-wrap">
                                                    <span
                                                        class="font-bold text-gray-900 text-sm sm:text-base group-hover:text-brand-700 transition-colors"><?= $flavor['name'] ?></span>
                                                    <?php
                                                    $showPrice = $flavor['additional_price'] > 0 && ($isCombo || !in_array($flavor['type'], ['refrigerante', 'bebida', 'cerveja']));
                                                    if ($showPrice):
                                                        ?>
                                                        <span
                                                            class="text-xs font-bold text-brand-600 bg-brand-50 px-2 py-1 rounded-full whitespace-nowrap">
                                                            + R$ <?= number_format($flavor['additional_price'], 2, ',', '.') ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                                <?php if ($flavor['description']): ?>
                                                    <p class="text-xs sm:text-sm text-gray-500 mt-1 leading-relaxed line-clamp-2">
                                                        <?= htmlspecialchars($flavor['description']) ?>
                                                    </p>
                                                <?php endif; ?>
                                            </div>

                                            <!-- Ring effect -->
                                            <div
                                                class="absolute inset-0 rounded-xl border-2 border-brand-500 opacity-0 peer-checked:opacity-100 peer-checked:border-brand-500 pointer-events-none transition-all">
                                            </div>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                                <p
                                    class="step-error text-red-500 text-sm mt-3 hidden font-medium flex items-center gap-2 bg-red-50 p-3 rounded-lg animate-pulse">
                                    <i class="fas fa-exclamation-circle"></i>
                                    <span>Selecione pelo menos 1 item para continuar.</span>
                                </p>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Wizard Controls -->
                    <div
                        class="fixed bottom-0 left-0 right-0 z-50 bg-white border-t border-gray-100 p-4 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.1)] flex flex-col sm:flex-row gap-3 sm:gap-4 sm:relative sm:shadow-none sm:p-0 sm:pt-6 sm:border-0 sm:bg-transparent transition-all">
                        <!-- Navigation Buttons -->
                        <div class="flex w-full gap-3 transition-opacity duration-300" id="nav-buttons-container">
                            <button type="button" id="prevBtn" onclick="changeStep(-1)"
                                class="hidden px-6 py-3.5 rounded-xl font-bold text-gray-700 bg-gray-100 hover:bg-gray-200 transition-colors flex-1 sm:flex-none justify-center items-center">
                                <i class="fas fa-arrow-left mr-2"></i> Voltar
                            </button>

                            <button type="button" id="nextBtn" onclick="changeStep(1)"
                                class="flex-grow bg-gray-900 hover:bg-gray-800 text-white font-bold py-3.5 px-6 rounded-xl flex items-center justify-center gap-2 transition-all shadow-lg hover:shadow-xl hover:-translate-y-1">
                                <span>Próximo</span>
                                <i class="fas fa-arrow-right"></i>
                            </button>
                        </div>

                        <!-- Final Add to Cart (Only visible on last step) -->
                        <div id="addToCartControls" class="hidden w-full flex-col sm:flex-row gap-3 animate-fade-in-up">
                            <div
                                class="w-full sm:w-32 bg-gray-100 rounded-xl flex items-center p-1 border border-gray-200">
                                <button type="button"
                                    class="w-10 h-full hover:bg-white rounded-lg transition-all text-gray-700 font-bold shadow-sm"
                                    onclick="this.nextElementSibling.stepDown()">-</button>
                                <input type="number" name="quantity" value="1" min="1" max="10"
                                    class="w-full text-center bg-transparent border-none focus:ring-0 p-0 text-gray-900 font-bold text-lg">
                                <button type="button"
                                    class="w-10 h-full hover:bg-white rounded-lg transition-all text-gray-700 font-bold shadow-sm"
                                    onclick="this.previousElementSibling.stepUp()">+</button>
                            </div>
                            <button type="submit"
                                class="flex-grow bg-gradient-to-r from-brand-600 to-orange-600 hover:from-brand-700 hover:to-orange-700 text-white font-bold py-3.5 px-6 rounded-xl flex items-center justify-center gap-2 shadow-lg shadow-brand-200/50 hover:shadow-xl hover:shadow-brand-300/50 transition-all transform hover:scale-[1.02] active:scale-[0.98]">
                                <i class="fas fa-shopping-bag text-xl"></i>
                                <span>Adicionar ao Pedido</span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const totalSteps = <?= count($comboSteps) ?>;
        let currentStep = 0;

        const steps = document.querySelectorAll('.step-container');
        const prevBtn = document.getElementById('prevBtn');
        const nextBtn = document.getElementById('nextBtn');
        const navContainer = document.getElementById('nav-buttons-container');
        const addToCartControls = document.getElementById('addToCartControls');
        const progressBar = document.getElementById('progress-bar');
        const stepProgressText = document.getElementById('step-progress-text');
        const stepCounter = document.getElementById('step-counter');

        window.changeStep = (direction) => {
            // Validate before moving forward
            if (direction === 1 && !validateStep(currentStep)) {
                return;
            }

            const newStep = currentStep + direction;
            if (newStep >= 0 && newStep < totalSteps) {
                showStep(newStep);
            }
        };

        function showStep(index) {
            // Hide current
            steps[currentStep].classList.add('hidden');
            steps[currentStep].classList.remove('block');

            // Show new
            currentStep = index;
            steps[currentStep].classList.remove('hidden');
            steps[currentStep].classList.add('block');

            // Update UI Controls
            if (currentStep === 0) {
                prevBtn.classList.add('hidden');
                prevBtn.classList.remove('flex');
            } else {
                prevBtn.classList.remove('hidden');
                prevBtn.classList.add('flex');
            }

            if (currentStep === totalSteps - 1) {
                // Last Step
                navContainer.classList.add('hidden'); // Hide the entire navigation container
                addToCartControls.classList.remove('hidden');
                addToCartControls.classList.add('flex');
            } else {
                // Middle/First Steps
                navContainer.classList.remove('hidden');
                addToCartControls.classList.add('hidden');
                addToCartControls.classList.remove('flex');
            }

            // Update Progress
            updateProgress();

            // SCRIPT ADJUSTMENT: Always scroll to the top of the wizard steps container
            // This ensures user sees the top of the list, not the middle
            const wizardContainer = document.getElementById('wizard-steps');
            if (wizardContainer) {
                // Calculate position relative to viewport to avoid jumping too far up if already visible
                const rect = wizardContainer.getBoundingClientRect();
                const offset = 100; // Buffer for sticky header

                // Only scroll if we are well below the top of the container
                window.scrollTo({
                    top: window.scrollY + rect.top - offset,
                    behavior: 'smooth'
                });
            }
        }

        function validateStep(index) {
            const step = steps[index];
            const checkboxes = step.querySelectorAll('input[type="checkbox"]:checked');
            const errorMsg = step.querySelector('.step-error');

            if (checkboxes.length === 0) {
                errorMsg.classList.remove('hidden');

                // Scroll error into view if not visible
                errorMsg.scrollIntoView({ behavior: 'smooth', block: 'center' });

                // Shake validation
                step.classList.add('animate-shake');
                setTimeout(() => step.classList.remove('animate-shake'), 500);

                // Also shake the next button to indicate failure
                const btn = document.getElementById('nextBtn');
                btn.classList.add('bg-red-600');
                setTimeout(() => btn.classList.remove('bg-red-600'), 200);

                return false;
            }
            errorMsg.classList.add('hidden');
            return true;
        }

        // Checkbox Logic (Max Selection)
        steps.forEach(step => {
            const max = parseInt(step.dataset.max);
            const stepIndex = step.dataset.stepIndex;
            const checkboxes = step.querySelectorAll(`.flavor-checkbox[data-step="${stepIndex}"]`);

            checkboxes.forEach(cb => {
                cb.addEventListener('change', () => {
                    const checkedCount = step.querySelectorAll(`.flavor-checkbox[data-step="${stepIndex}"]:checked`).length;
                    const errorMsg = step.querySelector('.step-error');

                    // Turn off error if user selects something
                    if (checkedCount > 0) {
                        errorMsg.classList.add('hidden');
                    }

                    if (checkedCount > max) {
                        cb.checked = false;
                        // Optional: Toast message here
                    }

                    // Update counter display
                    const finalCount = step.querySelectorAll(`.flavor-checkbox[data-step="${stepIndex}"]:checked`).length;
                    const counterEl = document.getElementById(`counter-display-${stepIndex}`);
                    if (counterEl) {
                        counterEl.innerText = `(${finalCount}/${max})`;
                    }
                });
            });
        });

        // Initial State
        showStep(0);
    });
</script>

<style>
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .animate-fade-in-up {
        animation: fadeInUp 0.5s ease-out;
    }

    @keyframes shake {

        0%,
        100% {
            transform: translateX(0);
        }

        25% {
            transform: translateX(-5px);
        }

        75% {
            transform: translateX(5px);
        }
    }

    .animate-shake {
        animation: shake 0.4s ease-in-out;
    }

    /* Custom Scrollbar for list */
    .custom-scrollbar::-webkit-scrollbar {
        width: 6px;
    }

    .custom-scrollbar::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 4px;
    }

    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: #d1d5db;
        border-radius: 4px;
    }

    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background: #9ca3af;
    }
</style>

<?php include __DIR__ . '/../views/layouts/footer.php'; ?>