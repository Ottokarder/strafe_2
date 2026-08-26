<?php
/**
 * Öffentliche Ergebnisse-Seite
 */

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

// Prüfen, ob das Rennen begonnen hat
$raceStarted = isRaceStarted();

// Prüfen, ob das Rennen vorbei ist
$raceFinished = isRaceFinished();

// Ergebnisse abrufen
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

// Startklassen abrufen
$startklassen = getStartklassen();

// Filter
$selectedClass = $_GET['class'] ?? '';

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
    <title>Ergebnisse - <?php echo APP_TITLE; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <meta http-equiv="refresh" content="30"> <!-- Auto-Refresh alle 30 Sekunden -->
</head>
<body class="public-page">
    <?php include __DIR__ . '/includes/header.php'; ?>
    
    <div class="container">
        <div class="page-header">
            <h1>Ergebnisse</h1>
            <p>
                <?php 
                if ($raceFinished) {
                    echo "Finale Ergebnisse des Kanadierrennens.";
                } elseif ($raceStarted) {
                    echo "Aktuelle Ergebnisse werden in Echtzeit aktualisiert.";
                } else {
                    echo "Ergebnisse werden während des Rennens hier angezeigt.";
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
        
        <?php if (empty($results)): ?>
            <div class="card">
                <div class="card-body">
                    <p>
                        <?php 
                        if ($raceStarted) {
                            echo "Noch keine Ergebnisse erfasst. Die Ergebnisse werden während des Rennens hier angezeigt.";
                        } else {
                            echo "Noch keine Ergebnisse verfügbar. Das Rennen beginnt am " . getSetting('race_date_saturday') . ".";
                        }
                        ?>
                    </p>
                </div>
            </div>
        <?php else: ?>
            <div class="card">
                <div class="card-header">
                    <h3>Filter</h3>
                </div>
                <div class="card-body">
                    <form method="GET" action="ergebnisse.php">
                        <div class="form-group">
                            <label for="class">Startklasse:</label>
                            <select id="class" name="class" onchange="this.form.submit()">
                                <option value="">Alle Startklassen</option>
                                <?php foreach ($startklassen as $klasse): ?>
                                    <option value="<?php echo htmlspecialchars($klasse); ?>" 
                                        <?php echo $selectedClass === $klasse ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($klasse); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </form>
                </div>
            </div>
            
            <?php foreach ($resultsByClass as $class => $classResults): ?>
                <?php if ($selectedClass && $selectedClass !== $class) continue; ?>
                
                <div class="card">
                    <div class="card-header">
                        <h3><?php echo htmlspecialchars($class); ?> (<?php echo count($classResults); ?>)</h3>
                    </div>
                    <div class="card-body">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Platz</th>
                                    <th>Mannschaft</th>
                                    <th>Rennzeit</th>
                                    <?php if ($raceFinished || isAdmin()): ?>
                                        <th>Strafsekunden</th>
                                    <?php endif; ?>
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
                                        <?php if ($raceFinished || isAdmin()): ?>
                                            <td><?php echo $r['penalty_seconds']; ?></td>
                                        <?php endif; ?>
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
            
            <div class="card">
                <div class="card-body">
                    <p class="note">
                        <strong>Hinweis:</strong> Die Ergebnisse werden automatisch alle 30 Sekunden aktualisiert.<br>
                        Die Platzierung erfolgt nach der Endzeit (Rennzeit + Strafsekunden).
                    </p>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
