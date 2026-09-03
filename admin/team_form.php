<?php
/**
 * Formular für Mannschaft bearbeiten/hinzufügen
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

// Aktuelle Aktion abrufen
$action = $_GET['action'] ?? '';
$teamId = $_GET['id'] ?? null;
$newCaptainId = $_GET['captain_id'] ?? null;
$newCaptainCreated = $_GET['new_captain'] ?? null;

// Standardwerte für neues Team
if ($action === 'add') {
    $team = [
        'id' => null,
        'name' => '',
        'startklasse' => '',
        'captain_id' => $newCaptainId ?: null,
        'captain_name' => '',
        'captain_email' => ''
    ];
    
    // Erfolgsmeldung anzeigen, wenn ein neuer Kapitän erstellt wurde
    if ($newCaptainCreated && $newCaptainId) {
        $success = 'Neuer Kapitän wurde erstellt und ausgewählt.';
    }
} else {
    // Team-Daten abrufen
    $team = getTeamById($teamId);
    if (!$team) {
        redirectWithError('/admin/teams.php', 'Mannschaft nicht gefunden.');
    }
}

// Alle Startklassen abrufen
$startklassen = getStartklassen();

// Alle Kapitäne abrufen
$captains = getAllCaptains();

// CSRF-Token generieren
$csrfToken = generateCSRFToken();

?>
<?php include __DIR__ . '/../includes/header.php'; ?>

    <div class="container">
        <div class="page-header">
            <h1><?php echo $action === 'add' ? 'Neue Mannschaft hinzufügen' : 'Mannschaft bearbeiten'; ?></h1>
            <a href="/admin/teams.php" class="btn btn-secondary">Zurück zur Übersicht</a>
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
                <h3><?php echo $action === 'add' ? 'Neue Mannschaft' : 'Mannschaft bearbeiten'; ?></h3>
            </div>
            <div class="card-body">
                <form method="POST" action="/admin/teams.php?action=<?php echo $action; ?><?php echo $team['id'] ? '&id=' . $team['id'] : ''; ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    
                    <div class="form-group">
                        <label for="name">Mannschaftsname *</label>
                        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($team['name']); ?>" required maxlength="100">
                        <small>Maximal 100 Zeichen erlaubt.</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="startklasse">Startklasse (optional)</label>
                        <select id="startklasse" name="startklasse">
                            <option value="">-- Keine Startklasse --</option>
                            <?php foreach ($startklassen as $klasse): ?>
                                <option value="<?php echo htmlspecialchars($klasse); ?>" 
                                    <?php echo $team['startklasse'] === $klasse ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($klasse); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="captain_id">Kapitän *</label>
                        <select id="captain_id" name="captain_id" required>
                            <option value="">-- Kapitän auswählen --</option>
                            <?php foreach ($captains as $captain): ?>
                                <option value="<?php echo $captain['id']; ?>" 
                                    <?php echo $team['captain_id'] == $captain['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($captain['name']); ?>
                                    <?php if (!empty($captain['email'])): ?>
                                        (<?php echo htmlspecialchars($captain['email']); ?>)
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small>
                            <a href="/admin/captains.php?action=add&return_to=teams" style="color: #007bff;" onclick="window.open(this.href, '_blank'); return false;">Neuen Kapitän hinzufügen</a>
                        </small>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <?php echo $action === 'add' ? 'Mannschaft erstellen' : 'Mannschaft aktualisieren'; ?>
                        </button>
                        <a href="/admin/teams.php" class="btn btn-secondary">Abbrechen</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        // Öffnet den Link zum Hinzufügen eines neuen Kapitäns in einem neuen Tab
        // und aktualisiert die Kapitän-Liste nach dem Schließen
        function openCaptainForm() {
            window.open('/admin/captains.php?action=add&return_to=teams', '_blank', 'width=600,height=400');
        }
    </script>
    
    <?php include __DIR__ . '/../includes/footer.php'; ?>
