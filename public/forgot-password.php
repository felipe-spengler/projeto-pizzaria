<?php
include __DIR__ . '/../views/layouts/header.php';
?>

<div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8 bg-white p-10 rounded-2xl shadow-xl">
        <div class="text-center">
            <h2 class="mt-6 text-3xl font-extrabold text-gray-900 font-display">
                Recuperar Senha
            </h2>
            <p class="mt-2 text-sm text-gray-600">
                Digite seu email para receber um link de redefinição.
            </p>
        </div>

        <form class="mt-8 space-y-6" action="#" method="POST">
            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-triangle text-yellow-400"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-yellow-700">
                            Modo de Simulação: Como não temos servidor de e-mail configurado, nenhuma mensagem será
                            enviada real.
                        </p>
                    </div>
                </div>
            </div>

            <div class="rounded-md shadow-sm -space-y-px">
                <div>
                    <label for="email-address" class="sr-only">Email</label>
                    <input id="email-address" name="email" type="email" autocomplete="email" required
                        class="appearance-none rounded-md relative block w-full px-3 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-brand-500 focus:border-brand-500 focus:z-10 sm:text-sm"
                        placeholder="Email cadastrado">
                </div>
            </div>

            <div>
                <button type="submit"
                    class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-lg text-white bg-brand-600 hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500 transition-colors">
                    Enviar Link
                </button>
            </div>

            <div class="text-center">
                <a href="login.php" class="font-medium text-brand-600 hover:text-brand-500">
                    <i class="fas fa-arrow-left mr-1"></i> Voltar para Login
                </a>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../views/layouts/footer.php'; ?>