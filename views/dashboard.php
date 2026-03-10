<?php
/**
 * View: Dashboard
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../models/Reservation.php';
require_once __DIR__ . '/../models/Room.php';
require_once __DIR__ . '/../models/User.php';

$reservationModel = new Reservation();
$roomModel        = new Room();
$userModel        = new User();

$upcomingMeetings = $reservationModel->getUpcomingByUser($currentUser['id']);
$availableRooms   = $reservationModel->getAvailableRoomsNow();
$totalRooms       = $roomModel->count();
$totalUsers       = $userModel->count();
$todayMeetings    = $reservationModel->countToday();
$totalReservations = $reservationModel->count();

require_once __DIR__ . '/../includes/header.php';
?>

<!-- Page Title -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold text-dark mb-1">
            <i class="bi bi-speedometer2 me-2"></i>Dashboard
        </h2>
        <p class="text-muted mb-0">Bem-vindo, <?= htmlspecialchars($currentUser['name']) ?>!</p>
    </div>
    <a href="/reuniao/views/calendar.php" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> Nova Reunião
    </a>
</div>

<!-- Stats Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3 col-sm-6">
        <div class="card stat-card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="stat-icon bg-primary-light text-primary">
                        <i class="bi bi-calendar-check"></i>
                    </div>
                    <div class="ms-3">
                        <h3 class="mb-0 fw-bold"><?= $todayMeetings ?></h3>
                        <small class="text-muted">Reuniões Hoje</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card stat-card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="stat-icon bg-success-light text-success">
                        <i class="bi bi-door-open"></i>
                    </div>
                    <div class="ms-3">
                        <h3 class="mb-0 fw-bold"><?= count($availableRooms) ?>/<?= $totalRooms ?></h3>
                        <small class="text-muted">Salas Disponíveis</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card stat-card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="stat-icon bg-warning-light text-warning">
                        <i class="bi bi-bookmark-star"></i>
                    </div>
                    <div class="ms-3">
                        <h3 class="mb-0 fw-bold"><?= $totalReservations ?></h3>
                        <small class="text-muted">Total de Reservas</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card stat-card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="stat-icon bg-info-light text-info">
                        <i class="bi bi-people"></i>
                    </div>
                    <div class="ms-3">
                        <h3 class="mb-0 fw-bold"><?= $totalUsers ?></h3>
                        <small class="text-muted">Usuários</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Próximas Reuniões -->
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 py-3">
                <h5 class="mb-0 fw-bold">
                    <i class="bi bi-clock-history me-2 text-primary"></i>Próximas Reuniões
                </h5>
            </div>
            <div class="card-body p-0">
                <?php if (empty($upcomingMeetings)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-calendar-x display-4 text-muted"></i>
                        <p class="text-muted mt-2">Nenhuma reunião agendada.</p>
                        <a href="/reuniao/views/calendar.php" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-plus-lg me-1"></i> Agendar Reunião
                        </a>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($upcomingMeetings as $meeting): ?>
                            <div class="list-group-item border-0 py-3">
                                <div class="d-flex align-items-center">
                                    <div class="meeting-color-bar me-3" style="background-color: <?= htmlspecialchars($meeting['room_color']) ?>"></div>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1 fw-semibold"><?= htmlspecialchars($meeting['title']) ?></h6>
                                        <div class="d-flex flex-wrap gap-3 text-muted small">
                                            <span>
                                                <i class="bi bi-door-open me-1"></i>
                                                <?= htmlspecialchars($meeting['room_name']) ?>
                                            </span>
                                            <span>
                                                <i class="bi bi-calendar3 me-1"></i>
                                                <?= date('d/m/Y', strtotime($meeting['start_datetime'])) ?>
                                            </span>
                                            <span>
                                                <i class="bi bi-clock me-1"></i>
                                                <?= date('H:i', strtotime($meeting['start_datetime'])) ?> -
                                                <?= date('H:i', strtotime($meeting['end_datetime'])) ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Salas Disponíveis -->
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 py-3">
                <h5 class="mb-0 fw-bold">
                    <i class="bi bi-door-open me-2 text-success"></i>Salas Disponíveis Agora
                </h5>
            </div>
            <div class="card-body p-0">
                <?php if (empty($availableRooms)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-door-closed display-4 text-muted"></i>
                        <p class="text-muted mt-2">Todas as salas estão ocupadas.</p>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($availableRooms as $room): ?>
                            <div class="list-group-item border-0 py-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1 fw-semibold">
                                            <span class="room-dot me-2" style="background-color: <?= htmlspecialchars($room['color']) ?>"></span>
                                            <?= htmlspecialchars($room['name']) ?>
                                        </h6>
                                        <small class="text-muted">
                                            <i class="bi bi-people me-1"></i>
                                            Capacidade: <?= $room['capacity'] ?> pessoas
                                        </small>
                                    </div>
                                    <span class="badge bg-success-light text-success px-3 py-2">
                                        <i class="bi bi-check-circle me-1"></i> Livre
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
