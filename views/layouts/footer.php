</main>

<footer class="bg-gray-900 text-white pt-16 pb-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-12 mb-12">
            <!-- Brand -->
            <div class="col-span-1 md:col-span-1">
                <a href="index.php" class="flex items-center gap-2 mb-6">
                    <div
                        class="w-10 h-10 bg-brand-600 rounded-full flex items-center justify-center text-white text-xl">
                        <i class="fas fa-pizza-slice"></i>
                    </div>
                    <span class="font-display font-bold text-2xl tracking-tight">Casa<span
                            class="text-brand-500">Nova</span></span>
                </a>
                <p class="text-gray-400 text-sm mb-6">
                    A tradição da verdadeira pizza italiana com o toque brasileiro que você ama.
                </p>
                <div class="flex gap-4">
                    <a href="#"
                        class="w-10 h-10 rounded-full bg-gray-800 flex items-center justify-center text-gray-400 hover:bg-brand-600 hover:text-white transition-all transform hover:-translate-y-1">
                        <i class="fab fa-instagram"></i>
                    </a>
                    <a href="#"
                        class="w-10 h-10 rounded-full bg-gray-800 flex items-center justify-center text-gray-400 hover:bg-brand-600 hover:text-white transition-all transform hover:-translate-y-1">
                        <i class="fab fa-facebook-f"></i>
                    </a>
                    <a href="#"
                        class="w-10 h-10 rounded-full bg-gray-800 flex items-center justify-center text-gray-400 hover:bg-brand-600 hover:text-white transition-all transform hover:-translate-y-1">
                        <i class="fab fa-whatsapp"></i>
                    </a>
                </div>
            </div>

            <!-- Links -->
            <div>
                <h3 class="font-display font-bold text-lg mb-6">Links Rápidos</h3>
                <ul class="space-y-4">
                    <li><a href="menu.php" class="text-gray-400 hover:text-brand-500 transition-colors">Cardápio
                            Completo</a></li>
                    <li><a href="orders.php" class="text-gray-400 hover:text-brand-500 transition-colors">Acompanhar
                            Pedido</a></li>
                    <li><a href="login.php" class="text-gray-400 hover:text-brand-500 transition-colors">Minha Conta</a>
                    </li>
                    <li><a href="#" class="text-gray-400 hover:text-brand-500 transition-colors">Política de
                            Privacidade</a></li>
                </ul>
            </div>

            <!-- Contact -->
            <div class="col-span-1 md:col-span-2">
                <h3 class="font-display font-bold text-lg mb-6">Contato</h3>
                <ul class="space-y-4 text-gray-400">
                    <li class="flex items-start gap-3">
                        <i class="fas fa-map-marker-alt mt-1 text-brand-500"></i>
                        <span>Rua das Pizzas, 123<br>Centro, São Paulo - SP</span>
                    </li>
                    <li class="flex items-center gap-3">
                        <i class="fas fa-phone-alt text-brand-500"></i>
                        <span>(11) 99999-9999</span>
                    </li>
                    <li class="flex items-center gap-3">
                        <i class="fas fa-envelope text-brand-500"></i>
                        <span>contato@casanovapizzaria.com</span>
                    </li>
                </ul>
            </div>
        </div>

        <div class="border-t border-gray-800 pt-8 text-center text-gray-500 text-sm">
            <p>&copy; <?php echo date('Y'); ?> Casa Nova Pizzaria. Todos os direitos reservados.</p>
        </div>
    </div>
</footer>

<script>
    // Mobile Menu Toggle
    const btn = document.getElementById('mobile-menu-btn');
    const menu = document.getElementById('mobile-menu');

    if (btn && menu) {
        btn.addEventListener('click', () => {
            menu.classList.toggle('hidden');
        });
    }
</script>
</body>

</html>