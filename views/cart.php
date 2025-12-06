<?php include __DIR__ . '/layouts/header.php'; ?>

<div class="bg-gray-50 min-h-screen py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h1 class="font-display font-bold text-3xl text-gray-900 mb-8">Seu Carrinho</h1>

        <div class="flex flex-col lg:flex-row gap-8">
            <!-- Cart Items -->
            <div class="flex-grow">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-8 text-center text-gray-500">
                        <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-shopping-basket text-3xl text-gray-300"></i>
                        </div>
                        <h3 class="font-semibold text-lg text-gray-900 mb-2">Seu carrinho está vazio</h3>
                        <p class="mb-6">Parece que você ainda não escolheu sua pizza favorita.</p>
                        <a href="/menu" class="btn-primary inline-block">
                            Ver Cardápio
                        </a>
                    </div>
                </div>
            </div>

            <!-- Summary -->
            <div class="w-full lg:w-96">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 sticky top-24">
                    <h2 class="font-display font-bold text-xl text-gray-900 mb-6">Resumo do Pedido</h2>

                    <div class="space-y-3 mb-6">
                        <div class="flex justify-between text-gray-600">
                            <span>Subtotal</span>
                            <span>R$ 0,00</span>
                        </div>
                        <div class="flex justify-between text-gray-600">
                            <span>Taxa de Entrega</span>
                            <span>R$ 0,00</span>
                        </div>
                        <div class="border-t border-gray-100 pt-3 flex justify-between font-bold text-lg text-gray-900">
                            <span>Total</span>
                            <span>R$ 0,00</span>
                        </div>
                    </div>

                    <button class="w-full btn-primary py-3 opacity-50 cursor-not-allowed" disabled>
                        Finalizar Pedido
                    </button>

                    <div class="mt-6 flex items-center justify-center gap-2 text-sm text-gray-500">
                        <i class="fas fa-lock"></i> Compra 100% Segura
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/layouts/footer.php'; ?>