<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Controllers\ProductController;

try {
    $controller = new ProductController();
    $method = $_SERVER['REQUEST_METHOD'];

    switch ($method) {
        case 'GET':
            if (isset($_GET['id'])) {
                echo json_encode($controller->show($_GET['id']));
            } else {
                echo json_encode($controller->index());
            }
            break;

        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            echo json_encode(['id' => $controller->store($input)]);
            break;

        case 'PUT':
            // Logic for PUT
            break;

        case 'DELETE':
            if (isset($_GET['id'])) {
                echo json_encode(['success' => $controller->delete($_GET['id'])]);
            }
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
