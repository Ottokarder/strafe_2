<?php
/**
 * Authentifizierungsfunktionen
 */

// Stelle sicher, dass die Session gestartet ist
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';

/**
 * Prüfen, ob der Benutzer angemeldet ist
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

/**
 * Prüfen, ob der Benutzer Admin ist
 * @return bool
 */
function isAdmin() {
    return isLoggedIn() && isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
}

/**
 * Benutzer anmelden
 * @param string $username Benutzername
 * @param string $password Passwort
 * @return bool
 */
function login($username, $password) {
    // Benutzer aus Datenbank abrufen
    $user = fetchOne("SELECT * FROM users WHERE username = ?", "s", [$username]);
    
    if ($user && password_verify($password, $user['password_hash'])) {
        // Session starten und Benutzerdaten speichern
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['is_admin'] = (bool)$user['is_admin'];
        $_SESSION['logged_in'] = true;
        $_SESSION['last_activity'] = time();
        
        // Audit-Log
        logAudit('LOGIN', 'users', $user['id']);
        
        return true;
    }
    
    return false;
}

/**
 * Benutzer abmelden
 */
function logout() {
    // Audit-Log
    if (isset($_SESSION['user_id'])) {
        logAudit('LOGOUT', 'users', $_SESSION['user_id']);
    }
    
    // Session löschen
    session_unset();
    session_destroy();
}

/**
 * Prüfen, ob der Benutzer Zugriff auf eine Seite hat
 * @param bool $requireAdmin Admin-Rechte erforderlich
 * @return bool
 */
function checkAccess($requireAdmin = false) {
    if ($requireAdmin && !isAdmin()) {
        return false;
    }
    
    if (!$requireAdmin && !isLoggedIn()) {
        return false;
    }
    
    return true;
}

/**
 * Weiterleitung zu Login-Seite mit Fehler
 * @param string $message Fehlermeldung
 */
function redirectToLogin($message = 'Bitte melden Sie sich an.') {
    $_SESSION['login_error'] = $message;
    header("Location: /login.php");
    exit();
}

/**
 * Weiterleitung mit Fehlermeldung
 * @param string $location Ziel-URL (leer = aktuelle Seite neu laden)
 * @param string $message Fehlermeldung
 */
function redirectWithError($location, $message) {
    $_SESSION['error'] = $message;
    if (empty($location)) {
        header("Location: " . $_SERVER['REQUEST_URI']);
    } else {
        header("Location: " . $location);
    }
    exit();
}

/**
 * Weiterleitung mit Erfolgsmeldung
 * @param string $location Ziel-URL (leer = aktuelle Seite neu laden)
 * @param string $message Erfolgsmeldung
 */
function redirectWithSuccess($location, $message) {
    $_SESSION['success'] = $message;
    if (empty($location)) {
        header("Location: " . $_SERVER['REQUEST_URI']);
    } else {
        header("Location: " . $location);
    }
    exit();
}

/**
 * CSRF-Token generieren
 * @return string
 */
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * CSRF-Token überprüfen
 * @param string $token Token
 * @return bool
 */
function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Eingaben validieren und bereinigen
 * @param string $input Eingabe
 * @return string Bereinigte Eingabe
 */
function validateInput($input) {
    $input = trim($input);
    $input = stripslashes($input);
    $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    return $input;
}

/**
 * E-Mail validieren
 * @param string $email E-Mail-Adresse
 * @return bool
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Zeit validieren (MM:SS oder HH:MM:SS)
 * @param string $time Zeit
 * @return bool
 */
function validateTime($time) {
    // Erlaubt MM:SS oder HH:MM:SS
    return preg_match('/^([0-5]?[0-9]):[0-5][0-9]$/', $time) || preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]:[0-5][0-9]$/', $time);
}

/**
 * Zeit validieren (nur MM:SS Format)
 * @param string $time Zeit
 * @return bool
 */
function validateTimeMMSS($time) {
    // Nur MM:SS Format (0-59 Minuten, 0-59 Sekunden)
    return preg_match('/^([0-5]?[0-9]):[0-5][0-9]$/', $time);
}

/**
 * Datum validieren (YYYY-MM-DD)
 * @param string $date Datum
 * @return bool
 */
function validateDate($date) {
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

/**
 * Zeit in Sekunden umrechnen
 * @param string $time Zeit (MM:SS oder HH:MM:SS)
 * @return int Sekunden
 */
function timeToSeconds($time) {
    $parts = explode(':', $time);
    if (count($parts) === 2) {
        // MM:SS Format
        list($m, $s) = $parts;
        return ($m * 60) + $s;
    } else {
        // HH:MM:SS Format
        list($h, $m, $s) = $parts;
        return ($h * 3600) + ($m * 60) + $s;
    }
}

/**
 * Sekunden in Zeit umrechnen (MM:SS oder HH:MM:SS)
 * @param int $seconds Sekunden
 * @return string Zeit
 */
function secondsToTime($seconds) {
    $h = floor($seconds / 3600);
    $m = floor(($seconds % 3600) / 60);
    $s = $seconds % 60;
    
    if ($h > 0) {
        return sprintf('%02d:%02d:%02d', $h, $m, $s);
    } else {
        return sprintf('%02d:%02d', $m, $s);
    }
}

/**
 * Zeit addieren
 * @param string $time1 Zeit 1
 * @param string $time2 Zeit 2
 * @return string Summe der Zeiten
 */
function addTimes($time1, $time2) {
    $seconds1 = timeToSeconds($time1);
    $seconds2 = timeToSeconds($time2);
    return secondsToTime($seconds1 + $seconds2);
}

/**
 * Zeit subtrahieren
 * @param string $time1 Zeit 1
 * @param string $time2 Zeit 2
 * @return string Differenz der Zeiten
 */
function subtractTimes($time1, $time2) {
    $seconds1 = timeToSeconds($time1);
    $seconds2 = timeToSeconds($time2);
    $diff = $seconds1 - $seconds2;
    return secondsToTime(abs($diff));
}

/**
 * Aktuelle Zeit abrufen
 * @return string
 */
function getCurrentTime() {
    return date('H:i:s');
}

/**
 * Aktuelles Datum abrufen
 * @return string
 */
function getCurrentDate() {
    return date('Y-m-d');
}

/**
 * Session-Timeout prüfen (30 Minuten Inaktivität)
 */
function checkSessionTimeout() {
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
        logout();
        redirectToLogin('Ihre Session ist abgelaufen. Bitte melden Sie sich erneut an.');
    }
    
    $_SESSION['last_activity'] = time();
}

/**
 * Benutzerdaten abrufen
 * @return array|null
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'] ?? null,
        'username' => $_SESSION['username'] ?? null,
        'is_admin' => $_SESSION['is_admin'] ?? false
    ];
}

/**
 * Passwort-Hash erstellen
 * @param string $password Passwort
 * @return string Hash
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Passwort überprüfen
 * @param string $password Passwort
 * @param string $hash Hash
 * @return bool
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Benutzer in Datenbank erstellen
 * @param string $username Benutzername
 * @param string $password Passwort
 * @param bool $isAdmin Admin-Rechte
 * @return int Benutzer-ID
 */
function createUser($username, $password, $isAdmin = false) {
    $passwordHash = hashPassword($password);
    
    $sql = "INSERT INTO users (username, password_hash, is_admin) VALUES (?, ?, ?)";
    execute($sql, "ssi", [$username, $passwordHash, $isAdmin ? 1 : 0]);
    
    return getLastInsertId();
}

/**
 * Benutzer in Datenbank aktualisieren
 * @param int $id Benutzer-ID
 * @param array $data Benutzerdaten
 * @return bool
 */
function updateUser($id, $data) {
    $sql = "UPDATE users SET username = ?, is_admin = ?";
    $params = [$data['username'], $data['is_admin'] ? 1 : 0];
    
    if (isset($data['password'])) {
        $sql .= ", password_hash = ?";
        $params[] = hashPassword($data['password']);
    }
    
    $sql .= " WHERE id = ?";
    $params[] = $id;
    
    return execute($sql, str_repeat('s', count($params) - 1) . 'i', $params) > 0;
}

/**
 * Benutzer aus Datenbank löschen
 * @param int $id Benutzer-ID
 * @return bool
 */
function deleteUser($id) {
    return execute("DELETE FROM users WHERE id = ?", "i", [$id]) > 0;
}

/**
 * Alle Benutzer abrufen
 * @return array
 */
function getAllUsers() {
    return fetchAll("SELECT id, username, is_admin, created_at FROM users ORDER BY username");
}

/**
 * Benutzer nach ID abrufen
 * @param int $id Benutzer-ID
 * @return array|null
 */
function getUserById($id) {
    return fetchOne("SELECT id, username, is_admin, created_at FROM users WHERE id = ?", "i", [$id]);
}

/**
 * Benutzer nach Benutzernamen abrufen
 * @param string $username Benutzername
 * @return array|null
 */
function getUserByUsername($username) {
    return fetchOne("SELECT id, username, is_admin, created_at FROM users WHERE username = ?", "s", [$username]);
}
