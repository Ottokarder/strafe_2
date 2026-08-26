<?php
/**
 * Startzeiten-Verwaltung
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

// Zugriff prüfen
if (!isAdmin()) {
    redirectToLogin('Sie haben keine Berechtigung für diese Seite.');
}

// Session-Timeout prüfen
checkSessionTimeout();

// Aktionen verarbeiten
$action = $_GET['action'] ?? '';
$startTimeId = $_GET['id'] ?? null;
$teamId = $_GET['team_id'] ?? null;

// Fehler und Erfolgsmeldungen
$error = $_SESSION['error'] ?? null;
unset($_SESSION['error']);

$success = $_SESSION['success'] ?? null;
unset($_SESSION['success']);

// CSRF-Token generieren
$csrfToken = generateCSRFToken();

// Alle Startzeiten zurücksetzen
if ($action === 'reset') {
    if (!isset($_GET['csrf_token']) || !validateCSRFToken($_GET['csrf_token'])) {
        redirectWithError('start_times.php', 'Ungültiges CSRF-Token.');
    }
    
    $count = resetAllStartTimes();
    redirectWithSuccess('start_times.php', "Alle Startzeiten zurückgesetzt. $count neue Startzeiten erstellt.");
}

// Startzeit zuweisen
if ($action === 'assign' && $startTimeId && $teamId) {
    if (!isset($_GET['csrf_token']) || !validateCSRFToken($_GET['csrf_token'])) {
        redirectWithError('start_times.php', 'Ungültiges CSRF-Token.');
    }
    
    // Prüfen, ob das Team bereits 3 Startzeiten hat
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

// Alle Teams abrufen
$teams = getAllTeams();

// Alle Startzeiten abrufen
$startTimes = getAllStartTimes();

// Nach Datum gruppieren
$startTimesByDate = [];
foreach ($startTimes as $st) {
    $date = $st['date'];
    if (!isset($startTimesByDate[$date])) {
        $startTimesByDate[$date] = [];
    }
    $startTimesByDate[$date][] = $st;
}

// Freie Startzeiten abrufen
$freeStartTimes = getFreeStartTimes();

// Team-Filter
$selectedTeamId = $teamId;

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Startzeiten-Verwaltung - <?php echo APP_TITLE; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="admin-page">
    <?php include __DIR__ . '/../includes/header.php'; ?>
    
    <div class="container">
        <div class="page-header">
            <h1>Startzeiten-Verwaltung</h1>
            <div class="header-actions">
                <a href="start_times.php?action=reset&csrf_token=<?php echo $csrfToken; ?>" 
                   class="btn btn-warning" 
                   onclick="return confirm('Alle Startzeiten zurücksetzen? Alle Zuweisungen gehen verloren!')">
                    Alle Startzeiten zurücksetzen
                </a>
                <?php if ($selectedTeamId): ?>
                    <a href="start_times.php" class="btn btn-secondary">Filter zurücksetzen</a>
                <?php endif; ?>
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
        
        <?php if ($selectedTeamId && $team = getTeamById($selectedTeamId)): ?>
            <div class="card">
                <div class="card-header">
                    <h3>Startzeiten für: <?php echo htmlspecialchars($team['name']); ?> (<?php echo htmlspecialchars($team['startklasse']); ?>)</h3>
                </div>
                <div class="card-body">
                    <p><strong>Kapitän:</strong> <?php echo htmlspecialchars($team['kapitaen']); ?></p>
                    <p><strong>Aktuelle Startzeiten:</strong> 
                        <?php 
                        $teamStartTimes = getStartTimesForTeam($selectedTeamId);
                        if (empty($teamStartTimes)) {
                            echo "Keine";
                        } else {
                            echo implode(', ', array_map(function($st) {
                                return htmlspecialchars($st['date'] . ' ' . $st['time']);
                            }, $teamStartTimes));
                        }
                        ?>
                    </p>
                    <p><strong>Verfügbare Slots:</strong> <?php echo count($teamStartTimes); ?> / <?php echo MAX_STARTS_PER_TEAM; ?></p>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <h3>Startzeiten nach Datum</h3>
            </div>
            <div class="card-body">
                <?php if (empty($startTimesByDate)): ?>
                    <p>Keine Startzeiten gefunden. <a href="start_times.php?action=reset&csrf_token=<?php echo $csrfToken; ?>">Startzeiten zurücksetzen</a></p>
                <?php else: ?>
                    <?php foreach ($startTimesByDate as $date => $times): ?>
                        <div class="start-time-date">
                            <h4><?php echo htmlspecialchars($date); ?></h4>
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Uhrzeit</th>
                                        <th>Mannschaft</th>
                                        <th>Startklasse</th>
                                        <th>Status</th>
                                        <th>Aktionen</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($times as $st): ?>
                                        <tr class="<?php echo $st['is_booked'] ? 'booked' : 'free'; ?>">
                                            <td><?php echo htmlspecialchars($st['time']); ?></td>
                                            <td><?php echo $st['team_name'] ? htmlspecialchars($st['team_name']) : 'Frei'; ?></td>
                                            <td><?php echo $st['startklasse'] ? htmlspecialchars($st['startklasse']) : ''; ?></td>
                                            <td><?php echo $st['is_booked'] ? 'Gebucht' : 'Frei'; ?></td>
                                            <td>
                                                <?php if ($st['is_booked']): ?>
                                                    <a href="start_times.php?action=release&id=<?php echo $st['id']; ?>&csrf_token=<?php echo $csrfToken; ?>" 
                                                       class="btn btn-small btn-warning" 
                                                       onclick="return confirm('Startzeit freigeben?')">Freigeben</a>
                                                <?php else: ?>
                                                    <a href="start_times.php?action=assign&id=<?php echo $st['id']; ?>&team_id=<?php echo $selectedTeamId ?: ''; ?>&csrf_token=<?php echo $csrfToken; ?>" 
                                                       class="btn btn-small btn-success">Zuweisen</a>
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
        
        <?php if (!$selectedTeamId): ?>
            <div class="card">
                <div class="card-header">
                    <h3>Freie Startzeiten (<?php echo count($freeStartTimes); ?>)</h3>
                </div>
                <div class="card-body">
                    <?php if (empty($freeStartTimes)): ?>
                        <p>Keine freien Startzeiten verfügbar.</p>
                    <?php else: ?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Datum</th>
                                    <th>Uhrzeit</th>
                                    <th>Aktion</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($freeStartTimes as $st): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($st['date']); ?></td>
                                        <td><?php echo htmlspecialchars($st['time']); ?></td>
                                        <td>
                                            <a href="start_times.php?action=assign&id=<?php echo $st['id']; ?>&csrf_token=<?php echo $csrfToken; ?>" 
                                               class="btn btn-small btn-success">Zuweisen</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if ($selectedTeamId): ?>
            <div class="card">
                <div class="card-header">
                    <h3>Startzeit zuweisen</h3>
                </div>
                <div class="card-body">
                    <form method="GET" action="start_times.php">
                        <input type="hidden" name="action" value="assign">
                        <input type="hidden" name="team_id" value="<?php echo $selectedTeamId; ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                        
                        <div class="form-group">
                            <label for="start_time_id">Verfügbare Startzeit auswählen:</label>
                            <select id="start_time_id" name="id" required>
                                <option value="">-- Bitte wählen --</option>
                                <?php foreach ($freeStartTimes as $st): ?>
                                    <option value="<?php echo $st['id']; ?>">
                                        <?php echo htmlspecialchars($st['date'] . ' ' . $st['time']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Startzeit zuweisen</button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
