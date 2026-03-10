<?php
/**
 * Model: Settings
 * Gerencia configurações do sistema armazenadas no banco
 */

require_once __DIR__ . '/../config/database.php';

class Settings
{
    private PDO $db;

    // Valores padrão de todas as configurações
    private static array $defaults = [
        // ---- Visual / Branding ----
        'app_name'            => 'MeetingRoom Manager',
        'app_subtitle'        => 'Sistema de Reserva de Salas de Reunião',
        'app_logo'            => '',
        'app_favicon'         => '',
        'app_footer'          => 'Desenvolvido por: Samos Informática LTDA',
        'color_primary'       => '#2563EB',
        'color_primary_dark'  => '#1D4ED8',
        'color_secondary'     => '#1E293B',
        'color_background'    => '#F1F5F9',
        'color_sidebar'       => '#1E293B',
        'login_bg_color1'     => '#2563EB',
        'login_bg_color2'     => '#1E293B',

        // ---- Email / SMTP ----
        'mail_enabled'        => '0',
        'mail_host'           => 'smtp.gmail.com',
        'mail_port'           => '587',
        'mail_username'       => '',
        'mail_password'       => '',
        'mail_from_address'   => '',
        'mail_from_name'      => 'MeetingRoom Manager',
        'mail_encryption'     => 'tls',

        // ---- WhatsApp / Evolution API ----
        'whatsapp_enabled'    => '0',
        'evolution_api_url'   => '',
        'evolution_api_key'   => '',
        'evolution_instance'  => '',
    ];

    public function __construct()
    {
        $this->db = getConnection();
        $this->ensureTable();
    }

    /**
     * Garante que a tabela settings existe
     */
    private function ensureTable(): void
    {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS settings (
                setting_key VARCHAR(100) PRIMARY KEY,
                setting_value TEXT,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    /**
     * Buscar uma configuração
     */
    public function get(string $key, ?string $default = null): string
    {
        $stmt = $this->db->prepare("SELECT setting_value FROM settings WHERE setting_key = :key");
        $stmt->execute(['key' => $key]);
        $row = $stmt->fetch();

        if ($row && $row['setting_value'] !== null) {
            return $row['setting_value'];
        }

        return $default ?? (self::$defaults[$key] ?? '');
    }

    /**
     * Salvar uma configuração
     */
    public function set(string $key, string $value): bool
    {
        $stmt = $this->db->prepare(
            "INSERT INTO settings (setting_key, setting_value) VALUES (:key, :value)
             ON DUPLICATE KEY UPDATE setting_value = :value2"
        );
        return $stmt->execute(['key' => $key, 'value' => $value, 'value2' => $value]);
    }

    /**
     * Salvar múltiplas configurações
     */
    public function setMany(array $data): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO settings (setting_key, setting_value) VALUES (:key, :value)
             ON DUPLICATE KEY UPDATE setting_value = :value2"
        );
        foreach ($data as $key => $value) {
            $stmt->execute(['key' => $key, 'value' => $value, 'value2' => $value]);
        }
    }

    /**
     * Buscar todas as configurações (com defaults)
     */
    public function getAll(): array
    {
        $settings = self::$defaults;

        $stmt = $this->db->query("SELECT setting_key, setting_value FROM settings");
        $rows = $stmt->fetchAll();

        foreach ($rows as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }

        return $settings;
    }

    /**
     * Buscar configurações por grupo (prefixo)
     */
    public function getGroup(string $prefix): array
    {
        $all    = $this->getAll();
        $result = [];
        foreach ($all as $key => $value) {
            if (str_starts_with($key, $prefix)) {
                $result[$key] = $value;
            }
        }
        return $result;
    }

    /**
     * Retornar valores padrão
     */
    public static function getDefaults(): array
    {
        return self::$defaults;
    }

    /**
     * Helper estático para usar em views sem instanciar toda hora
     */
    private static ?Settings $instance = null;

    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Helper estático: buscar valor
     */
    public static function val(string $key, ?string $default = null): string
    {
        return self::instance()->get($key, $default);
    }
}
