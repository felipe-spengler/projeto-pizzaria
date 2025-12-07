<?php
session_start();
require_once __DIR__ . '/../vendor/autoload.php';
use App\Config\Database;

$errors = [];
$old = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Sanitize and Validate Inputs
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Keep old values to repopulate form
    $old = compact('name', 'email', 'phone');

    if (empty($name))
        $errors['name'] = 'Nome é obrigatório.';
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL))
        $errors['email'] = 'Email inválido.';
    if (empty($phone))
        $errors['phone'] = 'Telefone é obrigatório.';
    if (strlen($password) < 6)
        $errors['password'] = 'A senha deve ter pelo menos 6 caracteres.';
    if ($password !== $confirm_password)
        $errors['confirm_password'] = 'As senhas não coincidem.';

    // 2. Business Logic Validation
    if (empty($errors)) {
        $db = Database::getInstance()->getConnection();

        // Check if email exists
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors['email'] = 'Este email já está cadastrado.';
        } else {
            // 3. Create User
            // Note: In a real app, use password_hash($password, PASSWORD_DEFAULT)
            // For now, keeping consistent with simple auth requested
            $sql = "INSERT INTO users (name, email, phone, password, role) VALUES (?, ?, ?, ?, 'customer')";
            $stmt = $db->prepare($sql);
            try {
                $stmt->execute([$name, $email, $phone, $password]);
                $_SESSION['flash_success'] = 'Conta criada com sucesso! Faça login para continuar.';
                header('Location: login.php');
                exit;
            } catch (PDOException $e) {
                // Log error ideally
                $errors['general'] = 'Erro ao criar conta. Tente novamente: ' . $e->getMessage();
            }
        }
    }
}

include __DIR__ . '/../views/layouts/header.php';
?>

<div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8 bg-white p-10 rounded-2xl shadow-xl border border-gray-100">
        <div class="text-center">
            <h2 class="font-display font-bold text-3xl text-gray-900">Crie sua conta</h2>
            <p class="mt-2 text-sm text-gray-600">
                Já tem uma conta? <a href="login.php" class="font-medium text-brand-600 hover:text-brand-500">Faça
                    login</a>
            </p>
        </div>

        <?php if (isset($errors['general'])): ?>
            <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-4">
                <p class="text-red-700 text-sm"><?= $errors['general'] ?></p>
            </div>
        <?php endif; ?>

        <form class="mt-8 space-y-4" action="register.php" method="POST">
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nome Completo</label>
                <input id="name" name="name" type="text" autocomplete="name" required
                    class="appearance-none block w-full px-3 py-3 border <?= isset($errors['name']) ? 'border-red-300' : 'border-gray-300' ?> rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-brand-500 focus:border-brand-500 sm:text-sm"
                    placeholder="Seu nome" value="<?= htmlspecialchars($old['name'] ?? '') ?>">
                <?php if (isset($errors['name'])): ?>
                    <p class="mt-1 text-xs text-red-600"><?= $errors['name'] ?></p>
                <?php endif; ?>
            </div>

            <div>
                <label for="email-address" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input id="email-address" name="email" type="email" autocomplete="email" required
                    class="appearance-none block w-full px-3 py-3 border <?= isset($errors['email']) ? 'border-red-300' : 'border-gray-300' ?> rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-brand-500 focus:border-brand-500 sm:text-sm"
                    placeholder="seu@email.com" value="<?= htmlspecialchars($old['email'] ?? '') ?>">
                <?php if (isset($errors['email'])): ?>
                    <p class="mt-1 text-xs text-red-600"><?= $errors['email'] ?></p>
                <?php endif; ?>
            </div>

            <div>
                <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Telefone / WhatsApp</label>
                <input id="phone" name="phone" type="tel" autocomplete="tel" required
                    class="appearance-none block w-full px-3 py-3 border <?= isset($errors['phone']) ? 'border-red-300' : 'border-gray-300' ?> rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-brand-500 focus:border-brand-500 sm:text-sm"
                    placeholder="(11) 99999-9999" value="<?= htmlspecialchars($old['phone'] ?? '') ?>">
                <?php if (isset($errors['phone'])): ?>
                    <p class="mt-1 text-xs text-red-600"><?= $errors['phone'] ?></p>
                <?php endif; ?>
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Senha</label>
                <input id="password" name="password" type="password" autocomplete="new-password" required
                    class="appearance-none block w-full px-3 py-3 border <?= isset($errors['password']) ? 'border-red-300' : 'border-gray-300' ?> rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-brand-500 focus:border-brand-500 sm:text-sm"
                    placeholder="Mínimo 6 caracteres">
                <?php if (isset($errors['password'])): ?>
                    <p class="mt-1 text-xs text-red-600"><?= $errors['password'] ?></p>
                <?php endif; ?>
            </div>

            <div>
                <label for="confirm-password" class="block text-sm font-medium text-gray-700 mb-1">Confirmar
                    Senha</label>
                <input id="confirm-password" name="confirm_password" type="password" autocomplete="new-password"
                    required
                    class="appearance-none block w-full px-3 py-3 border <?= isset($errors['confirm_password']) ? 'border-red-300' : 'border-gray-300' ?> rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-brand-500 focus:border-brand-500 sm:text-sm"
                    placeholder="Repita a senha">
                <?php if (isset($errors['confirm_password'])): ?>
                    <p class="mt-1 text-xs text-red-600"><?= $errors['confirm_password'] ?></p>
                <?php endif; ?>
            </div>

            <div>
                <button type="submit"
                    class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-brand-600 hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500 transition-colors mt-6">
                    Criar Conta
                </button>
            </div>
        </form>

        <p class="text-xs text-center text-gray-500">
            Ao criar uma conta, você concorda com nossos <a href="#" class="text-brand-600 hover:underline">Termos de
                Serviço</a> e <a href="#" class="text-brand-600 hover:underline">Política de Privacidade</a>.
        </p>
    </div>
</div>

<?php include __DIR__ . '/../views/layouts/footer.php'; ?>