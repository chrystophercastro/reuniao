#!/bin/bash
# ============================================
# MeetingRoom Manager - Entrypoint
# ============================================

set -e

echo "=========================================="
echo " MeetingRoom Manager - Iniciando..."
echo "=========================================="

# Aguardar MySQL ficar pronto
echo "[*] Aguardando MySQL..."
until php -r "
    try {
        new PDO(
            'mysql:host=' . getenv('DB_HOST') . ';port=' . (getenv('DB_PORT') ?: '3306'),
            getenv('DB_USER') ?: 'root',
            getenv('DB_PASS') ?: ''
        );
        echo 'OK';
        exit(0);
    } catch (Exception \$e) {
        exit(1);
    }
" 2>/dev/null; do
    echo "    MySQL não disponível ainda, tentando novamente em 3s..."
    sleep 3
done
echo "[✓] MySQL conectado!"

# Atualizar config/database.php com variáveis de ambiente (se definidas)
if [ -n "$DB_HOST" ]; then
    echo "[*] Atualizando configuração do banco de dados..."
    cat > /var/www/html/reuniao/config/database.php << 'PHPEOF'
<?php
/**
 * MeetingRoom Manager - Database Configuration (Docker)
 */

define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'reuniao');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_PORT', (int)(getenv('DB_PORT') ?: 3306));
define('DB_CHARSET', 'utf8mb4');

function getConnection(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die("Erro de conexão com o banco de dados: " . $e->getMessage());
        }
    }

    return $pdo;
}
PHPEOF
    echo "[✓] Configuração atualizada!"
fi

# Instalar Composer se vendor não existir
if [ ! -d "/var/www/html/reuniao/vendor" ]; then
    echo "[*] Instalando dependências do Composer..."
    cd /var/www/html/reuniao
    composer install --no-dev --optimize-autoloader --no-interaction 2>&1 || true
    echo "[✓] Dependências instaladas!"
fi

# Corrigir permissões
chown -R www-data:www-data /var/www/html/reuniao

echo "=========================================="
echo " MeetingRoom Manager - Pronto!"
echo " Acesse: http://localhost:8080/reuniao/"
echo "=========================================="

# Executar comando original (apache)
exec "$@"
