<?php
/**
 * Controller: UserController
 */

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../controllers/AuthController.php';

class UserController
{
    private User $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }

    /**
     * Listar todos os usuários
     */
    public function index(): array
    {
        return $this->userModel->getAll();
    }

    /**
     * Buscar usuário por ID
     */
    public function show(int $id): ?array
    {
        return $this->userModel->findById($id);
    }

    /**
     * Criar usuário (somente admin)
     */
    public function store(array $data): array
    {
        if (!AuthController::isAdmin()) {
            return ['success' => false, 'message' => 'Acesso negado.'];
        }

        $name     = trim($data['name'] ?? '');
        $email    = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';
        $role     = $data['role'] ?? 'user';
        $phone    = trim($data['phone'] ?? '');

        if (empty($name) || empty($email) || empty($password)) {
            return ['success' => false, 'message' => 'Preencha todos os campos obrigatórios.'];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Email inválido.'];
        }

        // Verificar email duplicado
        if ($this->userModel->findByEmail($email)) {
            return ['success' => false, 'message' => 'Este email já está cadastrado.'];
        }

        if (strlen($password) < 6) {
            return ['success' => false, 'message' => 'A senha deve ter pelo menos 6 caracteres.'];
        }

        try {
            $id = $this->userModel->create($name, $email, $password, $role, $phone);
            return ['success' => true, 'message' => 'Usuário criado com sucesso!', 'id' => $id];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erro ao criar usuário: ' . $e->getMessage()];
        }
    }

    /**
     * Atualizar usuário (somente admin)
     */
    public function update(int $id, array $data): array
    {
        if (!AuthController::isAdmin()) {
            return ['success' => false, 'message' => 'Acesso negado.'];
        }

        $name  = trim($data['name'] ?? '');
        $email = trim($data['email'] ?? '');
        $role  = $data['role'] ?? 'user';
        $phone = trim($data['phone'] ?? '');

        if (empty($name) || empty($email)) {
            return ['success' => false, 'message' => 'Nome e email são obrigatórios.'];
        }

        try {
            $this->userModel->update($id, $name, $email, $role, $phone);

            // Atualizar senha se informada
            if (!empty($data['password']) && strlen($data['password']) >= 6) {
                $this->userModel->updatePassword($id, $data['password']);
            }

            return ['success' => true, 'message' => 'Usuário atualizado com sucesso!'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erro ao atualizar usuário: ' . $e->getMessage()];
        }
    }

    /**
     * Deletar usuário (somente admin)
     */
    public function destroy(int $id): array
    {
        if (!AuthController::isAdmin()) {
            return ['success' => false, 'message' => 'Acesso negado.'];
        }

        // Não pode deletar a si mesmo
        if ($id == $_SESSION['user_id']) {
            return ['success' => false, 'message' => 'Você não pode excluir sua própria conta.'];
        }

        try {
            $this->userModel->delete($id);
            return ['success' => true, 'message' => 'Usuário excluído com sucesso!'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erro ao excluir usuário: ' . $e->getMessage()];
        }
    }
}
