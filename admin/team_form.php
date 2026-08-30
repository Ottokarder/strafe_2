<?php
/**
 * Formular für Mannschaft bearbeiten/hinzufügen
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

// Aktuelle Aktion abrufen
$action = $_GET['action'] ?? '';
$teamId = $_GET['id'] ?? null;

// Standardwerte für neues Team
if ($action === 'add') {
    $team = [
        'id' => null,
        'name' => '',
        'startklasse' => '',
        'kapitaen' => '',
        'email' => ''
    ];
}

// Alle Startklassen abrufen
$startklassen = getStartklassen();

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
        
        <div class="card">
            <div class="card-header">
                <h3><?php echo $action === 'add' ? 'Neue Mannschaft' : 'Mannschaft bearbeiten'; ?></h3>
            </div>
            <div class="card-body">
                <form method="POST" action="/admin/teams.php?action=<?php echo $action; ?><?php echo $team['id'] ? '&id=' . $team['id'] : ''; ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    
                    <div class="form-group">
                        <label for="name">Mannschaftsname *</label>
                        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($team['name']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="startklasse">Startklasse *</label>
                        <select id="startklasse" name="startklasse" required>
                            <option value="">-- Bitte wählen --</option>
                            <?php foreach ($startklassen as $klasse): ?>
                                <option value="<?php echo htmlspecialchars($klasse); ?>" 
                                    <?php echo $team['startklasse'] === $klasse ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($klasse); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="kapitaen">Kapitän *</label>
                        <input type="text" id="kapitaen" name="kapitaen" value="<?php echo htmlspecialchars($team['kapitaen']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">E-Mail *</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($team['email']); ?>" required>
                        <small>Diese E-Mail wird nicht öffentlich angezeigt.</small>
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
    
    <?php include __DIR__ . '/../includes/footer.php'; ?>