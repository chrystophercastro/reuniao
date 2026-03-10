<?php
/**
 * MeetingRoom Manager - Setup / Instalação via Web
 *
 * Acesse: http://localhost/reuniao/setup.php
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$step    = $_POST['step'] ?? ($_GET['step'] ?? 'form');
$message = '';
$errors  = [];
$success = false;

// =============================================
// STEP 2: Processar instalação
// =============================================
if ($step === 'install' && $_SERVER['REQUEST_METHOD'] === 'POST') {

    $host   = trim($_POST['db_host'] ?? 'localhost');
    $user   = trim($_POST['db_user'] ?? 'root');
    $pass   = $_POST['db_pass'] ?? '';
    $dbname = trim($_POST['db_name'] ?? 'reuniao');
    $port   = (int)($_POST['db_port'] ?? 3306);

    // Validação
    if (empty($host)) $errors[] = 'Host é obrigatório.';
    if (empty($user)) $errors[] = 'Usuário é obrigatório.';
    if (empty($dbname)) $errors[] = 'Nome do banco é obrigatório.';

    if (empty($errors)) {
        try {
            // 1) Conectar sem banco para criá-lo
            $dsn = "mysql:host={$host};port={$port};charset=utf8mb4";
            $pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);

            // 2) Criar banco
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbname}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `{$dbname}`");

            // 3) Criar tabelas
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS users (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(150) NOT NULL,
                    email VARCHAR(200) NOT NULL UNIQUE,
                    phone VARCHAR(20) DEFAULT '',
                    password VARCHAR(255) NOT NULL,
                    role ENUM('admin', 'user') NOT NULL DEFAULT 'user',
                    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");

            $pdo->exec("
                CREATE TABLE IF NOT EXISTS rooms (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(150) NOT NULL,
                    description TEXT,
                    capacity INT NOT NULL DEFAULT 1,
                    color VARCHAR(7) NOT NULL DEFAULT '#2563EB',
                    created_by INT NOT NULL,
                    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");

            $pdo->exec("
                CREATE TABLE IF NOT EXISTS reservations (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    room_id INT NOT NULL,
                    title VARCHAR(255) NOT NULL,
                    description TEXT,
                    start_datetime DATETIME NOT NULL,
                    end_datetime DATETIME NOT NULL,
                    created_by INT NOT NULL,
                    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
                    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
                    INDEX idx_room_datetime (room_id, start_datetime, end_datetime)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");

            $pdo->exec("
                CREATE TABLE IF NOT EXISTS reservation_participants (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    reservation_id INT NOT NULL,
                    user_id INT NOT NULL,
                    FOREIGN KEY (reservation_id) REFERENCES reservations(id) ON DELETE CASCADE,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                    UNIQUE KEY unique_participant (reservation_id, user_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");

            // Tabela de configurações (chave-valor)
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS settings (
                    `key` VARCHAR(100) PRIMARY KEY,
                    `value` TEXT,
                    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");

            // 4) Criar usuário admin padrão (se não existir)
            $adminEmail = trim($_POST['admin_email'] ?? 'admin@meetingroom.com');
            $adminName  = trim($_POST['admin_name'] ?? 'Administrador');
            $adminPass  = $_POST['admin_pass'] ?? 'admin123';

            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
            $stmt->execute(['email' => $adminEmail]);

            if (!$stmt->fetch()) {
                $hash = password_hash($adminPass, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare(
                    "INSERT INTO users (name, email, password, role) VALUES (:name, :email, :password, 'admin')"
                );
                $stmt->execute([
                    'name'     => $adminName,
                    'email'    => $adminEmail,
                    'password' => $hash,
                ]);
            }

            // 5) Atualizar config/database.php
            $configContent = "<?php\n";
            $configContent .= "/**\n * MeetingRoom Manager - Database Configuration\n */\n\n";
            $configContent .= "define('DB_HOST', " . var_export($host, true) . ");\n";
            $configContent .= "define('DB_NAME', " . var_export($dbname, true) . ");\n";
            $configContent .= "define('DB_USER', " . var_export($user, true) . ");\n";
            $configContent .= "define('DB_PASS', " . var_export($pass, true) . ");\n";
            $configContent .= "define('DB_PORT', " . var_export($port, true) . ");\n";
            $configContent .= "define('DB_CHARSET', 'utf8mb4');\n\n";
            $configContent .= "function getConnection(): PDO\n{\n";
            $configContent .= "    static \$pdo = null;\n\n";
            $configContent .= "    if (\$pdo === null) {\n";
            $configContent .= "        \$dsn = \"mysql:host=\" . DB_HOST . \";port=\" . DB_PORT . \";dbname=\" . DB_NAME . \";charset=\" . DB_CHARSET;\n\n";
            $configContent .= "        \$options = [\n";
            $configContent .= "            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,\n";
            $configContent .= "            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,\n";
            $configContent .= "            PDO::ATTR_EMULATE_PREPARES   => false,\n";
            $configContent .= "        ];\n\n";
            $configContent .= "        try {\n";
            $configContent .= "            \$pdo = new PDO(\$dsn, DB_USER, DB_PASS, \$options);\n";
            $configContent .= "        } catch (PDOException \$e) {\n";
            $configContent .= "            die(\"Erro de conexão com o banco de dados: \" . \$e->getMessage());\n";
            $configContent .= "        }\n";
            $configContent .= "    }\n\n";
            $configContent .= "    return \$pdo;\n";
            $configContent .= "}\n";

            file_put_contents(__DIR__ . '/config/database.php', $configContent);

            $success = true;
            $message = 'Instalação concluída com sucesso!';

        } catch (PDOException $e) {
            $errors[] = 'Erro de conexão: ' . $e->getMessage();
        } catch (Exception $e) {
            $errors[] = 'Erro: ' . $e->getMessage();
        }
    }

    $step = 'result';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup - MeetingRoom Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root { --primary: #2563EB; --secondary: #1E293B; }
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            font-family: 'Segoe UI', system-ui, sans-serif;
        }
        .setup-card {
            background: #fff;
            border-radius: 20px;
            padding: 40px;
            max-width: 580px;
            width: 100%;
            box-shadow: 0 25px 60px rgba(0,0,0,0.3);
        }
        .setup-icon {
            width: 70px; height: 70px;
            background: linear-gradient(135deg, var(--primary), #1D4ED8);
            border-radius: 16px;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 15px; font-size: 32px; color: #fff;
        }
        .step-indicator { display: flex; gap: 8px; justify-content: center; margin-bottom: 25px; }
        .step-dot {
            width: 10px; height: 10px; border-radius: 50%;
            background: #CBD5E1; transition: all 0.3s;
        }
        .step-dot.active { background: var(--primary); width: 30px; border-radius: 5px; }
        .form-control, .form-select { border-radius: 10px; padding: 10px 14px; border-color: #CBD5E1; }
        .form-control:focus, .form-select:focus { border-color: var(--primary); box-shadow: 0 0 0 0.2rem rgba(37,99,235,0.15); }
        .btn-primary { background: var(--primary); border-color: var(--primary); border-radius: 10px; padding: 10px 24px; font-weight: 600; }
        .btn-primary:hover { background: #1D4ED8; border-color: #1D4ED8; }
        .section-title { font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px; color: #64748B; font-weight: 700; margin-bottom: 12px; margin-top: 20px; }
    </style>
</head>
<body>
<div class="setup-card">
    <div class="text-center mb-3">
        <div class="setup-icon"><i class="bi bi-calendar-event"></i></div>
        <h2 class="fw-bold" style="color: var(--secondary);">MeetingRoom Manager</h2>
        <p class="text-muted mb-0">Assistente de Instalação</p>
    </div>

    <div class="step-indicator">
        <div class="step-dot <?= $step === 'form' ? 'active' : '' ?>"></div>
        <div class="step-dot <?= $step === 'result' ? 'active' : '' ?>"></div>
    </div>

    <?php if ($step === 'form'): ?>
    <!-- ============ FORMULÁRIO ============ -->
    <form method="POST" action="setup.php">
        <input type="hidden" name="step" value="install">

        <div class="section-title"><i class="bi bi-database me-1"></i> Conexão com o Banco de Dados</div>

        <div class="row g-3">
            <div class="col-8">
                <label class="form-label fw-semibold">Host</label>
                <input type="text" class="form-control" name="db_host" value="localhost" required>
            </div>
            <div class="col-4">
                <label class="form-label fw-semibold">Porta</label>
                <input type="number" class="form-control" name="db_port" value="3306" required>
            </div>
            <div class="col-6">
                <label class="form-label fw-semibold">Usuário</label>
                <input type="text" class="form-control" name="db_user" value="root" required>
            </div>
            <div class="col-6">
                <label class="form-label fw-semibold">Senha</label>
                <input type="password" class="form-control" name="db_pass" value="">
            </div>
            <div class="col-12">
                <label class="form-label fw-semibold">Nome do Banco</label>
                <input type="text" class="form-control" name="db_name" value="reuniao" required>
                <div class="form-text">O banco será criado automaticamente se não existir.</div>
            </div>
        </div>

        <div class="section-title"><i class="bi bi-shield-check me-1"></i> Usuário Administrador</div>

        <div class="row g-3">
            <div class="col-12">
                <label class="form-label fw-semibold">Nome</label>
                <input type="text" class="form-control" name="admin_name" value="Administrador" required>
            </div>
            <div class="col-12">
                <label class="form-label fw-semibold">Email</label>
                <input type="email" class="form-control" name="admin_email" value="admin@meetingroom.com" required>
            </div>
            <div class="col-12">
                <label class="form-label fw-semibold">Senha</label>
                <input type="password" class="form-control" name="admin_pass" value="admin123" required>
                <div class="form-text">Mínimo 6 caracteres. Padrão: admin123</div>
            </div>
        </div>

        <div class="d-grid mt-4">
            <button type="submit" class="btn btn-primary btn-lg">
                <i class="bi bi-rocket-takeoff me-2"></i> Instalar Sistema
            </button>
        </div>
    </form>

    <?php elseif ($step === 'result'): ?>
    <!-- ============ RESULTADO ============ -->

        <?php if ($success): ?>
            <div class="text-center py-3">
                <div class="mb-3">
                    <i class="bi bi-check-circle-fill text-success" style="font-size: 64px;"></i>
                </div>
                <h4 class="fw-bold text-success">Instalação Concluída!</h4>
                <p class="text-muted">O sistema foi instalado com sucesso. Todas as tabelas foram criadas e o usuário administrador está pronto.</p>

                <div class="alert alert-light border text-start mt-3">
                    <h6 class="fw-bold mb-2"><i class="bi bi-info-circle me-1"></i> Resumo</h6>
                    <ul class="mb-0 small">
                        <li>✅ Banco de dados criado</li>
                        <li>✅ Tabelas criadas (users, rooms, reservations, reservation_participants)</li>
                        <li>✅ Usuário admin criado</li>
                        <li>✅ Arquivo de configuração atualizado</li>
                    </ul>
                </div>

                <div class="alert alert-warning text-start mt-3">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    <strong>Importante:</strong> Por segurança, exclua ou renomeie o arquivo <code>setup.php</code> após a instalação.
                </div>

                <div class="d-grid mt-3">
                    <a href="/reuniao/views/login.php" class="btn btn-primary btn-lg">
                        <i class="bi bi-box-arrow-in-right me-2"></i> Acessar o Sistema
                    </a>
                </div>
            </div>

        <?php else: ?>
            <div class="text-center py-3">
                <div class="mb-3">
                    <i class="bi bi-x-circle-fill text-danger" style="font-size: 64px;"></i>
                </div>
                <h4 class="fw-bold text-danger">Erro na Instalação</h4>
                <p class="text-muted">Ocorreram erros durante a instalação:</p>

                <div class="alert alert-danger text-start">
                    <ul class="mb-0">
                        <?php foreach ($errors as $err): ?>
                            <li><?= htmlspecialchars($err) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <div class="d-grid mt-3">
                    <a href="setup.php" class="btn btn-primary btn-lg">
                        <i class="bi bi-arrow-left me-2"></i> Tentar Novamente
                    </a>
                </div>
            </div>
        <?php endif; ?>

    <?php endif; ?>

    <div class="text-center mt-4">
        <small class="text-muted">MeetingRoom Manager &copy; <?= date('Y') ?></small>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
