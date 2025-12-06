class="w-10 h-10 rounded-full bg-gray-800 flex items-center justify-center text-gray-400 hover:bg-brand-600
hover:text-white transition-all transform hover:-translate-y-1">
<i class="fab fa-instagram"></i>
</a>
<a href="#"
    class="w-10 h-10 rounded-full bg-gray-800 flex items-center justify-center text-gray-400 hover:bg-brand-600 hover:text-white transition-all transform hover:-translate-y-1">
    <i class="fab fa-facebook-f"></i>
</a>
<a href="#" <span>Rua das Pizzas, 123<br>Centro, São Paulo - SP</span>
    </li>
    <li class="flex items-center gap-3">
        <i class="fas fa-phone-alt text-brand-500"></i>
        <span>(11) 99999-9999</span>
    </li>
    <li class="flex items-center gap-3">
        <i class="fas fa-envelope text-brand-500"></i>
        <span>contato@pizzamaster.com</span>
    </li>
    </ul>
    </div>
    </div>

    <div class="border-t border-gray-800 pt-8 text-center text-gray-500 text-sm">
        <p>&copy; <?php echo date('Y'); ?> PizzaMaster. Todos os direitos reservados.</p>
    </div>
    </div>
    </footer>

    <script>
        // Mobile Menu Toggle
        const btn = document.getElementById('mobile-menu-btn');
        const menu = document.getElementById('mobile-menu');

        btn.addEventListener('click', () => {
            menu.classList.toggle('hidden');
        });
    </script>
    </body>

    </html>