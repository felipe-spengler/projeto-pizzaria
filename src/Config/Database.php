<?php

namespace App\Config;

use PDO;
use PDOException;

class Database
{
    private static $instance = null;
    private $conn;

    private function __construct()
    {
        $host = $_ENV['DB_HOST'] ?? 'whatsapp-chatt_mysql_app'; // Use o host interno como fallback se quiser
        $db_name = $_ENV['DB_NAME'] ?? 'pizzaria';
        $username = $_ENV['DB_USER'] ?? 'pizzaria';
        // AQUI: Ajuste o nome da variável de ambiente para ser lida corretamente
        $password = $_ENV['DB_PASSWORD'] ?? 'password';

        try {
            $this->conn = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8mb4", $username, $password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

            // Define o fuso horário para São Paulo (UTC-3)
            $this->conn->exec("SET time_zone = '-03:00'");
        } catch (PDOException $e) {
            echo "Connection Error: " . $e->getMessage();
            exit;
        }
    }

    public static function getInstance()
    {
        if (self::$instance == null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getConnection()
    {
        return $this->conn;
    }
}
