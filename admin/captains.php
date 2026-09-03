<?php
/**
 * Kapitän-Verwaltung
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

// Aktionen verarbeiten
$action = $_GET['action'] ?? '';
$captainId = $_GET['id'] ?? null;
$returnTo = $_GET['return_to'] ?? null;

// Fehler und Erfolgsmeldungen
$error = $_SESSION['error'] ?? null;
unset($_SESSION['error']);

$success = $_SESSION['success'] ?? null;
unset($_SESSION['success']);

// CSRF-Token generieren
$csrfToken = generateCSRFToken();

// Kapitän löschen
if ($action === 'delete' && $captainId) {
    // CSRF-Token überprüfen
    if (!isset($_GET['csrf_token']) || !validateCSRFToken($_GET['csrf_token'])) {
        $returnUrl = $returnTo ? '/admin/' . $returnTo . '.php' : '/admin/captains.php';
        redirectWithError($returnUrl, 'Ungültiges CSRF-Token.');
    }
    
    // Kapitän abrufen
    $captain = getCaptainById($captainId);
    if (!$captain) {
        $returnUrl = $returnTo ? '/admin/' . $returnTo . '.php' : '/admin/captains.php';
        redirectWithError($returnUrl, 'Kapitän nicht gefunden.');
    }
    
    // Prüfen, ob der Kapitän noch verwendet wird
    $teamCount = fetchOne("SELECT COUNT(*) as count FROM teams WHERE captain_id = ?", "i", [$captainId]);
    if ($teamCount['count'] > 0) {
        $returnUrl = $returnTo ? '/admin/' . $returnTo . '.php' : '/admin/captains.php';
        redirectWithError($returnUrl, 'Dieser Kapitän wird noch von ' . $teamCount['count'] . ' Mannschaft(en) verwendet und kann nicht gelöscht werden.');
    }
    
    // Kapitän löschen
    if (deleteCaptain($captainId)) {
        logAudit('DELETE', 'captains', $captainId, $captain);
        $returnUrl = $returnTo ? '/admin/' . $returnTo . '.php' : '/admin/captains.php';
        redirectWithSuccess($returnUrl, 'Kapitän erfolgreich gelöscht.');
    } else {
        $returnUrl = $returnTo ? '/admin/' . $returnTo . '.php' : '/admin/captains.php';
        redirectWithError($returnUrl, 'Fehler beim Löschen des Kapitäns.');
    }
}

// Kapitän bearbeiten oder hinzufügen
if ($action === 'edit' || $action === 'add') {
    $captain = null;
    if ($action === 'edit' && $captainId) {
        $captain = getCaptainById($captainId);
        if (!$captain) {
            $returnUrl = $returnTo ? '/admin/' . $returnTo . '.php' : '/admin/captains.php';
            redirectWithError($returnUrl, 'Kapitän nicht gefunden.');
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
                'email' => !empty($_POST['email']) ? validateInput($_POST['email']) : null,
                'phone' => !empty($_POST['phone']) ? validateInput($_POST['phone']) : null
            ];
            
            // Validierung
            if (empty($data['name'])) {
                $error = 'Bitte geben Sie einen Kapitänsnamen ein.';
            } elseif (!empty($data['email']) && !validateEmail($data['email'])) {
                $error = 'Bitte geben Sie eine gültige E-Mail-Adresse ein.';
            }
            
            if (!$error) {
                if ($action === 'add') {
                    // Neuer Kapitän erstellen
                    $captainId = createCaptain($data);
                    logAudit('INSERT', 'captains', $captainId, [], $data);
                    
                    // Zurück zur ursprünglichen Seite
                    $returnUrl = $returnTo ? '/admin/' . $returnTo . '.php' : '/admin/captains.php';
                    redirectWithSuccess($returnUrl, 'Kapitän erfolgreich erstellt.');
                } else {
                    // Kapitän aktualisieren
                    $oldCaptain = $captain;
                    if (updateCaptain($captainId, $data)) {
                        logAudit('UPDATE', 'captains', $captainId, $oldCaptain, $data);
                        $returnUrl = $returnTo ? '/admin/' . $returnTo . '.php?action=edit&id=' . $captainId : '/admin/captains.php';
                        redirectWithSuccess($returnUrl, 'Kapitän erfolgreich aktualisiert.');
                    } else {
                        $error = 'Fehler beim Aktualisieren des Kapitäns.';
                    }
                }
            }
        }
    }
    
    // Formular anzeigen
    include __DIR__ . '/captain_form.php';
    exit();
}

// Alle Kapitäne abrufen
$captains = getAllCaptains();

// Anzahl der Teams pro Kapitän abrufen
foreach ($captains as $key => $captain) {
    $teamCount = fetchOne("SELECT COUNT(*) as count FROM teams WHERE captain_id = ?", "i", [$captain['id']]);
    $captains[$key]['team_count'] = $teamCount['count'];
}

?>
<?php include __DIR__ . '/../includes/header.php'; ?>

    <div class="container">
        <div class="page-header">
            <h1>Kapitän-Verwaltung</h1>
            <a href="?action=add" class="btn btn-primary">Neuen Kapitän hinzufügen</a>
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
                <h3>Kapitäne (<?php echo count($captains); ?>)</h3>
            </div>
            <div class="card-body">
                <?php if (empty($captains)): ?>
                    <p>Keine Kapitäne gefunden. <a href="?action=add">Ersten Kapitän hinzufügen</a></p>
                <?php else: ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>E-Mail</th>
                                <th>Telefon</th>
                                <th>Mannschaften</th>
                                <th>Aktionen</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($captains as $captain): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($captain['name']); ?></td>
                                    <td><?php echo htmlspecialchars($captain['email'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($captain['phone'] ?? '-'); ?></td>
                                    <td><?php echo $captain['team_count']; ?></td>
                                    <td>
                                        <a href="?action=edit&id=<?php echo $captain['id']; ?>" class="btn btn-small btn-secondary">Bearbeiten</a>
                                        <?php if ($captain['team_count'] == 0): ?>
                                            <a href="?action=delete&id=<?php echo $captain['id']; ?>&csrf_token=<?php echo $csrfToken; ?>" 
                                               class="btn btn-small btn-danger" 
                                               onclick="return confirm('Möchten Sie diesen Kapitän wirklich löschen?')">Löschen</a>
                                        <?php else: ?>
                                            <span class="btn btn-small btn-danger" title="Wird von <?php echo $captain['team_count']; ?> Mannschaft(en) verwendet">Gesperrt</span>
                                        <?php endif; ?>
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
