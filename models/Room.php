<?php
/**
 * Model: Room
 */

require_once __DIR__ . '/../config/database.php';

class Room
{
    private PDO $db;

    public function __construct()
    {
        $this->db = getConnection();
    }

    /**
     * Buscar sala por ID
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT r.*, u.name AS creator_name
             FROM rooms r
             JOIN users u ON r.created_by = u.id
             WHERE r.id = :id"
        );
        $stmt->execute(['id' => $id]);
        $room = $stmt->fetch();
        return $room ?: null;
    }

    /**
     * Listar todas as salas
     */
    public function getAll(): array
    {
        $stmt = $this->db->query(
            "SELECT r.*, u.name AS creator_name
             FROM rooms r
             JOIN users u ON r.created_by = u.id
             ORDER BY r.name ASC"
        );
        return $stmt->fetchAll();
    }

    /**
     * Criar nova sala
     */
    public function create(string $name, string $description, int $capacity, string $color, int $createdBy): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO rooms (name, description, capacity, color, created_by)
             VALUES (:name, :description, :capacity, :color, :created_by)"
        );
        $stmt->execute([
            'name'        => $name,
            'description' => $description,
            'capacity'    => $capacity,
            'color'       => $color,
            'created_by'  => $createdBy,
        ]);
        return (int) $this->db->lastInsertId();
    }

    /**
     * Atualizar sala
     */
    public function update(int $id, string $name, string $description, int $capacity, string $color): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE rooms SET name = :name, description = :description, capacity = :capacity, color = :color WHERE id = :id"
        );
        return $stmt->execute([
            'id'          => $id,
            'name'        => $name,
            'description' => $description,
            'capacity'    => $capacity,
            'color'       => $color,
        ]);
    }

    /**
     * Deletar sala
     */
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM rooms WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Contar total de salas
     */
    public function count(): int
    {
        $stmt = $this->db->query("SELECT COUNT(*) as total FROM rooms");
        return (int) $stmt->fetch()['total'];
    }
}
