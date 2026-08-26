<?php
/**
 * Öffentliche Startzeiten-Seite
 */

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

// Prüfen, ob die Reservierung freigegeben ist
$reservationOpen = isReservationOpen();

// Prüfen, ob das Rennen begonnen hat
$raceStarted = isRaceStarted();

// Startzeiten abrufen
if ($raceStarted) {
    // Während des Rennens: alle Startzeiten mit Teams anzeigen
    $startTimes = fetchAll("SELECT st.date, st.time, t.name AS team_name, t.startklasse 
                             FROM start_times st 
                             LEFT JOIN teams t ON st.team_id = t.id 
                             WHERE st.is_booked = TRUE 
                             ORDER BY st.date, st.time");
} else {
    // Vor dem Rennen: nur gebuchte Startzeiten anzeigen
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
    <title>Startzeiten - <?php echo APP_TITLE; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <meta http-equiv="refresh" content="60">
</head>
<body class="public-page">
    <?php include __DIR__ . '/includes/header.php'; ?>
    
    <div class="container">
        <div class="page-header">
            <h1>Startzeiten</h1>
            <p>
                <?php 
                if ($raceStarted) {
                    echo "Aktuelle Startzeiten während des Rennens.";
                } elseif ($reservationOpen) {
                    echo "Startzeiten für das Kanadierrennen. Mannschaftskapitäne können freie Startzeiten beim Administrator reservieren.";
                } else {
                    echo "Startzeiten werden 6 Wochen vor dem Rennen veröffentlicht.";
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
        
        <?php if (empty($startTimes) && empty($freeStartTimes)): ?>
            <div class="card">
                <div class="card-body">
                    <p>Noch keine Startzeiten verfügbar.</p>
                    <?php if (!$reservationOpen): ?>
                        <p>Die Reservierung beginnt am <?php echo getSetting('reservation_start_date') ?? date('Y-m-d', strtotime('+6 weeks')); ?>.</p>
                    <?php endif; ?>
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
                                        echo '<td>' . htmlspecialchars($st['team_name'] ?? 'N/A') . '</td>';
                                        echo '<td>' . htmlspecialchars($st['startklasse'] ?? '') . '</td>';
                                        echo '<td>Gebucht</td>';
                                        echo '</tr>';
                                    }
                                }
                                
                                // Freie Startzeiten für diesen Tag
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
            
            <?php if ($reservationOpen && !$raceStarted && !empty($freeStartTimes)): ?>
                <div class="card">
                    <div class="card-header">
                        <h3>Freie Startzeiten</h3>
                    </div>
                    <div class="card-body">
                        <p>Die folgenden Startzeiten sind noch frei und können von Mannschaftskapitänen beim Administrator reserviert werden:</p>
                        
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
                            <strong>Informationen:</strong><br>
                            Startzeiten werden in 10-Minuten-Intervallen angeboten.<br>
                            Samstag: 14:00–18:00 Uhr (24 Startplätze)<br>
                            Sonntag: 11:00–16:00 Uhr (30 Startplätze)<br><br>
                            <strong>Reservierung:</strong> Mannschaftskapitäne melden sich bitte beim Administrator (Michael Fischer), um eine Startzeit zu reservieren.
                        </p>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    
    <?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
