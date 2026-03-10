<?php
/**
 * View: Calendário
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../models/Room.php';
require_once __DIR__ . '/../models/User.php';

$roomModel = new Room();
$userModel = new User();
$rooms     = $roomModel->getAll();
$users     = $userModel->getAll();

require_once __DIR__ . '/../includes/header.php';
?>

<!-- Page Title -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold text-dark mb-1">
            <i class="bi bi-calendar3 me-2"></i>Calendário
        </h2>
        <p class="text-muted mb-0">Visualize e gerencie as reservas de salas</p>
    </div>
    <div class="d-flex gap-2">
        <!-- Filtro por sala -->
        <select class="form-select" id="roomFilter" style="width: 200px;">
            <option value="">Todas as salas</option>
            <?php foreach ($rooms as $room): ?>
                <option value="<?= $room['id'] ?>" data-color="<?= htmlspecialchars($room['color']) ?>">
                    <?= htmlspecialchars($room['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button class="btn btn-primary" id="btnNewReservation">
            <i class="bi bi-plus-lg me-1"></i> Nova Reunião
        </button>
    </div>
</div>

<!-- Legenda das salas -->
<div class="mb-3 d-flex flex-wrap gap-3">
    <?php foreach ($rooms as $room): ?>
        <span class="badge room-legend-badge" style="background-color: <?= htmlspecialchars($room['color']) ?>">
            <?= htmlspecialchars($room['name']) ?>
        </span>
    <?php endforeach; ?>
</div>

<!-- Calendário -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-3">
        <div id="calendar"></div>
    </div>
</div>

<!-- ======================================= -->
<!-- Modal: Criar/Editar Reserva -->
<!-- ======================================= -->
<div class="modal fade" id="reservationModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary-custom text-white">
                <h5 class="modal-title" id="modalTitle">
                    <i class="bi bi-calendar-plus me-2"></i>Nova Reunião
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="reservationForm">
                    <input type="hidden" id="reservationId" name="id" value="">

                    <div class="mb-3">
                        <label for="resTitle" class="form-label fw-semibold">
                            <i class="bi bi-type me-1"></i> Título da Reunião *
                        </label>
                        <input type="text" class="form-control" id="resTitle" name="title"
                               placeholder="Ex: Reunião de Planejamento" required>
                    </div>

                    <div class="mb-3">
                        <label for="resDescription" class="form-label fw-semibold">
                            <i class="bi bi-text-paragraph me-1"></i> Descrição
                        </label>
                        <textarea class="form-control" id="resDescription" name="description" rows="2"
                                  placeholder="Detalhes da reunião..."></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="resRoom" class="form-label fw-semibold">
                            <i class="bi bi-door-open me-1"></i> Sala *
                        </label>
                        <select class="form-select" id="resRoom" name="room_id" required>
                            <option value="">Selecione uma sala</option>
                            <?php foreach ($rooms as $room): ?>
                                <option value="<?= $room['id'] ?>">
                                    <?= htmlspecialchars($room['name']) ?> (<?= $room['capacity'] ?> pessoas)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="row mb-3">
                        <div class="col-6">
                            <label for="resStart" class="form-label fw-semibold">
                                <i class="bi bi-clock me-1"></i> Início *
                            </label>
                            <input type="datetime-local" class="form-control" id="resStart" name="start" required>
                        </div>
                        <div class="col-6">
                            <label for="resEnd" class="form-label fw-semibold">
                                <i class="bi bi-clock-fill me-1"></i> Fim *
                            </label>
                            <input type="datetime-local" class="form-control" id="resEnd" name="end" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="resParticipants" class="form-label fw-semibold">
                            <i class="bi bi-people me-1"></i> Participantes
                        </label>
                        <select class="form-select" id="resParticipants" name="participants[]" multiple>
                            <?php foreach ($users as $user): ?>
                                <option value="<?= $user['id'] ?>"><?= htmlspecialchars($user['name']) ?> (<?= htmlspecialchars($user['email']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-danger me-auto" id="btnDeleteReservation" style="display:none;">
                    <i class="bi bi-trash me-1"></i> Excluir
                </button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-lg me-1"></i> Cancelar
                </button>
                <button type="button" class="btn btn-primary" id="btnSaveReservation">
                    <i class="bi bi-check-lg me-1"></i> Salvar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ======================================= -->
<!-- Modal: Visualizar Reserva -->
<!-- ======================================= -->
<div class="modal fade" id="viewReservationModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" id="viewModalHeader">
                <h5 class="modal-title text-white" id="viewModalTitle"></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <div class="d-flex align-items-center mb-2">
                        <i class="bi bi-door-open text-primary me-2"></i>
                        <strong>Sala:</strong>
                        <span class="ms-2" id="viewRoom"></span>
                    </div>
                    <div class="d-flex align-items-center mb-2">
                        <i class="bi bi-calendar3 text-primary me-2"></i>
                        <strong>Data:</strong>
                        <span class="ms-2" id="viewDate"></span>
                    </div>
                    <div class="d-flex align-items-center mb-2">
                        <i class="bi bi-clock text-primary me-2"></i>
                        <strong>Horário:</strong>
                        <span class="ms-2" id="viewTime"></span>
                    </div>
                    <div class="d-flex align-items-center mb-2">
                        <i class="bi bi-person text-primary me-2"></i>
                        <strong>Organizador:</strong>
                        <span class="ms-2" id="viewCreator"></span>
                    </div>
                    <div class="mb-2" id="viewDescriptionBlock" style="display:none;">
                        <div class="d-flex align-items-center mb-1">
                            <i class="bi bi-text-paragraph text-primary me-2"></i>
                            <strong>Descrição:</strong>
                        </div>
                        <p class="ms-4 text-muted" id="viewDescription"></p>
                    </div>
                    <div id="viewParticipantsBlock" style="display:none;">
                        <div class="d-flex align-items-center mb-1">
                            <i class="bi bi-people text-primary me-2"></i>
                            <strong>Participantes:</strong>
                        </div>
                        <div class="ms-4" id="viewParticipants"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-danger me-auto" id="btnViewDelete" style="display:none;">
                    <i class="bi bi-trash me-1"></i> Excluir
                </button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                <button type="button" class="btn btn-primary" id="btnViewEdit" style="display:none;">
                    <i class="bi bi-pencil me-1"></i> Editar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Dados para o JavaScript -->
<script>
    const APP_CONFIG = {
        currentUserId: <?= $currentUser['id'] ?>,
        currentUserRole: '<?= $currentUser['role'] ?>',
        apiBase: '/reuniao/api',
        rooms: <?= json_encode($rooms) ?>
    };
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
