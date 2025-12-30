<?php
require_once __DIR__ . '/../src/Config/Database.php'; 

use App\Config\Database;

// Fetch categories and products
$db = Database::getInstance()->getConnection();
$categories = $db->query("SELECT * FROM categories")->fetchAll();
// Fetch all active products
$stmt = $db->query("SELECT p.*, c.slug as category_slug FROM products p JOIN categories c ON p.category_id = c.id WHERE p.active = 1");
$products = $stmt->fetchAll(PDO::FETCH_GROUP); // Group by some unique key if needed, or just fetch all
// Actually FETCH_GROUP groups by first column if distinct.. lets just fetchAll and filter in PHP for simplicity or use a better query
$products = $stmt->fetchAll();

include __DIR__ . '/layouts/header.php';
?>

<div class="bg-brand-600 py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="font-display font-bold text-4xl text-white mb-4">Nosso Cardápio</h1>
        <p class="text-brand-100 text-lg">Escolha entre nossas deliciosas opções</p>
    </div>
</div>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">

    <!-- Category Filter (Simple anchor links) -->
    <div class="flex flex-wrap justify-center gap-4 mb-12">
        <a href="/menu"
            class="px-6 py-2 rounded-full bg-brand-600 text-white font-semibold shadow-md transition-transform transform hover:-translate-y-1">Todos</a>
        <?php foreach ($categories as $cat): ?>
            <a href="#cat-<?= $cat['id'] ?>"
                class="px-6 py-2 rounded-full bg-white text-gray-600 hover:bg-brand-50 hover:text-brand-600 font-semibold shadow-sm border border-gray-200 transition-all">
                <i class="fas fa-<?= $cat['icon'] ?> mr-2"></i><?= $cat['name'] ?>
            </a>
        <?php endforeach; ?>
    </div>

    <?php foreach ($categories as $category): ?>
        <div id="cat-<?= $category['id'] ?>" class="mb-16 scroll-mt-24">
            <h2 class="font-display font-bold text-2xl text-gray-900 mb-8 flex items-center gap-3">
                <i class="fas fa-<?= $category['icon'] ?> text-brand-500"></i>
                <?= $category['name'] ?>
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php
                $catProducts = array_filter($products, function ($p) use ($category) {
                    return $p['category_id'] == $category['id'];
                });

                foreach ($catProducts as $product):
                    ?>
                    <div class="card group flex flex-col h-full">
                        <div class="relative h-56 overflow-hidden">
                            <?php if ($product['image_url']): ?>
                                <img src="<?= $product['image_url'] ?>" alt="<?= $product['name'] ?>"
                                    class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">
                            <?php else: ?>
                                <div class="w-full h-full bg-gray-200 flex items-center justify-center text-gray-400">
                                    <i class="fas fa-image text-4xl"></i>
                                </div>
                            <?php endif; ?>

                            <?php if ($product['is_customizable']): ?>
                                <div
                                    class="absolute bottom-4 left-4 bg-black/60 backdrop-blur-md text-white px-3 py-1 rounded-lg text-xs font-medium">
                                    <i class="fas fa-palette mr-1"></i> Personalizável
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="p-6 flex flex-col flex-grow">
                            <div class="flex justify-between items-start mb-2">
                                <h3 class="font-display font-bold text-xl text-gray-900"><?= $product['name'] ?></h3>
                                <span class="font-bold text-brand-600 bg-brand-50 px-2 py-1 rounded-lg">
                                    R$ <?= number_format($product['price'], 2, ',', '.') ?>
                                </span>
                            </div>
                            <?php
                            $ingredients = array_filter(array_map('trim', preg_split('/[,;]+/', $product['description'] ?? '')));
                            ?>
                            <?php if (!empty($ingredients)): ?>
                                <div class="flex flex-wrap gap-2 mb-4 max-h-24 overflow-y-auto">
                                    <?php foreach ($ingredients as $ingredient): ?>
                                        <span class="px-2 py-1 bg-brand-50 text-brand-700 text-xs font-semibold rounded-lg border border-brand-100">
                                            <?= htmlspecialchars($ingredient) ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            <p class="text-gray-500 text-sm mb-6 flex-grow"><?= $product['description'] ?></p>

                            <a href="/product?id=<?= $product['id'] ?>"
                                class="btn-primary w-full text-center flex items-center justify-center gap-2 group-hover:bg-brand-700">
                                <i class="fas fa-plus"></i>
                                <?php echo $product['is_customizable'] ? 'Montar Pizza' : 'Adicionar'; ?>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php include __DIR__ . '/layouts/footer.php'; ?>