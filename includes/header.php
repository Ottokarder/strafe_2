<?php
/**
 * Header-Include
 */

require_once __DIR__ . '/../config_example.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';

// Session starten, falls noch nicht geschehen
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Aktuelle Seite bestimmen
$currentPage = basename($_SERVER['PHP_SELF']);

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Kanadierrennen CJD Kaltenstein - Verwaltung und Echtzeit-Ergebnisse">
    <title><?php echo defined('APP_TITLE') ? APP_TITLE : 'Kanadierrennen'; ?></title>
    <link rel="stylesheet" href="<?php echo strpos($currentPage, 'admin') !== false ? '../assets/css/style.css' : 'assets/css/style.css'; ?>">
    <link rel="icon" href="<?php echo defined('LOGO_PATH') ? LOGO_PATH : 'assets/images/logo.png'; ?>" type="image/png">
</head>
<body>
    <header class="header">
        <div class="header-container">
            <div class="header-brand">
                <?php if (file_exists(LOGO_PATH)): ?>
                    <img src="<?php echo LOGO_PATH; ?>" alt="<?php echo APP_TITLE; ?>" class="header-logo">
                <?php else: ?>
                    <h1 class="header-title"><?php echo APP_TITLE; ?></h1>
                <?php endif; ?>
            </div>
            
            <nav class="header-nav">
                <ul class="nav-list">
                    <li class="nav-item">
                        <a href="<?php echo strpos($currentPage, 'admin') !== false ? '../index.php' : 'index.php'; ?>" 
                           class="nav-link <?php echo $currentPage === 'index.php' ? 'active' : ''; ?>">
                            Startseite
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?php echo strpos($currentPage, 'admin') !== false ? '../start_times.php' : 'startzeiten.php'; ?>" 
                           class="nav-link <?php echo $currentPage === 'startzeiten.php' || $currentPage === 'start_times.php' ? 'active' : ''; ?>">
                            Startzeiten
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?php echo strpos($currentPage, 'admin') !== false ? '../results.php' : 'ergebnisse.php'; ?>" 
                           class="nav-link <?php echo $currentPage === 'ergebnisse.php' || $currentPage === 'results.php' ? 'active' : ''; ?>">
                            Ergebnisse
                        </a>
                    </li>
                    
                    <?php if (isAdmin()): ?>
                        <li class="nav-item">
                            <a href="<?php echo strpos($currentPage, 'admin') !== false ? 'index.php' : 'admin/index.php'; ?>" 
                               class="nav-link <?php echo strpos($currentPage, 'admin') !== false ? 'active' : ''; ?>">
                                Admin
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?php echo strpos($currentPage, 'admin') !== false ? '../logout.php' : 'logout.php'; ?>" 
                               class="nav-link">
                                Abmelden
                            </a>
                        </li>
                    <?php else: ?>
                        <?php if (!isLoggedIn()): ?>
                            <li class="nav-item">
                                <a href="<?php echo strpos($currentPage, 'admin') !== false ? '../login.php' : 'login.php'; ?>" 
                                   class="nav-link <?php echo $currentPage === 'login.php' ? 'active' : ''; ?>">
                                    Anmelden
                                </a>
                            </li>
                        <?php endif; ?>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>
    
    <main class="main-content">
