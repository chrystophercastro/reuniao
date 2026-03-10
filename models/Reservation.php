<?php
/**
 * Model: Reservation
 */

require_once __DIR__ . '/../config/database.php';

class Reservation
{
    private PDO $db;

    public function __construct()
    {
        $this->db = getConnection();
    }

    /**
     * Buscar reserva por ID
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT res.*, r.name AS room_name, r.color AS room_color, u.name AS creator_name
             FROM reservations res
             JOIN rooms r ON res.room_id = r.id
             JOIN users u ON res.created_by = u.id
             WHERE res.id = :id"
        );
        $stmt->execute(['id' => $id]);
        $reservation = $stmt->fetch();
        return $reservation ?: null;
    }

    /**
     * Listar todas as reservas (para o calendário)
     */
    public function getAll(?int $roomId = null): array
    {
        $sql = "SELECT res.*, r.name AS room_name, r.color AS room_color, u.name AS creator_name
                FROM reservations res
                JOIN rooms r ON res.room_id = r.id
                JOIN users u ON res.created_by = u.id";

        $params = [];
        if ($roomId !== null) {
            $sql .= " WHERE res.room_id = :room_id";
            $params['room_id'] = $roomId;
        }

        $sql .= " ORDER BY res.start_datetime ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Buscar reservas em um intervalo de datas
     */
    public function getByDateRange(string $start, string $end, ?int $roomId = null): array
    {
        $sql = "SELECT res.*, r.name AS room_name, r.color AS room_color, u.name AS creator_name
                FROM reservations res
                JOIN rooms r ON res.room_id = r.id
                JOIN users u ON res.created_by = u.id
                WHERE res.start_datetime >= :start AND res.end_datetime <= :end_date";

        $params = ['start' => $start, 'end_date' => $end];

        if ($roomId !== null) {
            $sql .= " AND res.room_id = :room_id";
            $params['room_id'] = $roomId;
        }

        $sql .= " ORDER BY res.start_datetime ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Buscar próximas reuniões do usuário
     */
    public function getUpcomingByUser(int $userId, int $limit = 5): array
    {
        $stmt = $this->db->prepare(
            "SELECT DISTINCT res.*, r.name AS room_name, r.color AS room_color
             FROM reservations res
             JOIN rooms r ON res.room_id = r.id
             LEFT JOIN reservation_participants rp ON res.id = rp.reservation_id
             WHERE (res.created_by = :uid1 OR rp.user_id = :uid2)
               AND res.start_datetime >= NOW()
             ORDER BY res.start_datetime ASC
             LIMIT :lim"
        );
        $stmt->bindValue('uid1', $userId, PDO::PARAM_INT);
        $stmt->bindValue('uid2', $userId, PDO::PARAM_INT);
        $stmt->bindValue('lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Verificar conflito de horário
     */
    public function hasConflict(int $roomId, string $start, string $end, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) AS total FROM reservations
                WHERE room_id = :room_id
                AND start_datetime < :end_dt
                AND end_datetime > :start_dt";

        $params = [
            'room_id'  => $roomId,
            'start_dt' => $start,
            'end_dt'   => $end,
        ];

        if ($excludeId !== null) {
            $sql .= " AND id != :exclude_id";
            $params['exclude_id'] = $excludeId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetch()['total'] > 0;
    }

    /**
     * Criar nova reserva
     */
    public function create(int $roomId, string $title, string $description, string $start, string $end, int $createdBy): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO reservations (room_id, title, description, start_datetime, end_datetime, created_by)
             VALUES (:room_id, :title, :description, :start_dt, :end_dt, :created_by)"
        );
        $stmt->execute([
            'room_id'     => $roomId,
            'title'       => $title,
            'description' => $description,
            'start_dt'    => $start,
            'end_dt'      => $end,
            'created_by'  => $createdBy,
        ]);
        return (int) $this->db->lastInsertId();
    }

    /**
     * Atualizar reserva
     */
    public function update(int $id, int $roomId, string $title, string $description, string $start, string $end): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE reservations
             SET room_id = :room_id, title = :title, description = :description,
                 start_datetime = :start_dt, end_datetime = :end_dt
             WHERE id = :id"
        );
        return $stmt->execute([
            'id'          => $id,
            'room_id'     => $roomId,
            'title'       => $title,
            'description' => $description,
            'start_dt'    => $start,
            'end_dt'      => $end,
        ]);
    }

    /**
     * Atualizar datas (drag & drop no calendário)
     */
    public function updateDates(int $id, string $start, string $end): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE reservations SET start_datetime = :start_dt, end_datetime = :end_dt WHERE id = :id"
        );
        return $stmt->execute([
            'id'       => $id,
            'start_dt' => $start,
            'end_dt'   => $end,
        ]);
    }

    /**
     * Deletar reserva
     */
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM reservations WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Adicionar participantes
     */
    public function addParticipants(int $reservationId, array $userIds): void
    {
        $stmt = $this->db->prepare(
            "INSERT IGNORE INTO reservation_participants (reservation_id, user_id) VALUES (:rid, :uid)"
        );
        foreach ($userIds as $uid) {
            $stmt->execute(['rid' => $reservationId, 'uid' => $uid]);
        }
    }

    /**
     * Remover todos os participantes de uma reserva
     */
    public function clearParticipants(int $reservationId): bool
    {
        $stmt = $this->db->prepare("DELETE FROM reservation_participants WHERE reservation_id = :rid");
        return $stmt->execute(['rid' => $reservationId]);
    }

    /**
     * Listar participantes de uma reserva
     */
    public function getParticipants(int $reservationId): array
    {
        $stmt = $this->db->prepare(
            "SELECT u.id, u.name, u.email
             FROM reservation_participants rp
             JOIN users u ON rp.user_id = u.id
             WHERE rp.reservation_id = :rid
             ORDER BY u.name ASC"
        );
        $stmt->execute(['rid' => $reservationId]);
        return $stmt->fetchAll();
    }

    /**
     * Contar reuniões de hoje
     */
    public function countToday(): int
    {
        $stmt = $this->db->query(
            "SELECT COUNT(*) AS total FROM reservations
             WHERE DATE(start_datetime) = CURDATE()"
        );
        return (int) $stmt->fetch()['total'];
    }

    /**
     * Contar total de reservas
     */
    public function count(): int
    {
        $stmt = $this->db->query("SELECT COUNT(*) as total FROM reservations");
        return (int) $stmt->fetch()['total'];
    }

    /**
     * Salas disponíveis agora
     */
    public function getAvailableRoomsNow(): array
    {
        $stmt = $this->db->query(
            "SELECT r.* FROM rooms r
             WHERE r.id NOT IN (
                SELECT room_id FROM reservations
                WHERE start_datetime <= NOW() AND end_datetime >= NOW()
             )
             ORDER BY r.name ASC"
        );
        return $stmt->fetchAll();
    }
}
