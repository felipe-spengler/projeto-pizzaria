<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Config\Database;
use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->safeLoad();

// Basic Router
$request = $_SERVER['REQUEST_URI'];
$basePath = ''; // If in subdirectory, add here

// Remove query string
$request = strtok($request, '?');

// Route Dispatcher
switch ($request) {
    case '/':
    case '/home':
        require __DIR__ . '/../views/home.php';
        break;
    case '/menu':
        require __DIR__ . '/../views/menu.php';
        break;
    case '/product':
        require __DIR__ . '/../views/product.php';
        break;
    case '/cart':
        require __DIR__ . '/../views/cart.php';
        break;
    case '/login':
        require __DIR__ . '/../views/auth/login.php';
        break;
    case '/register':
        require __DIR__ . '/../views/auth/register.php';
        break;
    case '/api/flavors':
        // Simple API endpoint example
        header('Content-Type: application/json');
        $db = Database::getInstance()->getConnection();
        $stmt = $db->query("SELECT * FROM flavors WHERE is_available = 1");
        echo json_encode($stmt->fetchAll());
        break;
    default:
        http_response_code(404);
        require __DIR__ . '/../views/404.php';
        break;
}
