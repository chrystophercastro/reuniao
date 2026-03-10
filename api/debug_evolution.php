<?php
/**
 * Debug: Testa conexão direta com Evolution API
 * Acesse: http://localhost/reuniao/api/debug_evolution.php
 */

session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Settings.php';

// Verificar admin
if (!isset($_SESSION['user_id'])) {
    die('Não autenticado. Faça login primeiro.');
}
$userModel = new User();
$user = $userModel->findById($_SESSION['user_id']);
if (!$user || $user['role'] !== 'admin') {
    die('Acesso negado.');
}

$baseUrl  = rtrim(Settings::val('evolution_api_url'), '/');
$apiKey   = Settings::val('evolution_api_key');
$instance = Settings::val('evolution_instance');

echo "<h2>🔍 Debug Evolution API</h2>";
echo "<pre>";
echo "URL Base:   {$baseUrl}\n";
echo "API Key:    " . substr($apiKey, 0, 8) . "...\n";
echo "Instância:  {$instance}\n";
echo "Encoded:    " . rawurlencode($instance) . "\n";
echo "</pre><hr>";

// Teste 1: Listar todas as instâncias
echo "<h3>1. GET /instance/fetchInstances</h3>";
$url1 = $baseUrl . "/instance/fetchInstances";
$r1 = curlRequest($url1, $apiKey, 'GET');
echo "<pre>" . htmlspecialchars(json_encode($r1, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . "</pre>";

// Se retornou instâncias, mostrar os nomes
if (!empty($r1['body']) && is_array($r1['body'])) {
    echo "<h4>📋 Instâncias encontradas:</h4><ul>";
    foreach ($r1['body'] as $inst) {
        $name = $inst['instance']['instanceName'] ?? $inst['instanceName'] ?? '???';
        $state = $inst['instance']['state'] ?? $inst['state'] ?? '???';
        $match = ($name === $instance) ? ' ✅ MATCH' : '';
        echo "<li><strong>{$name}</strong> — estado: {$state}{$match}</li>";
    }
    echo "</ul>";
}

echo "<hr>";

// Teste 2: connectionState com nome codificado
echo "<h3>2. GET /instance/connectionState/" . htmlspecialchars(rawurlencode($instance)) . "</h3>";
$url2 = $baseUrl . "/instance/connectionState/" . rawurlencode($instance);
$r2 = curlRequest($url2, $apiKey, 'GET');
echo "<pre>" . htmlspecialchars(json_encode($r2, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . "</pre>";

echo "<hr>";

// Teste 3: connectionState sem codificar
echo "<h3>3. GET /instance/connectionState/" . htmlspecialchars($instance) . " (sem encode)</h3>";
$url3 = $baseUrl . "/instance/connectionState/" . $instance;
$r3 = curlRequest($url3, $apiKey, 'GET');
echo "<pre>" . htmlspecialchars(json_encode($r3, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . "</pre>";

function curlRequest(string $url, string $apiKey, string $method = 'GET'): array
{
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'apikey: ' . $apiKey,
        ],
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error    = curl_error($ch);
    curl_close($ch);

    return [
        'url'      => $url,
        'httpCode' => $httpCode,
        'error'    => $error,
        'body'     => json_decode($response, true) ?? $response,
    ];
}
