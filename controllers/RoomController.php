<?php
/**
 * Controller: RoomController
 */

require_once __DIR__ . '/../models/Room.php';
require_once __DIR__ . '/../controllers/AuthController.php';

class RoomController
{
    private Room $roomModel;

    public function __construct()
    {
        $this->roomModel = new Room();
    }

    /**
     * Listar todas as salas
     */
    public function index(): array
    {
        return $this->roomModel->getAll();
    }

    /**
     * Buscar sala por ID
     */
    public function show(int $id): ?array
    {
        return $this->roomModel->findById($id);
    }

    /**
     * Criar sala (somente admin)
     */
    public function store(array $data): array
    {
        if (!AuthController::isAdmin()) {
            return ['success' => false, 'message' => 'Acesso negado. Somente administradores podem criar salas.'];
        }

        $name        = trim($data['name'] ?? '');
        $description = trim($data['description'] ?? '');
        $capacity    = (int)($data['capacity'] ?? 1);
        $color       = trim($data['color'] ?? '#2563EB');
        $createdBy   = $_SESSION['user_id'];

        if (empty($name)) {
            return ['success' => false, 'message' => 'O nome da sala é obrigatório.'];
        }

        if ($capacity < 1) {
            return ['success' => false, 'message' => 'A capacidade deve ser pelo menos 1.'];
        }

        try {
            $id = $this->roomModel->create($name, $description, $capacity, $color, $createdBy);
            return ['success' => true, 'message' => 'Sala criada com sucesso!', 'id' => $id];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erro ao criar sala: ' . $e->getMessage()];
        }
    }

    /**
     * Atualizar sala (somente admin)
     */
    public function update(int $id, array $data): array
    {
        if (!AuthController::isAdmin()) {
            return ['success' => false, 'message' => 'Acesso negado.'];
        }

        $name        = trim($data['name'] ?? '');
        $description = trim($data['description'] ?? '');
        $capacity    = (int)($data['capacity'] ?? 1);
        $color       = trim($data['color'] ?? '#2563EB');

        if (empty($name)) {
            return ['success' => false, 'message' => 'O nome da sala é obrigatório.'];
        }

        try {
            $this->roomModel->update($id, $name, $description, $capacity, $color);
            return ['success' => true, 'message' => 'Sala atualizada com sucesso!'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erro ao atualizar sala: ' . $e->getMessage()];
        }
    }

    /**
     * Deletar sala (somente admin)
     */
    public function destroy(int $id): array
    {
        if (!AuthController::isAdmin()) {
            return ['success' => false, 'message' => 'Acesso negado.'];
        }

        try {
            $this->roomModel->delete($id);
            return ['success' => true, 'message' => 'Sala excluída com sucesso!'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erro ao excluir sala: ' . $e->getMessage()];
        }
    }
}
