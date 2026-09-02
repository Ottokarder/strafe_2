<?php
/**
 * Datenbank-Konfiguration für die Kanadierrennen-Webanwendung
 * 
 * KOPIE DIESE DATEI NACH config.php UND TRAGE DIE ZUGANGSDAEN EIN
 * config.php WIRD NICHT IN GITHUB ÜBERTRAGEN!
 */

// ============================================
// WICHTIG: Session-Einstellungen müssen VOR session_start() gesetzt werden!
// ============================================

// Fehlerbehandlung
error_reporting(E_ALL);
ini_set('display_errors', 0); // Im Produktionsmodus auf 0 setzen
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

// Zeitzone setzen
date_default_timezone_set('Europe/Berlin');

// Session-Einstellungen (MÜSSEN VOR session_start() stehen!)
ini_set('session.cookie_lifetime', 0); // Session endet beim Schließen des Browsers
ini_set('session.cookie_secure', false); // Nur HTTPS verwenden, falls verfügbar
ini_set('session.cookie_httponly', true); // Schutz vor XSS

// Jetzt Session starten
session_start();

// Datenbank-Verbindung (MariaDB)
define('DB_HOST', 'localhost');      // Datenbank-Host (z. B. localhost oder IP)
define('DB_USER', 'dein_benutzername'); // Datenbank-Benutzername
define('DB_PASS', 'dein_passwort');     // Datenbank-Passwort
define('DB_NAME', 'strafe_2');          // Datenbankname

// Admin-Zugangsdaten
define('ADMIN_USERNAME', 'admin');      // Admin-Benutzername
define('ADMIN_PASSWORD', 'admin123');   // Admin-Passwort (BITTE ÄNDERN!)

// Anwendungseinstellungen
define('APP_TITLE', 'Kanadierrennen CJD Kaltenstein');
define('RACE_DATE_SATURDAY', '2025-06-21'); // Samstag des Rennens (YYYY-MM-DD)
define('RACE_DATE_SUNDAY', '2025-06-22');   // Sonntag des Rennens (YYYY-MM-DD)

// Startzeiten-Intervalle (in Minuten)
define('START_INTERVAL', 10); // 10-Minuten-Intervalle

// Samstag: 14:00-18:00 Uhr (24 Startplätze)
define('SATURDAY_START', '14:00');
define('SATURDAY_END', '18:00');

// Sonntag: 11:00-16:00 Uhr (30 Startplätze)
define('SUNDAY_START', '11:00');
define('SUNDAY_END', '16:00');

// Pfad zum Logo (relativ zu index.php)
define('LOGO_PATH', 'assets/images/logo.png');

// Startklassen
$startklassen = [
    'Damen',
    'Gemischte Mannschaften',
    'Herren',
    'Betriebsmannschaften',
    'Ortsteile'
];

// Maximale Startzeiten pro Mannschaft
define('MAX_STARTS_PER_TEAM', 3);

// Reservierungszeitraum (6 Wochen vor dem Rennen)
$reservationStartDate = date('Y-m-d', strtotime('-6 weeks', strtotime(RACE_DATE_SATURDAY)));

// Aktuelles Datum für Überprüfungen
$currentDate = date('Y-m-d');
$currentTime = date('H:i');
