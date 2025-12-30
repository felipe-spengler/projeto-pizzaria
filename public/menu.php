<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\Config\Database;

// Fetch categories and products
$db = Database::getInstance()->getConnection();
$categories = $db->query("SELECT * FROM categories")->fetchAll();
// Fetch active products with their category slug for filtering if needed
$stmt = $db->query("SELECT p.*, c.slug as category_slug FROM products p JOIN categories c ON p.category_id = c.id WHERE p.active = 1");
$products = $stmt->fetchAll();

include __DIR__ . '/../views/layouts/header.php';
?>

<div class="bg-brand-600 py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="font-display font-bold text-4xl text-white mb-4">Nosso Cardápio</h1>
        <p class="text-brand-100 text-lg">Escolha entre nossas deliciosas opções</p>
    </div>
</div>

<div class="max-w-7xl mx-auto px-2 sm:px-4 lg:px-8 py-6 sm:py-12">

    <!-- Category Filter -->
    <div class="flex flex-wrap justify-center gap-2 sm:gap-4 mb-6 sm:mb-12">
        <a href="menu.php"
            class="px-4 sm:px-6 py-2 rounded-full bg-brand-600 text-white font-semibold shadow-md transition-transform transform hover:-translate-y-1 text-sm sm:text-base">Todos</a>
        <?php foreach ($categories as $cat): ?>
            <a href="#cat-<?= $cat['id'] ?>"
                class="px-4 sm:px-6 py-2 rounded-full bg-white text-gray-600 hover:bg-brand-50 hover:text-brand-600 font-semibold shadow-sm border border-gray-200 transition-all text-sm sm:text-base">
                <i class="fas fa-<?= $cat['icon'] ?> mr-2"></i><?= $cat['name'] ?>
            </a>
        <?php endforeach; ?>
    </div>

    <?php foreach ($categories as $category): ?>
        <div id="cat-<?= $category['id'] ?>" class="mb-8 sm:mb-16 scroll-mt-24">
            <h2 class="font-display font-bold text-xl sm:text-2xl text-gray-900 mb-4 sm:mb-8 flex items-center gap-3 px-2 sm:px-0">
                <i class="fas fa-<?= $category['icon'] ?> text-brand-500"></i>
                <?= $category['name'] ?>
            </h2>

            <!-- Grid Layout Fixed -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3 sm:gap-6 lg:gap-8">
                <?php
                $catProducts = array_filter($products, function ($p) use ($category) {
                    return $p['category_id'] == $category['id'];
                });

                foreach ($catProducts as $product):
                    ?>
                    <div
                        class="bg-white rounded-xl sm:rounded-2xl shadow-lg overflow-hidden group hover:shadow-2xl transition-all duration-300 border border-gray-100 flex flex-col h-full cursor-pointer"
                        onclick="window.location.href='product.php?id=<?= $product['id'] ?>'">
                        <a href="product.php?id=<?= $product['id'] ?>" class="relative h-40 sm:h-48 overflow-hidden bg-gray-100 block">
                            <?php
                            $imgUrl = $product['image_url'];
                            // Override images based on user request
                            $pNameNorm = mb_strtoupper($product['name'] ?? '', 'UTF-8');
                            
                            // 1. Calzone & Combo Swap
                            if ($category['name'] === 'Calzones') {
                                $imgUrl = 'assets/images/calzone-real.png';
                            } elseif ($pNameNorm === 'COMBO 2 PIZZA G') {
                                $imgUrl = 'assets/images/combo-real.jpg';
                            } 
                            // 2. Beverages
                            elseif ($pNameNorm === 'REFRIGERANTE 2L' || $pNameNorm === 'REFRIGERANTE 1L') {
                                $imgUrl = 'assets/images/coca-cola-2l.png';
                            } elseif ($pNameNorm === 'REFRIGERANTE LATA') {
                                $imgUrl = 'https://images.unsplash.com/photo-1622483767028-3f66f32aef97?auto=format&fit=crop&w=800&q=80';
                            }
                            // 3. Pizza Rotation
                            elseif (str_contains($pNameNorm, 'PIZZA PEQUENA')) {
                                $imgUrl = 'https://images.unsplash.com/photo-1574071318508-1cdbab80d002?auto=format&fit=crop&w=800&q=80';
                            } elseif (str_contains($pNameNorm, 'PIZZA MÉDIA')) {
                                $imgUrl = 'https://images.unsplash.com/photo-1590947132387-155cc02f3212?auto=format&fit=crop&w=800&q=80';
                            } elseif (str_contains($pNameNorm, 'PIZZA GRANDE')) {
                                $imgUrl = 'https://images.unsplash.com/photo-1594007654729-407eedc4be65?auto=format&fit=crop&w=800&q=80';
                            } elseif (str_contains($pNameNorm, 'PIZZA GIGANTE')) {
                                $imgUrl = 'https://images.unsplash.com/photo-1513104890138-7c749659a591?auto=format&fit=crop&w=800&q=80';
                            } elseif (str_contains($pNameNorm, 'PIZZA BROTO')) {
                                $imgUrl = 'https://images.unsplash.com/photo-1588315029754-2dd089d39a1a?auto=format&fit=crop&w=800&q=80';
                            }
                            ?>
                            <img src="<?= $imgUrl ?>" alt="<?= $product['name'] ?>"
                                class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">

                            <?php if ($product['is_customizable']): ?>
                                <div
                                    class="absolute top-2 right-2 bg-brand-600 text-white text-xs font-bold px-2 py-1 rounded-full shadow-sm">
                                    Montar
                                </div>
                            <?php endif; ?>
                        </a>

                        <div class="p-3 sm:p-5 flex flex-col flex-grow">
                            <h3 class="font-display font-bold text-base sm:text-lg text-gray-900 mb-1 leading-tight"><?= $product['name'] ?>
                            </h3>
                            <?php
                            $ingredients = array_filter(array_map('trim', preg_split('/[,;]+/', $product['description'] ?? '')));
                            ?>
                            <?php if (!empty($ingredients)): ?>
                                <div class="flex flex-wrap gap-1.5 sm:gap-2 mb-2 sm:mb-3 max-h-20 sm:max-h-24 overflow-y-auto">
                                    <?php foreach ($ingredients as $ingredient): ?>
                                        <span class="px-1.5 sm:px-2 py-0.5 sm:py-1 bg-brand-50 text-brand-700 text-xs font-semibold rounded-lg border border-brand-100">
                                            <?= htmlspecialchars($ingredient) ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            <p class="text-gray-500 text-xs sm:text-sm mb-3 sm:mb-4 line-clamp-2"><?= $product['description'] ?></p>

                            <div class="mt-auto flex items-center justify-between">
                                <span class="text-lg sm:text-xl font-bold text-brand-600">R$
                                    <?= number_format($product['price'], 2, ',', '.') ?></span>
                                <a href="product.php?id=<?= $product['id'] ?>"
                                    class="w-9 h-9 sm:w-10 sm:h-10 rounded-full bg-brand-100 text-brand-600 flex items-center justify-center hover:bg-brand-600 hover:text-white transition-all transform hover:scale-110 shadow-sm"
                                    onclick="event.stopPropagation();">
                                    <i class="fas fa-plus text-sm sm:text-base"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <?php include __DIR__ . '/../views/layouts/footer.php'; ?>