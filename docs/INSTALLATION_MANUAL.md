# Kanadierrennen Webanwendung - Installationshandbuch

**Version:** 1.0  
**Letzte Aktualisierung:** 2025  
**Projekt:** [Ottokarder/strafe_2](https://github.com/Ottokarder/strafe_2)  

---

## 📖 Inhaltsverzeichnis

1. [Einleitung](#-einleitung)
2. [Systemanforderungen](#-systemanforderungen)
3. [Installationsvorbereitung](#-installationsvorbereitung)
4. [Dateien auf den Server kopieren](#-dateien-auf-den-server-kopieren)
5. [Konfiguration anpassen](#-konfiguration-anpassen)
6. [Datenbank einrichten](#-datenbank-einrichten)
7. [Sicherheit konfigurieren](#-sicherheit-konfigurieren)
8. [Domain und Webserver einrichten](#-domain-und-webserver-einrichten)
9. [Erste Schritte nach der Installation](#-erste-schritte-nach-der-installation)
10. [Testdurchlauf](#-testdurchlauf)
11. [Wartung und Backups](#-wartung-und-backups)
12. [Fehlerbehebung](#-fehlerbehebung)
13. [Anhang](#-anhang)

---

## 🎯 Einleitung

Dieses Handbuch beschreibt die Installation und Konfiguration der **Kanadierrennen-Webanwendung** für die Verwaltung und Echtzeit-Anzeige von Rennergebnissen. Die Anwendung wurde speziell für den **Kanuclub CJD Kaltenstein Vaihingen/Enz** entwickelt.

### Zielgruppe
- Systemadministratoren
- Webentwickler
- Rennverantwortliche

### Funktionen im Überblick
- Mannschaftsregistrierung (Name, Startklasse, Kapitän, E-Mail)
- Verwaltung von Startzeiten (Samstag/Sonntag, 10-Minuten-Intervalle)
- Erfassung von Rennergebnissen
- Echtzeit-Anzeige der Ergebnisse
- CSV-Export für Auswertungen
- Admin-Dashboard mit Statistiken

---

## 🖥️ Systemanforderungen

### Serveranforderungen
| Komponente | Anforderung | Hinweis |
|------------|-------------|---------|
| **Webserver** | Apache 2.4+ oder Nginx | Apache empfohlen |
| **PHP** | PHP 8.0 oder höher | mit mysqli-Erweiterung |
| **Datenbank** | MariaDB 10.5+ oder MySQL 8.0+ | utf8mb4-Unterstützung erforderlich |
| **Betriebssystem** | Linux (empfohlen) | Windows möglich, aber nicht offiziell getestet |
| **Speicherplatz** | 50 MB | inkl. Datenbank und Logs |
| **Arbeitsspeicher** | 128 MB | für PHP-Skripte |

### PHP-Erweiterungen
Die folgenden PHP-Erweiterungen müssen aktiviert sein:
- `mysqli` (für Datenbankverbindung)
- `json` (für Audit-Log)
- `session` (für Benutzerauthentifizierung)
- `password` (für Passwort-Hashing)
- `mbstring` (für UTF-8-Unterstützung)

### Prüfen der PHP-Konfiguration
```bash
php -m | grep -E "mysqli|json|session|password|mbstring"
```

---

## 📦 Installationsvorbereitung

### 1. Serverzugriff einrichten
Stellen Sie sicher, dass Sie:
- SSH-Zugriff auf den Server haben
- Root- oder Sudorechte besitzen
- Eine Domain oder Subdomain konfiguriert haben

### 2. Benötigte Tools installieren

**Auf Debian/Ubuntu:**
```bash
sudo apt update
sudo apt install -y apache2 mariadb-server php php-mysqli php-json php-mbstring git
```

**Auf CentOS/RHEL:**
```bash
sudo yum install -y httpd mariadb-server php php-mysqli php-json php-mbstring git
```

### 3. Datenbank-Benutzer vorbereiten
Notieren Sie sich folgende Daten für die spätere Konfiguration:
- Datenbank-Host (meist `localhost`)
- Datenbankname (z. B. `strafe_2`)
- Datenbank-Benutzername (z. B. `strafe_user`)
- Datenbank-Passwort (sicher wählen!)
- Admin-Benutzername und Passwort

---

## 📂 Dateien auf den Server kopieren

### Option 1: Git Clone (empfohlen)
```bash
# In das Webverzeichnis wechseln
cd /var/www/html/

# Repository klonen
git clone https://github.com/Ottokarder/strafe_2.git kanadierrennen

# In das Projektverzeichnis wechseln
cd kanadierrennen
```

### Option 2: FTP/SFTP
1. Laden Sie alle Dateien aus dem Repository herunter
2. Packen Sie sie in ein ZIP-Archiv
3. Laden Sie das Archiv per FTP/SFTP auf den Server hoch
4. Entpacken Sie das Archiv im Webverzeichnis

### Option 3: Manuell per SCP
```bash
# Von Ihrem lokalen Rechner
scp -r /pfad/zu/strafe_2/ benutzername@server:/var/www/html/kanadierrennen
```

### Verzeichnisstruktur
Nach der Installation sollte die Struktur wie folgt aussehen:
```
kanadierrennen/
├── .htaccess              # Apache-Konfiguration
├── .gitignore             # Git-Ignore-Dateien
├── config.php             # ⚠️ Wird von Ihnen erstellt!
├── config_example.php     # Vorlage für config.php
├── database_schema.sql    # Datenbank-Schema
├── index.php              # Öffentliche Startseite
├── startzeiten.php        # Startzeiten (öffentlich)
├── ergebnisse.php         # Ergebnisse (öffentlich)
├── login.php              # Admin-Login
├── logout.php             # Abmelden
├── Pflichtenheft.md      # Projektanforderungen
├── assets/                # CSS, JS, Bilder
│   ├── css/
│   ├── js/
│   └── images/
│       └── logo.png       # Logo des Kanuclubs
└── admin/                 # Admin-Bereich
    ├── index.php          # Dashboard
    ├── teams.php          # Mannschaftsverwaltung
    ├── team_form.php      # Mannschaft bearbeiten
    ├── start_times.php     # Startzeiten verwalten
    ├── results.php        # Ergebnisse verwalten
    ├── result_form.php    # Ergebnis bearbeiten
    ├── export.php         # CSV-Export
    └── users.php          # Benutzerverwaltung
└── includes/              # PHP-Includes
    ├── auth.php           # Authentifizierung
    ├── db.php             # Datenbankfunktionen
    ├── footer.php         # Footer
    └── header.php         # Header
```

---

## ⚙️ Konfiguration anpassen

### 1. config.php erstellen
Kopieren Sie die Vorlage und passen Sie sie an:
```bash
cp config_example.php config.php
```

### 2. config.php bearbeiten
Öffnen Sie die Datei mit einem Editor:
```bash
nano config.php
```

**Wichtige Einstellungen:**
```php
<?php
/**
 * Datenbank-Konfiguration für die Kanadierrennen-Webanwendung
 */

// Datenbank-Verbindung (MariaDB)
define('DB_HOST', 'localhost');      // Datenbank-Host
define('DB_USER', 'strafe_user');    // Datenbank-Benutzername
define('DB_PASS', 'sicheres_passwort_hier_eintragen'); // Datenbank-Passwort
define('DB_NAME', 'strafe_2');       // Datenbankname

// Admin-Zugangsdaten (Fallback, wird später in der DB gespeichert)
define('ADMIN_USERNAME', 'admin');
define('ADMIN_PASSWORD', 'neues_sicheres_admin_passwort'); // ⚠️ UNBEDINGT ÄNDERN!

// Anwendungseinstellungen
define('APP_TITLE', 'Kanadierrennen CJD Kaltenstein');

// Renn-Daten (können später im Admin-Bereich geändert werden)
define('RACE_DATE_SATURDAY', '2025-06-21'); // Samstag des Rennens
define('RACE_DATE_SUNDAY', '2025-06-22');   // Sonntag des Rennens

// Startzeiten-Intervalle (in Minuten)
define('START_INTERVAL', 10); // 10-Minuten-Intervalle

// Samstag: 14:00-18:00 Uhr
define('SATURDAY_START', '14:00');
define('SATURDAY_END', '18:00');

// Sonntag: 11:00-16:00 Uhr
define('SUNDAY_START', '11:00');
define('SUNDAY_END', '16:00');

// Pfad zum Logo
define('LOGO_PATH', 'assets/images/logo.png');

// Fehlerbehandlung
error_reporting(E_ALL);
ini_set('display_errors', 0); // Im Produktionsmodus auf 0 setzen
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

// Zeitzone setzen
date_default_timezone_set('Europe/Berlin');

// Session-Einstellungen
ini_set('session.cookie_lifetime', 0); // Session endet beim Schließen des Browsers
ini_set('session.cookie_secure', false); // Auf true setzen, wenn HTTPS aktiviert ist
ini_set('session.cookie_httponly', true); // Schutz vor XSS
session_start();

// Sicherheitsheader
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");

// Startklassen
$startklassen = [
    'Damen',
    'Gemischte Mannschaften',
    'Herren',
    'Betriebsmannschaften',
    'Ortsteile'
];

// Maximale Startzeiten pro Mannschaft
define('MAX_STARTS_PER_TEAM', 3);

// Reservierungszeitraum (6 Wochen vor dem Rennen)
$reservationStartDate = date('Y-m-d', strtotime('-6 weeks', strtotime(RACE_DATE_SATURDAY)));

// Aktuelles Datum für Überprüfungen
$currentDate = date('Y-m-d');
$currentTime = date('H:i');
```

### 3. Wichtige Hinweise zur Konfiguration
- **Datenbank-Passwort:** Verwenden Sie ein sicheres Passwort mit mindestens 16 Zeichen
- **Admin-Passwort:** Ändern Sie das Standard-Passwort `admin123` **SOFORT**!
- **HTTPS:** Aktivieren Sie `session.cookie_secure` nur, wenn HTTPS verfügbar ist
- **Zeitzone:** `Europe/Berlin` ist voreingestellt

---

## 🗃️ Datenbank einrichten

### 1. Datenbank und Benutzer anlegen
Melden Sie sich bei MariaDB/MySQL an:
```bash
sudo mysql -u root -p
```

Führen Sie folgende SQL-Befehle aus:
```sql
-- Datenbank erstellen
CREATE DATABASE strafe_2 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Benutzer erstellen
CREATE USER 'strafe_user'@'localhost' IDENTIFIED BY 'sicheres_passwort';

-- Berechtigungen zuweisen
GRANT ALL PRIVILEGES ON strafe_2.* TO 'strafe_user'@'localhost';

-- Änderungen übernehmen
FLUSH PRIVILEGES;

-- Optional: Remote-Zugriff ermöglichen (falls nötig)
CREATE USER 'strafe_user'@'%' IDENTIFIED BY 'sicheres_passwort';
GRANT ALL PRIVILEGES ON strafe_2.* TO 'strafe_user'@'%';
FLUSH PRIVILEGES;
```

### 2. Datenbank-Schema importieren
```bash
# Importieren Sie das Schema
mysql -u strafe_user -p strafe_2 < database_schema.sql
```

**Hinweis:** Das Schema enthält bereits:
- Standard-Administrator-Benutzer (`admin` / `admin123`)
- Standard-Einstellungen für Renn-Daten
- Prozeduren zum Generieren von Startzeiten
- Trigger für Audit-Logging

### 3. Startzeiten generieren
Die Startzeiten können auf zwei Arten generiert werden:

**Option 1: Über die Prozedur in der Datenbank:**
```sql
-- Startzeiten für Samstag generieren
CALL create_start_times_for_day('2025-06-21', '14:00', '18:00', 10);

-- Startzeiten für Sonntag generieren
CALL create_start_times_for_day('2025-06-22', '11:00', '16:00', 10);
```

**Option 2: Über den Admin-Bereich:**
1. Melden Sie sich im Admin-Bereich an
2. Gehen Sie zu "Startzeiten"
3. Klicken Sie auf "Alle Startzeiten zurücksetzen"

**Option 3: Über die resetAllStartTimes()-Funktion:**
Die Funktion liest die Einstellungen aus der Datenbank und generiert die Startzeiten automatisch.

---

## 🔐 Sicherheit konfigurieren

### 1. Dateiberechtigungen setzen
```bash
# Verzeichnisrechte
chmod 755 /var/www/html/kanadierrennen
chmod 644 /var/www/html/kanadierrennen/*.php
chmod 755 /var/www/html/kanadierrennen/admin/
chmod 644 /var/www/html/kanadierrennen/admin/*.php
chmod 755 /var/www/html/kanadierrennen/assets/
chmod 644 /var/www/html/kanadierrennen/assets/*

# Besitzer setzen (anpassen an Ihren Webserver-Benutzer)
chown -R www-data:www-data /var/www/html/kanadierrennen
```

### 2. Sensible Dateien schützen
Die `.htaccess`-Datei blockiert bereits den Zugriff auf:
- `config.php`
- `config_example.php`
- `.git/`-Verzeichnis
- `includes/`-Verzeichnis
- Dateien mit bestimmten Endungen (`.sql`, `.md`, `.log`, etc.)

### 3. HTTPS einrichten (empfohlen)

**Mit Let's Encrypt (kostenlos):**
```bash
# Certbot installieren
sudo apt install certbot python3-certbot-apache

# Zertifikat anfordern
sudo certbot --apache -d kanadierrennen.euredomain.de

# Automatische Verlängerung einrichten
sudo certbot renew --dry-run
```

**In der config.php aktivieren:**
```php
ini_set('session.cookie_secure', true); // Nur über HTTPS
```

**In der .htaccess aktivieren:**
Entkommentieren Sie die Zeilen für HTTPS-Weiterleitung:
```apache
RewriteCond %{HTTPS} off
RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

### 4. Firewall konfigurieren
```bash
# UFW (Ubuntu)
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable

# firewalld (CentOS)
sudo firewall-cmd --permanent --add-service=http
sudo firewall-cmd --permanent --add-service=https
sudo firewall-cmd --reload
```

---

## 🌐 Domain und Webserver einrichten

### 1. Apache konfigurieren

**a) Virtuellen Host erstellen:**
```bash
sudo nano /etc/apache2/sites-available/kanadierrennen.conf
```

**Inhalt der Konfiguration:**
```apache
<VirtualHost *:80>
    ServerName kanadierrennen.euredomain.de
    ServerAdmin webmaster@euredomain.de
    DocumentRoot /var/www/html/kanadierrennen
    
    <Directory /var/www/html/kanadierrennen>
        Options FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/kanadierrennen_error.log
    CustomLog ${APACHE_LOG_DIR}/kanadierrennen_access.log combined
</VirtualHost>
```

**b) Virtuellen Host aktivieren:**
```bash
sudo a2ensite kanadierrennen.conf
sudo a2enmod rewrite
sudo systemctl restart apache2
```

### 2. DNS-Einträge konfigurieren
Erstellen Sie einen A- oder CNAME-Eintrag für Ihre Domain:
```
kanadierrennen.euredomain.de.  IN  A  [Server-IP]
```

### 3. Testen der Konfiguration
```bash
# Apache-Konfiguration testen
sudo apache2ctl configtest

# Apache neu starten
sudo systemctl restart apache2

# Status prüfen
sudo systemctl status apache2
```

---

## 🚀 Erste Schritte nach der Installation

### 1. Admin-Passwort ändern
1. Melden Sie sich mit den Standard-Anmeldedaten an:
   - Benutzername: `admin`
   - Passwort: `admin123`
2. Gehen Sie zu **Benutzerverwaltung** (`/admin/users.php`)
3. Klicken Sie auf "Bearbeiten" beim Admin-Benutzer
4. Ändern Sie das Passwort und speichern Sie die Änderungen

### 2. Renn-Daten prüfen
1. Gehen Sie zu **Einstellungen** (`/admin/settings.php`)
2. Überprüfen Sie die Renn-Daten:
   - Samstag: 21. Juni 2025, 14:00-18:00 Uhr
   - Sonntag: 22. Juni 2025, 11:00-16:00 Uhr
3. Passen Sie die Daten bei Bedarf an

### 3. Startzeiten generieren
1. Gehen Sie zu **Startzeiten** (`/admin/start_times.php`)
2. Klicken Sie auf "Alle Startzeiten zurücksetzen"
3. Bestätigen Sie die Aktion
4. Überprüfen Sie, ob die Startzeiten generiert wurden

### 4. Test-Mannschaft anlegen
1. Gehen Sie zu **Mannschaften** (`/admin/teams.php`)
2. Klicken Sie auf "Neue Mannschaft"
3. Geben Sie Testdaten ein:
   - Name: Testmannschaft
   - Startklasse: Herren
   - Kapitän: Max Mustermann
   - E-Mail: test@example.com
4. Speichern Sie die Mannschaft

### 5. Startzeit zuweisen
1. Gehen Sie zu **Startzeiten** (`/admin/start_times.php`)
2. Wählen Sie eine freie Startzeit aus
3. Klicken Sie auf "Mannschaft zuordnen"
4. Wählen Sie die Testmannschaft aus
5. Bestätigen Sie die Zuordnung

### 6. Ergebnis erfassen
1. Gehen Sie zu **Ergebnisse** (`/admin/results.php`)
2. Klicken Sie auf "Neues Ergebnis"
3. Wählen Sie die Testmannschaft und die Startzeit aus
4. Geben Sie eine Rennzeit ein (Format: MM:SS, z. B. 45:23)
5. Speichern Sie das Ergebnis

---

## ✅ Testdurchlauf

### Öffentlicher Bereich
| Seite | URL | Erwartetes Ergebnis |
|-------|-----|---------------------|
| Startseite | `/` oder `/index.php` | Liste der gebuchten Startzeiten |
| Startzeiten | `/startzeiten.php` | Tabelle mit freien und gebuchten Startzeiten |
| Ergebnisse | `/ergebnisse.php` | Liste der erfassten Ergebnisse |

### Admin-Bereich
| Seite | URL | Erwartetes Ergebnis |
|-------|-----|---------------------|
| Login | `/login.php` | Login-Formular |
| Dashboard | `/admin/` oder `/admin/index.php` | Übersicht mit Statistiken |
| Mannschaften | `/admin/teams.php` | Liste aller Mannschaften |
| Startzeiten | `/admin/start_times.php` | Liste aller Startzeiten |
| Ergebnisse | `/admin/results.php` | Liste aller Ergebnisse |
| Benutzer | `/admin/users.php` | Liste aller Benutzer |
| Einstellungen | `/admin/settings.php` | Systemeinstellungen |

### Test-Checkliste
- [ ] Öffentliche Startseite lädt ohne Fehler
- [ ] Startzeiten werden korrekt angezeigt
- [ ] Ergebnisse werden korrekt angezeigt
- [ ] Admin-Login funktioniert
- [ ] Dashboard zeigt Statistiken an
- [ ] Mannschaft kann erstellt, bearbeitet und gelöscht werden
- [ ] Startzeit kann zugewiesen und entfernt werden
- [ ] Ergebnis kann erfasst und bearbeitet werden
- [ ] CSV-Export funktioniert
- [ ] Mobile Ansicht funktioniert (auf Smartphone testen)

---

## 🔧 Wartung und Backups

### 1. Regelmäßige Backups

**Datenbank-Backup (täglich):**
```bash
# Manuelles Backup
mysqldump -u strafe_user -p strafe_2 > /backup/strafe_2_$(date +%Y%m%d).sql

# Automatisches Backup (Cronjob)
0 3 * * * mysqldump -u strafe_user -p'sicheres_passwort' strafe_2 > /backup/strafe_2_$(date +\%Y\%m\%d).sql
```

**Dateisystem-Backup (wöchentlich):**
```bash
0 4 * * 0 tar -czvf /backup/kanadierrennen_$(date +%Y%m%d).tar.gz /var/www/html/kanadierrennen
```

### 2. Backup-Skript
Erstellen Sie ein Backup-Skript:
```bash
#!/bin/bash
# Backup-Skript für Kanadierrennen

BACKUP_DIR="/backup/kanadierrennen"
DATE=$(date +%Y%m%d_%H%M%S)

# Verzeichnis erstellen
mkdir -p $BACKUP_DIR

# Datenbank-Backup
mysqldump -u strafe_user -p'sicheres_passwort' strafe_2 > $BACKUP_DIR/db_$DATE.sql

# Dateisystem-Backup
tar -czvf $BACKUP_DIR/files_$DATE.tar.gz /var/www/html/kanadierrennen

# Alte Backups bereinigen (älter als 30 Tage)
find $BACKUP_DIR -type f -mtime +30 -delete
```

### 3. Log-Dateien

**Fehlerlog prüfen:**
```bash
tail -f /var/www/html/kanadierrennen/error.log
```

**Apache-Logs prüfen:**
```bash
tail -f /var/log/apache2/kanadierrennen_error.log
tail -f /var/log/apache2/kanadierrennen_access.log
```

### 4. Datenbank-Optimierung
```sql
-- Tabellen optimieren
OPTIMIZE TABLE teams, start_times, results, users, settings, audit_log;

-- Indizes prüfen
ANALYZE TABLE teams, start_times, results;
```

---

## ❌ Fehlerbehebung

### Häufige Probleme und Lösungen

| **Problem** | **Mögliche Ursache** | **Lösung** |
|-------------|----------------------|------------|
| **Weiße Seite / 500 Error** | PHP-Fehler | Fehlerlog prüfen (`error.log`) |
| **Datenbank-Verbindungsfehler** | Falsche Anmeldedaten | `config.php` prüfen |
| **Startzeiten werden nicht angezeigt** | Keine Startzeiten generiert | `resetAllStartTimes()` ausführen |
| **Zeiten werden falsch sortiert** | Falscher Datentyp | `time`-Spalte muss `TIME`-Typ sein |
| **CSV-Export funktioniert nicht** | Schreibrechte | Berechtigungen prüfen |
| **Login funktioniert nicht** | Falsches Passwort | Passwort in der Datenbank prüfen |
| **Sessions funktionieren nicht** | Session-Pfad | `session.save_path` in php.ini prüfen |
| **Bilder werden nicht angezeigt** | Falscher Pfad | `LOGO_PATH` in config.php prüfen |

### Detaillierte Fehlerbehebung

**1. PHP-Fehler analysieren:**
```bash
# Fehlerlog anzeigen
tail -n 50 /var/www/html/kanadierrennen/error.log

# PHP-Fehler in Echtzeit anzeigen (temporär)
php -S localhost:8000 -t /var/www/html/kanadierrennen
```

**2. Datenbank-Verbindung testen:**
```php
<?php
// test_db.php
require_once 'config.php';

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die("Verbindungsfehler: " . $conn->connect_error);
    }
    echo "Datenbankverbindung erfolgreich!";
    $conn->close();
} catch (Exception $e) {
    echo "Fehler: " . $e->getMessage();
}
```

**3. Apache-Konfiguration prüfen:**
```bash
# Konfiguration testen
sudo apache2ctl configtest

# Module prüfen
sudo apache2ctl -M | grep rewrite

# Virtuelle Hosts prüfen
sudo apache2ctl -S
```

**4. Berechtigungen prüfen:**
```bash
# Aktuelle Berechtigungen anzeigen
ls -la /var/www/html/kanadierrennen/

# Besitzer prüfen
ls -ld /var/www/html/kanadierrennen/
```

**5. PHP-Konfiguration prüfen:**
```bash
php -i | grep -E "memory_limit|upload_max_filesize|post_max_size"
```

---

## 📚 Anhang

### A. Datenbank-Struktur

Die Anwendung verwendet folgende Tabellen:

| Tabelle | Beschreibung |
|---------|--------------|
| `teams` | Mannschaften mit Name, Startklasse, Kapitän, E-Mail |
| `start_times` | Startzeiten mit Datum, Uhrzeit und Zuordnung zu Mannschaften |
| `results` | Rennergebnisse mit Zeit und Team-Referenz |
| `users` | Benutzer für die Admin-Authentifizierung |
| `settings` | Systemeinstellungen (Renn-Daten, Intervalle, etc.) |
| `audit_log` | Protokoll aller Änderungen für die Nachverfolgbarkeit |

### B. Wichtige SQL-Prozeduren

| Prozedur | Beschreibung |
|----------|--------------|
| `create_start_times_for_day(date, start_time, end_time, interval)` | Erstellt Startzeiten für einen Tag |
| `reset_all_start_times()` | Setzt alle Startzeiten zurück und generiert sie neu |

### C. Wichtige PHP-Funktionen

| Funktion | Datei | Beschreibung |
|----------|-------|--------------|
| `getDBConnection()` | `includes/db.php` | Stellt Datenbankverbindung her |
| `fetchAll()`, `fetchOne()` | `includes/db.php` | Führt SELECT-Abfragen aus |
| `execute()` | `includes/db.php` | Führt INSERT/UPDATE/DELETE aus |
| `authenticateUser()` | `includes/auth.php` | Authentifiziert Benutzer |
| `isAdmin()` | `includes/auth.php` | Prüft Admin-Rechte |
| `generateStartTimesForDay()` | `includes/db.php` | Generiert Startzeiten |
| `resetAllStartTimes()` | `includes/db.php` | Setzt Startzeiten zurück |
| `exportResultsToCSV()` | `includes/db.php` | Exportiert Ergebnisse als CSV |

### D. Standard-Administrator

| Benutzername | Passwort | Rolle |
|--------------|----------|------|
| `admin` | `admin123` | Administrator |

**⚠️ WICHTIG:** Ändern Sie das Passwort **SOFORT** nach der Installation!

### E. Standard-Einstellungen

| Einstellung | Wert |
|-------------|------|
| Renn-Datum Samstag | 2025-06-21 |
| Renn-Datum Sonntag | 2025-06-22 |
| Samstag Start | 14:00 |
| Samstag Ende | 18:00 |
| Sonntag Start | 11:00 |
| Sonntag Ende | 16:00 |
| Start-Intervall | 10 Minuten |
| Max. Startzeiten pro Mannschaft | 3 |
| Reservierungsstart | 6 Wochen vor dem Rennen |

### F. Startklassen

Die Anwendung unterstützt folgende Startklassen:
- Damen
- Gemischte Mannschaften
- Herren
- Betriebsmannschaften
- Ortsteile

Diese können in der `config.php` oder über die Datenbank angepasst werden.

---

## 📞 Support

### Kontakt
Bei Fragen oder Problemen wenden Sie sich bitte an:
- **Projektverantwortlicher:** Michael Fischer
- **Repository:** [https://github.com/Ottokarder/strafe_2](https://github.com/Ottokarder/strafe_2)

### Issues melden
1. Gehen Sie zu [https://github.com/Ottokarder/strafe_2/issues](https://github.com/Ottokarder/strafe_2/issues)
2. Klicken Sie auf "New Issue"
3. Beschreiben Sie das Problem mit:
   - PHP-Version
   - Datenbank-Version
   - Webserver-Version
   - Fehlerbeschreibung
   - Schritte zur Reproduktion
   - Fehlerlog-Auszüge

---

## 📝 Versionshistorie

| Version | Datum | Beschreibung |
|---------|-------|--------------|
| 1.0 | 2025 | Erstes Installationshandbuch |

---

**© 2025 Kanuclub CJD Kaltenstein Vaihingen/Enz**  
**Lizenz: MIT**  

---

*Dieses Handbuch wird regelmäßig aktualisiert. Bitte prüfen Sie das Repository auf die neueste Version.*
