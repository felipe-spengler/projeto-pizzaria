<?php include __DIR__ . '/../layouts/header.php'; ?>

<div class="min-h-[calc(100vh-80px)] flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8 bg-gray-50">
    <div class="max-w-md w-full space-y-8 bg-white p-10 rounded-3xl shadow-xl border border-gray-100">
        <div class="text-center">
            <h2 class="font-display font-bold text-3xl text-gray-900">Crie sua conta</h2>
            <p class="mt-2 text-sm text-gray-600">
                Já tem uma conta? <a href="/login" class="font-medium text-brand-600 hover:text-brand-500">Faça
                    login</a>
            </p>
        </div>

        <div class="mt-8 space-y-6">
            <form class="mt-8 space-y-4" action="#" method="POST">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nome Completo</label>
                    <input id="name" name="name" type="text" autocomplete="name" required class="input-field"
                        placeholder="Seu nome">
                </div>

                <div>
                    <label for="email-address" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input id="email-address" name="email" type="email" autocomplete="email" required
                        class="input-field" placeholder="seu@email.com">
                </div>

                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Telefone / WhatsApp</label>
                    <input id="phone" name="phone" type="tel" autocomplete="tel" required class="input-field"
                        placeholder="(11) 99999-9999">
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Senha</label>
                    <input id="password" name="password" type="password" autocomplete="new-password" required
                        class="input-field" placeholder="Mínimo 8 caracteres">
                </div>

                <div>
                    <label for="confirm-password" class="block text-sm font-medium text-gray-700 mb-1">Confirmar
                        Senha</label>
                    <input id="confirm-password" name="confirm_password" type="password" autocomplete="new-password"
                        required class="input-field" placeholder="Repita a senha">
                </div>

                <div>
                    <button type="submit" class="w-full btn-primary flex justify-center py-3 mt-6">
                        Criar Conta
                    </button>
                </div>
            </form>

            <p class="text-xs text-center text-gray-500">
                Ao criar uma conta, você concorda com nossos <a href="#" class="text-brand-600 hover:underline">Termos
                    de Serviço</a> e <a href="#" class="text-brand-600 hover:underline">Política de Privacidade</a>.
            </p>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../layouts/footer.php'; ?>