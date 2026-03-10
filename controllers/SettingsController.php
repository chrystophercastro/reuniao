<?php
/**
 * Controller: SettingsController
 * Gerencia configurações do sistema (somente admin)
 */

require_once __DIR__ . '/../models/Settings.php';
require_once __DIR__ . '/../controllers/AuthController.php';

class SettingsController
{
    private Settings $settings;

    public function __construct()
    {
        $this->settings = new Settings();
    }

    /**
     * Buscar todas as configurações
     */
    public function index(): array
    {
        return $this->settings->getAll();
    }

    /**
     * Salvar configurações visuais
     */
    public function saveVisual(array $data): array
    {
        if (!AuthController::isAdmin()) {
            return ['success' => false, 'message' => 'Acesso negado.'];
        }

        $keys = [
            'app_name', 'app_subtitle', 'app_footer',
            'color_primary', 'color_primary_dark', 'color_secondary',
            'color_background', 'color_sidebar',
            'login_bg_color1', 'login_bg_color2',
        ];

        $save = [];
        foreach ($keys as $key) {
            if (isset($data[$key])) {
                $save[$key] = trim($data[$key]);
            }
        }

        try {
            $this->settings->setMany($save);
            return ['success' => true, 'message' => 'Configurações visuais salvas com sucesso!'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erro ao salvar: ' . $e->getMessage()];
        }
    }

    /**
     * Salvar configurações de email
     */
    public function saveEmail(array $data): array
    {
        if (!AuthController::isAdmin()) {
            return ['success' => false, 'message' => 'Acesso negado.'];
        }

        $keys = [
            'mail_enabled', 'mail_host', 'mail_port', 'mail_username',
            'mail_password', 'mail_from_address', 'mail_from_name', 'mail_encryption',
        ];

        $save = [];
        foreach ($keys as $key) {
            if (isset($data[$key])) {
                $save[$key] = trim($data[$key]);
            }
        }

        // Checkbox
        $save['mail_enabled'] = isset($data['mail_enabled']) ? '1' : '0';

        try {
            $this->settings->setMany($save);
            return ['success' => true, 'message' => 'Configurações de email salvas com sucesso!'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erro ao salvar: ' . $e->getMessage()];
        }
    }

    /**
     * Salvar configurações do WhatsApp / Evolution API
     */
    public function saveWhatsApp(array $data): array
    {
        if (!AuthController::isAdmin()) {
            return ['success' => false, 'message' => 'Acesso negado.'];
        }

        $save = [
            'whatsapp_enabled'   => isset($data['whatsapp_enabled']) ? '1' : '0',
            'evolution_api_url'  => trim($data['evolution_api_url'] ?? ''),
            'evolution_api_key'  => trim($data['evolution_api_key'] ?? ''),
            'evolution_instance' => trim($data['evolution_instance'] ?? ''),
        ];

        try {
            $this->settings->setMany($save);
            return ['success' => true, 'message' => 'Configurações do WhatsApp salvas com sucesso!'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erro ao salvar: ' . $e->getMessage()];
        }
    }

    /**
     * Upload de logo
     */
    public function uploadLogo(array $file, string $type = 'app_logo'): array
    {
        if (!AuthController::isAdmin()) {
            return ['success' => false, 'message' => 'Acesso negado.'];
        }

        $allowed = ['image/png', 'image/jpeg', 'image/svg+xml', 'image/webp', 'image/x-icon', 'image/vnd.microsoft.icon'];

        if (!in_array($file['type'], $allowed)) {
            return ['success' => false, 'message' => 'Formato de imagem não suportado. Use PNG, JPG, SVG ou WEBP.'];
        }

        if ($file['size'] > 2 * 1024 * 1024) {
            return ['success' => false, 'message' => 'A imagem deve ter no máximo 2MB.'];
        }

        $uploadDir = __DIR__ . '/../assets/img/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = $type . '_' . time() . '.' . $ext;
        $filepath = $uploadDir . $filename;

        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            // Remover logo antiga
            $oldLogo = $this->settings->get($type);
            if ($oldLogo && file_exists(__DIR__ . '/../' . $oldLogo)) {
                @unlink(__DIR__ . '/../' . $oldLogo);
            }

            $relativePath = 'assets/img/' . $filename;
            $this->settings->set($type, $relativePath);

            return ['success' => true, 'message' => 'Imagem enviada com sucesso!', 'path' => $relativePath];
        }

        return ['success' => false, 'message' => 'Erro ao fazer upload da imagem.'];
    }

    /**
     * Remover logo
     */
    public function removeLogo(string $type = 'app_logo'): array
    {
        if (!AuthController::isAdmin()) {
            return ['success' => false, 'message' => 'Acesso negado.'];
        }

        $oldLogo = $this->settings->get($type);
        if ($oldLogo && file_exists(__DIR__ . '/../' . $oldLogo)) {
            @unlink(__DIR__ . '/../' . $oldLogo);
        }

        $this->settings->set($type, '');
        return ['success' => true, 'message' => 'Imagem removida com sucesso!'];
    }

    /**
     * Testar conexão SMTP
     */
    public function testEmail(string $to): array
    {
        if (!AuthController::isAdmin()) {
            return ['success' => false, 'message' => 'Acesso negado.'];
        }

        require_once __DIR__ . '/../config/mail.php';

        $subject = 'Teste - MeetingRoom Manager';
        $body    = '<div style="font-family:Arial,sans-serif;padding:20px;">
                        <h2>✅ Teste de Email</h2>
                        <p>Se você recebeu esta mensagem, a configuração de email está funcionando corretamente!</p>
                        <p style="color:#64748b;font-size:12px;">MeetingRoom Manager</p>
                    </div>';

        $result = sendMail($to, $subject, $body);

        if ($result) {
            return ['success' => true, 'message' => "Email de teste enviado para {$to}!"];
        }
        return ['success' => false, 'message' => 'Falha ao enviar email. Verifique as configurações SMTP.'];
    }

    /**
     * Testar conexão Evolution API
     */
    public function testWhatsApp(): array
    {
        if (!AuthController::isAdmin()) {
            return ['success' => false, 'message' => 'Acesso negado.'];
        }

        require_once __DIR__ . '/../config/evolution.php';

        try {
            $api    = new EvolutionAPI();
            $result = $api->getInstanceStatus();
            return $result;
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erro: ' . $e->getMessage()];
        }
    }
}
