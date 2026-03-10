<?php
/**
 * View: Login
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../models/Settings.php';

// Se já está logado, redirecionar
if (AuthController::isLoggedIn()) {
    header('Location: /reuniao/views/dashboard.php');
    exit;
}

// Carregar configurações visuais
$appName     = Settings::val('app_name');
$appSubtitle = Settings::val('app_subtitle');
$appFooter   = Settings::val('app_footer');
$appLogo     = Settings::val('app_logo');
$appFavicon  = Settings::val('app_favicon');
$colorPrimary     = Settings::val('color_primary');
$colorPrimaryDark = Settings::val('color_primary_dark');
$loginBg1    = Settings::val('login_bg_color1');
$loginBg2    = Settings::val('login_bg_color2');

$error = '';

// Processar formulário de login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $auth   = new AuthController();
    $result = $auth->login($_POST['email'] ?? '', $_POST['password'] ?? '');

    if ($result['success']) {
        header('Location: /reuniao/views/dashboard.php');
        exit;
    } else {
        $error = $result['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?= htmlspecialchars($appName) ?></title>
    <?php if (!empty($appFavicon)): ?>
        <link rel="icon" href="/reuniao/<?= htmlspecialchars($appFavicon) ?>">
    <?php endif; ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="/reuniao/assets/css/style.css" rel="stylesheet">
    <style>
        .login-page {
            background: linear-gradient(135deg, <?= htmlspecialchars($loginBg1) ?> 0%, <?= htmlspecialchars($loginBg2) ?> 100%) !important;
        }
        .login-icon {
            background: linear-gradient(135deg, <?= htmlspecialchars($colorPrimary) ?>, <?= htmlspecialchars($colorPrimaryDark) ?>) !important;
        }
        .login-card .btn-primary {
            background: linear-gradient(135deg, <?= htmlspecialchars($colorPrimary) ?>, <?= htmlspecialchars($colorPrimaryDark) ?>) !important;
            border: none !important;
        }
        .login-card .btn-primary:hover {
            background: linear-gradient(135deg, <?= htmlspecialchars($colorPrimaryDark) ?>, <?= htmlspecialchars($colorPrimary) ?>) !important;
        }
    </style>
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <?php if (!empty($appLogo)): ?>
                    <img src="/reuniao/<?= htmlspecialchars($appLogo) ?>" alt="Logo"
                         style="max-height: 70px; margin-bottom: 15px;">
                <?php else: ?>
                    <div class="login-icon">
                        <i class="bi bi-calendar-event"></i>
                    </div>
                <?php endif; ?>
                <h1><?= htmlspecialchars($appName) ?></h1>
                <p><?= htmlspecialchars($appSubtitle) ?></p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form method="POST" action="" id="loginForm">
                <div class="mb-3">
                    <label for="email" class="form-label">
                        <i class="bi bi-envelope me-1"></i> Email
                    </label>
                    <input type="email" class="form-control form-control-lg" id="email" name="email"
                           placeholder="seu@email.com" required autofocus
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>

                <div class="mb-4">
                    <label for="password" class="form-label">
                        <i class="bi bi-lock me-1"></i> Senha
                    </label>
                    <div class="input-group">
                        <input type="password" class="form-control form-control-lg" id="password" name="password"
                               placeholder="Sua senha" required>
                        <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-lg w-100">
                    <i class="bi bi-box-arrow-in-right me-2"></i> Entrar
                </button>
            </form>

            <div class="login-footer">
                <p class="text-muted small mb-0">
                    <?= htmlspecialchars($appFooter) ?>
                </p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function () {
            const pwd = document.getElementById('password');
            const icon = this.querySelector('i');
            if (pwd.type === 'password') {
                pwd.type = 'text';
                icon.classList.replace('bi-eye', 'bi-eye-slash');
            } else {
                pwd.type = 'password';
                icon.classList.replace('bi-eye-slash', 'bi-eye');
            }
        });
    </script>
</body>
</html>
