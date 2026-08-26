<?php
/**
 * Login-Seite
 */

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

// Bereits angemeldet?
if (isLoggedIn()) {
    header("Location: admin/index.php");
    exit();
}

// Login-Formular verarbeiten
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = validateInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $csrfToken = $_POST['csrf_token'] ?? '';
    
    // CSRF-Token überprüfen
    if (!validateCSRFToken($csrfToken)) {
        $_SESSION['login_error'] = 'Ungültiges CSRF-Token. Bitte versuchen Sie es erneut.';
        header("Location: login.php");
        exit();
    }
    
    // Anmeldung versuchen
    if (login($username, $password)) {
        // Erfolgreich angemeldet
        $_SESSION['success'] = 'Erfolgreich angemeldet!';
        header("Location: admin/index.php");
        exit();
    } else {
        // Fehlgeschlagen
        $_SESSION['login_error'] = 'Benutzername oder Passwort ist falsch.';
        header("Location: login.php");
        exit();
    }
}

// CSRF-Token generieren
$csrfToken = generateCSRFToken();

// Fehler abrufen und löschen
$error = $_SESSION['login_error'] ?? null;
unset($_SESSION['login_error']);

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Anmeldung - <?php echo APP_TITLE; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-header">
            <?php if (file_exists(LOGO_PATH)): ?>
                <img src="<?php echo LOGO_PATH; ?>" alt="<?php echo APP_TITLE; ?>" class="logo">
            <?php else: ?>
                <h1><?php echo APP_TITLE; ?></h1>
            <?php endif; ?>
        </div>
        
        <form method="POST" action="login.php" class="login-form">
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <div class="form-group">
                <label for="username">Benutzername:</label>
                <input type="text" id="username" name="username" required autocomplete="username" autofocus>
            </div>
            
            <div class="form-group">
                <label for="password">Passwort:</label>
                <input type="password" id="password" name="password" required autocomplete="current-password">
            </div>
            
            <button type="submit" class="btn btn-primary">Anmelden</button>
        </form>
        
        <div class="login-footer">
            <p><a href="index.php">Zur öffentlichen Ansichtsseite</a></p>
        </div>
    </div>
</body>
</html>
