<?php
/**
 * API: Buscar detalhes de uma reserva (GET)
 */

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../controllers/ReservationController.php';

if (!AuthController::isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Não autorizado']);
    exit;
}

$id = (int)($_GET['id'] ?? 0);

if ($id < 1) {
    echo json_encode(['error' => 'ID inválido']);
    exit;
}

$controller  = new ReservationController();
$reservation = $controller->show($id);

if (!$reservation) {
    http_response_code(404);
    echo json_encode(['error' => 'Reserva não encontrada']);
    exit;
}

echo json_encode($reservation);
