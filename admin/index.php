<?php
/**
 * Admin-Dashboard
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

// Session starten, falls noch nicht geschehen
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Zugriff prüfen
if (!isAdmin()) {
    redirectToLogin('Sie haben keine Berechtigung für diese Seite.');
}

// Session-Timeout prüfen
checkSessionTimeout();

// Aktuelle Benutzerdaten
$user = getCurrentUser();

// Statistiken abrufen
$teamCount = fetchOne("SELECT COUNT(*) as count FROM teams");
$startTimeCount = fetchOne("SELECT COUNT(*) as count FROM start_times WHERE is_booked = TRUE");
$freeStartTimes = fetchOne("SELECT COUNT(*) as count FROM start_times WHERE is_booked = FALSE");

// Letzte Änderungen
$recentChanges = fetchAll("SELECT al.*, u.username FROM audit_log al LEFT JOIN users u ON al.user_id = u.id ORDER BY al.created_at DESC LIMIT 10");

// Nächste Startzeiten
$upcomingStartTimes = fetchAll("SELECT st.*, t.name AS team_name, t.startklasse FROM start_times st LEFT JOIN teams t ON st.team_id = t.id WHERE st.date >= CURDATE() OR (st.date = CURDATE() AND st.time >= CURTIME()) ORDER BY st.date, st.time LIMIT 10");

// Fehler und Erfolgsmeldungen
$error = $_SESSION['error'] ?? null;
unset($_SESSION['error']);

$success = $_SESSION['success'] ?? null;
unset($_SESSION['success']);

?>
<?php include __DIR__ . '/../includes/header.php'; ?>

    
    <div class="container">
        <div class="admin-header">
            <h1>Admin Dashboard</h1>
            <p>Willkommen, <?php echo htmlspecialchars($user['username']); ?>!</p>
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
        
        <div class="dashboard-grid">
            <div class="card">
                <div class="card-header">
                    <h3>Mannschaften</h3>
                </div>
                <div class="card-body">
                    <p class="stat-number"><?php echo $teamCount['count']; ?></p>
                    <p>registrierte Mannschaften</p>
                    <a href="/admin/teams.php" class="btn btn-primary">Verwalten</a>
                </div>
            </div>
            

            <div class="card">
                <div class="card-header">
                    <h3>Startzeiten</h3>
                </div>
                <div class="card-body">
                    <p class="stat-number"><?php echo $startTimeCount['count']; ?></p>
                    <p>gebuchte Startzeiten</p>
                    <p class="stat-small"><?php echo $freeStartTimes['count']; ?> frei</p>
                    <a href="/admin/start_times.php" class="btn btn-primary">Verwalten</a>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h3>Mannschaften ohne Zuordnung</h3>
                </div>
                <div class="card-body">
                    <?php 
                    $teamsWithoutStart = fetchAll("SELECT t.name, t.id, t.startklasse FROM teams t LEFT JOIN start_times st ON t.id = st.team_id WHERE st.team_id IS NULL");
                    $teamsWithoutClass = fetchAll("SELECT t.name, t.id FROM teams t WHERE t.startklasse IS NULL OR t.startklasse = ''");
                    
                    $countWithoutStart = count($teamsWithoutStart);
                    $countWithoutClass = count($teamsWithoutClass);
                    
                    if ($countWithoutStart === 0 && $countWithoutClass === 0):
                    ?>
                        <p class="stat-number">0</p>
                        <p>Alle Mannschaften haben Startzeiten und Startklassen</p>
                    <?php else: ?>
                        <p><strong>Ohne Startzeit:</strong> <?php echo $countWithoutStart; ?></p>
                        <p><strong>Ohne Startklasse:</strong> <?php echo $countWithoutClass; ?></p>
                        <p style="margin-top: 1rem;">Mannschaften ohne Zuordnung:</p>
                        <ul style="margin-top: 0.5rem; padding-left: 1.5rem;">
                            <?php foreach ($teamsWithoutStart as $team): ?>
                                <li><?php echo htmlspecialchars($team['name']); ?> (ohne Startzeit)<?php echo $team['startklasse'] ? ' (Startklasse: ' . htmlspecialchars($team['startklasse']) . ')' : ' (ohne Startklasse)'; ?></li>
                            <?php endforeach; ?>
                            <?php foreach ($teamsWithoutClass as $team):
                                $isAlreadyListed = false;
                                foreach ($teamsWithoutStart as $t) {
                                    if ($t['id'] == $team['id']) {
                                        $isAlreadyListed = true;
                                        break;
                                    }
                                }
                                if (!$isAlreadyListed):
                            ?>
                                <li><?php echo htmlspecialchars($team['name']); ?> (ohne Startklasse)</li>
                            <?php endif; endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h3>Ergebnis-Statistik</h3>
                </div>
                <div class="card-body">
                    <?php 
                    $teamsWithResults = fetchOne("SELECT COUNT(DISTINCT team_id) as count FROM results");
                    $teamsWithoutResults = fetchOne("SELECT COUNT(*) as count FROM teams t LEFT JOIN results r ON t.id = r.team_id WHERE r.team_id IS NULL");
                    ?>
                    <p><strong>Mit Ergebnissen:</strong> <?php echo $teamsWithResults['count']; ?></p>
                    <p><strong>Ohne Ergebnisse:</strong> <?php echo $teamsWithoutResults['count']; ?></p>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h3>Schnellaktionen</h3>
                </div>
                <div class="card-body">
                    <a href="/admin/teams.php?action=add" class="btn btn-success">Neue Mannschaft</a>
                    <a href="/admin/settings.php" class="btn btn-warning" onclick="return confirm('Alle Startzeiten zurücksetzen?')">Startzeiten zurücksetzen</a>
                    <a href="/admin/export.php" class="btn btn-info">Daten exportieren</a>
                    <a href="/admin/users.php" class="btn btn-danger">Benutzer verwalten</a>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="column">
                <div class="card">
                    <div class="card-header">
                        <h3>Nächste Startzeiten</h3>
                    </div>
                    <div class="card-body">
                        <?php if (empty($upcomingStartTimes)): ?>
                            <p>Keine anstehenden Startzeiten.</p>
                        <?php else: ?>
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Datum</th>
                                        <th>Uhrzeit</th>
                                        <th>Mannschaft</th>
                                        <th>Startklasse</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($upcomingStartTimes as $st): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($st['date']); ?></td>
                                            <td><?php echo htmlspecialchars($st['time']); ?></td>
                                            <td><?php echo htmlspecialchars($st['team_name'] ?? 'Frei'); ?></td>
                                            <td><?php echo htmlspecialchars($st['startklasse'] ?? ''); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="column">
                <div class="card">
                    <div class="card-header">
                        <h3>Letzte Änderungen</h3>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recentChanges)): ?>
                            <p>Keine Änderungen gefunden.</p>
                        <?php else: ?>
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Zeitpunkt</th>
                                        <th>Benutzer</th>
                                        <th>Aktion</th>
                                        <th>Tabelle</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentChanges as $change): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($change['created_at']); ?></td>
                                            <td><?php echo htmlspecialchars($change['username'] ?? 'System'); ?></td>
                                            <td><?php echo htmlspecialchars($change['action']); ?></td>
                                            <td><?php echo htmlspecialchars($change['table_name'] ?? ''); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include __DIR__ . '/../includes/footer.php'; ?>
