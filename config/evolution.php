<?php
/**
 * Helper: Evolution API (WhatsApp)
 *
 * Integração com Evolution API para envio de mensagens WhatsApp
 * Docs: https://doc.evolution-api.com
 */

require_once __DIR__ . '/../models/Settings.php';

class EvolutionAPI
{
    private string $baseUrl;
    private string $apiKey;
    private string $instance;
    private bool   $enabled;

    public function __construct()
    {
        $settings       = Settings::instance();
        $this->baseUrl  = rtrim($settings->get('evolution_api_url'), '/');
        $this->apiKey   = $settings->get('evolution_api_key');
        $this->instance = $settings->get('evolution_instance');
        $this->enabled  = $settings->get('whatsapp_enabled') === '1';
    }

    /**
     * Verificar se está habilitado e configurado
     */
    public function isConfigured(): bool
    {
        return $this->enabled
            && !empty($this->baseUrl)
            && !empty($this->apiKey)
            && !empty($this->instance);
    }

    /**
     * Nome da instância codificado para URL
     */
    private function encodedInstance(): string
    {
        return rawurlencode($this->instance);
    }

    /**
     * Enviar mensagem de texto
     */
    public function sendText(string $phone, string $message): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'message' => 'WhatsApp não configurado.'];
        }

        $phone = $this->formatPhone($phone);

        $payload = [
            'number' => $phone,
            'text'   => $message,
        ];

        return $this->request(
            "/message/sendText/" . $this->encodedInstance(),
            $payload
        );
    }

    /**
     * Verificar status da instância
     */
    public function getInstanceStatus(): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'message' => 'WhatsApp não configurado. Preencha URL, API Key e Instância.'];
        }

        // Tenta connectionState (funciona em v1 e v2 com URL encoding)
        $result = $this->request(
            "/instance/connectionState/" . $this->encodedInstance(),
            null,
            'GET'
        );

        if ($result['success']) {
            // v1: {instance: {instanceName, state}}
            // v2: pode variar
            $state = $result['data']['instance']['state']
                  ?? $result['data']['state']
                  ?? $result['data']['connectionStatus']
                  ?? 'unknown';
            $connected = in_array(strtolower($state), ['open', 'connected']);
            return [
                'success' => $connected,
                'message' => $connected
                    ? "✅ Conectado! WhatsApp online ({$state})"
                    : "⚠️ Instância encontrada, mas não conectada ({$state})",
                'data'    => $result['data'],
            ];
        }

        // Fallback: fetchInstances e procurar pelo nome
        $result2 = $this->request(
            "/instance/fetchInstances",
            null,
            'GET'
        );

        if ($result2['success']) {
            $instances = $result2['data'] ?? [];
            if (!is_array($instances)) {
                $instances = [$instances];
            }

            foreach ($instances as $inst) {
                // v2 retorna 'name' e 'connectionStatus' no nível raiz
                $name = $inst['name']
                    ?? $inst['instance']['instanceName']
                    ?? $inst['instanceName']
                    ?? '';

                if ($name === $this->instance) {
                    $state = $inst['connectionStatus']
                          ?? $inst['instance']['state']
                          ?? $inst['state']
                          ?? 'unknown';
                    $connected = in_array(strtolower($state), ['open', 'connected']);
                    return [
                        'success' => $connected,
                        'message' => $connected
                            ? "✅ Conectado! WhatsApp online ({$state})"
                            : "⚠️ Instância encontrada, mas não conectada ({$state})",
                        'data'    => $inst,
                    ];
                }
            }

            return [
                'success' => false,
                'message' => "Instância '{$this->instance}' não encontrada. Verifique o nome exato.",
            ];
        }

        return $result2;
    }

    /**
     * Obter QR Code para conexão
     */
    public function getQRCode(): array
    {
        if (empty($this->baseUrl) || empty($this->apiKey) || empty($this->instance)) {
            return ['success' => false, 'message' => 'Configuração incompleta.'];
        }

        return $this->request(
            "/instance/connect/" . $this->encodedInstance(),
            null,
            'GET'
        );
    }

    /**
     * Enviar notificação de reunião via WhatsApp
     */
    public function sendMeetingNotification(string $phone, array $meetingData): array
    {
        $appName = Settings::val('app_name');

        $time = '';
        if (!empty($meetingData['start_time']) && !empty($meetingData['end_time'])) {
            $time = $meetingData['start_time'] . ' - ' . $meetingData['end_time'];
        } elseif (!empty($meetingData['time'])) {
            $time = $meetingData['time'];
        }

        $message = "📅 *Nova Reunião Agendada*\n\n"
            . "📌 *Título:* {$meetingData['title']}\n"
            . "🏢 *Sala:* {$meetingData['room']}\n"
            . "📆 *Data:* {$meetingData['date']}\n"
            . "🕐 *Horário:* {$time}\n";

        if (!empty($meetingData['organizer'])) {
            $message .= "👤 *Organizador:* {$meetingData['organizer']}\n";
        }

        if (!empty($meetingData['participants'])) {
            $message .= "\n👥 *Participantes:*\n{$meetingData['participants']}\n";
        }

        $message .= "\n_Mensagem automática - {$appName}_";

        return $this->sendText($phone, $message);
    }

    /**
     * Formatar número de telefone para padrão internacional
     */
    private function formatPhone(string $phone): string
    {
        // Remover tudo que não é número
        $phone = preg_replace('/\D/', '', $phone);

        // Se começa com 0, remover
        $phone = ltrim($phone, '0');

        // Se não tem código do país, adicionar 55 (Brasil)
        if (strlen($phone) <= 11) {
            $phone = '55' . $phone;
        }

        return $phone;
    }

    /**
     * Fazer requisição à API
     */
    private function request(string $endpoint, ?array $data = null, string $method = 'POST'): array
    {
        $url = $this->baseUrl . $endpoint;

        // Para GET com dados, adicionar como query string
        if ($method === 'GET' && $data !== null) {
            $url .= '?' . http_build_query($data);
            $data = null;
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'apikey: ' . $this->apiKey,
            ],
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data !== null) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($error) {
            error_log("Evolution API cURL Error: " . $error);
            return ['success' => false, 'message' => 'Erro de conexão: ' . $error];
        }

        $decoded = json_decode($response, true);

        // Log para debug
        error_log("Evolution API [{$method}] {$url} -> HTTP {$httpCode} | Response: " . substr($response, 0, 500));

        if ($httpCode >= 200 && $httpCode < 300) {
            return ['success' => true, 'data' => $decoded];
        }

        // Extrair mensagem de erro detalhada
        $errorMsg = "Erro HTTP {$httpCode}";
        if (is_array($decoded)) {
            if (!empty($decoded['message'])) {
                $errorMsg = is_array($decoded['message'])
                    ? implode('; ', $decoded['message'])
                    : $decoded['message'];
            } elseif (!empty($decoded['error'])) {
                $errorMsg = $decoded['error'];
            } elseif (!empty($decoded['response']['message'])) {
                $errorMsg = $decoded['response']['message'];
            }
        }

        return [
            'success' => false,
            'message' => $errorMsg,
            'data'    => $decoded,
        ];
    }
}
