<?php
/**
 * MeetingRoom Manager - Ponto de entrada
 *
 * Redireciona para a tela de login ou dashboard
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header('Location: /reuniao/views/dashboard.php');
} else {
    header('Location: /reuniao/views/login.php');
}
exit;
