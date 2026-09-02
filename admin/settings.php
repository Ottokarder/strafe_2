<?php
/**
 * Systemeinstellungen
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
$action = $_POST['action'] ?? '';

// Fehler und Erfolgsmeldungen
$error = $_SESSION['error'] ?? null;
unset($_SESSION['error']);

$success = $_SESSION['success'] ?? null;
unset($_SESSION['success']);

// CSRF-Token generieren
$csrfToken = generateCSRFToken();

// Einstellungen speichern
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'save_settings') {
    $postCsrfToken = $_POST['csrf_token'] ?? '';
    
    // CSRF-Token überprüfen
    if (!validateCSRFToken($postCsrfToken)) {
        redirectWithError('/admin/settings.php', 'Ungültiges CSRF-Token.');
    }
    
    // Renn-Daten speichern
    $settings = [
        'race_date_saturday' => trim($_POST['race_date_saturday'] ?? ''),
        'race_date_sunday' => trim($_POST['race_date_sunday'] ?? ''),
        'saturday_start_time' => trim($_POST['saturday_start_time'] ?? ''),
        'saturday_end_time' => trim($_POST['saturday_end_time'] ?? ''),
        'sunday_start_time' => trim($_POST['sunday_start_time'] ?? ''),
        'sunday_end_time' => trim($_POST['sunday_end_time'] ?? ''),
        'start_interval_minutes' => (int)($_POST['start_interval_minutes'] ?? 10),
        'max_starts_per_team' => (int)($_POST['max_starts_per_team'] ?? 3),
        'reservation_start_date' => trim($_POST['reservation_start_date'] ?? ''),
        'app_title' => trim($_POST['app_title'] ?? 'Kanadierrennen CJD Kaltenstein')
    ];
    
    // Validierung
    $errors = [];
    
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $settings['race_date_saturday'])) {
        $errors[] = 'Bitte geben Sie ein gültiges Datum für Samstag ein (YYYY-MM-DD).';
    }
    
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $settings['race_date_sunday'])) {
        $errors[] = 'Bitte geben Sie ein gültiges Datum für Sonntag ein (YYYY-MM-DD).';
    }
    
    if (!preg_match('/^\d{2}:\d{2}$/', $settings['saturday_start_time'])) {
        $errors[] = 'Bitte geben Sie eine gültige Startzeit für Samstag ein (HH:MM).';
    }
    
    if (!preg_match('/^\d{2}:\d{2}$/', $settings['saturday_end_time'])) {
        $errors[] = 'Bitte geben Sie eine gültige Endzeit für Samstag ein (HH:MM).';
    }
    
    if (!preg_match('/^\d{2}:\d{2}$/', $settings['sunday_start_time'])) {
        $errors[] = 'Bitte geben Sie eine gültige Startzeit für Sonntag ein (HH:MM).';
    }
    
    if (!preg_match('/^\d{2}:\d{2}$/', $settings['sunday_end_time'])) {
        $errors[] = 'Bitte geben Sie eine gültige Endzeit für Sonntag ein (HH:MM).';
    }
    
    if ($settings['start_interval_minutes'] < 5 || $settings['start_interval_minutes'] > 60) {
        $errors[] = 'Das Startintervall muss zwischen 5 und 60 Minuten liegen.';
    }
    
    if ($settings['max_starts_per_team'] < 1 || $settings['max_starts_per_team'] > 10) {
        $errors[] = 'Die maximale Anzahl Startzeiten pro Mannschaft muss zwischen 1 und 10 liegen.';
    }
    
    if (!empty($settings['reservation_start_date']) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $settings['reservation_start_date'])) {
        $errors[] = 'Bitte geben Sie ein gültiges Reservierungsstartdatum ein (YYYY-MM-DD).';
    }
    
    if (!empty($errors)) {
        $_SESSION['error'] = implode('<br>', $errors);
        header("Location: /admin/settings.php");
        exit();
    }
    
    // Einstellungen speichern
    foreach ($settings as $key => $value) {
        saveSetting($key, $value);
    }
    
    // Audit-Log
    logAudit('UPDATE', 'settings', 0, [], $settings);
    
    redirectWithSuccess('/admin/settings.php', 'Einstellungen erfolgreich gespeichert.');
}

// Aktuelle Einstellungen abrufen
$settings = [
    'race_date_saturday' => getSetting('race_date_saturday') ?? RACE_DATE_SATURDAY,
    'race_date_sunday' => getSetting('race_date_sunday') ?? RACE_DATE_SUNDAY,
    'saturday_start_time' => getSetting('saturday_start_time') ?? SATURDAY_START,
    'saturday_end_time' => getSetting('saturday_end_time') ?? SATURDAY_END,
    'sunday_start_time' => getSetting('sunday_start_time') ?? SUNDAY_START,
    'sunday_end_time' => getSetting('sunday_end_time') ?? SUNDAY_END,
    'start_interval_minutes' => getSetting('start_interval_minutes') ?? START_INTERVAL,
    'max_starts_per_team' => getSetting('max_starts_per_team') ?? MAX_STARTS_PER_TEAM,
    'reservation_start_date' => getSetting('reservation_start_date') ?? $GLOBALS['reservationStartDate'],
    'app_title' => getSetting('app_title') ?? APP_TITLE
];

// Startklassen abrufen
$startklassen = getStartklassen();

?>
<?php include __DIR__ . '/../includes/header.php'; ?>

    <div class="container">
        <div class="page-header">
            <h1>Systemeinstellungen</h1>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <h3>Renn-Daten</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="/admin/settings.php">
                    <input type="hidden" name="action" value="save_settings">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    
                    <div class="form-group">
                        <label for="app_title">Anwendungstitel</label>
                        <input type="text" id="app_title" name="app_title" value="<?php echo htmlspecialchars($settings['app_title']); ?>" required>
                    </div>
                    
                    <h4>Samstag</h4>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="race_date_saturday">Datum *</label>
                            <input type="date" id="race_date_saturday" name="race_date_saturday" value="<?php echo htmlspecialchars($settings['race_date_saturday']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="saturday_start_time">Startzeit *</label>
                            <input type="time" id="saturday_start_time" name="saturday_start_time" value="<?php echo htmlspecialchars($settings['saturday_start_time']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="saturday_end_time">Endzeit *</label>
                            <input type="time" id="saturday_end_time" name="saturday_end_time" value="<?php echo htmlspecialchars($settings['saturday_end_time']); ?>" required>
                        </div>
                    </div>
                    
                    <h4>Sonntag</h4>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="race_date_sunday">Datum *</label>
                            <input type="date" id="race_date_sunday" name="race_date_sunday" value="<?php echo htmlspecialchars($settings['race_date_sunday']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="sunday_start_time">Startzeit *</label>
                            <input type="time" id="sunday_start_time" name="sunday_start_time" value="<?php echo htmlspecialchars($settings['sunday_start_time']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="sunday_end_time">Endzeit *</label>
                            <input type="time" id="sunday_end_time" name="sunday_end_time" value="<?php echo htmlspecialchars($settings['sunday_end_time']); ?>" required>
                        </div>
                    </div>
                    
                    <h4>Startzeiten-Einstellungen</h4>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="start_interval_minutes">Startintervall (Minuten) *</label>
                            <input type="number" id="start_interval_minutes" name="start_interval_minutes" 
                                   value="<?php echo htmlspecialchars($settings['start_interval_minutes']); ?>" 
                                   min="5" max="60" required>
                        </div>
                        <div class="form-group">
                            <label for="max_starts_per_team">Max. Startzeiten pro Mannschaft *</label>
                            <input type="number" id="max_starts_per_team" name="max_starts_per_team" 
                                   value="<?php echo htmlspecialchars($settings['max_starts_per_team']); ?>" 
                                   min="1" max="10" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="reservation_start_date">Reservierungsstartdatum (optional)</label>
                        <input type="date" id="reservation_start_date" name="reservation_start_date" 
                               value="<?php echo htmlspecialchars($settings['reservation_start_date']); ?>">
                        <small>Ab diesem Datum können Mannschaften Startzeiten reservieren</small>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Einstellungen speichern</button>
                        <a href="/admin/" class="btn btn-secondary">Abbrechen</a>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h3>Startklassen</h3>
            </div>
            <div class="card-body">
                <p>Aktuell verfügbare Startklassen:</p>
                <ul>
                    <?php foreach ($startklassen as $klasse): ?>
                        <li><?php echo htmlspecialchars($klasse); ?></li>
                    <?php endforeach; ?>
                </ul>
                <p><small>Hinweis: Startklassen können in der Datei <code>config.php</code> angepasst werden.</small></p>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h3>Startzeiten zurücksetzen</h3>
            </div>
            <div class="card-body">
                <p>Hier können Sie alle Startzeiten zurücksetzen und neu generieren.</p>
                <a href="/admin/start_times.php?action=reset&csrf_token=<?php echo $csrfToken; ?>" 
                   class="btn btn-warning" 
                   onclick="return confirm('Alle Startzeiten zurücksetzen? Alle Zuweisungen und Ergebnisse gehen verloren!')">
                    Alle Startzeiten zurücksetzen
                </a>
            </div>
        </div>
    </div>
    
    <?php include __DIR__ . '/../includes/footer.php'; ?>
