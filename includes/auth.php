<?php
/**
 * Auth guard - incluir no topo de páginas protegidas
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../controllers/AuthController.php';

AuthController::requireAuth();

$currentUser = AuthController::getUser();
$isAdmin     = AuthController::isAdmin();
