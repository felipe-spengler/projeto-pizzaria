<?php

use Dotenv\Dotenv;

// Carrega as variáveis de ambiente do arquivo .env na raiz do projeto
// Se o arquivo não existir, não faz nada (silencioso)
// O autoload do composer já deve ter sido carregado antes deste arquivo
// Mas como este arquivo será incluído pelo autoload, o Dotenv deve estar disponível

// Se a classe Dotenv não existir, significa que as dependências não foram instaladas
if (class_exists(Dotenv::class)) {
    // Caminho para a raiz do projeto (assumindo que este arquivo está em src/bootstrap.php)
    $rootDir = __DIR__ . '/../';
    
    // Verifica se existe arquivo .env antes de tentar carregar
    if (file_exists($rootDir . '.env')) {
        $dotenv = Dotenv::createImmutable($rootDir);
        $dotenv->safeLoad();
    }
}
