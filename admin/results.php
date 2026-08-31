<?php
/**
 * Ergebniserfassung
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
$resultId = $_GET['id'] ?? null;

// Fehler und Erfolgsmeldungen
$error = $_SESSION['error'] ?? null;
unset($_SESSION['error']);

$success = $_SESSION['success'] ?? null;
unset($_SESSION['success']);

// CSRF-Token generieren
$csrfToken = generateCSRFToken();

// Ergebnis löschen
if ($action === 'delete' && $resultId) {
    if (!isset($_GET['csrf_token']) || !validateCSRFToken($_GET['csrf_token'])) {
        redirectWithError('results.php', 'Ungültiges CSRF-Token.');
    }
    
    $result = fetchOne("SELECT * FROM results WHERE id = ?", "i", [$resultId]);
    if (!$result) {
        redirectWithError('results.php', 'Ergebnis nicht gefunden.');
    }
    
    if (deleteResult($resultId)) {
        logAudit('DELETE', 'results', $resultId, $result);
        redirectWithSuccess('results.php', 'Ergebnis erfolgreich gelöscht.');
    } else {
        redirectWithError('results.php', 'Fehler beim Löschen des Ergebnisses.');
    }
}

// Ergebnis bearbeiten oder hinzufügen
if ($action === 'edit' || $action === 'add') {
    $result = null;
    if ($action === 'edit' && $resultId) {
        $result = fetchOne("SELECT * FROM results WHERE id = ?", "i", [$resultId]);
        if (!$result) {
            redirectWithError('results.php', 'Ergebnis nicht gefunden.');
        }
    }
    
    // Formular verarbeiten
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $csrfToken = $_POST['csrf_token'] ?? '';
        
        // CSRF-Token überprüfen
        if (!validateCSRFToken($csrfToken)) {
            $error = 'Ungültiges CSRF-Token.';
        } else {
            $data = [
                'team_id' => (int)($_POST['team_id'] ?? 0),
                'start_time_id' => !empty($_POST['start_time_id']) ? (int)$_POST['start_time_id'] : null,
                'time' => validateInput($_POST['time'] ?? '')
            ];
            
            // Validierung
            if (empty($data['team_id'])) {
                $error = 'Bitte wählen Sie eine Mannschaft aus.';
            } elseif (empty($data['time'])) {
                $error = 'Bitte geben Sie eine Zeit ein.';
            } elseif (!validateTime($data['time'])) {
                $error = 'Bitte geben Sie eine gültige Zeit ein (MM:SS).';
            }
            
            if (!$error) {
                if ($action === 'add') {
                    // Neues Ergebnis erstellen
                    $resultId = createResult($data);
                    logAudit('INSERT', 'results', $resultId, [], $data);
                    redirectWithSuccess('results.php', 'Ergebnis erfolgreich erfasst.');
                } else {
                    // Ergebnis aktualisieren
                    $oldResult = $result;
                    if (updateResult($resultId, $data)) {
                        logAudit('UPDATE', 'results', $resultId, $oldResult, $data);
                        redirectWithSuccess('results.php', 'Ergebnis erfolgreich aktualisiert.');
                    } else {
                        $error = 'Fehler beim Aktualisieren des Ergebnisses.';
                    }
                }
            }
        }
    }
    
    // Alle Teams abrufen
    $teams = getAllTeams();
    
    // Alle Startzeiten abrufen
    $startTimes = getAllStartTimes();
    
    // Formular anzeigen
    include __DIR__ . '/result_form.php';
    exit();
}

// Alle Ergebnisse abrufen
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

// Alle Startklassen abrufen
$startklassen = getStartklassen();

// Filter
$selectedClass = $_GET['class'] ?? '';

?>
<?php include __DIR__ . '/../includes/header.php'; ?>

    
    <div class="container">
        <div class="page-header">
            <h1>Ergebniserfassung</h1>
            <a href="results.php?action=add" class="btn btn-primary">Neues Ergebnis hinzufügen</a>
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
        
        <div class="card">
            <div class="card-header">
                <h3>Ergebnisse (<?php echo count($results); ?>)</h3>
            </div>
            <div class="card-body">
                <?php if (empty($results)): ?>
                    <p>Keine Ergebnisse gefunden. <a href="results.php?action=add">Erstes Ergebnis hinzufügen</a></p>
                <?php else: ?>
                    <div class="filter-bar">
                        <form method="GET" action="results.php">
                            <select name="class" onchange="this.form.submit()">
                                <option value="">Alle Startklassen</option>
                                <?php foreach ($startklassen as $klasse): ?>
                                    <option value="<?php echo htmlspecialchars($klasse); ?>" 
                                        <?php echo $selectedClass === $klasse ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($klasse); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    </div>
                    
                    <?php foreach ($resultsByClass as $class => $classResults): ?>
                        <?php if ($selectedClass && $selectedClass !== $class) continue; ?>
                        
                        <div class="result-class">
                            <h4><?php echo htmlspecialchars($class); ?> (<?php echo count($classResults); ?>)</h4>
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Platz</th>
                                        <th>Mannschaft</th>
                                        <th>Rennzeit</th>
                                        <th>Strafsekunden</th>
                                        <th>Endzeit</th>
                                        <th>Renntag</th>
                                        <th>Startzeit</th>
                                        <th>Aktionen</th>
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
                                            <td><?php echo $r['penalty_seconds']; ?></td>
                                            <td><?php echo htmlspecialchars($r['final_time']); ?></td>
                                            <td><?php echo htmlspecialchars($r['race_date'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($r['start_time'] ?? ''); ?></td>
                                            <td>
                                                <a href="results.php?action=edit&id=<?php echo $r['id']; ?>" class="btn btn-small btn-secondary">Bearbeiten</a>
                                                <a href="results.php?action=delete&id=<?php echo $r['id']; ?>&csrf_token=<?php echo $csrfToken; ?>" 
                                                   class="btn btn-small btn-danger" 
                                                   onclick="return confirm('Möchten Sie dieses Ergebnis wirklich löschen?')">Löschen</a>
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
    </div>
    
    <?php include __DIR__ . '/../includes/footer.php'; ?>