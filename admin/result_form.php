<?php
/**
 * Formular für Ergebnis bearbeiten/hinzufügen
 */

// Standardwerte für neues Ergebnis
if ($action === 'add') {
    $result = [
        'id' => null,
        'team_id' => '',
        'start_time_id' => null,
        'time' => '',
        'penalty_seconds' => 0
    ];
}

// Alle Teams abrufen
$teams = getAllTeams();

// Alle Startzeiten abrufen
$startTimes = getAllStartTimes();

// Startzeiten nach Team gruppieren
$startTimesByTeam = [];
foreach ($startTimes as $st) {
    if ($st['team_id']) {
        if (!isset($startTimesByTeam[$st['team_id']])) {
            $startTimesByTeam[$st['team_id']] = [];
        }
        $startTimesByTeam[$st['team_id']][] = $st;
    }
}

?>
<?php include __DIR__ . '/../includes/header.php'; ?>

    
    <div class="container">
        <div class="page-header">
            <h1><?php echo $action === 'add' ? 'Neues Ergebnis hinzufügen' : 'Ergebnis bearbeiten'; ?></h1>
            <a href="/admin/results.php" class="btn btn-secondary">Zurück zur Übersicht</a>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <h3><?php echo $action === 'add' ? 'Neues Ergebnis' : 'Ergebnis bearbeiten'; ?></h3>
            </div>
            <div class="card-body">
                <form method="POST" action="../results.php?action=<?php echo $action; ?><?php echo $result['id'] ? '&id=' . $result['id'] : ''; ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    
                    <div class="form-group">
                        <label for="team_id">Mannschaft *</label>
                        <select id="team_id" name="team_id" required onchange="updateStartTimes()">
                            <option value="">-- Bitte wählen --</option>
                            <?php foreach ($teams as $team): ?>
                                <option value="<?php echo $team['id']; ?>" 
                                    <?php echo $result['team_id'] == $team['id'] ? 'selected' : ''; ?>
                                    data-start-times="<?php echo htmlspecialchars(json_encode($startTimesByTeam[$team['id']] ?? [])); ?>">
                                    <?php echo htmlspecialchars($team['name']); ?> (<?php echo htmlspecialchars($team['startklasse']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="start_time_id">Startzeit (optional)</label>
                        <select id="start_time_id" name="start_time_id">
                            <option value="">-- Keine Startzeit ausgewahlt --</option>
                            <?php if ($result['team_id'] && isset($startTimesByTeam[$result['team_id']])): ?>
                                <?php foreach ($startTimesByTeam[$result['team_id']] as $st): ?>
                                    <option value="<?php echo $st['id']; ?>" 
                                        <?php echo $result['start_time_id'] == $st['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($st['date'] . ' ' . $st['time']); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="time">Rennzeit * (HH:MM:SS)</label>
                        <input type="text" id="time" name="time" 
                               value="<?php echo htmlspecialchars($result['time']); ?>" 
                               placeholder="HH:MM:SS" required>
                        <small>Format: Stunden:Minuten:Sekunden (z. B. 00:45:23)</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="penalty_seconds">Strafsekunden</label>
                        <input type="number" id="penalty_seconds" name="penalty_seconds" 
                               value="<?php echo $result['penalty_seconds']; ?>" min="0">
                        <small>Strafsekunden, die zur Rennzeit addiert werden.</small>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <?php echo $action === 'add' ? 'Ergebnis speichern' : 'Ergebnis aktualisieren'; ?>
                        </button>
                        <a href="/admin/results.php" class="btn btn-secondary">Abbrechen</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        function updateStartTimes() {
            const teamSelect = document.getElementById('team_id');
            const startTimeSelect = document.getElementById('start_time_id');
            const selectedTeamId = teamSelect.value;
            
            // Startzeiten für das ausgewählte Team abrufen
            const option = teamSelect.options[teamSelect.selectedIndex];
            const startTimes = JSON.parse(option.getAttribute('data-start-times') || '[]');
            
            // Startzeit-Select zurücksetzen
            startTimeSelect.innerHTML = '<option value="">-- Keine Startzeit ausgewählt --</option>';
            
            // Neue Optionen hinzufügen
            startTimes.forEach(function(st) {
                const option = document.createElement('option');
                option.value = st.id;
                option.textContent = st.date + ' ' + st.time;
                startTimeSelect.appendChild(option);
            });
        }
        
        // Initial aufrufen, falls bereits ein Team ausgewählt ist
        document.addEventListener('DOMContentLoaded', function() {
            const teamSelect = document.getElementById('team_id');
            if (teamSelect.value) {
                updateStartTimes();
            }
        });
    </script>
    
    <?php include __DIR__ . '/../includes/footer.php'; ?>