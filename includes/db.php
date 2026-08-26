<?php
/**
 * Datenbank-Verbindungsfunktionen
 */

require_once __DIR__ . '/../config.php';

/**
 * Datenbank-Verbindung herstellen
 * @return mysqli Datenbank-Verbindungsobjekt
 */
function getDBConnection() {
    static $conn = null;
    
    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if ($conn->connect_error) {
            error_log("Datenbank-Verbindungsfehler: " . $conn->connect_error);
            die("Datenbank-Verbindung fehlgeschlagen. Bitte versuchen Sie es später erneut.");
        }
        
        $conn->set_charset("utf8mb4");
    }
    
    return $conn;
}

/**
 * Abfrage ausführen mit vorbereitetem Statement
 * @param string $sql SQL-Abfrage mit Platzhaltern
 * @param string $types Typen der Parameter (z. B. "isi" für int, string, int)
 * @param array $params Parameter-Werte
 * @return mysqli_stmt Vorbereitetes Statement
 */
function executeQuery($sql, $types = '', $params = []) {
    $conn = getDBConnection();
    $stmt = $conn->prepare($sql);
    
    if ($stmt === false) {
        error_log("SQL-Fehler: " . $conn->error . " - Abfrage: " . $sql);
        die("Datenbankfehler aufgetreten.");
    }
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    return $stmt;
}

/**
 * Einfache SELECT-Abfrage mit Rückgabe aller Zeilen
 * @param string $sql SQL-Abfrage
 * @param string $types Typen der Parameter
 * @param array $params Parameter-Werte
 * @return array Array mit den Ergebnissen
 */
function fetchAll($sql, $types = '', $params = []) {
    $stmt = executeQuery($sql, $types, $params);
    $result = $stmt->execute();
    
    if ($result === false) {
        error_log("Abfrage fehlgeschlagen: " . $stmt->error);
        return [];
    }
    
    $resultSet = $stmt->get_result();
    $rows = [];
    
    if ($resultSet !== false) {
        while ($row = $resultSet->fetch_assoc()) {
            $rows[] = $row;
        }
        $resultSet->free();
    }
    
    $stmt->close();
    return $rows;
}

/**
 * Einfache SELECT-Abfrage mit Rückgabe einer Zeile
 * @param string $sql SQL-Abfrage
 * @param string $types Typen der Parameter
 * @param array $params Parameter-Werte
 * @return array|null Eine Zeile oder null
 */
function fetchOne($sql, $types = '', $params = []) {
    $rows = fetchAll($sql, $types, $params);
    return !empty($rows) ? $rows[0] : null;
}

/**
 * INSERT/UPDATE/DELETE-Abfrage ausführen
 * @param string $sql SQL-Abfrage
 * @param string $types Typen der Parameter
 * @param array $params Parameter-Werte
 * @return int Anzahl der betroffenen Zeilen
 */
function execute($sql, $types = '', $params = []) {
    $stmt = executeQuery($sql, $types, $params);
    $result = $stmt->execute();
    
    if ($result === false) {
        error_log("Abfrage fehlgeschlagen: " . $stmt->error);
        $stmt->close();
        return 0;
    }
    
    $affectedRows = $stmt->affected_rows;
    $stmt->close();
    return $affectedRows;
}

/**
 * Letzte Insert-ID abrufen
 * @return int
 */
function getLastInsertId() {
    $conn = getDBConnection();
    return $conn->insert_id;
}

/**
 * Transaktion starten
 */
function beginTransaction() {
    $conn = getDBConnection();
    $conn->begin_transaction();
}

/**
 * Transaktion committen
 */
function commitTransaction() {
    $conn = getDBConnection();
    $conn->commit();
}

/**
 * Transaktion zurückrollen
 */
function rollbackTransaction() {
    $conn = getDBConnection();
    $conn->rollback();
}

/**
 * Tabellenexistenz prüfen
 * @param string $tableName Tabellenname
 * @return bool
 */
function tableExists($tableName) {
    $conn = getDBConnection();
    $result = $conn->query("SHOW TABLES LIKE '" . $conn->real_escape_string($tableName) . "'");
    return $result && $result->num_rows > 0;
}

/**
 * Alle Teams abrufen
 * @return array
 */
function getAllTeams() {
    return fetchAll("SELECT * FROM teams ORDER BY startklasse, name");
}

/**
 * Team nach ID abrufen
 * @param int $id Team-ID
 * @return array|null
 */
function getTeamById($id) {
    return fetchOne("SELECT * FROM teams WHERE id = ?", "i", [$id]);
}

/**
 * Team erstellen
 * @param array $data Team-Daten
 * @return int Team-ID
 */
function createTeam($data) {
    beginTransaction();
    
    try {
        $sql = "INSERT INTO teams (name, startklasse, kapitaen, email) VALUES (?, ?, ?, ?)";
        execute($sql, "ssss", [
            $data['name'],
            $data['startklasse'],
            $data['kapitaen'],
            $data['email']
        ]);
        
        $teamId = getLastInsertId();
        commitTransaction();
        return $teamId;
    } catch (Exception $e) {
        rollbackTransaction();
        throw $e;
    }
}

/**
 * Team aktualisieren
 * @param int $id Team-ID
 * @param array $data Team-Daten
 * @return bool
 */
function updateTeam($id, $data) {
    $sql = "UPDATE teams SET name = ?, startklasse = ?, kapitaen = ?, email = ? WHERE id = ?";
    return execute($sql, "ssssi", [
        $data['name'],
        $data['startklasse'],
        $data['kapitaen'],
        $data['email'],
        $id
    ]) > 0;
}

/**
 * Team löschen
 * @param int $id Team-ID
 * @return bool
 */
function deleteTeam($id) {
    return execute("DELETE FROM teams WHERE id = ?", "i", [$id]) > 0;
}

/**
 * Alle Startzeiten abrufen
 * @return array
 */
function getAllStartTimes() {
    return fetchAll("SELECT st.*, t.name AS team_name, t.startklasse FROM start_times st LEFT JOIN teams t ON st.team_id = t.id ORDER BY st.date, st.time");
}

/**
 * Freie Startzeiten abrufen
 * @return array
 */
function getFreeStartTimes() {
    return fetchAll("SELECT * FROM start_times WHERE team_id IS NULL OR is_booked = FALSE ORDER BY date, time");
}

/**
 * Startzeit einem Team zuweisen
 * @param int $startTimeId Startzeit-ID
 * @param int $teamId Team-ID
 * @return bool
 */
function assignStartTimeToTeam($startTimeId, $teamId) {
    // Prüfen, ob das Team bereits 3 Startzeiten hat
    $count = fetchOne("SELECT COUNT(*) as count FROM start_times WHERE team_id = ?", "i", [$teamId]);
    if ($count['count'] >= MAX_STARTS_PER_TEAM) {
        return false;
    }
    
    return execute("UPDATE start_times SET team_id = ?, is_booked = TRUE WHERE id = ?", "ii", [$teamId, $startTimeId]) > 0;
}

/**
 * Startzeit von einem Team entfernen
 * @param int $startTimeId Startzeit-ID
 * @return bool
 */
function removeStartTimeFromTeam($startTimeId) {
    return execute("UPDATE start_times SET team_id = NULL, is_booked = FALSE WHERE id = ?", "i", [$startTimeId]) > 0;
}

/**
 * Startzeit erstellen
 * @param string $date Datum
 * @param string $time Uhrzeit
 * @return int Startzeit-ID
 */
function createStartTime($date, $time) {
    $sql = "INSERT INTO start_times (date, time) VALUES (?, ?)";
    execute($sql, "ss", [$date, $time]);
    return getLastInsertId();
}

/**
 * Alle Ergebnisse abrufen
 * @return array
 */
function getAllResults() {
    return fetchAll("SELECT r.*, t.name AS team_name, t.startklasse, st.date AS race_date, st.time AS start_time 
                     FROM results r 
                     JOIN teams t ON r.team_id = t.id 
                     LEFT JOIN start_times st ON r.start_time_id = st.id 
                     ORDER BY t.startklasse, r.final_time");
}

/**
 * Ergebnis erstellen
 * @param array $data Ergebnis-Daten
 * @return int Ergebnis-ID
 */
function createResult($data) {
    $sql = "INSERT INTO results (team_id, start_time_id, time, penalty_seconds) VALUES (?, ?, ?, ?)";
    execute($sql, "iisi", [
        $data['team_id'],
        $data['start_time_id'] ?? null,
        $data['time'],
        $data['penalty_seconds'] ?? 0
    ]);
    return getLastInsertId();
}

/**
 * Ergebnis aktualisieren
 * @param int $id Ergebnis-ID
 * @param array $data Ergebnis-Daten
 * @return bool
 */
function updateResult($id, $data) {
    $sql = "UPDATE results SET team_id = ?, start_time_id = ?, time = ?, penalty_seconds = ? WHERE id = ?";
    return execute($sql, "iisi", [
        $data['team_id'],
        $data['start_time_id'] ?? null,
        $data['time'],
        $data['penalty_seconds'] ?? 0,
        $id
    ]) > 0;
}

/**
 * Ergebnis löschen
 * @param int $id Ergebnis-ID
 * @return bool
 */
function deleteResult($id) {
    return execute("DELETE FROM results WHERE id = ?", "i", [$id]) > 0;
}

/**
 * Ergebnisse nach Startklasse abrufen
 * @param string $startklasse Startklasse
 * @return array
 */
function getResultsByClass($startklasse) {
    return fetchAll("SELECT r.*, t.name AS team_name, t.startklasse, st.date AS race_date, st.time AS start_time 
                     FROM results r 
                     JOIN teams t ON r.team_id = t.id 
                     LEFT JOIN start_times st ON r.start_time_id = st.id 
                     WHERE t.startklasse = ? 
                     ORDER BY r.final_time", "s", [$startklasse]);
}

/**
 * Startzeiten für ein Team abrufen
 * @param int $teamId Team-ID
 * @return array
 */
function getStartTimesForTeam($teamId) {
    return fetchAll("SELECT * FROM start_times WHERE team_id = ? ORDER BY date, time", "i", [$teamId]);
}

/**
 * Benutzer authentifizieren
 * @param string $username Benutzername
 * @param string $password Passwort
 * @return bool
 */
function authenticateUser($username, $password) {
    $user = fetchOne("SELECT * FROM users WHERE username = ?", "s", [$username]);
    
    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['is_admin'] = $user['is_admin'];
        $_SESSION['logged_in'] = true;
        return true;
    }
    
    return false;
}

/**
 * Benutzer abmelden
 */
function logoutUser() {
    session_unset();
    session_destroy();
}

/**
 * Benutzer registrieren
 * @param string $username Benutzername
 * @param string $password Passwort
 * @param bool $isAdmin Admin-Rechte
 * @return int Benutzer-ID
 */
function registerUser($username, $password, $isAdmin = false) {
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    $sql = "INSERT INTO users (username, password_hash, is_admin) VALUES (?, ?, ?)";
    execute($sql, "ssi", [$username, $passwordHash, $isAdmin ? 1 : 0]);
    return getLastInsertId();
}

/**
 * Einstellungen abrufen
 * @param string $key Schlüssel
 * @return string|null
 */
function getSetting($key) {
    $setting = fetchOne("SELECT value FROM settings WHERE `key` = ?", "s", [$key]);
    return $setting ? $setting['value'] : null;
}

/**
 * Einstellung speichern
 * @param string $key Schlüssel
 * @param string $value Wert
 * @return bool
 */
function saveSetting($key, $value) {
    $existing = fetchOne("SELECT id FROM settings WHERE `key` = ?", "s", [$key]);
    
    if ($existing) {
        return execute("UPDATE settings SET value = ? WHERE `key` = ?", "ss", [$value, $key]) > 0;
    } else {
        return execute("INSERT INTO settings (`key`, value) VALUES (?, ?)", "ss", [$key, $value]) > 0;
    }
}

/**
 * Audit-Log-Eintrag erstellen
 * @param string $action Aktion
 * @param string $tableName Tabellenname
 * @param int $recordId Datensatz-ID
 * @param array $oldValues Alte Werte
 * @param array $newValues Neue Werte
 */
function logAudit($action, $tableName, $recordId, $oldValues = [], $newValues = []) {
    $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    $sql = "INSERT INTO audit_log (user_id, action, table_name, record_id, old_values, new_values, ip_address) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    execute($sql, "isissss", [
        $userId,
        $action,
        $tableName,
        $recordId,
        json_encode($oldValues),
        json_encode($newValues),
        $ipAddress
    ]);
}

/**
 * Startzeiten für einen Tag generieren
 * @param string $date Datum
 * @param string $startTime Startzeit
 * @param string $endTime Endzeit
 * @param int $interval Intervall in Minuten
 * @return int Anzahl der erstellten Startzeiten
 */
function generateStartTimesForDay($date, $startTime, $endTime, $interval) {
    $startTimestamp = strtotime($date . ' ' . $startTime);
    $endTimestamp = strtotime($date . ' ' . $endTime);
    
    $count = 0;
    
    for ($time = $startTimestamp; $time < $endTimestamp; $time += $interval * 60) {
        $currentTime = date('H:i', $time);
        $existing = fetchOne("SELECT id FROM start_times WHERE date = ? AND time = ?", "ss", [$date, $currentTime]);
        
        if (!$existing) {
            createStartTime($date, $currentTime);
            $count++;
        }
    }
    
    return $count;
}

/**
 * Alle Startzeiten zurücksetzen
 * @return int Anzahl der erstellten Startzeiten
 */
function resetAllStartTimes() {
    $saturdayDate = getSetting('race_date_saturday') ?? RACE_DATE_SATURDAY;
    $sundayDate = getSetting('race_date_sunday') ?? RACE_DATE_SUNDAY;
    $saturdayStart = getSetting('saturday_start_time') ?? SATURDAY_START;
    $saturdayEnd = getSetting('saturday_end_time') ?? SATURDAY_END;
    $sundayStart = getSetting('sunday_start_time') ?? SUNDAY_START;
    $sundayEnd = getSetting('sunday_end_time') ?? SUNDAY_END;
    $interval = (int)(getSetting('start_interval_minutes') ?? START_INTERVAL);
    
    beginTransaction();
    
    try {
        // Alle Startzeiten löschen
        execute("DELETE FROM start_times");
        
        // Samstag
        $saturdayCount = generateStartTimesForDay($saturdayDate, $saturdayStart, $saturdayEnd, $interval);
        
        // Sonntag
        $sundayCount = generateStartTimesForDay($sundayDate, $sundayStart, $sundayEnd, $interval);
        
        commitTransaction();
        return $saturdayCount + $sundayCount;
    } catch (Exception $e) {
        rollbackTransaction();
        throw $e;
    }
}

/**
 * CSV-Export der Ergebnisse
 * @param string $startklasse Startklasse (optional)
 * @return string CSV-Inhalt
 */
function exportResultsToCSV($startklasse = null) {
    $query = "SELECT 
                t.name AS 'Mannschaft',
                t.startklasse AS 'Startklasse',
                r.time AS 'Rennzeit',
                r.penalty_seconds AS 'Strafsekunden',
                r.final_time AS 'Endzeit',
                st.date AS 'Renntag',
                st.time AS 'Startzeit'
              FROM results r 
              JOIN teams t ON r.team_id = t.id 
              LEFT JOIN start_times st ON r.start_time_id = st.id";
    
    $params = [];
    $types = '';
    
    if ($startklasse) {
        $query .= " WHERE t.startklasse = ?";
        $params = [$startklasse];
        $types = "s";
    }
    
    $query .= " ORDER BY t.startklasse, r.final_time";
    
    $results = fetchAll($query, $types, $params);
    
    if (empty($results)) {
        return "Keine Ergebnisse gefunden\n";
    }
    
    // Header
    $csv = "Mannschaft;Startklasse;Rennzeit;Strafsekunden;Endzeit;Renntag;Startzeit\n";
    
    // Daten
    foreach ($results as $row) {
        $csv .= sprintf(
            "%s;%s;%s;%d;%s;%s;%s\n",
            escapeCSV($row['Mannschaft']),
            escapeCSV($row['Startklasse']),
            escapeCSV($row['Rennzeit']),
            $row['Strafsekunden'] ?? 0,
            escapeCSV($row['Endzeit']),
            escapeCSV($row['Renntag']),
            escapeCSV($row['Startzeit'])
        );
    }
    
    return $csv;
}

/**
 * CSV-Export der Startzeiten
 * @return string CSV-Inhalt
 */
function exportStartTimesToCSV() {
    $results = fetchAll("SELECT 
                st.date AS 'Datum',
                st.time AS 'Uhrzeit',
                t.name AS 'Mannschaft',
                t.startklasse AS 'Startklasse',
                CASE WHEN st.team_id IS NOT NULL THEN 'Ja' ELSE 'Nein' END AS 'Gebucht'
              FROM start_times st 
              LEFT JOIN teams t ON st.team_id = t.id 
              ORDER BY st.date, st.time");
    
    if (empty($results)) {
        return "Keine Startzeiten gefunden\n";
    }
    
    $csv = "Datum;Uhrzeit;Mannschaft;Startklasse;Gebucht\n";
    
    foreach ($results as $row) {
        $csv .= sprintf(
            "%s;%s;%s;%s;%s\n",
            escapeCSV($row['Datum']),
            escapeCSV($row['Uhrzeit']),
            escapeCSV($row['Mannschaft'] ?? ''),
            escapeCSV($row['Startklasse'] ?? ''),
            escapeCSV($row['Gebucht'])
        );
    }
    
    return $csv;
}

/**
 * CSV-Export der Teams
 * @return string CSV-Inhalt
 */
function exportTeamsToCSV() {
    $results = fetchAll("SELECT 
                name AS 'Mannschaft',
                startklasse AS 'Startklasse',
                kapitaen AS 'Kapitän',
                email AS 'E-Mail'
              FROM teams 
              ORDER BY startklasse, name");
    
    if (empty($results)) {
        return "Keine Mannschaften gefunden\n";
    }
    
    $csv = "Mannschaft;Startklasse;Kapitän;E-Mail\n";
    
    foreach ($results as $row) {
        $csv .= sprintf(
            "%s;%s;%s;%s\n",
            escapeCSV($row['Mannschaft']),
            escapeCSV($row['Startklasse']),
            escapeCSV($row['Kapitän']),
            escapeCSV($row['E-Mail'])
        );
    }
    
    return $csv;
}

/**
 * String für CSV escape
 * @param string $value Wert
 * @return string Escapeter Wert
 */
function escapeCSV($value) {
    if ($value === null) {
        return '';
    }
    
    // Semikolon und Anführungszeichen escape
    $value = str_replace([';', '"'], ['\\;', '""'], $value);
    
    // Wenn der Wert Semikolon, Anführungszeichen oder Zeilenumbruch enthält, in Anführungszeichen setzen
    if (strpos($value, ';') !== false || strpos($value, '"') !== false || strpos($value, "\n") !== false) {
        $value = '"' . $value . '"';
    }
    
    return $value;
}

/**
 * Startklassen abrufen
 * @return array
 */
function getStartklassen() {
    global $startklassen;
    return $startklassen;
}

/**
 * Prüfen, ob die Reservierung freigegeben ist
 * @return bool
 */
function isReservationOpen() {
    $reservationStart = getSetting('reservation_start_date') ?? $GLOBALS['reservationStartDate'];
    $currentDate = date('Y-m-d');
    
    return $currentDate >= $reservationStart;
}

/**
 * Prüfen, ob das Rennen bereits begonnen hat
 * @return bool
 */
function isRaceStarted() {
    $saturdayDate = getSetting('race_date_saturday') ?? RACE_DATE_SATURDAY;
    $currentDate = date('Y-m-d');
    $currentTime = date('H:i');
    
    if ($currentDate > $saturdayDate) {
        return true;
    }
    
    if ($currentDate == $saturdayDate) {
        $saturdayStart = getSetting('saturday_start_time') ?? SATURDAY_START;
        return $currentTime >= $saturdayStart;
    }
    
    return false;
}

/**
 * Prüfen, ob das Rennen vorbei ist
 * @return bool
 */
function isRaceFinished() {
    $sundayDate = getSetting('race_date_sunday') ?? RACE_DATE_SUNDAY;
    $currentDate = date('Y-m-d');
    $currentTime = date('H:i');
    
    if ($currentDate > $sundayDate) {
        return true;
    }
    
    if ($currentDate == $sundayDate) {
        $sundayEnd = getSetting('sunday_end_time') ?? SUNDAY_END;
        return $currentTime >= $sundayEnd;
    }
    
    return false;
}
