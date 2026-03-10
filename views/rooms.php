<?php
/**
 * View: Salas
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../controllers/RoomController.php';

$roomController = new RoomController();

// Processar ações via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isAdmin) {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $result = $roomController->store($_POST);
    } elseif ($action === 'update') {
        $result = $roomController->update((int)$_POST['id'], $_POST);
    } elseif ($action === 'delete') {
        $result = $roomController->destroy((int)$_POST['id']);
    }

    // Redirect para evitar resubmissão do formulário
    if (isset($result)) {
        $_SESSION['flash_message'] = $result['message'];
        $_SESSION['flash_type']    = $result['success'] ? 'success' : 'danger';
        header('Location: /reuniao/views/rooms.php');
        exit;
    }
}

$rooms = $roomController->index();

// Flash message
$flashMessage = $_SESSION['flash_message'] ?? null;
$flashType    = $_SESSION['flash_type'] ?? 'info';
unset($_SESSION['flash_message'], $_SESSION['flash_type']);

require_once __DIR__ . '/../includes/header.php';
?>

<!-- Page Title -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold text-dark mb-1">
            <i class="bi bi-door-open me-2"></i>Salas de Reunião
        </h2>
        <p class="text-muted mb-0">Gerencie as salas disponíveis para reserva</p>
    </div>
    <?php if ($isAdmin): ?>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#roomModal" onclick="clearRoomForm()">
            <i class="bi bi-plus-lg me-1"></i> Nova Sala
        </button>
    <?php endif; ?>
</div>

<?php if ($flashMessage): ?>
    <div class="alert alert-<?= $flashType ?> alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($flashMessage) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Cards de Salas -->
<div class="row g-4">
    <?php if (empty($rooms)): ?>
        <div class="col-12">
            <div class="text-center py-5">
                <i class="bi bi-door-closed display-3 text-muted"></i>
                <p class="text-muted mt-3 fs-5">Nenhuma sala cadastrada.</p>
                <?php if ($isAdmin): ?>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#roomModal" onclick="clearRoomForm()">
                        <i class="bi bi-plus-lg me-1"></i> Criar primeira sala
                    </button>
                <?php endif; ?>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($rooms as $room): ?>
            <div class="col-lg-4 col-md-6">
                <div class="card border-0 shadow-sm room-card h-100">
                    <div class="card-header border-0 py-3" style="background-color: <?= htmlspecialchars($room['color']) ?>">
                        <h5 class="card-title mb-0 text-white fw-bold">
                            <i class="bi bi-door-open me-2"></i>
                            <?= htmlspecialchars($room['name']) ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted"><?= htmlspecialchars($room['description'] ?: 'Sem descrição') ?></p>
                        <div class="d-flex gap-3 text-muted small">
                            <span>
                                <i class="bi bi-people-fill me-1"></i>
                                <?= $room['capacity'] ?> pessoas
                            </span>
                            <span>
                                <i class="bi bi-person me-1"></i>
                                <?= htmlspecialchars($room['creator_name']) ?>
                            </span>
                        </div>
                    </div>
                    <?php if ($isAdmin): ?>
                        <div class="card-footer bg-white border-0 d-flex gap-2">
                            <button class="btn btn-sm btn-outline-primary flex-grow-1"
                                    onclick="editRoom(<?= htmlspecialchars(json_encode($room)) ?>)">
                                <i class="bi bi-pencil me-1"></i> Editar
                            </button>
                            <button class="btn btn-sm btn-outline-danger"
                                    onclick="deleteRoom(<?= $room['id'] ?>, '<?= htmlspecialchars($room['name'], ENT_QUOTES) ?>')">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php if ($isAdmin): ?>
<!-- Modal: Criar/Editar Sala -->
<div class="modal fade" id="roomModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="">
                <div class="modal-header bg-primary-custom text-white">
                    <h5 class="modal-title" id="roomModalTitle">
                        <i class="bi bi-door-open me-2"></i>Nova Sala
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" id="roomAction" value="create">
                    <input type="hidden" name="id" id="roomId" value="">

                    <div class="mb-3">
                        <label for="roomName" class="form-label fw-semibold">Nome da Sala *</label>
                        <input type="text" class="form-control" id="roomName" name="name"
                               placeholder="Ex: Sala de Reunião A" required>
                    </div>

                    <div class="mb-3">
                        <label for="roomDescription" class="form-label fw-semibold">Descrição</label>
                        <textarea class="form-control" id="roomDescription" name="description" rows="2"
                                  placeholder="Descrição da sala..."></textarea>
                    </div>

                    <div class="row mb-3">
                        <div class="col-6">
                            <label for="roomCapacity" class="form-label fw-semibold">Capacidade *</label>
                            <input type="number" class="form-control" id="roomCapacity" name="capacity"
                                   min="1" value="10" required>
                        </div>
                        <div class="col-6">
                            <label for="roomColor" class="form-label fw-semibold">Cor</label>
                            <input type="color" class="form-control form-control-color w-100" id="roomColor"
                                   name="color" value="#2563EB">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i> Salvar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Confirmar exclusão -->
<div class="modal fade" id="deleteRoomModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <form method="POST" action="">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" id="deleteRoomId" value="">
                <div class="modal-body text-center py-4">
                    <i class="bi bi-exclamation-triangle display-4 text-danger"></i>
                    <h5 class="mt-3">Excluir Sala</h5>
                    <p class="text-muted">Tem certeza que deseja excluir a sala <strong id="deleteRoomName"></strong>?</p>
                    <p class="text-danger small">Todas as reservas desta sala serão excluídas.</p>
                </div>
                <div class="modal-footer justify-content-center border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash me-1"></i> Excluir
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function clearRoomForm() {
        document.getElementById('roomModalTitle').innerHTML = '<i class="bi bi-door-open me-2"></i>Nova Sala';
        document.getElementById('roomAction').value = 'create';
        document.getElementById('roomId').value = '';
        document.getElementById('roomName').value = '';
        document.getElementById('roomDescription').value = '';
        document.getElementById('roomCapacity').value = 10;
        document.getElementById('roomColor').value = '#2563EB';
    }

    function editRoom(room) {
        document.getElementById('roomModalTitle').innerHTML = '<i class="bi bi-pencil me-2"></i>Editar Sala';
        document.getElementById('roomAction').value = 'update';
        document.getElementById('roomId').value = room.id;
        document.getElementById('roomName').value = room.name;
        document.getElementById('roomDescription').value = room.description || '';
        document.getElementById('roomCapacity').value = room.capacity;
        document.getElementById('roomColor').value = room.color;
        new bootstrap.Modal(document.getElementById('roomModal')).show();
    }

    function deleteRoom(id, name) {
        document.getElementById('deleteRoomId').value = id;
        document.getElementById('deleteRoomName').textContent = name;
        new bootstrap.Modal(document.getElementById('deleteRoomModal')).show();
    }
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
