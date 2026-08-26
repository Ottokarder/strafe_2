<?php
/**
 * Logout-Seite
 */

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

// Benutzer abmelden
logout();

// Weiterleitung zur Login-Seite
header("Location: login.php?logout=1");
exit();
