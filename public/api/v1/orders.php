<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Controllers\OrderController;

try {
    $controller = new OrderController();
    $method = $_SERVER['REQUEST_METHOD'];

    // Basic Auth Check (Simple Token or Session for now) - Improve later
    // session_start();
    // if (!isset($_SESSION['user_id'])) {
    //     http_response_code(401);
    //     echo json_encode(['error' => 'Unauthorized']);
    //     exit;
    // }

    switch ($method) {
        case 'GET':
            if (isset($_GET['id'])) {
                echo json_encode($controller->show($_GET['id']));
            } else {
                // Parse filters from $_GET
                $filters = $_GET;
                echo json_encode($controller->index($filters));
            }
            break;

        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            echo json_encode(['id' => $controller->store($input)]);
            break;

        case 'PUT': // Update Status
            $input = json_decode(file_get_contents('php://input'), true);
            if (isset($_GET['id']) && isset($input['status'])) {
                echo json_encode(['success' => $controller->updateStatus($_GET['id'], $input['status'])]);
            }
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
