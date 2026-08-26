<?php
/**
 * Öffentliche Startseite
 */

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

// Prüfen, ob die Reservierung freigegeben ist
$reservationOpen = isReservationOpen();

// Prüfen, ob das Rennen begonnen hat
$raceStarted = isRaceStarted();

// Prüfen, ob das Rennen vorbei ist
$raceFinished = isRaceFinished();

// Startzeiten abrufen (nur gebuchte, wenn Rennen begonnen hat)
if ($raceStarted) {
    $startTimes = fetchAll("SELECT st.date, st.time, t.name AS team_name, t.startklasse 
                             FROM start_times st 
                             JOIN teams t ON st.team_id = t.id 
                             WHERE st.is_booked = TRUE 
                             ORDER BY st.date, st.time");
} else {
    // Vor dem Rennen: nur gebuchte Startzeiten anzeigen (ohne E-Mails)
    $startTimes = fetchAll("SELECT st.date, st.time, t.name AS team_name, t.startklasse 
                             FROM start_times st 
                             LEFT JOIN teams t ON st.team_id = t.id 
                             WHERE st.is_booked = TRUE 
                             ORDER BY st.date, st.time");
}

// Freie Startzeiten (nur wenn Reservierung offen und Rennen nicht begonnen hat)
$freeStartTimes = [];
if ($reservationOpen && !$raceStarted) {
    $freeStartTimes = fetchAll("SELECT date, time FROM start_times WHERE is_booked = FALSE ORDER BY date, time");
}

// Ergebnisse abrufen (nur wenn Rennen begonnen hat)
$results = [];
if ($raceStarted) {
    $results = getAllResults();
    
    // Nach Startklasse gruppieren
    $resultsByClass = [];
    foreach ($results as $r) {
        $class = $r['startklasse'];
        if (!isset($resultsByClass[$class])) {
            $resultsByClass[$class] = [];
        }
        $resultsByClass[$class][] = $r;
    }
}

// Startklassen abrufen
$startklassen = getStartklassen();

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
    <title><?php echo APP_TITLE; ?> - Startzeiten und Ergebnisse</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <meta http-equiv="refresh" content="60"> <!-- Auto-Refresh alle 60 Sekunden -->
</head>
<body class="public-page">
    <?php include __DIR__ . '/includes/header.php'; ?>
    
    <div class="container">
        <div class="hero">
            <h1>Kanadierrennen CJD Kaltenstein</h1>
            <p class="hero-subtitle">
                <?php 
                if ($raceFinished) {
                    echo "Das Rennen ist beendet. Hier sind die finalen Ergebnisse.";
                } elseif ($raceStarted) {
                    echo "Das Rennen läuft! Hier sind die aktuellen Ergebnisse in Echtzeit.";
                } elseif ($reservationOpen) {
                    echo "Die Reservierung der Startzeiten ist jetzt möglich.";
                } else {
                    echo "Willkommen beim Kanadierrennen! Die Reservierung beginnt 6 Wochen vor dem Rennen.";
                }
                ?>
            </p>
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
        
        <?php if ($raceStarted): ?>
            <!-- Ergebnisse anzeigen -->
            <div class="section">
                <h2>Ergebnisse</h2>
                <p>Die Ergebnisse werden in Echtzeit aktualisiert.</p>
                
                <?php if (empty($results)): ?>
                    <div class="card">
                        <div class="card-body">
                            <p>Noch keine Ergebnisse erfasst.</p>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($resultsByClass as $class => $classResults): ?>
                        <div class="card">
                            <div class="card-header">
                                <h3><?php echo htmlspecialchars($class); ?></h3>
                            </div>
                            <div class="card-body">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Platz</th>
                                            <th>Mannschaft</th>
                                            <th>Rennzeit</th>
                                            <th>Endzeit</th>
                                            <th>Renntag</th>
                                            <th>Startzeit</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        // Sortieren nach Endzeit
                                        usort($classResults, function($a, $b) {
                                            return strtotime($a['final_time']) <=> strtotime($b['final_time']);
                                        });
                                        
                                        foreach ($classResults as $index => $r): ?>
                                            <tr>
                                                <td><?php echo $index + 1; ?></td>
                                                <td><?php echo htmlspecialchars($r['team_name']); ?></td>
                                                <td><?php echo htmlspecialchars($r['time']); ?></td>
                                                <td><?php echo htmlspecialchars($r['final_time']); ?></td>
                                                <td><?php echo htmlspecialchars($r['race_date'] ?? ''); ?></td>
                                                <td><?php echo htmlspecialchars($r['start_time'] ?? ''); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <!-- Startzeiten anzeigen -->
        <div class="section">
            <h2>Startzeiten</h2>
            
            <?php if ($reservationOpen && !$raceStarted): ?>
                <p>Die folgenden Startzeiten sind bereits reserviert. Mannschaftskapitäne können freie Startzeiten beim Administrator reservieren.</p>
            <?php else: ?>
                <p>Hier sehen Sie die Startzeiten für das Rennen.</p>
            <?php endif; ?>
            
            <?php if (empty($startTimes) && empty($freeStartTimes)): ?>
                <div class="card">
                    <div class="card-body">
                        <p>Noch keine Startzeiten verfügbar.</p>
                    </div>
                </div>
            <?php else: ?>
                <?php 
                // Startzeiten nach Datum gruppieren
                $startTimesByDate = [];
                foreach ($startTimes as $st) {
                    $date = $st['date'];
                    if (!isset($startTimesByDate[$date])) {
                        $startTimesByDate[$date] = [];
                    }
                    $startTimesByDate[$date][] = $st;
                }
                
                // Freie Startzeiten nach Datum gruppieren
                $freeStartTimesByDate = [];
                foreach ($freeStartTimes as $st) {
                    $date = $st['date'];
                    if (!isset($freeStartTimesByDate[$date])) {
                        $freeStartTimesByDate[$date] = [];
                    }
                    $freeStartTimesByDate[$date][] = $st;
                }
                
                // Alle Daten zusammenführen
                $allDates = array_unique(array_merge(array_keys($startTimesByDate), array_keys($freeStartTimesByDate)));
                sort($allDates);
                
                foreach ($allDates as $date): ?>
                    <div class="card">
                        <div class="card-header">
                            <h3><?php echo htmlspecialchars($date); ?></h3>
                        </div>
                        <div class="card-body">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Uhrzeit</th>
                                        <th>Mannschaft</th>
                                        <th>Startklasse</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    // Gebuchte Startzeiten für diesen Tag
                                    if (isset($startTimesByDate[$date])) {
                                        foreach ($startTimesByDate[$date] as $st) {
                                            echo '<tr class="booked">';
                                            echo '<td>' . htmlspecialchars($st['time']) . '</td>';
                                            echo '<td>' . htmlspecialchars($st['team_name'] ?? '') . '</td>';
                                            echo '<td>' . htmlspecialchars($st['startklasse'] ?? '') . '</td>';
                                            echo '<td>Gebucht</td>';
                                            echo '</tr>';
                                        }
                                    }
                                    
                                    // Freie Startzeiten für diesen Tag (nur wenn Reservierung offen)
                                    if ($reservationOpen && !$raceStarted && isset($freeStartTimesByDate[$date])) {
                                        foreach ($freeStartTimesByDate[$date] as $st) {
                                            echo '<tr class="free">';
                                            echo '<td>' . htmlspecialchars($st['time']) . '</td>';
                                            echo '<td colspan="2">Frei für Reservierung</td>';
                                            echo '<td>Frei</td>';
                                            echo '</tr>';
                                        }
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
            
            <?php if ($reservationOpen && !$raceStarted && !empty($freeStartTimes)): ?>
                <div class="card">
                    <div class="card-header">
                        <h3>Freie Startzeiten (<?php echo count($freeStartTimes); ?>)</h3>
                    </div>
                    <div class="card-body">
                        <p>Mannschaftskapitäne können sich beim Administrator melden, um eine der folgenden freien Startzeiten zu reservieren:</p>
                        
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Datum</th>
                                    <th>Uhrzeit</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($freeStartTimes as $st): ?>
                                    <tr class="free">
                                        <td><?php echo htmlspecialchars($st['date']); ?></td>
                                        <td><?php echo htmlspecialchars($st['time']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        
                        <p class="note">
                            <strong>Hinweis:</strong> Startzeiten werden in 10-Minuten-Intervallen angeboten.<br>
                            Samstag: 14:00–18:00 Uhr (24 Startplätze)<br>
                            Sonntag: 11:00–16:00 Uhr (30 Startplätze)
                        </p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Mannschaftsübersicht -->
        <div class="section">
            <h2>Mannschaften</h2>
            <p>Alle registrierten Mannschaften für das Rennen.</p>
            
            <?php 
            $teams = getAllTeams();
            if (empty($teams)): ?>
                <div class="card">
                    <div class="card-body">
                        <p>Noch keine Mannschaften registriert.</p>
                    </div>
                </div>
            <?php else: ?>
                <div class="card">
                    <div class="card-body">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Startklasse</th>
                                    <th>Kapitän</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($teams as $team): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($team['name']); ?></td>
                                        <td><?php echo htmlspecialchars($team['startklasse']); ?></td>
                                        <td><?php echo htmlspecialchars($team['kapitaen']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
