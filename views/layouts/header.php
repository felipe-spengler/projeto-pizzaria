<?php
// Ensure session is started securely
if (session_status() === PHP_SESSION_NONE) {
    // Check if autoloader is needed
    if (!class_exists('App\Config\Session')) {
        $autoloadPath = __DIR__ . '/../../vendor/autoload.php';
        if (file_exists($autoloadPath)) {
            require_once $autoloadPath;
        }
    }

    // Start Session using our Config class if available, otherwise fallback
    if (class_exists('App\Config\Session')) {
        App\Config\Session::start();
    } else {
        session_start();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pizzaria Premium</title>
    <!-- Tailwind CSS via CDN (Fix styling issues immediately) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'], display: ['Poppins', 'sans-serif'] },
                    colors: {
                        brand: {
                            50: '#fff1f2', 100: '#ffe4e6', 200: '#fecdd3', 300: '#fda4af', 400: '#fb7185',
                            500: '#f43f5e', 600: '#e11d48', 700: '#be123c', 800: '#9f1239', 900: '#881337',
                        }
                    }
                }
            }
        }
    </script>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@500;600;700&display=swap"
        rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <meta name="description" content="A melhor pizza da cidade, feita com ingredientes selecionados.">
    <style>
        /* Custom Scrollbar */
        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #e11d48;
            border-radius: 10px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #be123c;
        }

        /* Smooth transitions */
        * {
            transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Touch improvements for mobile */
        @media (max-width: 640px) {

            button,
            a,
            label {
                -webkit-tap-highlight-color: rgba(225, 29, 72, 0.1);
            }
        }
    </style>
</head>

<body class="bg-gray-50 flex flex-col min-h-screen">
    <!-- Navbar -->
    <nav class="bg-white/80 backdrop-blur-md sticky top-0 z-50 border-b border-gray-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-20">
                <!-- Logo -->
                <div class="flex-shrink-0 flex items-center">
                    <a href="index.php" class="flex items-center gap-2 group">
                        <div
                            class="w-10 h-10 bg-brand-600 rounded-full flex items-center justify-center text-white text-xl shadow-lg shadow-brand-500/30 group-hover:scale-110 transition-transform">
                            <i class="fas fa-pizza-slice"></i>
                        </div>
                        <span class="font-display font-bold text-2xl text-gray-900 tracking-tight">Casa<span
                                class="text-brand-600">Nova</span></span>
                    </a>
                </div>

                <!-- Desktop Menu -->
                <div class="hidden md:flex items-center space-x-8">
                    <a href="menu.php"
                        class="text-gray-600 hover:text-brand-600 font-medium transition-colors">Cardápio</a>

                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="orders.php" class="text-gray-600 hover:text-brand-600 font-medium transition-colors">Meus
                            Pedidos</a>
                    <?php endif; ?>

                    <div class="flex items-center gap-4 ml-4 pl-4 border-l border-gray-200">
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <!-- Admin Link -->
                            <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                                <a href="admin/" class="text-gray-600 hover:text-brand-600 font-medium transition-colors"
                                    title="Painel Administrativo">
                                    <i class="fas fa-user-shield text-xl"></i>
                                </a>
                            <?php endif; ?>

                            <a href="cart.php" class="relative p-2 text-gray-600 hover:text-brand-600 transition-colors">
                                <i class="fas fa-shopping-bag text-xl"></i>
                                <?php if (!empty($_SESSION['cart'])): ?>
                                    <span
                                        class="absolute top-0 right-0 -mt-1 -mr-1 px-1.5 py-0.5 bg-brand-600 rounded-full text-xs font-bold text-white shadow-sm"><?= count($_SESSION['cart']) ?></span>
                                <?php endif; ?>
                            </a>
                            <a href="orders.php" class="text-gray-600 hover:text-brand-600 transition-colors">
                                <i class="fas fa-receipt text-xl"></i>
                            </a>
                            <a href="logout.php" class="btn-primary py-2 px-5 text-sm">
                                Sair
                            </a>
                        <?php else: ?>
                            <a href="login.php" class="btn-primary py-2 px-5 text-sm">
                                Entrar
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Mobile menu button -->
                <div class="md:hidden flex items-center">
                    <button class="text-gray-600 hover:text-gray-900 focus:outline-none p-2" id="mobile-menu-btn">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Menu -->
        <div class="md:hidden hidden bg-white border-t border-gray-100" id="mobile-menu">
            <div class="px-4 pt-2 pb-4 space-y-1">
                <a href="index.php"
                    class="block px-3 py-2 rounded-md text-base font-medium text-gray-900 hover:bg-gray-50 hover:text-brand-600">Início</a>
                <a href="menu.php"
                    class="block px-3 py-2 rounded-md text-base font-medium text-gray-600 hover:bg-gray-50 hover:text-brand-600">Cardápio</a>

                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="cart.php"
                        class="block px-3 py-2 rounded-md text-base font-medium text-gray-600 hover:bg-gray-50 hover:text-brand-600">
                        Carrinho <?php if (!empty($_SESSION['cart'])): ?>(<?= count($_SESSION['cart']) ?>)<?php endif; ?>
                    </a>
                    <a href="orders.php"
                        class="block px-3 py-2 rounded-md text-base font-medium text-gray-600 hover:bg-gray-50 hover:text-brand-600">Meus
                        Pedidos</a>
                    <a href="logout.php"
                        class="block px-3 py-2 mt-4 text-center rounded-xl font-bold bg-gray-600 text-white">Sair</a>
                <?php else: ?>
                    <a href="login.php"
                        class="block px-3 py-2 mt-4 text-center rounded-xl font-bold bg-brand-600 text-white shadow-lg shadow-brand-500/30">Entrar</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Flash Messages (Success/Error) -->
    <?php if (isset($_SESSION['flash_success'])): ?>
        <div class="fixed top-24 right-4 z-50 animate-slide-down">
            <div class="bg-green-600 text-white px-6 py-4 rounded-xl shadow-2xl flex items-center gap-3 max-w-md">
                <i class="fas fa-check-circle text-2xl"></i>
                <span class="font-medium"><?= $_SESSION['flash_success'] ?></span>
            </div>
        </div>
        <script>
            setTimeout(() => {
                document.querySelector('.animate-slide-down')?.remove();
            }, 4000);
        </script>
        <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['flash_error'])): ?>
        <div class="fixed top-24 right-4 z-50 animate-slide-down">
            <div class="bg-red-600 text-white px-6 py-4 rounded-xl shadow-2xl flex items-center gap-3 max-w-md">
                <i class="fas fa-exclamation-circle text-2xl"></i>
                <span class="font-medium"><?= $_SESSION['flash_error'] ?></span>
            </div>
        </div>
        <script>
            setTimeout(() => {
                document.querySelector('.animate-slide-down')?.remove();
            }, 4000);
        </script>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <main class="flex-grow">