<?php

use Dotenv\Dotenv;

// Caminho para a raiz do projeto (src/bootstrap.php → raiz)
$rootDir = __DIR__ . '/../';

// Tenta carregar variáveis do arquivo .env (desenvolvimento local)
// Se não existir .env, usa as variáveis já injetadas pelo Coolify (ou Docker)
if (class_exists(Dotenv::class) && file_exists($rootDir . '.env')) {
    // Usa createUnsafeImmutable para que $_ENV E getenv() funcionem juntos
    $dotenv = Dotenv::createUnsafeImmutable($rootDir);
    $dotenv->safeLoad();
}

// Garante que as variáveis de ambiente do sistema (injetadas pelo Coolify/Docker)
// também estejam disponíveis em $_ENV, caso não venham do .env
$envVars = [
    'DB_HOST',
    'DB_NAME',
    'DB_USER',
    'DB_PASS',
    'DB_PASSWORD',
    'APP_URL',
    'GOOGLE_CLIENT_ID',
    'GOOGLE_CLIENT_SECRET',
    'GOOGLE_REDIRECT_URI',
];

foreach ($envVars as $var) {
    if (!isset($_ENV[$var])) {
        $val = getenv($var);
        if ($val !== false) {
            $_ENV[$var] = $val;
        }
    }
}
