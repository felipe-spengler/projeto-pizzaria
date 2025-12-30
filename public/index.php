<?php
require_once __DIR__ . '/../vendor/autoload.php';
include __DIR__ . '/../views/layouts/header.php';
?>

<!-- Hero Section -->
<div
    class="relative bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900 min-h-[700px] flex items-center overflow-hidden">
    <!-- Overlay/Background Image -->
    <div class="absolute inset-0 bg-gradient-to-r from-gray-900/95 via-gray-900/90 to-transparent z-10"></div>
    <div
        class="absolute inset-0 bg-[url('https://images.unsplash.com/photo-1513104890138-7c749659a591?ixlib=rb-4.0.3&auto=format&fit=crop&w=2000&q=80')] bg-cover bg-center opacity-30">
    </div>

    <!-- Decorative Elements -->
    <div class="absolute top-20 right-20 w-72 h-72 bg-brand-500/10 rounded-full blur-3xl"></div>
    <div class="absolute bottom-20 left-20 w-96 h-96 bg-orange-500/10 rounded-full blur-3xl"></div>

    <div class="relative z-20 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full py-20">
        <div class="max-w-3xl animate-slide-up">
            <span
                class="inline-flex items-center gap-2 py-2 px-4 rounded-full bg-gradient-to-r from-brand-500/20 to-orange-500/20 text-brand-400 font-bold text-sm mb-8 border border-brand-500/30 backdrop-blur-sm shadow-lg">
                <i class="fas fa-fire text-orange-500 animate-pulse"></i>
                Sinta o Sabor da Tradição!
            </span>
            <h1 class="font-display font-bold text-5xl md:text-7xl text-white leading-tight mb-8">
                Casa Nova <br />
                <span
                    class="text-transparent bg-clip-text bg-gradient-to-r from-brand-400 via-orange-400 to-yellow-400 animate-gradient">Pizzaria</span>
            </h1>
            <p class="text-gray-300 text-xl md:text-2xl mb-6 leading-relaxed font-medium">
                Com opções que vão do <span class="text-brand-400 font-bold">clássico ao gourmet</span>, temos o sabor
                perfeito para todos os gostos.
            </p>
            <p class="text-gray-400 text-lg md:text-xl mb-10 leading-relaxed">
                Pizzas assadas no capricho, com uma <span class="text-orange-400">massa fininha e crocante</span>, e
                cobertas com <span class="text-orange-400">molhos irresistíveis</span>.
            </p>
            <div class="flex flex-col sm:flex-row gap-4">
                <a href="menu.php"
                    class="group relative inline-flex items-center justify-center gap-3 px-8 py-4 rounded-xl font-bold text-lg text-white bg-gradient-to-r from-brand-600 to-orange-600 hover:from-brand-500 hover:to-orange-500 transition-all transform hover:scale-105 hover:shadow-2xl shadow-lg">
                    <i class="fas fa-pizza-slice group-hover:rotate-12 transition-transform"></i>
                    Ver Cardápio
                    <i class="fas fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                </a>
            </div>
        </div>
    </div>
</div>

<style>
    @keyframes gradient {

        0%,
        100% {
            background-position: 0% 50%;
        }

        50% {
            background-position: 100% 50%;
        }
    }

    .animate-gradient {
        background-size: 200% 200%;
        animation: gradient 3s ease infinite;
    }
</style>

<!-- Featured Section -->
<section id="featured" class="py-20 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-2xl mx-auto mb-16">
            <h2 class="font-display font-bold text-3xl md:text-4xl text-gray-900 mb-4">Mais Pedidas</h2>
            <p class="text-gray-600 text-lg">As favoritas dos nossos clientes, preparadas com carinho especial.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <!-- Product Card 1 -->
            <div class="card group">
                <div class="relative h-64 overflow-hidden">
                    <img src="https://images.unsplash.com/photo-1565299624946-b28f40a0ae38?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80"
                        alt="Pizza"
                        class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">
                    <button
                        class="absolute bottom-4 right-4 w-12 h-12 bg-white rounded-full flex items-center justify-center text-brand-600 shadow-lg hover:bg-brand-600 hover:text-white transition-all transform hover:scale-110">
                        <i class="fas fa-plus"></i>
                    </button>
                    <div
                        class="absolute top-4 left-4 bg-brand-500 text-white text-xs font-bold px-3 py-1 rounded-full uppercase tracking-wider">
                        Popular
                    </div>
                </div>
                <div class="p-6">
                    <div class="flex justify-between items-start mb-2">
                        <h3
                            class="font-display font-bold text-xl text-gray-900 group-hover:text-brand-600 transition-colors">
                            Pizza Calabresa</h3>
                        <span class="font-bold text-brand-600 bg-brand-50 px-2 py-1 rounded-lg">R$ 49,90</span>
                    </div>
                    <p class="text-gray-500 text-sm mb-4 line-clamp-2">Molho de tomate especial, mussarela derretida,
                        fatias de calabresa defumada e cebola roxa.</p>
                    <div class="flex items-center gap-2 text-sm text-gray-400">
                        <i class="far fa-clock"></i> 30-40 min
                        <span class="w-1 h-1 bg-gray-300 rounded-full"></span>
                        <i class="fas fa-fire text-orange-500"></i> Forno a lenha
                    </div>
                </div>
            </div>

            <!-- Product Card 2 -->
            <div class="card group">
                <div class="relative h-64 overflow-hidden">
                    <img src="https://images.unsplash.com/photo-1513104890138-7c749659a591?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80"
                        alt="Pizza"
                        class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">
                    <button
                        class="absolute bottom-4 right-4 w-12 h-12 bg-white rounded-full flex items-center justify-center text-brand-600 shadow-lg hover:bg-brand-600 hover:text-white transition-all transform hover:scale-110">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
                <div class="p-6">
                    <div class="flex justify-between items-start mb-2">
                        <h3
                            class="font-display font-bold text-xl text-gray-900 group-hover:text-brand-600 transition-colors">
                            Quatro Queijos</h3>
                        <span class="font-bold text-brand-600 bg-brand-50 px-2 py-1 rounded-lg">R$ 55,90</span>
                    </div>
                    <p class="text-gray-500 text-sm mb-4 line-clamp-2">Blend exclusivo de Mussarela, Provolone,
                        Gorgonzola e Parmesão gratinado.</p>
                    <div class="flex items-center gap-2 text-sm text-gray-400">
                        <i class="far fa-clock"></i> 30-40 min
                        <span class="w-1 h-1 bg-gray-300 rounded-full"></span>
                        <i class="fas fa-leaf text-green-500"></i> Vegetariana
                    </div>
                </div>
            </div>
        </div>

        <div class="text-center mt-12">
            <a href="menu.php"
                class="inline-flex items-center gap-2 font-semibold text-brand-600 hover:text-brand-700 transition-colors">
                Ver cardápio completo <i class="fas fa-arrow-right"></i>
            </a>
        </div>
    </div>
</section>

<!-- Values Section -->
<section class="py-20 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 text-center">
            <div class="p-6">
                <div
                    class="w-16 h-16 bg-brand-50 rounded-full flex items-center justify-center text-brand-600 text-2xl mb-6 mx-auto">
                    <i class="fas fa-truck-fast"></i>
                </div>
                <h3 class="font-display font-bold text-xl mb-3">Entrega Express</h3>
                <p class="text-gray-500">Entregamos quente e rápido na sua casa em até 40 minutos.</p>
            </div>
            <div class="p-6">
                <div
                    class="w-16 h-16 bg-brand-50 rounded-full flex items-center justify-center text-brand-600 text-2xl mb-6 mx-auto">
                    <i class="fas fa-certificate"></i>
                </div>
                <h3 class="font-display font-bold text-xl mb-3">Qualidade Premium</h3>
                <p class="text-gray-500">Ingredientes selecionados e importados para o melhor sabor.</p>
            </div>
            <div class="p-6">
                <div
                    class="w-16 h-16 bg-brand-50 rounded-full flex items-center justify-center text-brand-600 text-2xl mb-6 mx-auto">
                    <i class="fas fa-mobile-screen"></i>
                </div>
                <h3 class="font-display font-bold text-xl mb-3">Pedido Fácil</h3>
                <p class="text-gray-500">Peça pelo site ou app em poucos cliques e acompanhe tudo.</p>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/../views/layouts/footer.php'; ?>