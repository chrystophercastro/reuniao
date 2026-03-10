#!/bin/bash
# ============================================
# MeetingRoom Manager - Easypanel Entrypoint
# Usa getenv() do PHP para evitar problemas
# com caracteres especiais nas senhas
# ============================================

APP_DIR="/var/www/html"

echo "=========================================="
echo " MeetingRoom Manager"
echo " Inicializando..."
echo "=========================================="

# ---- Exportar variáveis com fallback para PHP getenv() ----
export DB_HOST="${DB_HOST:-localhost}"
export DB_PORT="${DB_PORT:-3306}"
export DB_NAME="${DB_NAME:-reuniao}"
export DB_USER="${DB_USER:-root}"
export DB_PASS="${DB_PASS:-}"
export ADMIN_NAME="${ADMIN_NAME:-Administrador}"
export ADMIN_EMAIL="${ADMIN_EMAIL:-admin@meetingroom.com}"
export ADMIN_PASS="${ADMIN_PASS:-admin123}"
export APP_PORT="${APP_PORT:-80}"

echo "[*] Configuracao detectada:"
echo "    DB_HOST = ${DB_HOST}"
echo "    DB_PORT = ${DB_PORT}"
echo "    DB_NAME = ${DB_NAME}"
echo "    DB_USER = ${DB_USER}"
echo "    DB_PASS = (${#DB_PASS} caracteres)"
echo "    APP_PORT= ${APP_PORT}"
echo ""

# ---- 0. Configurar porta do Apache ----
if [ "$APP_PORT" != "80" ]; then
    echo "[*] Alterando porta do Apache para ${APP_PORT}..."
    sed -i "s/Listen 80/Listen ${APP_PORT}/g" /etc/apache2/ports.conf
    sed -i "s/:80/:${APP_PORT}/g" /etc/apache2/sites-available/000-default.conf
    echo "[+] Apache na porta ${APP_PORT}"
fi

# ---- 1. Resolver DNS do host MySQL ----
echo "[*] Resolvendo DNS de '${DB_HOST}'..."
if command -v getent > /dev/null 2>&1; then
    RESOLVED=$(getent hosts "${DB_HOST}" 2>/dev/null | awk '{print $1}')
    if [ -n "$RESOLVED" ]; then
        echo "[+] ${DB_HOST} -> ${RESOLVED}"
    else
        echo "[!] Nao conseguiu resolver '${DB_HOST}'"
    fi
fi

# ---- 2. Aguardar MySQL (usa getenv do PHP, SEM interpolacao bash) ----
echo "[*] Tentando conectar ao MySQL ${DB_HOST}:${DB_PORT}..."
MAX_RETRIES=30
RETRY=0
MYSQL_OK=false

while [ $RETRY -lt $MAX_RETRIES ]; do
    RESULT=$(php -r '
        $host = getenv("DB_HOST");
        $port = getenv("DB_PORT");
        $user = getenv("DB_USER");
        $pass = getenv("DB_PASS");
        try {
            $pdo = new PDO(
                "mysql:host={$host};port={$port}",
                $user,
                $pass
            );
            echo "CONNECTED";
        } catch (Exception $e) {
            echo "FAIL:" . $e->getMessage();
        }
    ' 2>&1)

    if echo "$RESULT" | grep -q "CONNECTED"; then
        MYSQL_OK=true
        echo "[+] MySQL conectado!"
        break
    fi

    RETRY=$((RETRY + 1))
    if [ $RETRY -eq 1 ] || [ $((RETRY % 5)) -eq 0 ] || [ $RETRY -eq $MAX_RETRIES ]; then
        ERRMSG=$(echo "$RESULT" | sed 's/FAIL://')
        echo "    ERRO: ${ERRMSG}"
    fi
    echo "    Tentativa ${RETRY}/${MAX_RETRIES}..."
    sleep 3
done

if [ "$MYSQL_OK" = false ]; then
    echo ""
    echo "============================================"
    echo " !! MySQL NAO conectou apos ${MAX_RETRIES} tentativas"
    echo "============================================"
    echo " O Apache sera iniciado assim mesmo."
    echo " Acesse /setup.php para configurar manualmente."
    echo "============================================"
    echo ""
fi

# ---- 3. Gerar config/database.php via PHP (seguro para chars especiais) ----
echo "[*] Gerando config/database.php..."
php -r '
    $host = getenv("DB_HOST");
    $name = getenv("DB_NAME");
    $user = getenv("DB_USER");
    $pass = getenv("DB_PASS");
    $port = getenv("DB_PORT");

    $pass_escaped = addslashes($pass);

    $content = "<?php
/**
 * MeetingRoom Manager - Database Configuration
 * Gerado automaticamente pelo instalador
 */

define(\"DB_HOST\", \"" . addslashes($host) . "\");
define(\"DB_NAME\", \"" . addslashes($name) . "\");
define(\"DB_USER\", \"" . addslashes($user) . "\");
define(\"DB_PASS\", \"" . $pass_escaped . "\");
define(\"DB_PORT\", " . intval($port) . ");
define(\"DB_CHARSET\", \"utf8mb4\");

function getConnection(): PDO
{
    static \$pdo = null;

    if (\$pdo === null) {
        \$dsn = \"mysql:host=\" . DB_HOST . \";port=\" . DB_PORT . \";dbname=\" . DB_NAME . \";charset=\" . DB_CHARSET;

        \$options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            \$pdo = new PDO(\$dsn, DB_USER, DB_PASS, \$options);
        } catch (PDOException \$e) {
            die(\"Erro de conexao com o banco de dados: \" . \$e->getMessage());
        }
    }

    return \$pdo;
}
";
    file_put_contents("/var/www/html/config/database.php", $content);
    echo "OK";
' 2>&1
echo ""
echo "[+] config/database.php gerado!"

# ---- 4. Se MySQL conectou, criar tabelas automaticamente ----
if [ "$MYSQL_OK" = true ]; then
echo "[*] Criando banco de dados e tabelas..."
php -r '
    $host = getenv("DB_HOST");
    $port = getenv("DB_PORT");
    $name = getenv("DB_NAME");
    $user = getenv("DB_USER");
    $pass = getenv("DB_PASS");
    $adminName  = getenv("ADMIN_NAME");
    $adminEmail = getenv("ADMIN_EMAIL");
    $adminPass  = getenv("ADMIN_PASS");

    try {
        $pdo = new PDO("mysql:host={$host};port={$port};charset=utf8mb4", $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);

        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `{$name}`");

        $pdo->exec("CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(150) NOT NULL,
            email VARCHAR(200) NOT NULL UNIQUE,
            phone VARCHAR(20) DEFAULT \"\",
            password VARCHAR(255) NOT NULL,
            role ENUM(\"admin\", \"user\") NOT NULL DEFAULT \"user\",
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS rooms (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(150) NOT NULL,
            description TEXT,
            capacity INT NOT NULL DEFAULT 1,
            color VARCHAR(7) NOT NULL DEFAULT \"#2563EB\",
            created_by INT NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS reservations (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS reservation_participants (
            id INT AUTO_INCREMENT PRIMARY KEY,
            reservation_id INT NOT NULL,
            user_id INT NOT NULL,
            FOREIGN KEY (reservation_id) REFERENCES reservations(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            UNIQUE KEY unique_participant (reservation_id, user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS settings (
            `key` VARCHAR(100) PRIMARY KEY,
            `value` TEXT NOT NULL DEFAULT \"\",
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        echo "Tabelas OK. ";

        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$adminEmail]);
        if (!$stmt->fetch()) {
            $hash = password_hash($adminPass, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, \"admin\")");
            $stmt->execute([$adminName, $adminEmail, $hash]);
            echo "Admin criado.";
        } else {
            echo "Admin ja existe.";
        }

    } catch (Exception $e) {
        echo "ERRO: " . $e->getMessage();
    }
' 2>&1
echo ""
echo "[+] Banco instalado!"
fi

# ---- 5. Ajustar URLs (Easypanel roda na raiz /) ----
echo "[*] Configurando URLs para Easypanel (raiz /)..."
find "${APP_DIR}" -type f \( -name "*.php" -o -name "*.js" \) \
    -not -path "*/vendor/*" \
    -not -path "*/node_modules/*" \
    -exec sed -i 's|/reuniao/|/|g' {} + 2>/dev/null || true
echo "[+] URLs ajustadas!"

# ---- 6. Composer ----
if [ ! -d "${APP_DIR}/vendor" ]; then
    echo "[*] Instalando dependencias do Composer..."
    cd "${APP_DIR}"
    composer install --no-dev --optimize-autoloader --no-interaction 2>&1 || true
    echo "[+] Dependencias instaladas!"
fi

# ---- 7. Permissoes ----
echo "[*] Ajustando permissoes..."
chown -R www-data:www-data "${APP_DIR}"
chmod -R 755 "${APP_DIR}"
chmod -R 775 "${APP_DIR}/assets/img" 2>/dev/null || true

echo ""
echo "=========================================="
echo " MeetingRoom Manager - Apache subindo!"
echo "=========================================="
if [ "$MYSQL_OK" = true ]; then
    echo " Login: ${ADMIN_EMAIL}"
    echo " Senha: ${ADMIN_PASS}"
else
    echo " MySQL offline - use /setup.php"
fi
echo " Porta: ${APP_PORT}"
echo "=========================================="

# Executar Apache
exec "$@"
