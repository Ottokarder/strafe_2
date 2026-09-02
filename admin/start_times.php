<?php
/**
 * Startzeiten-Verwaltung - Neue Version
 * Kombinierte Ansicht für Startzeiten, Mannschaftszuordnung und Ergebniserfassung
 */

// SESSION MUSS GANZ AM ANFANG STEHEN - VOR ALLEN INCLUDES UND OUTPUT
session_start();

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

// Zugriff prüfen
if (!isAdmin()) {
    redirectToLogin('Sie haben keine Berechtigung für diese Seite.');
}

// Session-Timeout prüfen
checkSessionTimeout();

// Aktionen verarbeiten
$action = $_REQUEST['action'] ?? '';
$startTimeId = $_REQUEST['id'] ?? null;
$teamId = $_REQUEST['team_id'] ?? null;

// Fehler und Erfolgsmeldungen
$error = $_SESSION['error'] ?? null;
unset($_SESSION['error']);

$success = $_SESSION['success'] ?? null;
unset($_SESSION['success']);

// CSRF-Token generieren
$csrfToken = generateCSRFToken();

// ============================================
// AKTIONEN VERARBEITEN
// ============================================

// Alle Startzeiten zurücksetzen
if ($action === 'reset') {
    if (!isset($_GET['csrf_token']) || !validateCSRFToken($_GET['csrf_token'])) {
        redirectWithError('start_times.php', 'Ungültiges CSRF-Token.');
    }
    
    $count = resetAllStartTimes();
    redirectWithSuccess('start_times.php', "Alle Startzeiten zurückgesetzt. $count neue Startzeiten erstellt.");
}

// Startzeit zuweisen
if ($action === 'assign' && $startTimeId) {
    if (empty($teamId)) {
        redirectWithError('start_times.php', 'Bitte wählen Sie eine Mannschaft aus, der die Startzeit zugewiesen werden soll.');
    }
    if (!isset($_GET['csrf_token']) || !validateCSRFToken($_GET['csrf_token'])) {
        redirectWithError('start_times.php', 'Ungültiges CSRF-Token.');
    }
    
    // Prüfen, ob das Team bereits MAX_STARTS_PER_TEAM Startzeiten hat
    $team = getTeamById($teamId);
    if (!$team) {
        redirectWithError('start_times.php', 'Mannschaft nicht gefunden.');
    }
    
    $startTime = fetchOne("SELECT * FROM start_times WHERE id = ?", "i", [$startTimeId]);
    if (!$startTime) {
        redirectWithError('start_times.php', 'Startzeit nicht gefunden.');
    }
    
    if ($startTime['is_booked']) {
        redirectWithError('start_times.php', 'Diese Startzeit ist bereits gebucht.');
    }
    
    $teamStartTimes = getStartTimesForTeam($teamId);
    if (count($teamStartTimes) >= MAX_STARTS_PER_TEAM) {
        redirectWithError('start_times.php', 'Diese Mannschaft hat bereits ' . MAX_STARTS_PER_TEAM . ' Startzeiten. Maximale Anzahl erreicht.');
    }
    
    if (assignStartTimeToTeam($startTimeId, $teamId)) {
        logAudit('ASSIGN', 'start_times', $startTimeId, ['team_id' => null], ['team_id' => $teamId]);
        redirectWithSuccess('start_times.php', 'Startzeit erfolgreich zugewiesen.');
    } else {
        redirectWithError('start_times.php', 'Fehler beim Zuweisen der Startzeit.');
    }
}

// Startzeit freigeben
if ($action === 'release' && $startTimeId) {
    if (!isset($_GET['csrf_token']) || !validateCSRFToken($_GET['csrf_token'])) {
        redirectWithError('start_times.php', 'Ungültiges CSRF-Token.');
    }
    
    $startTime = fetchOne("SELECT * FROM start_times WHERE id = ?", "i", [$startTimeId]);
    if (!$startTime) {
        redirectWithError('start_times.php', 'Startzeit nicht gefunden.');
    }
    
    if (removeStartTimeFromTeam($startTimeId)) {
        logAudit('RELEASE', 'start_times', $startTimeId, ['team_id' => $startTime['team_id']], ['team_id' => null]);
        redirectWithSuccess('start_times.php', 'Startzeit erfolgreich freigegeben.');
    } else {
        redirectWithError('start_times.php', 'Fehler beim Freigeben der Startzeit.');
    }
}

// Ergebnis speichern (von Modal)
if ($action === 'save_result' && $startTimeId) {
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        redirectWithError('start_times.php', 'Ungültiges CSRF-Token.');
    }
    
    $startTime = fetchOne("SELECT * FROM start_times WHERE id = ?", "i", [$startTimeId]);
    if (!$startTime || !$startTime['team_id']) {
        redirectWithError('start_times.php', 'Startzeit nicht gefunden oder keine Mannschaft zugewiesen.');
    }
    
    $timeInput = trim($_POST['race_time'] ?? '');
    
    // Validierung: MM:SS Format
    if (!preg_match('/^([0-5]?[0-9]):[0-5][0-9]$/', $timeInput)) {
        redirectWithError('start_times.php', 'Bitte geben Sie eine gültige Zeit im Format MM:SS ein (z. B. 45:23).');
    }
    
    // MM:SS in HH:MM:SS umwandeln für die Datenbank
    list($minutes, $seconds) = explode(':', $timeInput);
    $dbTime = sprintf('%02d:%02d:%02d', 0, (int)$minutes, (int)$seconds);
    
    // Prüfen, ob bereits ein Ergebnis für diese Startzeit existiert
    $existingResult = fetchOne("SELECT id FROM results WHERE start_time_id = ?", "i", [$startTimeId]);
    
    if ($existingResult) {
        // Ergebnis aktualisieren
        $updated = execute("UPDATE results SET time = ? WHERE id = ?", "si", [$dbTime, $existingResult['id']]);
        if ($updated > 0) {
            logAudit('UPDATE', 'results', $existingResult['id'], ['time' => $existingResult['time']], ['time' => $dbTime]);
            redirectWithSuccess('start_times.php', 'Rennergebnis erfolgreich aktualisiert.');
        } else {
            // Debug-Info
            $errorMsg = 'Fehler beim Aktualisieren des Ergebnisses. ID: ' . $existingResult['id'] . ', Zeit: ' . $dbTime;
            error_log($errorMsg);
            redirectWithError('start_times.php', $errorMsg);
        }
    } else {
        // Neues Ergebnis erstellen - direkte Abfrage ohne Prepared Statement
        $conn = getDBConnection();
        $sql = "INSERT INTO results (team_id, start_time_id, time) VALUES (" . (int)$startTime['team_id'] . ", " . (int)$startTimeId . ", '" . $conn->real_escape_string($dbTime) . "')";
        $result = $conn->query($sql);
        
        if ($result === true) {
            $resultId = $conn->insert_id;
            logAudit('INSERT', 'results', $resultId, [], ['team_id' => $startTime['team_id'], 'start_time_id' => $startTimeId, 'time' => $dbTime]);
            redirectWithSuccess('start_times.php', 'Rennergebnis erfolgreich gespeichert.');
        } else {
            // Debug-Info
            $errorMsg = 'Fehler beim Speichern des Ergebnisses. SQL: ' . $sql . ' | MySQL-Fehler: ' . $conn->error;
            error_log($errorMsg);
            redirectWithError('start_times.php', 'Datenbankfehler: ' . $conn->error);
        }
    }
}

// ============================================
// DATEN ABRUFEN
// ============================================

// Alle Teams abrufen (sortiert nach Eingabezeitpunkt, neueste zuerst)
$teams = fetchAll("SELECT * FROM teams ORDER BY created_at DESC");

// Alle Startzeiten abrufen
$startTimes = fetchAll("SELECT st.*, t.name AS team_name, t.startklasse, t.id AS team_id, 
                              r.time AS race_time, r.id AS result_id
                       FROM start_times st 
                       LEFT JOIN teams t ON st.team_id = t.id 
                       LEFT JOIN results r ON st.id = r.start_time_id 
                       ORDER BY st.date, st.time");

// Nach Datum gruppieren
$startTimesByDate = [];
foreach ($startTimes as $st) {
    $date = $st['date'];
    if (!isset($startTimesByDate[$date])) {
        $startTimesByDate[$date] = [];
    }
    $startTimesByDate[$date][] = $st;
}

// Alle Daten für die Anzeige
$allDates = array_keys($startTimesByDate);
sort($allDates);

?>
<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="container">
    <div class="page-header">
        <h1>Startzeiten & Rennergebnisse</h1>
        <div class="header-actions">
            <a href="start_times.php?action=reset&csrf_token=<?php echo $csrfToken; ?>" 
               class="btn btn-warning" 
               onclick="return confirm('Alle Startzeiten zurücksetzen? Alle Zuweisungen und Ergebnisse gehen verloren!')">
                Alle Startzeiten zurücksetzen
            </a>
            <a href="teams.php" class="btn btn-primary">Neue Mannschaft anlegen</a>
        </div>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-error">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success">
            <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <!-- ============================================
         STARTZEITEN-LISTE
         ============================================ -->
    <div class="card">
        <div class="card-header">
            <h3>Startzeiten nach Datum</h3>
            <p class="card-subtitle">Klicken Sie auf "Mannschaft zuordnen" um eine Mannschaft auszuwählen, oder auf "Ergebnis eingeben" um die Rennzeit zu erfassen.</p>
        </div>
        <div class="card-body">
            <?php if (empty($startTimesByDate)): ?>
                <p>Keine Startzeiten gefunden. <a href="start_times.php?action=reset&csrf_token=<?php echo $csrfToken; ?>">Startzeiten zurücksetzen</a></p>
            <?php else: ?>
                <?php foreach ($startTimesByDate as $date => $times): ?>
                    <div class="start-time-date-section" style="margin-bottom: 2rem;">
                        <h4 style="background: #f5f5f5; padding: 0.5rem 1rem; border-radius: 4px; margin-bottom: 1rem;">
                            <?php echo htmlspecialchars($date); ?>
                        </h4>
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th style="width: 100px;">Uhrzeit</th>
                                    <th style="width: 250px;">Mannschaft</th>
                                    <th style="width: 150px;">Startklasse</th>
                                    <th style="width: 120px;">Rennzeit</th>
                                    <th style="width: 200px;">Aktionen</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($times as $st): ?>
                                    <tr class="<?php echo $st['is_booked'] ? 'booked' : 'free'; ?>" 
                                        style="<?php echo !$st['is_booked'] ? 'background: #fff3cd;' : ''; ?>">
                                        <td><strong><?php echo htmlspecialchars($st['time']); ?></strong></td>
                                        <td>
                                            <?php if ($st['team_id']): ?>
                                                <?php echo htmlspecialchars($st['team_name']); ?>
                                            <?php else: ?>
                                                <span style="color: #666;">Frei</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php echo $st['team_id'] ? htmlspecialchars($st['startklasse'] ?? '') : '<span style="color: #666;">-</span>'; ?>
                                        </td>
                                        <td>
                                            <?php if ($st['race_time']): ?>
                                                <span class="badge badge-success">
                                                    <?php 
                                                    // HH:MM:SS in MM:SS umwandeln für die Anzeige
                                                    $raceTime = $st['race_time'];
                                                    $parts = explode(':', $raceTime);
                                                    if (count($parts) === 3 && $parts[0] == '00') {
                                                        echo htmlspecialchars($parts[1] . ':' . $parts[2]);
                                                    } else {
                                                        echo htmlspecialchars($raceTime);
                                                    }
                                                    ?>
                                                </span>
                                            <?php else: ?>
                                                <span style="color: #666;">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!$st['is_booked']): ?>
                                                <!-- Mannschaft zuordnen Button -->
                                                <button class="btn btn-small btn-primary assign-team-btn"
                                                        data-start-time-id="<?php echo $st['id']; ?>"
                                                        data-date="<?php echo htmlspecialchars($st['date']); ?>"
                                                        data-time="<?php echo htmlspecialchars($st['time']); ?>">
                                                    Mannschaft zuordnen
                                                </button>
                                            <?php else: ?>
                                                <!-- Ergebnis eingeben Button -->
                                                <button class="btn btn-small btn-success enter-result-btn"
                                                        data-start-time-id="<?php echo $st['id']; ?>"
                                                        data-team-id="<?php echo $st['team_id']; ?>"
                                                        data-team-name="<?php echo htmlspecialchars($st['team_name']); ?>"
                                                        data-existing-time="<?php echo htmlspecialchars($st['race_time'] ?? ''); ?>">
                                                    Ergebnis eingeben
                                                </button>
                                                
                                                <!-- Freigeben Button -->
                                                <a href="start_times.php?action=release&id=<?php echo $st['id']; ?>&csrf_token=<?php echo $csrfToken; ?>"
                                                   class="btn btn-small btn-warning"
                                                   onclick="return confirm('Möchten Sie diese Startzeit wirklich freigeben?')">
                                                    Freigeben
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- ============================================
         MODAL: MANNSCHAFT ZUORDNEN
         ============================================ -->
    <div id="assignTeamModal" class="modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
        <div class="modal-content" style="background: white; margin: 5% auto; padding: 2rem; border-radius: 8px; width: 90%; max-width: 600px; max-height: 80vh; overflow-y: auto;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h2 style="margin: 0;">Mannschaft auswählen</h2>
                <button class="close-modal" style="background: none; border: none; font-size: 1.5rem; cursor: pointer;">&times;</button>
            </div>
            
            <div style="margin-bottom: 1rem;">
                <label for="teamFilter" style="display: block; margin-bottom: 0.5rem; font-weight: bold;">Mannschaft filtern:</label>
                <input type="text" id="teamFilter" placeholder="Name eingeben..." 
                       style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px; font-size: 1rem;">
            </div>
            
            <div id="teamList" style="max-height: 300px; overflow-y: auto; border: 1px solid #eee; border-radius: 4px;">
                <?php foreach ($teams as $team): ?>
                    <div class="team-item" 
                         data-team-id="<?php echo $team['id']; ?>"
                         data-team-name="<?php echo htmlspecialchars($team['name']); ?>"
                         style="padding: 0.75rem; border-bottom: 1px solid #eee; cursor: pointer;">
                        <strong><?php echo htmlspecialchars($team['name']); ?></strong>
                        <span style="color: #666; margin-left: 1rem;">(<?php echo htmlspecialchars($team['startklasse']); ?>)</span>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div style="margin-top: 1.5rem; text-align: right;">
                <button class="btn btn-secondary close-modal" style="margin-right: 1rem;">Abbrechen</button>
            </div>
            
            <input type="hidden" id="currentStartTimeId" value="">
        </div>
    </div>

    <!-- ============================================
         MODAL: RENNERGEBNIS EINGEBEN
         ============================================ -->
    <div id="enterResultModal" class="modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
        <div class="modal-content" style="background: white; margin: 10% auto; padding: 2rem; border-radius: 8px; width: 90%; max-width: 500px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h2 style="margin: 0;">Rennergebnis eingeben</h2>
                <button class="close-modal" style="background: none; border: none; font-size: 1.5rem; cursor: pointer;">&times;</button>
            </div>
            
            <form id="resultForm" method="POST" action="start_times.php">
                <input type="hidden" name="action" value="save_result">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                <input type="hidden" name="id" id="resultStartTimeId" value="">
                
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: bold;">Mannschaft:</label>
                    <div id="resultTeamName" style="padding: 0.5rem; background: #f5f5f5; border-radius: 4px;"></div>
                </div>
                
                <div style="margin-bottom: 1.5rem;">
                    <label for="race_time" style="display: block; margin-bottom: 0.5rem; font-weight: bold;">Rennzeit (MM:SS):</label>
                    <input type="text" id="race_time" name="race_time" 
                           placeholder="MM:SS (z. B. 45:23)" 
                           pattern="[0-5]?[0-9]:[0-5][0-9]"
                           style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px; font-size: 1rem;"
                           required>
                    <small style="color: #666; display: block; margin-top: 0.5rem;">
                        Bitte geben Sie die Zeit im Format Minuten:Sekunden ein (z. B. 45:23).
                    </small>
                </div>
                
                <div style="text-align: right;">
                    <button type="button" class="btn btn-secondary close-modal" style="margin-right: 1rem;">Abbrechen</button>
                    <button type="submit" class="btn btn-primary">Speichern</button>
                </div>
            </form>
        </div>
    </div>

    <style>
        .team-item:hover {
            background-color: #e9ecef;
        }
        .booked {
            background-color: #d4edda;
        }
        .free {
            background-color: #fff3cd;
        }
        .badge-success {
            background-color: #28a745;
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.875rem;
        }
        .modal {
            animation: fadeIn 0.2s;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
    </style>

    <script>
        // ============================================
        // MODAL FUNKTIONEN
        // ============================================
        
        // Modal öffnen
        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'block';
        }
        
        // Modal schließen
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        // Alle Close-Buttons
        document.querySelectorAll('.close-modal').forEach(function(btn) {
            btn.addEventListener('click', function() {
                // Finde das Eltern-Modal
                let modal = this.closest('.modal');
                if (modal) {
                    modal.style.display = 'none';
                }
            });
        });
        
        // Modal schließt beim Klick außerhalb
        document.querySelectorAll('.modal').forEach(function(modal) {
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    modal.style.display = 'none';
                }
            });
        });
        
        // ============================================
        // MANNSCHAFT ZUORDNEN MODAL
        // ============================================
        
        let currentStartTimeIdForAssign = null;
        
        document.querySelectorAll('.assign-team-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                currentStartTimeIdForAssign = this.dataset.startTimeId;
                document.getElementById('currentStartTimeId').value = currentStartTimeIdForAssign;
                
                // Modal öffnen
                openModal('assignTeamModal');
                
                // Filter zurücksetzen
                document.getElementById('teamFilter').value = '';
                document.querySelectorAll('.team-item').forEach(function(item) {
                    item.style.display = 'block';
                });
            });
        });
        
        // Team-Filter
        document.getElementById('teamFilter').addEventListener('input', function() {
            const filter = this.value.toLowerCase();
            document.querySelectorAll('.team-item').forEach(function(item) {
                const teamName = item.dataset.teamName.toLowerCase();
                if (teamName.includes(filter)) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        });
        
        // Team auswählen
        document.querySelectorAll('.team-item').forEach(function(item) {
            item.addEventListener('click', function() {
                const teamId = this.dataset.teamId;
                const startTimeId = document.getElementById('currentStartTimeId').value;
                
                // Weiterleitung zur Zuweisung
                window.location.href = 'start_times.php?action=assign&id=' + startTimeId + '&team_id=' + teamId + '&csrf_token=<?php echo $csrfToken; ?>';
            });
        });
        
        // ============================================
        // ERGEBNIS EINGEBEN MODAL
        // ============================================
        
        document.querySelectorAll('.enter-result-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const startTimeId = this.dataset.startTimeId;
                const teamName = this.dataset.teamName;
                const existingTime = this.dataset.existingTime;
                
                document.getElementById('resultStartTimeId').value = startTimeId;
                document.getElementById('resultTeamName').textContent = teamName;
                
                // Vorhandene Zeit anzeigen (falls vorhanden)
                if (existingTime) {
                    // HH:MM:SS in MM:SS umwandeln
                    const parts = existingTime.split(':');
                    if (parts.length === 3 && parts[0] === '00') {
                        document.getElementById('race_time').value = parts[1] + ':' + parts[2];
                    } else {
                        document.getElementById('race_time').value = existingTime;
                    }
                } else {
                    document.getElementById('race_time').value = '';
                }
                
                openModal('enterResultModal');
            });
        });
        
        // Zeit-Validierung im Formular
        document.getElementById('resultForm').addEventListener('submit', function(e) {
            const timeInput = document.getElementById('race_time').value;
            const timeRegex = /^([0-5]?[0-9]):[0-5][0-9]$/;
            
            if (!timeRegex.test(timeInput)) {
                alert('Bitte geben Sie eine gültige Zeit im Format MM:SS ein (z. B. 45:23).');
                e.preventDefault();
                return false;
            }
            
            return true;
        });
        
        // ESC-Taste zum Schließen der Modals
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal').forEach(function(modal) {
                    if (modal.style.display === 'block') {
                        modal.style.display = 'none';
                    }
                });
            }
        });
    </script>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
