#!/bin/bash
# ============================================
# MeetingRoom Manager - Easypanel Entrypoint
# Instalação 100% automática via variáveis
# ============================================

set -e

APP_DIR="/var/www/html"
LOCK_FILE="${APP_DIR}/.installed"

echo "=========================================="
echo " 🏢 MeetingRoom Manager"
echo " Inicializando para Easypanel..."
echo "=========================================="

# ---- Variáveis com fallback ----
DB_HOST="${DB_HOST:-localhost}"
DB_PORT="${DB_PORT:-3306}"
DB_NAME="${DB_NAME:-reuniao}"
DB_USER="${DB_USER:-root}"
DB_PASS="${DB_PASS:-}"
ADMIN_NAME="${ADMIN_NAME:-Administrador}"
ADMIN_EMAIL="${ADMIN_EMAIL:-admin@meetingroom.com}"
ADMIN_PASS="${ADMIN_PASS:-admin123}"
APP_URL="${APP_URL:-}"

echo "[*] Configuração:"
echo "    DB Host:  ${DB_HOST}:${DB_PORT}"
echo "    DB Name:  ${DB_NAME}"
echo "    DB User:  ${DB_USER}"

# ---- 1. Aguardar MySQL ----
echo "[*] Aguardando MySQL..."
MAX_RETRIES=60
RETRY=0
until php -r "
    try {
        new PDO(
            'mysql:host=${DB_HOST};port=${DB_PORT}',
            '${DB_USER}',
            '${DB_PASS}'
        );
        echo 'OK';
        exit(0);
    } catch (Exception \$e) {
        exit(1);
    }
" 2>/dev/null; do
    RETRY=$((RETRY + 1))
    if [ $RETRY -ge $MAX_RETRIES ]; then
        echo "[✗] MySQL não respondeu após ${MAX_RETRIES} tentativas. Abortando."
        exit 1
    fi
    echo "    Tentativa ${RETRY}/${MAX_RETRIES}..."
    sleep 2
done
echo "[✓] MySQL conectado!"

# ---- 2. Gerar config/database.php ----
echo "[*] Gerando configuração do banco..."
cat > "${APP_DIR}/config/database.php" << PHPEOF
<?php
/**
 * MeetingRoom Manager - Database Configuration
 * Gerado automaticamente pelo instalador Easypanel
 */

define('DB_HOST', '${DB_HOST}');
define('DB_NAME', '${DB_NAME}');
define('DB_USER', '${DB_USER}');
define('DB_PASS', '${DB_PASS}');
define('DB_PORT', ${DB_PORT});
define('DB_CHARSET', 'utf8mb4');

function getConnection(): PDO
{
    static \$pdo = null;

    if (\$pdo === null) {
        \$dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;

        \$options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            \$pdo = new PDO(\$dsn, DB_USER, DB_PASS, \$options);
        } catch (PDOException \$e) {
            die("Erro de conexão com o banco de dados: " . \$e->getMessage());
        }
    }

    return \$pdo;
}
PHPEOF
echo "[✓] config/database.php gerado!"

# ---- 3. Criar banco e tabelas (auto-install) ----
echo "[*] Criando banco de dados e tabelas..."
php -r "
    try {
        \$pdo = new PDO('mysql:host=${DB_HOST};port=${DB_PORT};charset=utf8mb4', '${DB_USER}', '${DB_PASS}', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);

        // Criar banco
        \$pdo->exec(\"CREATE DATABASE IF NOT EXISTS \\\`${DB_NAME}\\\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci\");
        \$pdo->exec(\"USE \\\`${DB_NAME}\\\`\");

        // Tabela users
        \$pdo->exec(\"CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(150) NOT NULL,
            email VARCHAR(200) NOT NULL UNIQUE,
            phone VARCHAR(20) DEFAULT '',
            password VARCHAR(255) NOT NULL,
            role ENUM('admin', 'user') NOT NULL DEFAULT 'user',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci\");

        // Tabela rooms
        \$pdo->exec(\"CREATE TABLE IF NOT EXISTS rooms (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(150) NOT NULL,
            description TEXT,
            capacity INT NOT NULL DEFAULT 1,
            color VARCHAR(7) NOT NULL DEFAULT '#2563EB',
            created_by INT NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci\");

        // Tabela reservations
        \$pdo->exec(\"CREATE TABLE IF NOT EXISTS reservations (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci\");

        // Tabela reservation_participants
        \$pdo->exec(\"CREATE TABLE IF NOT EXISTS reservation_participants (
            id INT AUTO_INCREMENT PRIMARY KEY,
            reservation_id INT NOT NULL,
            user_id INT NOT NULL,
            FOREIGN KEY (reservation_id) REFERENCES reservations(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            UNIQUE KEY unique_participant (reservation_id, user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci\");

        // Tabela settings
        \$pdo->exec(\"CREATE TABLE IF NOT EXISTS settings (
            \\\`key\\\` VARCHAR(100) PRIMARY KEY,
            \\\`value\\\` TEXT NOT NULL DEFAULT '',
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci\");

        echo 'Tabelas OK. ';

        // Admin padrão
        \$stmt = \$pdo->prepare('SELECT id FROM users WHERE email = ?');
        \$stmt->execute(['${ADMIN_EMAIL}']);
        if (!\$stmt->fetch()) {
            \$hash = password_hash('${ADMIN_PASS}', PASSWORD_DEFAULT);
            \$stmt = \$pdo->prepare('INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, \"admin\")');
            \$stmt->execute(['${ADMIN_NAME}', '${ADMIN_EMAIL}', \$hash]);
            echo 'Admin criado.';
        } else {
            echo 'Admin já existe.';
        }

    } catch (Exception \$e) {
        echo 'ERRO: ' . \$e->getMessage();
        exit(1);
    }
" 2>&1
echo ""
echo "[✓] Banco instalado!"

# ---- 4. Ajustar URLs (Easypanel roda na raiz /) ----
echo "[*] Configurando URLs para Easypanel (raiz /)..."
# Substituir todas as referências /reuniao/ por / nos arquivos PHP e JS
find "${APP_DIR}" -type f \( -name "*.php" -o -name "*.js" \) \
    -not -path "*/vendor/*" \
    -not -path "*/node_modules/*" \
    -exec sed -i 's|/reuniao/|/|g' {} + 2>/dev/null || true
echo "[✓] URLs ajustadas!"

# ---- 5. Composer ----
if [ ! -d "${APP_DIR}/vendor" ]; then
    echo "[*] Instalando dependências do Composer..."
    cd "${APP_DIR}"
    composer install --no-dev --optimize-autoloader --no-interaction 2>&1 || true
    echo "[✓] Dependências instaladas!"
fi

# ---- 6. Permissões ----
echo "[*] Ajustando permissões..."
chown -R www-data:www-data "${APP_DIR}"
chmod -R 755 "${APP_DIR}"
chmod -R 775 "${APP_DIR}/assets/img" 2>/dev/null || true

# ---- 7. Marcar como instalado ----
touch "${LOCK_FILE}"

echo "=========================================="
echo " ✅ MeetingRoom Manager - Pronto!"
echo "=========================================="
echo " Admin: ${ADMIN_EMAIL}"
echo " Senha: ${ADMIN_PASS}"
echo "=========================================="

# Executar Apache
exec "$@"
