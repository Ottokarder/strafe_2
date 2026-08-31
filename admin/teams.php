<?php
/**
 * Mannschaftsverwaltung
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
$teamId = $_GET['id'] ?? null;

// Fehler und Erfolgsmeldungen
$error = $_SESSION['error'] ?? null;
unset($_SESSION['error']);

$success = $_SESSION['success'] ?? null;
unset($_SESSION['success']);

// CSRF-Token generieren
$csrfToken = generateCSRFToken();

// Alle Startklassen abrufen
$startklassen = getStartklassen();

// Team löschen
if ($action === 'delete' && $teamId) {
    // CSRF-Token überprüfen
    if (!isset($_GET['csrf_token']) || !validateCSRFToken($_GET['csrf_token'])) {
        redirectWithError('/admin/teams.php', 'Ungültiges CSRF-Token.');
    }
    
    // Team abrufen
    $team = getTeamById($teamId);
    if (!$team) {
        redirectWithError('/admin/teams.php', 'Mannschaft nicht gefunden.');
    }
    
    // Team löschen
    if (deleteTeam($teamId)) {
        logAudit('DELETE', 'teams', $teamId, $team);
        redirectWithSuccess('/admin/teams.php', 'Mannschaft erfolgreich gelöscht.');
    } else {
        redirectWithError('/admin/teams.php', 'Fehler beim Löschen der Mannschaft.');
    }
}

// Team bearbeiten oder hinzufügen
if ($action === 'edit' || $action === 'add') {
    $team = null;
    if ($action === 'edit' && $teamId) {
        $team = getTeamById($teamId);
        if (!$team) {
            redirectWithError('/admin/teams.php', 'Mannschaft nicht gefunden.');
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
                'name' => validateInput($_POST['name'] ?? ''),
                'startklasse' => validateInput($_POST['startklasse'] ?? ''),
                'kapitaen' => validateInput($_POST['kapitaen'] ?? ''),
                'email' => validateInput($_POST['email'] ?? '')
            ];
            
            // Validierung
            if (empty($data['name'])) {
                $error = 'Bitte geben Sie einen Mannschaftsnamen ein.';
            } elseif (empty($data['kapitaen'])) {
                $error = 'Bitte geben Sie einen Kapitänsnamen ein.';
            } elseif (!validateEmail($data['email'])) {
                $error = 'Bitte geben Sie eine gültige E-Mail-Adresse ein.';
            }
            
            if (!$error) {
                if ($action === 'add') {
                    // Neue Mannschaft erstellen
                    $teamId = createTeam($data);
                    logAudit('INSERT', 'teams', $teamId, [], $data);
                    redirectWithSuccess('/admin/teams.php', 'Mannschaft erfolgreich erstellt.');
                } else {
                    // Mannschaft aktualisieren
                    $oldTeam = $team;
                    if (updateTeam($teamId, $data)) {
                        logAudit('UPDATE', 'teams', $teamId, $oldTeam, $data);
                        redirectWithSuccess('/admin/teams.php', 'Mannschaft erfolgreich aktualisiert.');
                    } else {
                        $error = 'Fehler beim Aktualisieren der Mannschaft.';
                    }
                }
            }
        }
    }
    
    // Formular anzeigen
    include __DIR__ . '/team_form.php';
    exit();
}

// Alle Mannschaften abrufen
$teams = getAllTeams();

// Startzeiten für jede Mannschaft abrufen
foreach ($teams as $key => $team) {
    $teams[$key]['start_times'] = getStartTimesForTeam($team['id']);
    $teams[$key]['start_count'] = count($teams[$key]['start_times']);
}

?>
<?php include __DIR__ . '/../includes/header.php'; ?>

    <div class="container">
        <div class="page-header">
            <h1>Mannschaftsverwaltung</h1>
            <a href="?action=add" class="btn btn-primary">Neue Mannschaft hinzufügen</a>
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
                <h3>Mannschaften (<?php echo count($teams); ?>)</h3>
            </div>
            <div class="card-body">
                <?php if (empty($teams)): ?>
                    <p>Keine Mannschaften gefunden. <a href="?action=add">Erste Mannschaft hinzufügen</a></p>
                <?php else: ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Startklasse</th>
                                <th>Kapitän</th>
                                <th>Startzeiten</th>
                                <th>Aktionen</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($teams as $team): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($team['name']); ?></td>
                                    <td><?php echo htmlspecialchars($team['startklasse']); ?></td>
                                    <td><?php echo htmlspecialchars($team['kapitaen']); ?></td>
                                    <td><?php echo $team['start_count']; ?> / <?php echo MAX_STARTS_PER_TEAM; ?></td>
                                    <td>
                                        <a href="?action=edit&id=<?php echo $team['id']; ?>" class="btn btn-small btn-secondary">Bearbeiten</a>
                                        <a href="../start_times.php?team_id=<?php echo $team['id']; ?>" class="btn btn-small btn-info">Startzeiten</a>
                                        <a href="?action=delete&id=<?php echo $team['id']; ?>&csrf_token=<?php echo $csrfToken; ?>" 
                                           class="btn btn-small btn-danger" 
                                           onclick="return confirm('Möchten Sie diese Mannschaft wirklich löschen? Alle zugehörigen Startzeiten und Ergebnisse werden ebenfalls gelöscht.')">Löschen</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <?php include __DIR__ . '/../includes/footer.php'; ?>