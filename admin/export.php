<?php
/**
 * CSV-Export
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

// Zugriff prüfen
if (!isAdmin()) {
    redirectToLogin('Sie haben keine Berechtigung für diese Seite.');
}

// Session-Timeout prüfen
checkSessionTimeout();

// Export-Aktion
$action = $_GET['action'] ?? '';
$class = $_GET['class'] ?? '';

// CSRF-Token generieren
$csrfToken = generateCSRFToken();

// Alle Startklassen abrufen
$startklassen = getStartklassen();

// Export durchführen
if ($action === 'export_results' || $action === 'export_start_times' || $action === 'export_teams') {
    $csv = '';
    $filename = '';
    
    switch ($action) {
        case 'export_results':
            $csv = exportResultsToCSV($class);
            $filename = $class ? 'ergebnisse_' . strtolower(str_replace(' ', '_', $class)) . '.csv' : 'alle_ergebnisse.csv';
            break;
        case 'export_start_times':
            $csv = exportStartTimesToCSV();
            $filename = 'startzeiten.csv';
            break;
        case 'export_teams':
            $csv = exportTeamsToCSV();
            $filename = 'mannschaften.csv';
            break;
    }
    
    // CSV-Download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename);
    header('Pragma: no-cache');
    header('Expires: 0');
    
    echo $csv;
    exit();
}

?>
<?php include __DIR__ . '/../includes/header.php'; ?>

    
    <div class="container">
        <div class="page-header">
            <h1>Daten exportieren</h1>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h3>Export-Optionen</h3>
            </div>
            <div class="card-body">
                <div class="export-section">
                    <h4>Ergebnisse exportieren</h4>
                    <p>Exportieren Sie die Rennergebnisse als CSV-Datei.</p>
                    
                    <form method="GET" action="export.php">
                        <input type="hidden" name="action" value="export_results">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                        
                        <div class="form-group">
                            <label for="class">Startklasse:</label>
                            <select id="class" name="class">
                                <option value="">Alle Startklassen</option>
                                <?php foreach ($startklassen as $klasse): ?>
                                    <option value="<?php echo htmlspecialchars($klasse); ?>">
                                        <?php echo htmlspecialchars($klasse); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Ergebnisse exportieren</button>
                    </form>
                </div>
                
                <hr>
                
                <div class="export-section">
                    <h4>Startzeiten exportieren</h4>
                    <p>Exportieren Sie alle Startzeiten als CSV-Datei.</p>
                    
                    <form method="GET" action="export.php">
                        <input type="hidden" name="action" value="export_start_times">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                        
                        <button type="submit" class="btn btn-primary">Startzeiten exportieren</button>
                    </form>
                </div>
                
                <hr>
                
                <div class="export-section">
                    <h4>Mannschaften exportieren</h4>
                    <p>Exportieren Sie alle Mannschaften als CSV-Datei.</p>
                    
                    <form method="GET" action="export.php">
                        <input type="hidden" name="action" value="export_teams">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                        
                        <button type="submit" class="btn btn-primary">Mannschaften exportieren</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <?php include __DIR__ . '/../includes/footer.php'; ?>