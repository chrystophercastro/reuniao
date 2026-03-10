<?php
/**
 * MeetingRoom Manager - Database Configuration
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'reuniao');
define('DB_USER', 'root');
define('DB_PASS', 'Ca190799@#');
define('DB_PORT', 3306);
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
