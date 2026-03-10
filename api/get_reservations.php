<?php
/**
 * API: Buscar reservas (GET)
 *
 * Retorno JSON para o FullCalendar
 */

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../controllers/ReservationController.php';

// Verificar autenticação
if (!AuthController::isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Não autorizado']);
    exit;
}

$controller = new ReservationController();

$roomId = isset($_GET['room_id']) && $_GET['room_id'] !== '' ? (int) $_GET['room_id'] : null;

$events = $controller->index($roomId);

echo json_encode($events);
