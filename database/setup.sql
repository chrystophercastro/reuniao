-- ============================================
-- MeetingRoom Manager - Database Setup
-- ============================================

CREATE DATABASE IF NOT EXISTS reuniao
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE reuniao;

-- --------------------------------------------
-- Tabela: users
-- --------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(200) NOT NULL UNIQUE,
    phone VARCHAR(20) DEFAULT '',
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') NOT NULL DEFAULT 'user',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------
-- Tabela: rooms
-- --------------------------------------------
CREATE TABLE IF NOT EXISTS rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    description TEXT,
    capacity INT NOT NULL DEFAULT 1,
    color VARCHAR(7) NOT NULL DEFAULT '#2563EB',
    created_by INT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------
-- Tabela: reservations
-- --------------------------------------------
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------
-- Tabela: reservation_participants
-- --------------------------------------------
CREATE TABLE IF NOT EXISTS reservation_participants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reservation_id INT NOT NULL,
    user_id INT NOT NULL,
    FOREIGN KEY (reservation_id) REFERENCES reservations(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_participant (reservation_id, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------
-- Usuário administrador padrão
-- Senha: admin123
-- --------------------------------------------
INSERT INTO users (name, email, password, role) VALUES
('Administrador', 'admin@meetingroom.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Senha: user123
INSERT INTO users (name, email, password, role) VALUES
('Usuário Teste', 'user@meetingroom.com', '$2y$10$Ey8oY.1FlqFCKbJjpCqKYOXSGMjXn5VjGwJkR3hHaOSy5yM1F6bGK', 'user');

-- --------------------------------------------
-- Tabela: settings (chave-valor)
-- --------------------------------------------
CREATE TABLE IF NOT EXISTS settings (
    `key` VARCHAR(100) PRIMARY KEY,
    `value` TEXT NOT NULL DEFAULT '',
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Configurações padrão
INSERT INTO settings (`key`, `value`) VALUES
('app_name', 'MeetingRoom Manager'),
('app_subtitle', 'Sistema de Reserva de Salas de Reunião'),
('app_footer', 'Desenvolvido por: Samos Informática LTDA'),
('app_logo', ''),
('app_favicon', ''),
('color_primary', '#2563EB'),
('color_primary_dark', '#1D4ED8'),
('color_secondary', '#1E293B'),
('color_background', '#F1F5F9'),
('color_sidebar', '#1E293B'),
('login_bg_color1', '#1E3A5F'),
('login_bg_color2', '#4A90D9'),
('mail_enabled', '0'),
('mail_host', ''),
('mail_port', '587'),
('mail_username', ''),
('mail_password', ''),
('mail_from_address', ''),
('mail_from_name', 'MeetingRoom Manager'),
('mail_encryption', 'tls'),
('whatsapp_enabled', '0'),
('evolution_api_url', ''),
('evolution_api_key', ''),
('evolution_instance', '')
ON DUPLICATE KEY UPDATE `value` = VALUES(`value`);
