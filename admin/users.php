<?php
/**
 * Benutzerverwaltung
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

// Session starten
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
$userId = $_GET['id'] ?? null;

// Fehler und Erfolgsmeldungen
$error = $_SESSION['error'] ?? null;
unset($_SESSION['error']);

$success = $_SESSION['success'] ?? null;
unset($_SESSION['success']);

// CSRF-Token generieren
$csrfToken = generateCSRFToken();

// Benutzer löschen
if ($action === 'delete' && $userId) {
    // CSRF-Token überprüfen
    if (!isset($_GET['csrf_token']) || !validateCSRFToken($_GET['csrf_token'])) {
        redirectWithError('/admin/users.php', 'Ungültiges CSRF-Token.');
    }
    
    // Benutzer abrufen
    $user = fetchOne("SELECT * FROM users WHERE id = ?", "i", [$userId]);
    if (!$user) {
        redirectWithError('/admin/users.php', 'Benutzer nicht gefunden.');
    }
    
    // Eigenen Account kann man nicht löschen
    if ($user['id'] == $_SESSION['user_id']) {
        redirectWithError('/admin/users.php', 'Sie können Ihren eigenen Account nicht löschen.');
    }
    
    // Letzten Admin kann man nicht löschen
    $adminCount = fetchOne("SELECT COUNT(*) as count FROM users WHERE is_admin = TRUE");
    if ($user['is_admin'] && $adminCount['count'] <= 1) {
        redirectWithError('/admin/users.php', 'Sie können den letzten Administrator nicht löschen.');
    }
    
    // Benutzer löschen
    if (execute("DELETE FROM users WHERE id = ?", "i", [$userId]) > 0) {
        logAudit('DELETE', 'users', $userId, $user);
        redirectWithSuccess('/admin/users.php', 'Benutzer erfolgreich gelöscht.');
    } else {
        redirectWithError('/admin/users.php', 'Fehler beim Löschen des Benutzers.');
    }
}

// Benutzer bearbeiten
if ($action === 'edit' && $userId) {
    $user = fetchOne("SELECT * FROM users WHERE id = ?", "i", [$userId]);
    if (!$user) {
        redirectWithError('/admin/users.php', 'Benutzer nicht gefunden.');
    }
    
    // Formular verarbeiten
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $csrfToken = $_POST['csrf_token'] ?? '';
        
        // CSRF-Token überprüfen
        if (!validateCSRFToken($csrfToken)) {
            $error = 'Ungültiges CSRF-Token.';
        } else {
            $username = trim($_POST['username'] ?? '');
            $isAdmin = isset($_POST['is_admin']) ? 1 : 0;
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            
            // Validierung
            if (empty($username)) {
                $error = 'Bitte geben Sie einen Benutzernamen ein.';
            } elseif ($newPassword !== $confirmPassword && !empty($newPassword)) {
                $error = 'Die Passwörter stimmen nicht überein.';
            } elseif (!empty($newPassword) && strlen($newPassword) < 8) {
                $error = 'Das Passwort muss mindestens 8 Zeichen lang sein.';
            }
            
            if (!$error) {
                $oldUser = $user;
                
                // Benutzer aktualisieren
                if (empty($newPassword)) {
                    // Nur Username und Admin-Status aktualisieren
                    $updated = execute("UPDATE users SET username = ?, is_admin = ? WHERE id = ?", "sii", [$username, $isAdmin, $userId]);
                } else {
                    // Passwort mit aktualisieren
                    $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
                    $updated = execute("UPDATE users SET username = ?, password_hash = ?, is_admin = ? WHERE id = ?", "ssii", [$username, $passwordHash, $isAdmin, $userId]);
                }
                
                if ($updated > 0) {
                    // Session aktualisieren, falls eigener Account bearbeitet wurde
                    if ($userId == $_SESSION['user_id']) {
                        $_SESSION['username'] = $username;
                        $_SESSION['is_admin'] = (bool)$isAdmin;
                    }
                    
                    logAudit('UPDATE', 'users', $userId, $oldUser, ['username' => $username, 'is_admin' => (bool)$isAdmin]);
                    redirectWithSuccess('/admin/users.php', 'Benutzer erfolgreich aktualisiert.');
                } else {
                    $error = 'Fehler beim Aktualisieren des Benutzers.';
                }
            }
        }
    }
    
    // Formular anzeigen
    include __DIR__ . '/user_form.php';
    exit();
}

// Neuer Benutzer
if ($action === 'add') {
    // Formular verarbeiten
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $csrfToken = $_POST['csrf_token'] ?? '';
        
        // CSRF-Token überprüfen
        if (!validateCSRFToken($csrfToken)) {
            $error = 'Ungültiges CSRF-Token.';
        } else {
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            $isAdmin = isset($_POST['is_admin']) ? 1 : 0;
            
            // Validierung
            if (empty($username)) {
                $error = 'Bitte geben Sie einen Benutzernamen ein.';
            } elseif (empty($password)) {
                $error = 'Bitte geben Sie ein Passwort ein.';
            } elseif ($password !== $confirmPassword) {
                $error = 'Die Passwörter stimmen nicht überein.';
            } elseif (strlen($password) < 8) {
                $error = 'Das Passwort muss mindestens 8 Zeichen lang sein.';
            }
            
            // Prüfen, ob Benutzername bereits existiert
            $existingUser = fetchOne("SELECT id FROM users WHERE username = ?", "s", [$username]);
            if ($existingUser) {
                $error = 'Dieser Benutzername ist bereits vergeben.';
            }
            
            if (!$error) {
                // Benutzer erstellen
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                $userId = execute("INSERT INTO users (username, password_hash, is_admin) VALUES (?, ?, ?)", "ssi", [$username, $passwordHash, $isAdmin]);
                
                if ($userId > 0) {
                    logAudit('INSERT', 'users', $userId, [], ['username' => $username, 'is_admin' => (bool)$isAdmin]);
                    redirectWithSuccess('/admin/users.php', 'Benutzer erfolgreich erstellt.');
                } else {
                    $error = 'Fehler beim Erstellen des Benutzers.';
                }
            }
        }
    }
    
    // Formular anzeigen
    include __DIR__ . '/user_form.php';
    exit();
}

// Alle Benutzer abrufen
$users = fetchAll("SELECT * FROM users ORDER BY username");

?>
<?php include __DIR__ . '/../includes/header.php'; ?>

    <div class="container">
        <div class="page-header">
            <h1>Benutzerverwaltung</h1>
            <a href="?action=add" class="btn btn-primary">Neuen Benutzer hinzufügen</a>
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
                <h3>Benutzer (<?php echo count($users); ?>)</h3>
            </div>
            <div class="card-body">
                <?php if (empty($users)): ?>
                    <p>Keine Benutzer gefunden. <a href="?action=add">Ersten Benutzer hinzufügen</a></p>
                <?php else: ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Benutzername</th>
                                <th>Rolle</th>
                                <th>Erstellt am</th>
                                <th>Aktionen</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td><?php echo $user['is_admin'] ? '<span class="badge badge-admin">Administrator</span>' : '<span class="badge badge-user">Benutzer</span>'; ?></td>
                                    <td><?php echo date('d.m.Y H:i', strtotime($user['created_at'])); ?></td>
                                    <td>
                                        <a href="?action=edit&id=<?php echo $user['id']; ?>" class="btn btn-small btn-secondary">Bearbeiten</a>
                                        <?php if ($user['id'] != $_SESSION['user_id'] || count($users) > 1): ?>
                                            <a href="?action=delete&id=<?php echo $user['id']; ?>&csrf_token=<?php echo $csrfToken; ?>" 
                                               class="btn btn-small btn-danger" 
                                               onclick="return confirm('Möchten Sie diesen Benutzer wirklich löschen?')">Löschen</a>
                                        <?php else: ?>
                                            <span class="btn btn-small btn-disabled" title="Dieser Benutzer kann nicht gelöscht werden">Löschen</span>
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
