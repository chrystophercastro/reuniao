<?php
/**
 * API: Atualizar reserva (POST)
 */

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../controllers/ReservationController.php';

if (!AuthController::isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Não autorizado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

$controller = new ReservationController();

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$id = (int)($input['id'] ?? 0);

if ($id < 1) {
    echo json_encode(['success' => false, 'message' => 'ID da reserva é obrigatório']);
    exit;
}

$result = $controller->update($id, $input);

echo json_encode($result);
