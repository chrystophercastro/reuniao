<?php
/**
 * View: Configurações do Sistema (Admin)
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../controllers/SettingsController.php';

AuthController::requireAdmin();

$controller = new SettingsController();

// Processar formulários
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'save_visual':
            $result = $controller->saveVisual($_POST);
            break;
        case 'save_email':
            $result = $controller->saveEmail($_POST);
            break;
        case 'save_whatsapp':
            $result = $controller->saveWhatsApp($_POST);
            break;
        case 'upload_logo':
            if (isset($_FILES['logo_file']) && $_FILES['logo_file']['error'] === UPLOAD_ERR_OK) {
                $result = $controller->uploadLogo($_FILES['logo_file'], 'app_logo');
            } else {
                $result = ['success' => false, 'message' => 'Selecione uma imagem.'];
            }
            break;
        case 'upload_favicon':
            if (isset($_FILES['favicon_file']) && $_FILES['favicon_file']['error'] === UPLOAD_ERR_OK) {
                $result = $controller->uploadLogo($_FILES['favicon_file'], 'app_favicon');
            } else {
                $result = ['success' => false, 'message' => 'Selecione uma imagem.'];
            }
            break;
        case 'remove_logo':
            $result = $controller->removeLogo('app_logo');
            break;
        case 'remove_favicon':
            $result = $controller->removeLogo('app_favicon');
            break;
        case 'test_email':
            $result = $controller->testEmail($_POST['test_email_to'] ?? $currentUser['email']);
            break;
        case 'test_whatsapp':
            $result = $controller->testWhatsApp();
            break;
    }

    if (isset($result)) {
        $_SESSION['flash_message'] = $result['message'] ?? ($result['success'] ? 'Salvo!' : 'Erro');
        $_SESSION['flash_type']    = $result['success'] ? 'success' : 'danger';
        $activeTab = match ($action) {
            'save_email', 'test_email' => 'email',
            'save_whatsapp', 'test_whatsapp' => 'whatsapp',
            default => 'visual',
        };
        $_SESSION['settings_tab'] = $activeTab;
        header('Location: /reuniao/views/settings.php');
        exit;
    }
}

$cfg = $controller->index();

// Flash
$flashMessage = $_SESSION['flash_message'] ?? null;
$flashType    = $_SESSION['flash_type'] ?? 'info';
$activeTab    = $_SESSION['settings_tab'] ?? 'visual';
unset($_SESSION['flash_message'], $_SESSION['flash_type'], $_SESSION['settings_tab']);

require_once __DIR__ . '/../includes/header.php';
?>

<!-- Page Title -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold text-dark mb-1">
            <i class="bi bi-gear me-2"></i>Configurações
        </h2>
        <p class="text-muted mb-0">Personalize o sistema, configure email e WhatsApp</p>
    </div>
</div>

<?php if ($flashMessage): ?>
    <div class="alert alert-<?= $flashType ?> alert-dismissible fade show" role="alert">
        <i class="bi bi-<?= $flashType === 'success' ? 'check-circle' : 'exclamation-triangle' ?> me-2"></i>
        <?= htmlspecialchars($flashMessage) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Tabs -->
<ul class="nav nav-tabs mb-4" id="settingsTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link <?= $activeTab === 'visual' ? 'active' : '' ?>" id="visual-tab"
                data-bs-toggle="tab" data-bs-target="#visual" type="button">
            <i class="bi bi-palette me-1"></i> Aparência
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link <?= $activeTab === 'email' ? 'active' : '' ?>" id="email-tab"
                data-bs-toggle="tab" data-bs-target="#email" type="button">
            <i class="bi bi-envelope me-1"></i> Email / SMTP
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link <?= $activeTab === 'whatsapp' ? 'active' : '' ?>" id="whatsapp-tab"
                data-bs-toggle="tab" data-bs-target="#whatsapp" type="button">
            <i class="bi bi-whatsapp me-1"></i> WhatsApp
        </button>
    </li>
</ul>

<div class="tab-content" id="settingsTabContent">

    <!-- ================================================================ -->
    <!-- TAB: Aparência -->
    <!-- ================================================================ -->
    <div class="tab-pane fade <?= $activeTab === 'visual' ? 'show active' : '' ?>" id="visual">
        <div class="row g-4">
            <!-- Branding -->
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0 py-3">
                        <h5 class="mb-0 fw-bold"><i class="bi bi-type me-2 text-primary"></i>Identidade</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="save_visual">

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Nome do Sistema</label>
                                <input type="text" class="form-control" name="app_name"
                                       value="<?= htmlspecialchars($cfg['app_name']) ?>">
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Subtítulo</label>
                                <input type="text" class="form-control" name="app_subtitle"
                                       value="<?= htmlspecialchars($cfg['app_subtitle']) ?>">
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Rodapé</label>
                                <input type="text" class="form-control" name="app_footer"
                                       value="<?= htmlspecialchars($cfg['app_footer']) ?>">
                            </div>

                            <hr>
                            <h6 class="fw-bold mb-3"><i class="bi bi-palette2 me-1"></i> Cores do Sistema</h6>

                            <div class="row g-3 mb-3">
                                <div class="col-6">
                                    <label class="form-label small fw-semibold">Cor Primária</label>
                                    <div class="input-group">
                                        <input type="color" class="form-control form-control-color" name="color_primary"
                                               value="<?= htmlspecialchars($cfg['color_primary']) ?>" id="colorPrimary">
                                        <input type="text" class="form-control" value="<?= htmlspecialchars($cfg['color_primary']) ?>"
                                               id="colorPrimaryText" maxlength="7" style="max-width:100px;">
                                    </div>
                                </div>
                                <div class="col-6">
                                    <label class="form-label small fw-semibold">Cor Primária Escura</label>
                                    <div class="input-group">
                                        <input type="color" class="form-control form-control-color" name="color_primary_dark"
                                               value="<?= htmlspecialchars($cfg['color_primary_dark']) ?>" id="colorPrimaryDark">
                                        <input type="text" class="form-control" value="<?= htmlspecialchars($cfg['color_primary_dark']) ?>"
                                               id="colorPrimaryDarkText" maxlength="7" style="max-width:100px;">
                                    </div>
                                </div>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-6">
                                    <label class="form-label small fw-semibold">Cor Secundária / Sidebar</label>
                                    <div class="input-group">
                                        <input type="color" class="form-control form-control-color" name="color_secondary"
                                               value="<?= htmlspecialchars($cfg['color_secondary']) ?>">
                                        <input type="text" class="form-control" value="<?= htmlspecialchars($cfg['color_secondary']) ?>"
                                               maxlength="7" style="max-width:100px;">
                                    </div>
                                </div>
                                <div class="col-6">
                                    <label class="form-label small fw-semibold">Background</label>
                                    <div class="input-group">
                                        <input type="color" class="form-control form-control-color" name="color_background"
                                               value="<?= htmlspecialchars($cfg['color_background']) ?>">
                                        <input type="text" class="form-control" value="<?= htmlspecialchars($cfg['color_background']) ?>"
                                               maxlength="7" style="max-width:100px;">
                                    </div>
                                </div>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-6">
                                    <label class="form-label small fw-semibold">Sidebar</label>
                                    <input type="color" class="form-control form-control-color w-100" name="color_sidebar"
                                           value="<?= htmlspecialchars($cfg['color_sidebar']) ?>">
                                </div>
                            </div>

                            <hr>
                            <h6 class="fw-bold mb-3"><i class="bi bi-box-arrow-in-right me-1"></i> Tela de Login</h6>

                            <div class="row g-3 mb-3">
                                <div class="col-6">
                                    <label class="form-label small fw-semibold">Gradiente - Cor 1</label>
                                    <input type="color" class="form-control form-control-color w-100" name="login_bg_color1"
                                           value="<?= htmlspecialchars($cfg['login_bg_color1']) ?>">
                                </div>
                                <div class="col-6">
                                    <label class="form-label small fw-semibold">Gradiente - Cor 2</label>
                                    <input type="color" class="form-control form-control-color w-100" name="login_bg_color2"
                                           value="<?= htmlspecialchars($cfg['login_bg_color2']) ?>">
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary w-100 mt-2">
                                <i class="bi bi-check-lg me-1"></i> Salvar Aparência
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Logo & Favicon -->
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-0 py-3">
                        <h5 class="mb-0 fw-bold"><i class="bi bi-image me-2 text-primary"></i>Logo</h5>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-3">
                            <?php if (!empty($cfg['app_logo'])): ?>
                                <img src="/reuniao/<?= htmlspecialchars($cfg['app_logo']) ?>" alt="Logo"
                                     class="img-fluid mb-2" style="max-height: 80px;">
                                <br>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="action" value="remove_logo">
                                    <button type="submit" class="btn btn-sm btn-outline-danger mt-2">
                                        <i class="bi bi-trash me-1"></i> Remover
                                    </button>
                                </form>
                            <?php else: ?>
                                <div class="p-4 bg-light rounded-3 mb-2">
                                    <i class="bi bi-image display-4 text-muted"></i>
                                    <p class="text-muted small mt-2 mb-0">Nenhuma logo definida</p>
                                </div>
                            <?php endif; ?>
                        </div>
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="upload_logo">
                            <div class="input-group">
                                <input type="file" class="form-control" name="logo_file" accept="image/*">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-upload"></i>
                                </button>
                            </div>
                            <div class="form-text">PNG, JPG, SVG ou WEBP. Máximo 2MB. Tamanho sugerido: 200x50px</div>
                        </form>
                    </div>
                </div>

                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-0 py-3">
                        <h5 class="mb-0 fw-bold"><i class="bi bi-globe me-2 text-primary"></i>Favicon</h5>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-3">
                            <?php if (!empty($cfg['app_favicon'])): ?>
                                <img src="/reuniao/<?= htmlspecialchars($cfg['app_favicon']) ?>" alt="Favicon"
                                     class="img-fluid mb-2" style="max-height: 48px;">
                                <br>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="action" value="remove_favicon">
                                    <button type="submit" class="btn btn-sm btn-outline-danger mt-2">
                                        <i class="bi bi-trash me-1"></i> Remover
                                    </button>
                                </form>
                            <?php else: ?>
                                <div class="p-3 bg-light rounded-3 mb-2">
                                    <i class="bi bi-globe display-5 text-muted"></i>
                                    <p class="text-muted small mt-2 mb-0">Nenhum favicon definido</p>
                                </div>
                            <?php endif; ?>
                        </div>
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="upload_favicon">
                            <div class="input-group">
                                <input type="file" class="form-control" name="favicon_file" accept="image/*">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-upload"></i>
                                </button>
                            </div>
                            <div class="form-text">ICO, PNG ou SVG. Tamanho: 32x32 ou 64x64px</div>
                        </form>
                    </div>
                </div>

                <!-- Preview -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0 py-3">
                        <h5 class="mb-0 fw-bold"><i class="bi bi-eye me-2 text-primary"></i>Pré-visualização</h5>
                    </div>
                    <div class="card-body p-0">
                        <div id="themePreview" class="rounded-bottom" style="overflow:hidden;">
                            <div class="p-3 text-white text-center" id="previewNavbar"
                                 style="background: linear-gradient(135deg, <?= htmlspecialchars($cfg['color_primary']) ?>, <?= htmlspecialchars($cfg['color_primary_dark']) ?>);">
                                <strong><?= htmlspecialchars($cfg['app_name']) ?></strong>
                            </div>
                            <div class="d-flex" style="height: 80px;">
                                <div class="p-2" id="previewSidebar"
                                     style="width:60px; background-color: <?= htmlspecialchars($cfg['color_sidebar']) ?>;">
                                    <div class="bg-white bg-opacity-25 rounded mb-1" style="height:10px;"></div>
                                    <div class="bg-white bg-opacity-25 rounded mb-1" style="height:10px;"></div>
                                    <div class="bg-white bg-opacity-25 rounded" style="height:10px;"></div>
                                </div>
                                <div class="flex-grow-1 p-2" id="previewBg"
                                     style="background-color: <?= htmlspecialchars($cfg['color_background']) ?>;">
                                    <div class="bg-white rounded p-2 shadow-sm" style="height:100%;">
                                        <div class="bg-light rounded" style="height:10px; width:60%;"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ================================================================ -->
    <!-- TAB: Email / SMTP -->
    <!-- ================================================================ -->
    <div class="tab-pane fade <?= $activeTab === 'email' ? 'show active' : '' ?>" id="email">
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0 py-3">
                        <h5 class="mb-0 fw-bold"><i class="bi bi-envelope me-2 text-primary"></i>Configurações SMTP</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="save_email">

                            <div class="form-check form-switch mb-4">
                                <input class="form-check-input" type="checkbox" id="mailEnabled" name="mail_enabled"
                                       value="1" <?= $cfg['mail_enabled'] === '1' ? 'checked' : '' ?>>
                                <label class="form-check-label fw-semibold" for="mailEnabled">
                                    Habilitar envio de email
                                </label>
                            </div>

                            <div class="row g-3">
                                <div class="col-8">
                                    <label class="form-label fw-semibold">Servidor SMTP</label>
                                    <input type="text" class="form-control" name="mail_host"
                                           value="<?= htmlspecialchars($cfg['mail_host']) ?>"
                                           placeholder="smtp.gmail.com">
                                </div>
                                <div class="col-4">
                                    <label class="form-label fw-semibold">Porta</label>
                                    <input type="number" class="form-control" name="mail_port"
                                           value="<?= htmlspecialchars($cfg['mail_port']) ?>">
                                </div>
                                <div class="col-6">
                                    <label class="form-label fw-semibold">Usuário / Email</label>
                                    <input type="text" class="form-control" name="mail_username"
                                           value="<?= htmlspecialchars($cfg['mail_username']) ?>"
                                           placeholder="seu-email@gmail.com">
                                </div>
                                <div class="col-6">
                                    <label class="form-label fw-semibold">Senha / App Password</label>
                                    <input type="password" class="form-control" name="mail_password"
                                           value="<?= htmlspecialchars($cfg['mail_password']) ?>"
                                           placeholder="Senha do SMTP">
                                </div>
                                <div class="col-6">
                                    <label class="form-label fw-semibold">Email Remetente</label>
                                    <input type="email" class="form-control" name="mail_from_address"
                                           value="<?= htmlspecialchars($cfg['mail_from_address']) ?>"
                                           placeholder="noreply@empresa.com">
                                </div>
                                <div class="col-6">
                                    <label class="form-label fw-semibold">Nome Remetente</label>
                                    <input type="text" class="form-control" name="mail_from_name"
                                           value="<?= htmlspecialchars($cfg['mail_from_name']) ?>">
                                </div>
                                <div class="col-6">
                                    <label class="form-label fw-semibold">Criptografia</label>
                                    <select class="form-select" name="mail_encryption">
                                        <option value="tls" <?= $cfg['mail_encryption'] === 'tls' ? 'selected' : '' ?>>TLS</option>
                                        <option value="ssl" <?= $cfg['mail_encryption'] === 'ssl' ? 'selected' : '' ?>>SSL</option>
                                        <option value="" <?= empty($cfg['mail_encryption']) ? 'selected' : '' ?>>Nenhuma</option>
                                    </select>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary mt-4">
                                <i class="bi bi-check-lg me-1"></i> Salvar Configurações
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Testar Email -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0 py-3">
                        <h5 class="mb-0 fw-bold"><i class="bi bi-send me-2 text-success"></i>Testar Email</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="test_email">
                            <p class="text-muted small">Envie um email de teste para verificar se as configurações estão corretas.</p>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Enviar para</label>
                                <input type="email" class="form-control" name="test_email_to"
                                       value="<?= htmlspecialchars($currentUser['email']) ?>" required>
                            </div>
                            <button type="submit" class="btn btn-success w-100">
                                <i class="bi bi-send me-1"></i> Enviar Teste
                            </button>
                        </form>
                    </div>
                </div>

                <div class="card border-0 shadow-sm mt-4">
                    <div class="card-body">
                        <h6 class="fw-bold"><i class="bi bi-info-circle me-1 text-primary"></i> Dicas para Gmail</h6>
                        <ul class="small text-muted mb-0">
                            <li>Host: <code>smtp.gmail.com</code></li>
                            <li>Porta: <code>587</code> (TLS) ou <code>465</code> (SSL)</li>
                            <li>Ative a <strong>Verificação em 2 etapas</strong></li>
                            <li>Gere uma <strong>Senha de App</strong> em <a href="https://myaccount.google.com/apppasswords" target="_blank">myaccount.google.com</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ================================================================ -->
    <!-- TAB: WhatsApp / Evolution API -->
    <!-- ================================================================ -->
    <div class="tab-pane fade <?= $activeTab === 'whatsapp' ? 'show active' : '' ?>" id="whatsapp">
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0 py-3">
                        <h5 class="mb-0 fw-bold"><i class="bi bi-whatsapp me-2" style="color:#25D366;"></i>Evolution API</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="save_whatsapp">

                            <div class="form-check form-switch mb-4">
                                <input class="form-check-input" type="checkbox" id="whatsappEnabled" name="whatsapp_enabled"
                                       value="1" <?= $cfg['whatsapp_enabled'] === '1' ? 'checked' : '' ?>>
                                <label class="form-check-label fw-semibold" for="whatsappEnabled">
                                    Habilitar notificações via WhatsApp
                                </label>
                            </div>

                            <div class="alert alert-info small">
                                <i class="bi bi-info-circle me-1"></i>
                                Integração com <a href="https://doc.evolution-api.com" target="_blank"><strong>Evolution API</strong></a>
                                para envio de mensagens WhatsApp. Você precisa ter uma instância da Evolution API rodando.
                            </div>

                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label fw-semibold">URL da API</label>
                                    <input type="url" class="form-control" name="evolution_api_url"
                                           value="<?= htmlspecialchars($cfg['evolution_api_url']) ?>"
                                           placeholder="https://sua-api.com">
                                    <div class="form-text">URL base da sua instância Evolution API (sem barra no final)</div>
                                </div>
                                <div class="col-6">
                                    <label class="form-label fw-semibold">API Key (Global)</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" name="evolution_api_key" id="evoApiKey"
                                               value="<?= htmlspecialchars($cfg['evolution_api_key']) ?>"
                                               placeholder="Sua API Key">
                                        <button class="btn btn-outline-secondary" type="button" onclick="toggleField('evoApiKey')">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <label class="form-label fw-semibold">Nome da Instância</label>
                                    <input type="text" class="form-control" name="evolution_instance"
                                           value="<?= htmlspecialchars($cfg['evolution_instance']) ?>"
                                           placeholder="meetingroom">
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary mt-4">
                                <i class="bi bi-check-lg me-1"></i> Salvar Configurações
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Status & Teste -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0 py-3">
                        <h5 class="mb-0 fw-bold"><i class="bi bi-wifi me-2 text-success"></i>Status da Conexão</h5>
                    </div>
                    <div class="card-body text-center">
                        <div id="whatsappStatus">
                            <i class="bi bi-question-circle display-4 text-muted"></i>
                            <p class="text-muted mt-2">Clique para verificar</p>
                        </div>
                        <form method="POST" class="mt-3">
                            <input type="hidden" name="action" value="test_whatsapp">
                            <button type="submit" class="btn btn-success w-100">
                                <i class="bi bi-arrow-repeat me-1"></i> Verificar Conexão
                            </button>
                        </form>
                    </div>
                </div>

                <div class="card border-0 shadow-sm mt-4">
                    <div class="card-header bg-white border-0 py-3">
                        <h5 class="mb-0 fw-bold"><i class="bi bi-send me-2" style="color:#25D366;"></i>Teste de Envio</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small">Envie uma mensagem de teste via WhatsApp.</p>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Número (com DDD)</label>
                            <input type="text" class="form-control" id="testWhatsappPhone"
                                   placeholder="11999999999">
                        </div>
                        <button type="button" class="btn btn-success w-100" onclick="sendTestWhatsApp()">
                            <i class="bi bi-whatsapp me-1"></i> Enviar Teste
                        </button>
                        <div id="whatsappTestResult" class="mt-2"></div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm mt-4">
                    <div class="card-body">
                        <h6 class="fw-bold"><i class="bi bi-info-circle me-1 text-primary"></i> Como configurar</h6>
                        <ol class="small text-muted mb-0">
                            <li>Instale a <a href="https://github.com/EvolutionAPI/evolution-api" target="_blank">Evolution API</a></li>
                            <li>Crie uma instância</li>
                            <li>Copie a API Key e o nome da instância</li>
                            <li>Conecte seu WhatsApp via QR Code</li>
                            <li>Cole os dados aqui e ative</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleField(id) {
    const el = document.getElementById(id);
    el.type = el.type === 'password' ? 'text' : 'password';
}

function sendTestWhatsApp() {
    const phone = document.getElementById('testWhatsappPhone').value.trim();
    const resultDiv = document.getElementById('whatsappTestResult');

    if (!phone) {
        resultDiv.innerHTML = '<div class="alert alert-warning small py-2">Informe o número.</div>';
        return;
    }

    resultDiv.innerHTML = '<div class="spinner-border spinner-border-sm text-success"></div> Enviando...';

    fetch('/reuniao/api/test_whatsapp.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ phone: phone })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            resultDiv.innerHTML = '<div class="alert alert-success small py-2"><i class="bi bi-check-circle me-1"></i> Mensagem enviada!</div>';
        } else {
            resultDiv.innerHTML = '<div class="alert alert-danger small py-2"><i class="bi bi-x-circle me-1"></i> ' + (data.message || 'Erro') + '</div>';
        }
    })
    .catch(() => {
        resultDiv.innerHTML = '<div class="alert alert-danger small py-2">Erro de conexão.</div>';
    });
}

// Sincronizar color pickers com text inputs
document.querySelectorAll('input[type="color"]').forEach(function(picker) {
    const textInput = picker.parentElement.querySelector('input[type="text"]');
    if (textInput) {
        picker.addEventListener('input', function() { textInput.value = this.value; });
        textInput.addEventListener('input', function() {
            if (/^#[0-9A-Fa-f]{6}$/.test(this.value)) picker.value = this.value;
        });
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
