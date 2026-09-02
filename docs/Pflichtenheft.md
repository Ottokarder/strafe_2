# Pflichtenheft: Webanwendung zur Verwaltung und Ergebnisanzeige eines Kanadierrennens

---

## 1. Projektziele
Die Webanwendung dient der Verwaltung und Echtzeit-Anzeige der Ergebnisse eines Kanadierrennens. Sie ermöglicht die Vorab-Registrierung von Mannschaften, die Verwaltung von Startzeiten, die Erfassung von Rennergebnissen und die öffentliche Anzeige der Ergebnisse.

---

## 2. Funktionale Anforderungen

### 2.1. Benutzerrollen
- **Administrator (Michael Fischer):**
  - Verwaltet Mannschaften, Startzeiten und Ergebnisse.
  - Trägt Mannschaftsdaten (Name, Startklasse, Kapitän, E-Mail) über ein Online-Formular ein.
  - Bearbeitet Mannschaftsdaten (Name, Startklasse) nach Rennbeginn.
  - Trägt Rennergebnisse (Zeiten) ein.
  - Generiert Auswertungen (Listen nach Startklasse, sortiert nach Zeit).
  - Kann für eine Mannschaft bis zu drei Startzeiten verwalten.

- **Mannschaftskapitäne:**
  - Können freie Startzeiten auf der Webseite einsehen.
  - Melden sich beim Administrator, um eine Startzeit zu reservieren.

- **Öffentliche Nutzer:**
  - Können die Liste der Startzeiten (ohne Kapitäns-E-Mails) einsehen.
  - Können die Rennergebnisse in Echtzeit einsehen.

### 2.2. Funktionen

#### 2.2.1. Vor dem Rennen
- **Mannschaftsregistrierung:**
  - Administrator trägt Mannschaftsnamen, Startklasse (Damen, gemischte Mannschaften, Herren, Betriebsmannschaften, Ortsteile), Kapitänsname und E-Mail über ein Online-Formular ein.
  - **Mehrfachstart:** Eine Mannschaft kann bis zu drei Startzeiten belegen.
  - Startzeiten werden in 10-Minuten-Intervallen angeboten:
    - Samstag: 14:00–18:00 Uhr (24 Startplätze).
    - Sonntag: 11:00–16:00 Uhr (30 Startplätze).
  - **Liste der freien Startzeiten wird sofort ab Beginn des Reservierungszeitraums (6 Wochen vor dem Rennen) auf der Webseite veröffentlicht.**

#### 2.2.2. Während des Rennens
- **Änderungen:**
  - Administrator kann Mannschaftsnamen und Startklasse nachträglich ändern.
- **Ergebniserfassung:**
  - Administrator trägt die erreichte Zeit einer Mannschaft ein.
  - Ergebnisse werden sofort öffentlich angezeigt.

#### 2.2.3. Nach dem Rennen
- **Auswertung:**
  - Für jede Startklasse wird eine Liste erstellt, sortiert nach der erreichten Zeit (kürzeste Zeit zuerst).
  - Listen werden auf einer Webseite angezeigt.
  - Listen können als CSV-Dateien heruntergeladen werden.

### 2.3. Daten
- **Mannschaftsdaten:**
  - Name, Startklasse, Kapitänsname, E-Mail (nicht öffentlich).
- **Startzeiten:**
  - Datum, Uhrzeit, zugewiesene Mannschaft (bis zu drei Startzeiten pro Mannschaft).
- **Ergebnisse:**
  - Mannschaftsname, Startklasse, erreichte Zeit.

---

## 3. Nicht-funktionale Anforderungen

### 3.1. Performance
- Die Webanwendung muss Echtzeit-Updates für Ergebnisse und Startzeiten unterstützen.
- Auswertungen müssen jederzeit (auch während des Rennens) abrufbar sein.

### 3.2. Sicherheit
- E-Mail-Adressen der Kapitäne dürfen nicht öffentlich einsehbar sein.
- Zugriff auf administrative Funktionen nur für den Administrator.

### 3.3. Benutzerfreundlichkeit
- Klare und intuitive Oberfläche für Administrator und öffentliche Nutzer.
- Mobile Optimierung für die Nutzung vor Ort.
- Design orientiert sich an [https://kccjd.de](https://kccjd.de).
- **Logo:** Das Logo des Kanuclubs CJD Kaltenstein Vaihingen/Enz wird auf der Webseite angezeigt.

### 3.4. Technische Rahmenbedingungen
- **Frontend:** Responsive Webdesign (HTML, CSS, JavaScript).
- **Backend:** Server zur Datenverwaltung (z. B. PHP, Node.js, Python).
- **Datenbank:** MariaDB (angelegt über phpMyAdmin).
- **Hosting:**
  - Eigenständige Webanwendung auf einem vServer der Telekom.
  - Aufruf über eine Subdomain.
  - Lokale Tests auf dem heimischen Server von Michael Fischer möglich.
- **Versionsverwaltung:**
  - GitHub-Repository: [Ottokarder/strafe_2](https://github.com/Ottokarder/strafe_2).
  - Austausch von Daten und Dateien über das Repository.
- **Datenbankkonfiguration:**
  - SQL-Dateien für die Datenbankkonfiguration werden bereitgestellt.

---

## 4. Projektrahmen

### 4.1. Zeitplan
- **6 Wochen vor dem Rennen:** Beginn der Mannschaftsregistrierung und Veröffentlichung der Startzeiten.
- **Renntage:** Samstag (14:00–18:00 Uhr) und Sonntag (11:00–16:00 Uhr).
- **Nach dem Rennen:** Auswertung und Bereitstellung der CSV-Dateien.

### 4.2. Budget
- Keine zusätzlichen Kosten für Softwarelizenzen (Nutzung von Open-Source-Technologien).
- Hosting über vServer der Telekom.

### 4.3. Team
- **Administrator:** Michael Fischer (Verwaltung, Dateneingabe).
- **Entwicklung:** Unterstützung durch KI (Pflichtenheft, Implementierung).

---

## 5. Risiken und Abhängigkeiten
- **Risiken:**
  - Technische Probleme während des Rennens (z. B. Serverausfall).
  - Falsche Dateneingabe durch Administrator.
- **Abhängigkeiten:**
  - Verfügbarkeit des vServers.
  - korrekte Konfiguration der MariaDB-Datenbank.

---

## 6. Akzeptanzkriterien
- Die Webanwendung muss alle beschriebenen Funktionen abdecken.
- Die Ergebnisse müssen korrekt sortiert und in Echtzeit angezeigt werden.
- CSV-Export muss fehlerfrei funktionieren.
- Die Oberfläche muss auf allen gängigen Browsern und Geräten nutzbar sein.

---

## 7. Dokumentation und Wartung
- **Benutzerdokumentation:** Kurzanleitung für den Administrator.
- **Wartung:** Regelmäßige Backups der Datenbank.
- **Support:** Bei Problemen: Kontakt über E-Mail oder Telefon.

---

## 8. Offene Fragen
- Gibt es weitere spezielle Design-Anforderungen (z. B. Farbschema)?
