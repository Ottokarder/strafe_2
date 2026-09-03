<?php
/**
 * Formular für Kapitän bearbeiten/hinzufügen
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
$captainId = $_GET['id'] ?? null;
$returnTo = $_GET['return_to'] ?? null;

// Standardwerte für neuen Kapitän
if ($action === 'add') {
    $captain = [
        'id' => null,
        'name' => '',
        'email' => '',
        'phone' => ''
    ];
} else {
    // Kapitän-Daten abrufen
    $captain = getCaptainById($captainId);
    if (!$captain) {
        $returnUrl = $returnTo ? '/admin/' . $returnTo . '.php' : '/admin/captains.php';
        redirectWithError($returnUrl, 'Kapitän nicht gefunden.');
    }
}

// CSRF-Token generieren
$csrfToken = generateCSRFToken();

?>
<?php include __DIR__ . '/../includes/header.php'; ?>

    <div class="container">
        <div class="page-header">
            <h1><?php echo $action === 'add' ? 'Neuen Kapitän hinzufügen' : 'Kapitän bearbeiten'; ?></h1>
            <?php if ($returnTo): ?>
                <a href="/admin/<?php echo $returnTo; ?>.php" class="btn btn-secondary">Zurück zur Übersicht</a>
            <?php else: ?>
                <a href="/admin/captains.php" class="btn btn-secondary">Zurück zur Übersicht</a>
            <?php endif; ?>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <h3><?php echo $action === 'add' ? 'Neuer Kapitän' : 'Kapitän bearbeiten'; ?></h3>
            </div>
            <div class="card-body">
                <form method="POST" action="/admin/captains.php?action=<?php echo $action; ?><?php echo $captain['id'] ? '&id=' . $captain['id'] : ''; ?><?php echo $returnTo ? '&return_to=' . $returnTo : ''; ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    
                    <div class="form-group">
                        <label for="name">Name *</label>
                        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($captain['name']); ?>" required maxlength="100">
                        <small>Maximal 100 Zeichen erlaubt.</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">E-Mail (optional)</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($captain['email']); ?>" placeholder="z. B. kapitaen@example.com" maxlength="255">
                        <small>Diese E-Mail wird nicht öffentlich angezeigt. Maximal 255 Zeichen.</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Telefon (optional)</label>
                        <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($captain['phone']); ?>" placeholder="z. B. 01234 56789" maxlength="50">
                        <small>Maximal 50 Zeichen erlaubt.</small>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <?php echo $action === 'add' ? 'Kapitän erstellen' : 'Kapitän aktualisieren'; ?>
                        </button>
                        <?php if ($returnTo): ?>
                            <a href="/admin/<?php echo $returnTo; ?>.php" class="btn btn-secondary">Abbrechen</a>
                        <?php else: ?>
                            <a href="/admin/captains.php" class="btn btn-secondary">Abbrechen</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <?php include __DIR__ . '/../includes/footer.php'; ?>
