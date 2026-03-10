<?php
/**
 * API: Enviar mensagem de teste via WhatsApp (Evolution API)
 */

session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../config/evolution.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Não autenticado']);
    exit;
}

$userModel = new User();
$user = $userModel->findById($_SESSION['user_id']);
if (!$user || $user['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Acesso negado']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$phone = $data['phone'] ?? '';

if (empty($phone)) {
    echo json_encode(['success' => false, 'message' => 'Informe o número de telefone']);
    exit;
}

try {
    $evo = new EvolutionAPI();
    $appName = Settings::val('app_name');
    $result = $evo->sendText($phone, "✅ *Teste de conexão*\n\nEsta é uma mensagem de teste do sistema *{$appName}*.\n\nSe você recebeu esta mensagem, a integração com WhatsApp está funcionando corretamente! 🎉");

    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Mensagem enviada com sucesso!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Falha ao enviar. Verifique as configurações da API.']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erro: ' . $e->getMessage()]);
}
