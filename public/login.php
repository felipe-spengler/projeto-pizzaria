<?php
require_once __DIR__ . '/../vendor/autoload.php';
use App\Config\Database;
use App\Config\Session;
use App\Auth\GoogleAuth;

// Inicia sessão com configurações otimizadas
Session::start();

$error = '';

// Initialize Google Auth
$googleAuth = new GoogleAuth();
$googleLoginUrl = $googleAuth->isConfigured() ? $googleAuth->getAuthUrl() : null;

// Handle error messages from redirects
if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'no_code':
            $error = 'Erro ao autenticar com o Google. Tente novamente.';
            break;
        case 'google_auth_failed':
            $error = 'Falha na autenticação do Google. Tente novamente.';
            break;
        case 'database_error':
            $error = 'Erro ao salvar seus dados. Tente novamente.';
            break;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    $db = Database::getInstance()->getConnection();
    // Allow login by email OR username (if we had a username column, but here we assume email column holds "admin")
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && ($password === $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];

        if ($user['role'] === 'admin') {
            header('Location: admin/');
        } else {
            // Check if there's a redirect parameter
            if (isset($_GET['redirect'])) {
                header('Location: ' . $_GET['redirect']);
            } else {
                header('Location: index.php');
            }
        }
        exit;
    } else {
        $error = 'Usuário ou senha inválidos.';
    }
}

include __DIR__ . '/../views/layouts/header.php';
?>

<div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8 bg-white p-10 rounded-2xl shadow-xl">
        <div class="text-center">
            <h2 class="mt-6 text-3xl font-extrabold text-gray-900 font-display">
                Entrar na sua conta
            </h2>
            <p class="mt-2 text-sm text-gray-600">
                Ou <a href="register.php" class="font-medium text-brand-600 hover:text-brand-500">crie sua conta
                    grátis</a>
            </p>
        </div>

        <?php if (isset($_SESSION['flash_success'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline"><?= $_SESSION['flash_success'] ?></span>
            </div>
            <?php unset($_SESSION['flash_success']); ?>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline"><?= $error ?></span>
            </div>
        <?php endif; ?>

        <form class="mt-8 space-y-6" action="login.php" method="POST">
            <div class="rounded-md shadow-sm -space-y-px">
                <div>
                    <label for="email-address" class="sr-only">Email ou Usuário</label>
                    <input id="email-address" name="email" type="text" autocomplete="email" required
                        class="appearance-none rounded-none relative block w-full px-3 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-t-md focus:outline-none focus:ring-brand-500 focus:border-brand-500 focus:z-10 sm:text-sm"
                        placeholder="Email ou Usuário">
                </div>
                <div>
                    <label for="password" class="sr-only">Senha</label>
                    <input id="password" name="password" type="password" autocomplete="current-password" required
                        class="appearance-none rounded-none relative block w-full px-3 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-b-md focus:outline-none focus:ring-brand-500 focus:border-brand-500 focus:z-10 sm:text-sm"
                        placeholder="Senha">
                </div>
            </div>

            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <input id="remember-me" name="remember-me" type="checkbox"
                        class="h-4 w-4 text-brand-600 focus:ring-brand-500 border-gray-300 rounded">
                    <label for="remember-me" class="ml-2 block text-sm text-gray-900">Lembrar de mim</label>
                </div>

                <div class="text-sm">
                    <a href="forgot-password.php" class="font-medium text-brand-600 hover:text-brand-500">
                        Esqueceu sua senha?
                    </a>
                </div>
            </div>

            <div>
                <button type="submit"
                    class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-lg text-white bg-brand-600 hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500 transition-colors">
                    Entrar
                </button>
            </div>

            <div class="relative">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-gray-300"></div>
                </div>
                <div class="relative flex justify-center text-sm">
                    <span class="px-2 bg-white text-gray-500">Ou continue com</span>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-3">
                <?php if ($googleLoginUrl): ?>
                    <a href="<?= $googleLoginUrl ?>"
                        class="w-full inline-flex justify-center py-3 px-4 border-2 border-gray-300 rounded-lg shadow-sm bg-white text-sm font-semibold text-gray-700 hover:bg-gray-50 transition-all hover:border-gray-400 hover:shadow-md">
                        <svg class="w-5 h-5 mr-2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"
                                fill="#4285F4" />
                            <path
                                d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"
                                fill="#34A853" />
                            <path
                                d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"
                                fill="#FBBC05" />
                            <path
                                d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"
                                fill="#EA4335" />
                        </svg>
                        Continuar com Google
                    </a>
                <?php else: ?>
                    <div
                        class="w-full inline-flex justify-center py-3 px-4 border-2 border-gray-200 rounded-lg shadow-sm bg-gray-50 text-sm font-medium text-gray-400 cursor-not-allowed">
                        <i class="fab fa-google text-gray-400 text-lg mr-2"></i>
                        Google (Configure as credenciais)
                    </div>
                    <p class="text-xs text-gray-500 text-center mt-2">
                        Veja o arquivo <code class="bg-gray-100 px-1 rounded">GOOGLE_LOGIN_SETUP.md</code> para configurar
                    </p>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../views/layouts/footer.php'; ?>