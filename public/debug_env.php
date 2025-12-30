<?php
echo "<h1>Debug Ambiente</h1>";
echo "DB_HOST: " . ($_ENV['DB_HOST'] ?? getenv('DB_HOST') ?? 'NÃO DEFINIDO') . "<br>";
echo "DB_USER: " . ($_ENV['DB_USER'] ?? getenv('DB_USER') ?? 'NÃO DEFINIDO') . "<br>";
// Mostrar só os primeiros 3 caracteres da senha por segurança
$pass = $_ENV['DB_PASSWORD'] ?? $_ENV['DB_PASS'] ?? getenv('DB_PASSWORD') ?? getenv('DB_PASS') ?? 'NÃO DEFINIDO';
echo "DB_PASS (len): " . strlen($pass) . " - Inicia com: " . substr($pass, 0, 3) . "***<br>";
