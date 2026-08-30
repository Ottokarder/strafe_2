<?php
/**
 * Admin-Dashboard
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

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
$resultCount = fetchOne("SELECT COUNT(*) as count FROM results");
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
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo APP_TITLE; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="admin-page">
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
                    <a href="teams.php" class="btn btn-primary">Verwalten</a>
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
                    <a href="start_times.php" class="btn btn-primary">Verwalten</a>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h3>Ergebnisse</h3>
                </div>
                <div class="card-body">
                    <p class="stat-number"><?php echo $resultCount['count']; ?></p>
                    <p>erfasste Ergebnisse</p>
                    <a href="results.php" class="btn btn-primary">Verwalten</a>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h3>Schnellaktionen</h3>
                </div>
                <div class="card-body">
                    <a href="/admin/teams.php?action=add" class="btn btn-success">Neue Mannschaft</a>
                    <a href="start_times.php?action=reset" class="btn btn-warning" onclick="return confirm('Alle Startzeiten zurücksetzen?')">Startzeiten zurücksetzen</a>
                    <a href="export.php" class="btn btn-info">Daten exportieren</a>
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
</body>
</html>
