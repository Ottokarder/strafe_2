-- Datenbank-Schema für die Kanadierrennen-Webanwendung
-- MariaDB SQL-Dump
-- Version: 3.0 (korrigierte DELIMITER-Handhabung)

-- Datenbank erstellen
CREATE DATABASE IF NOT EXISTS `strafe_2` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `strafe_2`;

-- ============================================
-- TABELLEN
-- ============================================

-- 1. Mannschaften
CREATE TABLE IF NOT EXISTS `teams` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `startklasse` VARCHAR(50) NOT NULL,
    `kapitaen` VARCHAR(100) NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_startklasse` (`startklasse`),
    INDEX `idx_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Startzeiten
CREATE TABLE IF NOT EXISTS `start_times` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `team_id` INT,
    `date` DATE NOT NULL,
    `time` TIME NOT NULL,
    `is_booked` BOOLEAN DEFAULT FALSE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_team_id` (`team_id`),
    INDEX `idx_date_time` (`date`, `time`),
    FOREIGN KEY (`team_id`) REFERENCES `teams`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Ergebnisse
CREATE TABLE IF NOT EXISTS `results` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `team_id` INT NOT NULL,
    `start_time_id` INT,
    `time` TIME NOT NULL,
    `penalty_seconds` INT DEFAULT 0,
    `final_time` TIME GENERATED ALWAYS AS (ADDTIME(`time`, SEC_TO_TIME(`penalty_seconds`))) STORED,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_team_id` (`team_id`),
    INDEX `idx_start_time_id` (`start_time_id`),
    FOREIGN KEY (`team_id`) REFERENCES `teams`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`start_time_id`) REFERENCES `start_times`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Benutzer (für Admin-Authentifizierung)
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `is_admin` BOOLEAN DEFAULT FALSE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. System-Einstellungen
CREATE TABLE IF NOT EXISTS `settings` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `key` VARCHAR(100) NOT NULL UNIQUE,
    `value` TEXT,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_key` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Logbuch für Änderungen
CREATE TABLE IF NOT EXISTS `audit_log` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `user_id` INT,
    `action` VARCHAR(50) NOT NULL,
    `table_name` VARCHAR(50),
    `record_id` INT,
    `old_values` JSON,
    `new_values` JSON,
    `ip_address` VARCHAR(45),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_action` (`action`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- DATEN EINFÜGEN
-- ============================================

-- Standard-Administrator-Benutzer (Passwort: admin123 - BITTE ÄNDERN!)
INSERT IGNORE INTO `users` (`username`, `password_hash`, `is_admin`) 
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1);

-- Standard-Einstellungen
INSERT IGNORE INTO `settings` (`key`, `value`) VALUES 
('startklassen', 'Damen|Gemischte Mannschaften|Herren|Betriebsmannschaften|Ortsteile'),
('max_starts_per_team', '3'),
('race_date_saturday', '2025-06-21'),
('race_date_sunday', '2025-06-22'),
('saturday_start_time', '14:00'),
('saturday_end_time', '18:00'),
('sunday_start_time', '11:00'),
('sunday_end_time', '16:00'),
('start_interval_minutes', '10'),
('reservation_start_date', DATE_SUB('2025-06-21', INTERVAL 6 WEEK));

-- ============================================
-- VIEWS (ANSICHTEN)
-- ============================================

-- Alle freien Startzeiten
CREATE OR REPLACE VIEW `free_start_times` AS
SELECT 
    st.id,
    st.date,
    st.time,
    st.is_booked
FROM start_times st
LEFT JOIN teams t ON st.team_id = t.id
WHERE st.team_id IS NULL OR st.is_booked = FALSE
ORDER BY st.date, st.time;

-- Ergebnisse nach Startklasse sortiert
CREATE OR REPLACE VIEW `results_by_class` AS
SELECT 
    t.name AS team_name,
    t.startklasse,
    r.time AS race_time,
    r.penalty_seconds,
    r.final_time,
    st.date AS race_date,
    st.time AS start_time
FROM results r
JOIN teams t ON r.team_id = t.id
LEFT JOIN start_times st ON r.start_time_id = st.id
ORDER BY t.startklasse, r.final_time;

-- Mannschaftsübersicht mit Startzeiten
CREATE OR REPLACE VIEW `team_overview` AS
SELECT 
    t.id,
    t.name,
    t.startklasse,
    t.kapitaen,
    t.email,
    GROUP_CONCAT(CONCAT(st.date, ' ', st.time) SEPARATOR ', ') AS start_times,
    COUNT(st.id) AS start_count,
    MIN(r.final_time) AS best_time
FROM teams t
LEFT JOIN start_times st ON t.id = st.team_id
LEFT JOIN results r ON t.id = r.team_id
GROUP BY t.id, t.name, t.startklasse, t.kapitaen, t.email
ORDER BY t.startklasse, t.name;

-- Startzeiten für Samstag
CREATE OR REPLACE VIEW `start_times_saturday` AS
SELECT 
    st.id,
    st.date,
    st.time,
    t.id AS team_id,
    t.name AS team_name,
    t.startklasse,
    st.is_booked
FROM start_times st
LEFT JOIN teams t ON st.team_id = t.id
WHERE st.date = (SELECT value FROM settings WHERE `key` = 'race_date_saturday')
ORDER BY st.time;

-- Startzeiten für Sonntag
CREATE OR REPLACE VIEW `start_times_sunday` AS
SELECT 
    st.id,
    st.date,
    st.time,
    t.id AS team_id,
    t.name AS team_name,
    t.startklasse,
    st.is_booked
FROM start_times st
LEFT JOIN teams t ON st.team_id = t.id
WHERE st.date = (SELECT value FROM settings WHERE `key` = 'race_date_sunday')
ORDER BY st.time;

-- ============================================
-- PROZEDUREN
-- ============================================

DELIMITER //

-- Prozedur zum Erstellen von Startzeiten für einen Tag
CREATE PROCEDURE IF NOT EXISTS create_start_times_for_day(IN p_date DATE, IN p_start_time TIME, IN p_end_time TIME, IN p_interval INT)
BEGIN
    DECLARE v_current_time TIME;
    DECLARE v_end_reached BOOLEAN DEFAULT FALSE;
    
    SET v_current_time = p_start_time;
    
    WHILE NOT v_end_reached DO
        -- Startzeit einfügen
        INSERT IGNORE INTO start_times (date, time, is_booked)
        VALUES (p_date, v_current_time, FALSE);
        
        -- Nächste Zeit berechnen
        SET v_current_time = ADDTIME(v_current_time, SEC_TO_TIME(p_interval * 60));
        
        -- Abbruchbedingung
        IF v_current_time >= p_end_time THEN
            SET v_end_reached = TRUE;
        END IF;
    END WHILE;
END//

-- Prozedur zum Zurücksetzen aller Startzeiten
CREATE PROCEDURE IF NOT EXISTS reset_all_start_times()
BEGIN
    -- Alle Startzeiten löschen
    DELETE FROM start_times;
    
    -- Neue Startzeiten für Samstag erstellen
    CALL create_start_times_for_day(
        (SELECT value FROM settings WHERE `key` = 'race_date_saturday'),
        (SELECT value FROM settings WHERE `key` = 'saturday_start_time'),
        (SELECT value FROM settings WHERE `key` = 'saturday_end_time'),
        (SELECT value FROM settings WHERE `key` = 'start_interval_minutes')
    );
    
    -- Neue Startzeiten für Sonntag erstellen
    CALL create_start_times_for_day(
        (SELECT value FROM settings WHERE `key` = 'race_date_sunday'),
        (SELECT value FROM settings WHERE `key` = 'sunday_start_time'),
        (SELECT value FROM settings WHERE `key` = 'sunday_end_time'),
        (SELECT value FROM settings WHERE `key` = 'start_interval_minutes')
    );
END//

DELIMITER ;

-- ============================================
-- TRIGGER (mit DROP IF EXISTS)
-- ============================================

DELIMITER //

-- Trigger für Teams
DROP TRIGGER IF EXISTS after_team_insert//;
CREATE TRIGGER after_team_insert
AFTER INSERT ON teams
FOR EACH ROW
BEGIN
    INSERT INTO audit_log (user_id, action, table_name, record_id, new_values, ip_address)
    VALUES (IFNULL(@current_user_id, 0), 'INSERT', 'teams', NEW.id, 
            JSON_OBJECT('name', NEW.name, 'startklasse', NEW.startklasse, 'kapitaen', NEW.kapitaen, 'email', NEW.email), 
            IFNULL(@current_ip, 'localhost'));
END//

DROP TRIGGER IF EXISTS after_team_update//;
CREATE TRIGGER after_team_update
AFTER UPDATE ON teams
FOR EACH ROW
BEGIN
    INSERT INTO audit_log (user_id, action, table_name, record_id, old_values, new_values, ip_address)
    VALUES (IFNULL(@current_user_id, 0), 'UPDATE', 'teams', NEW.id,
            JSON_OBJECT('name', OLD.name, 'startklasse', OLD.startklasse, 'kapitaen', OLD.kapitaen, 'email', OLD.email),
            JSON_OBJECT('name', NEW.name, 'startklasse', NEW.startklasse, 'kapitaen', NEW.kapitaen, 'email', NEW.email),
            IFNULL(@current_ip, 'localhost'));
END//

DROP TRIGGER IF EXISTS after_team_delete//;
CREATE TRIGGER after_team_delete
AFTER DELETE ON teams
FOR EACH ROW
BEGIN
    INSERT INTO audit_log (user_id, action, table_name, record_id, old_values, ip_address)
    VALUES (IFNULL(@current_user_id, 0), 'DELETE', 'teams', OLD.id,
            JSON_OBJECT('name', OLD.name, 'startklasse', OLD.startklasse, 'kapitaen', OLD.kapitaen, 'email', OLD.email),
            IFNULL(@current_ip, 'localhost'));
END//

-- Trigger für Startzeiten
DROP TRIGGER IF EXISTS after_start_times_insert//;
CREATE TRIGGER after_start_times_insert
AFTER INSERT ON start_times
FOR EACH ROW
BEGIN
    INSERT INTO audit_log (user_id, action, table_name, record_id, new_values, ip_address)
    VALUES (IFNULL(@current_user_id, 0), 'INSERT', 'start_times', NEW.id,
            JSON_OBJECT('team_id', NEW.team_id, 'date', NEW.date, 'time', NEW.time, 'is_booked', NEW.is_booked),
            IFNULL(@current_ip, 'localhost'));
END//

DROP TRIGGER IF EXISTS after_start_times_update//;
CREATE TRIGGER after_start_times_update
AFTER UPDATE ON start_times
FOR EACH ROW
BEGIN
    INSERT INTO audit_log (user_id, action, table_name, record_id, old_values, new_values, ip_address)
    VALUES (IFNULL(@current_user_id, 0), 'UPDATE', 'start_times', NEW.id,
            JSON_OBJECT('team_id', OLD.team_id, 'date', OLD.date, 'time', OLD.time, 'is_booked', OLD.is_booked),
            JSON_OBJECT('team_id', NEW.team_id, 'date', NEW.date, 'time', NEW.time, 'is_booked', NEW.is_booked),
            IFNULL(@current_ip, 'localhost'));
END//

-- Trigger für Ergebnisse
DROP TRIGGER IF EXISTS after_results_insert//;
CREATE TRIGGER after_results_insert
AFTER INSERT ON results
FOR EACH ROW
BEGIN
    INSERT INTO audit_log (user_id, action, table_name, record_id, new_values, ip_address)
    VALUES (IFNULL(@current_user_id, 0), 'INSERT', 'results', NEW.id,
            JSON_OBJECT('team_id', NEW.team_id, 'start_time_id', NEW.start_time_id, 'time', NEW.time, 'penalty_seconds', NEW.penalty_seconds),
            IFNULL(@current_ip, 'localhost'));
END//

DROP TRIGGER IF EXISTS after_results_update//;
CREATE TRIGGER after_results_update
AFTER UPDATE ON results
FOR EACH ROW
BEGIN
    INSERT INTO audit_log (user_id, action, table_name, record_id, old_values, new_values, ip_address)
    VALUES (IFNULL(@current_user_id, 0), 'UPDATE', 'results', NEW.id,
            JSON_OBJECT('team_id', OLD.team_id, 'start_time_id', OLD.start_time_id, 'time', OLD.time, 'penalty_seconds', OLD.penalty_seconds),
            JSON_OBJECT('team_id', NEW.team_id, 'start_time_id', NEW.start_time_id, 'time', NEW.time, 'penalty_seconds', NEW.penalty_seconds),
            IFNULL(@current_ip, 'localhost'));
END//

DELIMITER ;
