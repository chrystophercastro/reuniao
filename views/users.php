<?php
/**
 * View: Gerenciamento de Usuários (Admin)
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../controllers/UserController.php';

// Somente admin
AuthController::requireAdmin();

$userController = new UserController();

// Processar ações via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $result = $userController->store($_POST);
    } elseif ($action === 'update') {
        $result = $userController->update((int)$_POST['id'], $_POST);
    } elseif ($action === 'delete') {
        $result = $userController->destroy((int)$_POST['id']);
    }

    if (isset($result)) {
        $_SESSION['flash_message'] = $result['message'];
        $_SESSION['flash_type']    = $result['success'] ? 'success' : 'danger';
        header('Location: /reuniao/views/users.php');
        exit;
    }
}

$users = $userController->index();

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
            <i class="bi bi-people me-2"></i>Usuários
        </h2>
        <p class="text-muted mb-0">Gerencie os usuários do sistema</p>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#userModal" onclick="clearUserForm()">
        <i class="bi bi-person-plus me-1"></i> Novo Usuário
    </button>
</div>

<?php if ($flashMessage): ?>
    <div class="alert alert-<?= $flashType ?> alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($flashMessage) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Tabela de Usuários -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">#</th>
                        <th>Nome</th>
                        <th>Email</th>
                        <th>Telefone</th>
                        <th>Perfil</th>
                        <th>Criado em</th>
                        <th class="text-end pe-4">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td class="ps-4"><?= $user['id'] ?></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar-circle me-2">
                                        <?= strtoupper(substr($user['name'], 0, 1)) ?>
                                    </div>
                                    <strong><?= htmlspecialchars($user['name']) ?></strong>
                                </div>
                            </td>
                            <td class="text-muted"><?= htmlspecialchars($user['email']) ?></td>
                            <td class="text-muted">
                                <?php if (!empty($user['phone'])): ?>
                                    <i class="bi bi-whatsapp text-success me-1"></i><?= htmlspecialchars($user['phone']) ?>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($user['role'] === 'admin'): ?>
                                    <span class="badge bg-primary px-3 py-2">
                                        <i class="bi bi-shield-check me-1"></i> Admin
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-secondary px-3 py-2">
                                        <i class="bi bi-person me-1"></i> Usuário
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="text-muted"><?= date('d/m/Y H:i', strtotime($user['created_at'])) ?></td>
                            <td class="text-end pe-4">
                                <button class="btn btn-sm btn-outline-primary me-1"
                                        onclick="editUser(<?= htmlspecialchars(json_encode($user)) ?>)">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <?php if ($user['id'] != $currentUser['id']): ?>
                                    <button class="btn btn-sm btn-outline-danger"
                                            onclick="deleteUser(<?= $user['id'] ?>, '<?= htmlspecialchars($user['name'], ENT_QUOTES) ?>')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal: Criar/Editar Usuário -->
<div class="modal fade" id="userModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="">
                <div class="modal-header bg-primary-custom text-white">
                    <h5 class="modal-title" id="userModalTitle">
                        <i class="bi bi-person-plus me-2"></i>Novo Usuário
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" id="userAction" value="create">
                    <input type="hidden" name="id" id="userId" value="">

                    <div class="mb-3">
                        <label for="userName" class="form-label fw-semibold">Nome Completo *</label>
                        <input type="text" class="form-control" id="userName" name="name"
                               placeholder="Nome do usuário" required>
                    </div>

                    <div class="mb-3">
                        <label for="userEmail" class="form-label fw-semibold">Email *</label>
                        <input type="email" class="form-control" id="userEmail" name="email"
                               placeholder="email@exemplo.com" required>
                    </div>

                    <div class="mb-3">
                        <label for="userPhone" class="form-label fw-semibold">
                            <i class="bi bi-whatsapp text-success me-1"></i>Telefone / WhatsApp
                        </label>
                        <input type="text" class="form-control" id="userPhone" name="phone"
                               placeholder="11999999999">
                        <div class="form-text">Número com DDD para notificações WhatsApp</div>
                    </div>

                    <div class="mb-3">
                        <label for="userPassword" class="form-label fw-semibold">
                            Senha <span id="passwordHint" class="text-muted small">(mínimo 6 caracteres)</span>
                        </label>
                        <input type="password" class="form-control" id="userPassword" name="password"
                               placeholder="Senha" minlength="6">
                    </div>

                    <div class="mb-3">
                        <label for="userRole" class="form-label fw-semibold">Perfil *</label>
                        <select class="form-select" id="userRole" name="role" required>
                            <option value="user">Usuário</option>
                            <option value="admin">Administrador</option>
                        </select>
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
<div class="modal fade" id="deleteUserModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <form method="POST" action="">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" id="deleteUserId" value="">
                <div class="modal-body text-center py-4">
                    <i class="bi bi-exclamation-triangle display-4 text-danger"></i>
                    <h5 class="mt-3">Excluir Usuário</h5>
                    <p class="text-muted">Tem certeza que deseja excluir o usuário <strong id="deleteUserName"></strong>?</p>
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
    function clearUserForm() {
        document.getElementById('userModalTitle').innerHTML = '<i class="bi bi-person-plus me-2"></i>Novo Usuário';
        document.getElementById('userAction').value = 'create';
        document.getElementById('userId').value = '';
        document.getElementById('userName').value = '';
        document.getElementById('userEmail').value = '';
        document.getElementById('userPhone').value = '';
        document.getElementById('userPassword').value = '';
        document.getElementById('userPassword').setAttribute('required', 'required');
        document.getElementById('passwordHint').textContent = '(mínimo 6 caracteres) *';
        document.getElementById('userRole').value = 'user';
    }

    function editUser(user) {
        document.getElementById('userModalTitle').innerHTML = '<i class="bi bi-pencil me-2"></i>Editar Usuário';
        document.getElementById('userAction').value = 'update';
        document.getElementById('userId').value = user.id;
        document.getElementById('userName').value = user.name;
        document.getElementById('userEmail').value = user.email;
        document.getElementById('userPhone').value = user.phone || '';
        document.getElementById('userPassword').value = '';
        document.getElementById('userPassword').removeAttribute('required');
        document.getElementById('passwordHint').textContent = '(deixe em branco para manter)';
        document.getElementById('userRole').value = user.role;
        new bootstrap.Modal(document.getElementById('userModal')).show();
    }

    function deleteUser(id, name) {
        document.getElementById('deleteUserId').value = id;
        document.getElementById('deleteUserName').textContent = name;
        new bootstrap.Modal(document.getElementById('deleteUserModal')).show();
    }
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
