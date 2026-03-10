<?php
/**
 * Controller: ReservationController
 */

require_once __DIR__ . '/../models/Reservation.php';
require_once __DIR__ . '/../models/Room.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Settings.php';
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../config/mail.php';
require_once __DIR__ . '/../config/evolution.php';

class ReservationController
{
    private Reservation $reservationModel;
    private Room $roomModel;
    private User $userModel;

    public function __construct()
    {
        $this->reservationModel = new Reservation();
        $this->roomModel        = new Room();
        $this->userModel        = new User();
    }

    /**
     * Listar reservas (para o FullCalendar)
     */
    public function index(?int $roomId = null): array
    {
        $reservations = $this->reservationModel->getAll($roomId);
        $events = [];

        foreach ($reservations as $res) {
            $events[] = [
                'id'              => $res['id'],
                'title'           => $res['title'],
                'start'           => $res['start_datetime'],
                'end'             => $res['end_datetime'],
                'backgroundColor' => $res['room_color'],
                'borderColor'     => $res['room_color'],
                'extendedProps'   => [
                    'room_id'      => $res['room_id'],
                    'room_name'    => $res['room_name'],
                    'description'  => $res['description'],
                    'creator_name' => $res['creator_name'],
                    'created_by'   => $res['created_by'],
                ],
            ];
        }

        return $events;
    }

    /**
     * Buscar uma reserva com detalhes
     */
    public function show(int $id): ?array
    {
        $reservation = $this->reservationModel->findById($id);
        if ($reservation) {
            $reservation['participants'] = $this->reservationModel->getParticipants($id);
        }
        return $reservation;
    }

    /**
     * Criar reserva
     */
    public function store(array $data): array
    {
        $roomId       = (int)($data['room_id'] ?? 0);
        $title        = trim($data['title'] ?? '');
        $description  = trim($data['description'] ?? '');
        $start        = trim($data['start'] ?? '');
        $end          = trim($data['end'] ?? '');
        $participants = $data['participants'] ?? [];
        $createdBy    = $_SESSION['user_id'];

        // Validações
        if (empty($title)) {
            return ['success' => false, 'message' => 'O título da reunião é obrigatório.'];
        }
        if ($roomId < 1) {
            return ['success' => false, 'message' => 'Selecione uma sala.'];
        }
        if (empty($start) || empty($end)) {
            return ['success' => false, 'message' => 'Informe as datas de início e fim.'];
        }
        if (strtotime($end) <= strtotime($start)) {
            return ['success' => false, 'message' => 'A data de fim deve ser posterior à data de início.'];
        }

        // Verificar conflito
        if ($this->reservationModel->hasConflict($roomId, $start, $end)) {
            return ['success' => false, 'message' => 'Esta sala já possui reunião neste horário.'];
        }

        try {
            $id = $this->reservationModel->create($roomId, $title, $description, $start, $end, $createdBy);

            // Adicionar participantes
            if (!empty($participants)) {
                $participantIds = array_map('intval', $participants);
                $this->reservationModel->addParticipants($id, $participantIds);
            }
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Erro ao criar reserva: ' . $e->getMessage()];
        }

        // Enviar notificações (fora do try/catch da criação - nunca bloqueia)
        try {
            $this->sendNotification($id);
        } catch (\Throwable $e) {
            error_log("Erro ao enviar notificações da reserva #{$id}: " . $e->getMessage());
        }

        return ['success' => true, 'message' => 'Reunião agendada com sucesso!', 'id' => $id];
    }

    /**
     * Atualizar reserva
     */
    public function update(int $id, array $data): array
    {
        $reservation = $this->reservationModel->findById($id);

        if (!$reservation) {
            return ['success' => false, 'message' => 'Reserva não encontrada.'];
        }

        // Verificar permissão
        if ($reservation['created_by'] != $_SESSION['user_id'] && $_SESSION['user_role'] !== 'admin') {
            return ['success' => false, 'message' => 'Você não tem permissão para editar esta reserva.'];
        }

        $roomId       = (int)($data['room_id'] ?? $reservation['room_id']);
        $title        = trim($data['title'] ?? $reservation['title']);
        $description  = trim($data['description'] ?? $reservation['description']);
        $start        = trim($data['start'] ?? $reservation['start_datetime']);
        $end          = trim($data['end'] ?? $reservation['end_datetime']);
        $participants = $data['participants'] ?? [];

        // Verificar conflito (excluindo a própria reserva)
        if ($this->reservationModel->hasConflict($roomId, $start, $end, $id)) {
            return ['success' => false, 'message' => 'Esta sala já possui reunião neste horário.'];
        }

        try {
            $this->reservationModel->update($id, $roomId, $title, $description, $start, $end);

            // Atualizar participantes
            $this->reservationModel->clearParticipants($id);
            if (!empty($participants)) {
                $participantIds = array_map('intval', $participants);
                $this->reservationModel->addParticipants($id, $participantIds);
            }

            return ['success' => true, 'message' => 'Reserva atualizada com sucesso!'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erro ao atualizar reserva: ' . $e->getMessage()];
        }
    }

    /**
     * Atualizar datas via drag & drop
     */
    public function updateDates(int $id, string $start, string $end): array
    {
        $reservation = $this->reservationModel->findById($id);

        if (!$reservation) {
            return ['success' => false, 'message' => 'Reserva não encontrada.'];
        }

        if ($reservation['created_by'] != $_SESSION['user_id'] && $_SESSION['user_role'] !== 'admin') {
            return ['success' => false, 'message' => 'Sem permissão.'];
        }

        // Verificar conflito
        if ($this->reservationModel->hasConflict($reservation['room_id'], $start, $end, $id)) {
            return ['success' => false, 'message' => 'Conflito de horário ao mover a reunião.'];
        }

        try {
            $this->reservationModel->updateDates($id, $start, $end);
            return ['success' => true, 'message' => 'Reunião movida com sucesso!'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erro ao mover reunião.'];
        }
    }

    /**
     * Deletar reserva
     */
    public function destroy(int $id): array
    {
        $reservation = $this->reservationModel->findById($id);

        if (!$reservation) {
            return ['success' => false, 'message' => 'Reserva não encontrada.'];
        }

        // Somente criador ou admin pode excluir
        if ($reservation['created_by'] != $_SESSION['user_id'] && $_SESSION['user_role'] !== 'admin') {
            return ['success' => false, 'message' => 'Você não tem permissão para excluir esta reserva.'];
        }

        try {
            $this->reservationModel->delete($id);
            return ['success' => true, 'message' => 'Reserva excluída com sucesso!'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erro ao excluir reserva.'];
        }
    }

    /**
     * Enviar notificação por email e WhatsApp
     */
    private function sendNotification(int $reservationId): void
    {
        try {
            $reservation  = $this->reservationModel->findById($reservationId);
            $participants = $this->reservationModel->getParticipants($reservationId);
            $creator      = $this->userModel->findById($reservation['created_by']);

            $startDate = date('d/m/Y', strtotime($reservation['start_datetime']));
            $startTime = date('H:i', strtotime($reservation['start_datetime']));
            $endTime   = date('H:i', strtotime($reservation['end_datetime']));

            $participantNames = array_map(fn($p) => $p['name'], $participants);
            $participantList  = !empty($participantNames) ? implode(', ', $participantNames) : 'Nenhum';

            $appName      = Settings::val('app_name');
            $colorPrimary = Settings::val('color_primary');

            // ==========================================
            // NOTIFICAÇÃO POR EMAIL (só se habilitado)
            // ==========================================
            if (Settings::val('mail_enabled') === '1') {
                try {
                    $subject = "Nova reunião agendada: {$reservation['title']}";

                    $body = "
                        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                            <div style='background-color: {$colorPrimary}; color: white; padding: 20px; border-radius: 8px 8px 0 0;'>
                                <h2 style='margin:0;'>📅 Nova Reunião Agendada</h2>
                            </div>
                            <div style='background-color: #ffffff; padding: 20px; border: 1px solid #e2e8f0; border-radius: 0 0 8px 8px;'>
                                <p>Uma nova reunião foi marcada.</p>
                                <table style='width:100%; border-collapse: collapse;'>
                                    <tr><td style='padding:8px; font-weight:bold;'>Título:</td><td style='padding:8px;'>{$reservation['title']}</td></tr>
                                    <tr><td style='padding:8px; font-weight:bold;'>Sala:</td><td style='padding:8px;'>{$reservation['room_name']}</td></tr>
                                    <tr><td style='padding:8px; font-weight:bold;'>Data:</td><td style='padding:8px;'>{$startDate}</td></tr>
                                    <tr><td style='padding:8px; font-weight:bold;'>Horário:</td><td style='padding:8px;'>{$startTime} - {$endTime}</td></tr>
                                    <tr><td style='padding:8px; font-weight:bold;'>Organizador:</td><td style='padding:8px;'>{$creator['name']}</td></tr>
                                    <tr><td style='padding:8px; font-weight:bold;'>Participantes:</td><td style='padding:8px;'>{$participantList}</td></tr>
                                </table>
                                <p style='margin-top:15px; color:#64748b; font-size:12px;'>Este é um email automático do {$appName}.</p>
                            </div>
                        </div>
                    ";

                    sendMail($creator['email'], $subject, $body);

                    foreach ($participants as $participant) {
                        sendMail($participant['email'], $subject, $body);
                    }
                } catch (\Throwable $e) {
                    error_log("Erro ao enviar email de notificação: " . $e->getMessage());
                }
            }

            // ==========================================
            // NOTIFICAÇÃO VIA WHATSAPP (só se habilitado)
            // ==========================================
            if (Settings::val('whatsapp_enabled') === '1') {
                try {
                    $evo = new EvolutionAPI();

                    $meetingData = [
                        'title'        => $reservation['title'],
                        'room'         => $reservation['room_name'],
                        'date'         => $startDate,
                        'start_time'   => $startTime,
                        'end_time'     => $endTime,
                        'organizer'    => $creator['name'],
                        'participants' => $participantList,
                        'description'  => $reservation['description'] ?? '',
                    ];

                    // Enviar WhatsApp para o criador
                    if (!empty($creator['phone'])) {
                        $evo->sendMeetingNotification($creator['phone'], $meetingData);
                    }

                    // Enviar WhatsApp para os participantes
                    foreach ($participants as $participant) {
                        $participantUser = $this->userModel->findById($participant['user_id']);
                        if ($participantUser && !empty($participantUser['phone'])) {
                            $evo->sendMeetingNotification($participantUser['phone'], $meetingData);
                        }
                    }
                } catch (\Throwable $e) {
                    error_log("Erro ao enviar WhatsApp: " . $e->getMessage());
                }
            }
        } catch (\Throwable $e) {
            error_log("Erro ao enviar notificação: " . $e->getMessage());
        }
    }
}
