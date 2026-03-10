<?php
/**
 * Header - Layout principal (com tema dinâmico)
 */

require_once __DIR__ . '/../models/Settings.php';

$currentPage = basename($_SERVER['PHP_SELF'], '.php');

// Carregar configurações visuais
$cfg = Settings::instance()->getAll();
$appName  = htmlspecialchars($cfg['app_name']);
$appLogo  = $cfg['app_logo'] ?? '';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $appName ?></title>

    <?php if (!empty($cfg['app_favicon'])): ?>
    <link rel="icon" href="/reuniao/<?= htmlspecialchars($cfg['app_favicon']) ?>">
    <?php endif; ?>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- FullCalendar -->
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.css" rel="stylesheet">
    <!-- Select2 -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="/reuniao/assets/css/style.css" rel="stylesheet">

    <!-- Dynamic Theme Colors -->
    <style>
        :root {
            --primary: <?= htmlspecialchars($cfg['color_primary']) ?>;
            --primary-dark: <?= htmlspecialchars($cfg['color_primary_dark']) ?>;
            --primary-light: <?= htmlspecialchars($cfg['color_primary']) ?>22;
            --secondary: <?= htmlspecialchars($cfg['color_secondary']) ?>;
            --background: <?= htmlspecialchars($cfg['color_background']) ?>;
            --sidebar-bg: <?= htmlspecialchars($cfg['color_sidebar']) ?>;
        }
        .bg-primary-custom {
            background: linear-gradient(135deg, <?= htmlspecialchars($cfg['color_primary']) ?> 0%, <?= htmlspecialchars($cfg['color_primary_dark']) ?> 100%) !important;
        }
        .bg-sidebar { background-color: <?= htmlspecialchars($cfg['color_sidebar']) ?>; }
        .btn-primary { background-color: <?= htmlspecialchars($cfg['color_primary']) ?>; border-color: <?= htmlspecialchars($cfg['color_primary']) ?>; }
        .btn-primary:hover { background-color: <?= htmlspecialchars($cfg['color_primary_dark']) ?>; border-color: <?= htmlspecialchars($cfg['color_primary_dark']) ?>; }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary-custom fixed-top">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center" href="/reuniao/views/dashboard.php">
            <?php if ($appLogo): ?>
                <img src="/reuniao/<?= htmlspecialchars($appLogo) ?>" alt="Logo" height="32" class="me-2">
            <?php else: ?>
                <i class="bi bi-calendar-event me-2"></i>
            <?php endif; ?>
            <span class="fw-bold"><?= $appName ?></span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-center">
                <li class="nav-item">
                    <span class="nav-link text-light">
                        <i class="bi bi-person-circle me-1"></i>
                        <?= htmlspecialchars($currentUser['name'] ?? '') ?>
                        <span class="badge bg-light text-primary ms-1"><?= htmlspecialchars($currentUser['role'] ?? '') ?></span>
                    </span>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-light" href="/reuniao/views/logout.php" title="Sair">
                        <i class="bi bi-box-arrow-right"></i> Sair
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Layout principal -->
<div class="d-flex" id="wrapper">
    <!-- Sidebar -->
    <nav class="sidebar bg-sidebar" id="sidebar">
        <div class="sidebar-content">
            <ul class="nav flex-column mt-3">
                <li class="nav-item">
                    <a class="nav-link sidebar-link <?= $currentPage === 'dashboard' ? 'active' : '' ?>"
                       href="/reuniao/views/dashboard.php">
                        <i class="bi bi-speedometer2"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link sidebar-link <?= $currentPage === 'calendar' ? 'active' : '' ?>"
                       href="/reuniao/views/calendar.php">
                        <i class="bi bi-calendar3"></i>
                        <span>Calendário</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link sidebar-link <?= $currentPage === 'rooms' ? 'active' : '' ?>"
                       href="/reuniao/views/rooms.php">
                        <i class="bi bi-door-open"></i>
                        <span>Salas</span>
                    </a>
                </li>
                <?php if ($isAdmin): ?>
                <li class="nav-item">
                    <a class="nav-link sidebar-link <?= $currentPage === 'users' ? 'active' : '' ?>"
                       href="/reuniao/views/users.php">
                        <i class="bi bi-people"></i>
                        <span>Usuários</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link sidebar-link <?= $currentPage === 'settings' ? 'active' : '' ?>"
                       href="/reuniao/views/settings.php">
                        <i class="bi bi-gear"></i>
                        <span>Configurações</span>
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <!-- Conteúdo principal -->
    <main class="main-content" id="mainContent">
        <div class="container-fluid p-4">
