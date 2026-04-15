<?php

namespace App\Config;

use PDO;
use PDOException;

class Database
{
    private static $instance = null;
    private $conn;

    /**
     * Helper para ler variável de ambiente:
     * 1º tenta $_ENV (carregado pelo .env via Dotenv)
     * 2º tenta getenv() (variáveis do sistema / Coolify / Docker)
     * 3º usa o $default fornecido
     */
    private static function env(string $key, string $default = ''): string
    {
        if (isset($_ENV[$key]) && $_ENV[$key] !== '') {
            return $_ENV[$key];
        }
        $val = getenv($key);
        if ($val !== false && $val !== '') {
            return $val;
        }
        return $default;
    }

    private function __construct()
    {
        $host = self::env('DB_HOST', 'mysql');
        $db_name = self::env('DB_NAME', 'pizzaria');
        $username = self::env('DB_USER', 'pizzaria_user');
        // Aceita DB_PASSWORD ou DB_PASS (compatível com Coolify e .env)
        $password = self::env('DB_PASSWORD', self::env('DB_PASS', 'secret123'));

        try {
            $this->conn = new PDO(
                "mysql:host=$host;dbname=$db_name;charset=utf8mb4",
                $username,
                $password,
                [
                    PDO::ATTR_PERSISTENT => true,
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]
            );

            // Define o fuso horário para São Paulo (UTC-3)
            $this->conn->exec("SET time_zone = '-03:00'");
        } catch (PDOException $e) {
            // Em produção evita expor detalhes; loga error e exibe mensagem genérica
            error_log("Database connection error: " . $e->getMessage());
            echo "Erro ao conectar ao banco de dados. Tente novamente mais tarde.";
            exit;
        }
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getConnection()
    {
        return $this->conn;
    }
}
