<?php
/**
 * Formular für Benutzer bearbeiten/hinzufügen
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
$userId = $_GET['id'] ?? null;

// Standardwerte für neuen Benutzer
if ($action === 'add') {
    $user = [
        'id' => null,
        'username' => '',
        'is_admin' => false
    ];
    $formTitle = 'Neuen Benutzer hinzufügen';
    $submitText = 'Benutzer erstellen';
} else {
    // Benutzer abrufen
    $user = fetchOne("SELECT * FROM users WHERE id = ?", "i", [$userId]);
    if (!$user) {
        redirectWithError('/admin/users.php', 'Benutzer nicht gefunden.');
    }
    $formTitle = 'Benutzer bearbeiten';
    $submitText = 'Benutzer aktualisieren';
}

// CSRF-Token generieren
$csrfToken = generateCSRFToken();

?>
<?php include __DIR__ . '/../includes/header.php'; ?>

    <div class="container">
        <div class="page-header">
            <h1><?php echo $formTitle; ?></h1>
            <a href="/admin/users.php" class="btn btn-secondary">Zurück zur Übersicht</a>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <h3><?php echo $formTitle; ?></h3>
            </div>
            <div class="card-body">
                <form method="POST" action="/admin/users.php?action=<?php echo $action; ?><?php echo $user['id'] ? '&id=' . $user['id'] : ''; ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    
                    <div class="form-group">
                        <label for="username">Benutzername *</label>
                        <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                    </div>
                    
                    <?php if ($action === 'add'): ?>
                        <div class="form-group">
                            <label for="password">Passwort *</label>
                            <input type="password" id="password" name="password" required minlength="8">
                            <small>Mindestens 8 Zeichen</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">Passwort bestätigen *</label>
                            <input type="password" id="confirm_password" name="confirm_password" required minlength="8">
                        </div>
                    <?php else: ?>
                        <div class="form-group">
                            <label for="new_password">Neues Passwort (optional)</label>
                            <input type="password" id="new_password" name="new_password" minlength="8">
                            <small>Lassen Sie dieses Feld leer, um das Passwort nicht zu ändern</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">Neues Passwort bestätigen (optional)</label>
                            <input type="password" id="confirm_password" name="confirm_password" minlength="8">
                        </div>
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="is_admin" value="1" <?php echo $user['is_admin'] ? 'checked' : ''; ?>>
                            Administrator-Rechte
                        </label>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <?php echo $submitText; ?>
                        </button>
                        <a href="/admin/users.php" class="btn btn-secondary">Abbrechen</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <?php include __DIR__ . '/../includes/footer.php'; ?>
