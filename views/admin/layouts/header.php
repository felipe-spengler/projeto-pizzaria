<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Casa Nova Pizzaria</title>
    <!-- Tailwind CSS via CDN -->
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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="bg-gray-100 font-sans antialiased">
    <div class="min-h-screen flex">
        <!-- Sidebar -->
        <aside class="w-64 bg-gray-900 text-white flex flex-col fixed h-full z-20 transition-all duration-300">
            <div class="h-20 flex items-center justify-center border-b border-gray-800">
                <a href="/admin" class="flex items-center gap-2">
                    <div
                        class="w-8 h-8 bg-brand-600 rounded-full flex items-center justify-center text-white text-sm shadow-lg">
                        <i class="fas fa-pizza-slice"></i>
                    </div>
                    <span class="font-display font-bold text-xl tracking-tight">Casa<span
                            class="text-brand-500">Nova</span></span>
                </a>
            </div>

            <nav class="flex-grow p-4 space-y-2 overflow-y-auto">
                <p class="px-4 text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2 mt-4">Principal</p>
                <a href="/admin/dashboard"
                    class="flex items-center gap-3 px-4 py-3 rounded-xl bg-brand-600 text-white shadow-lg shadow-brand-900/20">
                    <i class="fas fa-chart-pie w-5"></i>
                    <span class="font-medium">Dashboard</span>
                </a>
                <a href="#"
                    class="flex items-center gap-3 px-4 py-3 rounded-xl text-gray-400 hover:bg-gray-800 hover:text-white transition-colors">
                    <i class="fas fa-shopping-bag w-5"></i>
                    <span class="font-medium">Pedidos</span>
                </a>
                <a href="#"
                    class="flex items-center gap-3 px-4 py-3 rounded-xl text-gray-400 hover:bg-gray-800 hover:text-white transition-colors">
                    <i class="fas fa-users w-5"></i>
                    <span class="font-medium">Clientes</span>
                </a>

                <p class="px-4 text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2 mt-8">Cardápio</p>
                <a href="#"
                    class="flex items-center gap-3 px-4 py-3 rounded-xl text-gray-400 hover:bg-gray-800 hover:text-white transition-colors">
                    <i class="fas fa-list w-5"></i>
                    <span class="font-medium">Produtos</span>
                </a>
                <a href="#"
                    class="flex items-center gap-3 px-4 py-3 rounded-xl text-gray-400 hover:bg-gray-800 hover:text-white transition-colors">
                    <i class="fas fa-utensils w-5"></i>
                    <span class="font-medium">Sabores</span>
                </a>
            </nav>

            <div class="p-4 border-t border-gray-800">
                <a href="/"
                    class="flex items-center gap-3 px-4 py-2 text-gray-400 hover:text-white transition-colors text-sm">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Sair do Painel</span>
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-grow ml-64 p-8">
            <header class="flex justify-between items-center mb-8">
                <div>
                    <h1 class="font-display font-bold text-2xl text-gray-900">Dashboard</h1>
                    <p class="text-gray-500 text-sm">Bem-vindo ao painel de controle.</p>
                </div>
                <div class="flex items-center gap-4">
                    <button
                        class="w-10 h-10 rounded-full bg-white border border-gray-200 flex items-center justify-center text-gray-500 hover:text-brand-600 transition-colors relative">
                        <i class="fas fa-bell"></i>
                        <span class="absolute top-2 right-2 w-2 h-2 bg-red-500 rounded-full border border-white"></span>
                    </button>
                    <div class="flex items-center gap-3 pl-4 border-l border-gray-200">
                        <div class="text-right hidden sm:block">
                            <p class="text-sm font-bold text-gray-900">Admin</p>
                            <p class="text-xs text-brand-600">Gerente</p>
                        </div>
                        <div class="w-10 h-10 rounded-full bg-gray-300 overflow-hidden">
                            <img src="https://ui-avatars.com/api/?name=Admin&background=f43f5e&color=fff" alt="Admin">
                        </div>
                    </div>
                </div>
            </header>