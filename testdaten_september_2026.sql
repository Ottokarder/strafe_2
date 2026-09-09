-- =====================================================================
-- Testdaten für Probelauf - Kanadierrennen 12./13. September 2026
-- =====================================================================
-- Wirkungsweise:
--   1. Löscht die Daten (nicht die Tabellenstruktur) aus:
--        captains, teams, start_times, results, audit_log
--      und setzt deren AUTO_INCREMENT zurück.
--   2. Aktualisiert die Renndaten in der Tabelle `settings` auf
--      Samstag 12.09.2026 / Sonntag 13.09.2026 (inkl. Reservierungsbeginn).
--   3. Legt die Kapitäne, Mannschaften und Startzeiten gemäß der
--      übergebenen Tabelle an.
--   NICHT angetastet: users
--   settings wird nur aktualisiert (Renndaten), sonstige Einstellungen
--   (Startklassen, Intervall etc.) bleiben unverändert.
--
-- Import: phpMyAdmin -> SQL-Tab importieren.
-- =====================================================================

-- WICHTIG: Datenbanknamen an deine Umgebung anpassen.
-- In der Fehlermeldung war die DB 'strafe2_tst' zu sehen; trage hier den
-- tatsächlichen Namen deiner phpMyAdmin-Datenbank ein (oder kommentiere die
-- USE-Zeile aus, wenn die richtige DB in phpMyAdmin bereits ausgewählt ist).
USE `strafe2_tst`;

-- ---------------------------------------------------------------------
-- 1) Vorhandene Daten löschen (Tabellenstruktur bleibt erhalten)
-- ---------------------------------------------------------------------
-- Hinweis: TRUNCATE funktioniert bei Tabellen mit Foreign-Key-Beziehungen
-- (z.B. start_times <- results) auch mit FOREIGN_KEY_CHECKS=0 in MariaDB
-- nicht zuverlässig. Daher DELETE mit anschließendem AUTO_INCREMENT-Reset,
-- genau wie die Anwendung selbst in resetAllStartTimes().
SET FOREIGN_KEY_CHECKS = 0;

DELETE FROM `results`;
DELETE FROM `start_times`;
DELETE FROM `teams`;
DELETE FROM `captains`;
DELETE FROM `audit_log`;

ALTER TABLE `results`     AUTO_INCREMENT = 1;
ALTER TABLE `start_times` AUTO_INCREMENT = 1;
ALTER TABLE `teams`       AUTO_INCREMENT = 1;
ALTER TABLE `captains`    AUTO_INCREMENT = 1;
ALTER TABLE `audit_log`   AUTO_INCREMENT = 1;

SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------
-- 2) Renndaten in settings anpassen (September 2026)
-- ---------------------------------------------------------------------
UPDATE `settings` SET `value` = '2026-09-12' WHERE `key` = 'race_date_saturday';
UPDATE `settings` SET `value` = '2026-09-13' WHERE `key` = 'race_date_sunday';
-- Reservierungsbeginn = 6 Wochen vor dem Rennen (12.09.2026 - 42 Tage = 01.08.2026)
UPDATE `settings` SET `value` = '2026-08-01' WHERE `key` = 'reservation_start_date';

-- ---------------------------------------------------------------------
-- 3) Kapitäne (eindeutig pro Ansprechpartner-Name)
-- ---------------------------------------------------------------------
-- Kontakt-Regel: Wert mit '@' -> email; Wert nur aus Ziffern/Telefonformat
-- ohne '@' -> phone; sonst (z.B. nur ein Name in der E-Mail-Spalte) -> kein Kontakt.
-- Bei mehrfach auftretendem Kapitän mit unterschiedlichen Kontaktdaten wurde
-- die erste plausible/valide Adresse gewählt (siehe Anmerkungen am Blockende):
--   Peter Schimke: 2. Telefon (DIVaN) 07042 4342 nicht gespeichert.
--   Anita Götz (Horrheim): Tippfehler-Typo ignoriert.
--   Dirk Sommerfeld (Fitnessfrauen): dirk.Sommerfeld@... (großes S) ignoriert.
--   Martina Fischer: mehrere Adressen, martina.fischer@vaihingen.de ist konsistent.
INSERT INTO `captains` (`id`, `name`, `email`, `phone`) VALUES
(1,  'Peter Schimke',    NULL,                              '07042 4324'),
(2,  'Katrin Reutter',   'katrin.reutter@posteo.de',        NULL),
(3,  'Jakob Stärk',      'jakob.staerk@t-online.de',         NULL),
(4,  'Anita Götz',       'anita-goetz@t-online.de',          NULL),
(5,  'Dirk Sommerfeld',  'dirk.sommerfeld@clausing.eu',      NULL),
(6,  'Martina Fischer',  'martina.fischer@vaihingen.de',     NULL),
(7,  'Birgit Wawers',    'birgit@wawers.de',                 NULL),
(8,  'Matthias Siehler', 'msiehler@t-online.de',             NULL),
(9,  'Jens Renz',        'jens.re@web.de',                   NULL),
(10, 'Frau Hoffmann',    NULL,                               '1716294284'),
(11, 'Gabi Wiederrich',  'gabi@wiederrich.de',               NULL),
(12, 'Marc Arndt',       'Arndt.Ma@web.de',                  NULL),
(13, 'Andrea Kratel',    'meike.guenter@gruene.landtag-bw.de', NULL),
(14, 'Nadine Renz',      'nadine.renz@mvvaihingen.de',       NULL),
(15, 'Simon Rommel',     'simon1893@gmx.net',                NULL);

-- ---------------------------------------------------------------------
-- 4) Mannschaften
-- ---------------------------------------------------------------------
-- Startklassen-Mapping:
--   gemischt / gemischte Mannschaft -> Gemischte Mannschaften
--   Stadtteile                       -> Ortsteile
--   Heren (Tippfehler)               -> Herren
--   Damen / Herren / Betriebsmannschaften -> wie angegeben
--   leer                             -> leer (keine Startklasse)
-- Mehrfach auftretende Namen:
--   - Dynamo Tresen (Herren/Damen/gemischt) -> 3 separate Teams mit
--     Suffix je Startklasse.
--   - Bündnis 90/die Grünen (3x, gleiche leere Klasse) -> 3 separate
--     Teams mit numerischem Suffix (2)/(3), da Suffix = Startklasse
--     hier leer wäre.
INSERT INTO `teams` (`id`, `name`, `startklasse`, `captain_id`) VALUES
-- Samstag
(1,  'Die Linke',                          'Gemischte Mannschaften', 1),
(2,  'Team Füllmi',                        '',                       2),
(3,  'DIVaN e.V.',                         'Gemischte Mannschaften', 1),
(4,  'DLRG OG Vaihingen',                  '',                       3),
(5,  'SV Horrheim Fußball',               'Herren',                 4),
-- Sonntag
(6,  'Jedermänner I TVV',                 'Herren',                 5),
(7,  'Ortschaftsrat Roßwag',              'Ortsteile',              6),
(8,  'TVV Dance Fitness',                'Damen',                  7),
(9,  'TVV Fitnessfrauen',                'Damen',                  5),
(10, 'Ortschaftsrat Enzweihingen',        'Ortsteile',              8),
(11, 'Ortschaftsrat Horrheim',            'Ortsteile',              4),
(12, 'Freie Wähler Vaihingen/Enz',        'Gemischte Mannschaften', 9),
(13, 'Landfrauen Roßwag',                 'Damen',                  10),
(14, 'Die roten Radlerinnen',             'Damen',                  11),
(15, 'Ortschaftsrat Riet',                'Ortsteile',              6),
(16, 'Kegelfreunde Bontrup',              'Herren',                 12),
(17, 'Bündnis 90/die Grünen',             '',                       13),
(18, 'Ortschaftsrat Kleinglattbach Die Paddelhasen', 'Ortsteile',    6),
(19, 'Bündnis 90/die Grünen (2)',          '',                       13),
(20, 'Bündnis 90/die Grünen (3)',          '',                       13),
(21, 'Musikverein Vaihingen Der kleine Kreis', 'Herren',             14),
(22, 'Dynamo Tresen (Herren)',            'Herren',                 15),
(23, 'Musikverein Vaihingen',             'Gemischte Mannschaften', 14),
(24, 'Ortschaftsrat Ensingen',            'Ortsteile',              6),
(25, 'Dynamo Tresen (Damen)',             'Damen',                  15),
(26, 'Ortschaftsrat Aurich',              'Ortsteile',              6),
(27, 'Stadtverwaltung',                   'Betriebsmannschaften',   6),
(28, 'Dynamo Tresen (Gemischte Mannschaften)', 'Gemischte Mannschaften', 15);

-- ---------------------------------------------------------------------
-- 5) Startzeiten
-- ---------------------------------------------------------------------
-- Es wird genau der Raster aus der übergebenen Tabelle angelegt
-- (Samstag 14:00-18:00, Sonntag 11:00-16:00, je 10-Minuten-Takt).
-- Gebuchte Slots: team_id gesetzt, is_booked=1.
-- Freie Slots:   team_id=NULL, is_booked=0.
-- paid: überall 0 (nicht bezahlt) - "kostenlos" ist als Notiz erfasst.
-- Bemerkung -> notes.
INSERT INTO `start_times` (`team_id`, `date`, `time`, `is_booked`, `paid`, `notes`) VALUES
-- === Samstag 12.09.2026 ===
(1,  '2026-09-12', '14:00', 1, 0, NULL),
(2,  '2026-09-12', '14:10', 1, 0, NULL),
(NULL,'2026-09-12','14:20', 0, 0, NULL),
(NULL,'2026-09-12','14:30', 0, 0, NULL),
(NULL,'2026-09-12','14:40', 0, 0, NULL),
(NULL,'2026-09-12','14:50', 0, 0, NULL),
(3,  '2026-09-12', '15:00', 1, 0, NULL),
(NULL,'2026-09-12','15:10', 0, 0, NULL),
(NULL,'2026-09-12','15:20', 0, 0, NULL),
(NULL,'2026-09-12','15:30', 0, 0, NULL),
(NULL,'2026-09-12','15:40', 0, 0, NULL),
-- Hinweis: In der Originaltabelle stand an dieser Position "14:40" (Tippfehler
-- im Raster) - korrigiert zu 15:40, um den fortlaufenden 10-Minuten-Takt zu wahren.
(NULL,'2026-09-12','15:50', 0, 0, NULL),
(4,  '2026-09-12', '16:00', 1, 0, 'kostenlos wegen Abnahme Rettungsfähigkeit'),
(NULL,'2026-09-12','16:10', 0, 0, NULL),
(NULL,'2026-09-12','16:20', 0, 0, NULL),
(5,  '2026-09-12', '16:30', 1, 0, NULL),
(NULL,'2026-09-12','16:40', 0, 0, NULL),
(NULL,'2026-09-12','16:50', 0, 0, NULL),
(NULL,'2026-09-12','17:00', 0, 0, NULL),
(NULL,'2026-09-12','17:10', 0, 0, NULL),
(NULL,'2026-09-12','17:20', 0, 0, NULL),
(NULL,'2026-09-12','17:30', 0, 0, NULL),
(NULL,'2026-09-12','17:40', 0, 0, NULL),
(NULL,'2026-09-12','17:50', 0, 0, NULL),
(NULL,'2026-09-12','18:00', 0, 0, NULL),
-- === Sonntag 13.09.2026 ===
(6,  '2026-09-13', '11:00', 1, 0, NULL),
(7,  '2026-09-13', '11:10', 1, 0, 'Rechnung Frau Fischer'),
(8,  '2026-09-13', '11:20', 1, 0, NULL),
(NULL,'2026-09-13','11:30', 0, 0, NULL),
(9,  '2026-09-13', '11:40', 1, 0, NULL),
(NULL,'2026-09-13','11:50', 0, 0, NULL),
(10, '2026-09-13', '12:00', 1, 0, 'Rechnung Frau Fischer'),
(11, '2026-09-13', '12:10', 1, 0, NULL),
(NULL,'2026-09-13','12:20', 0, 0, NULL),
(12, '2026-09-13', '12:30', 1, 0, NULL),
(NULL,'2026-09-13','12:40', 0, 0, NULL),
(NULL,'2026-09-13','12:50', 0, 0, NULL),
(NULL,'2026-09-13','13:00', 0, 0, NULL),
(13, '2026-09-13', '13:10', 1, 0, NULL),
(NULL,'2026-09-13','13:20', 0, 0, NULL),
(14, '2026-09-13', '13:30', 1, 0, NULL),
(NULL,'2026-09-13','13:40', 0, 0, NULL),
(15, '2026-09-13', '13:50', 1, 0, 'Rechnung Frau Fischer'),
(16, '2026-09-13', '14:00', 1, 0, NULL),
(17, '2026-09-13', '14:10', 1, 0, NULL),
(18, '2026-09-13', '14:20', 1, 0, 'Rechnung Frau Fischer'),
(19, '2026-09-13', '14:30', 1, 0, NULL),
(20, '2026-09-13', '14:40', 1, 0, NULL),
(21, '2026-09-13', '14:50', 1, 0, NULL),
(22, '2026-09-13', '15:00', 1, 0, NULL),
(23, '2026-09-13', '15:10', 1, 0, NULL),
(24, '2026-09-13', '15:20', 1, 0, 'Rechnung Frau Fischer'),
(25, '2026-09-13', '15:30', 1, 0, NULL),
(26, '2026-09-13', '15:40', 1, 0, 'Rechnung Frau Fischer'),
(27, '2026-09-13', '15:50', 1, 0, 'Rechnung Frau Fischer'),
(28, '2026-09-13', '16:00', 1, 0, NULL);

-- =====================================================================
-- Kontrolle (optional ausführen)
-- =====================================================================
-- SELECT COUNT(*) AS anzahl_teams FROM teams;        -- erwartet: 28
-- SELECT COUNT(*) AS anzahl_captains FROM captains;  -- erwartet: 15
-- SELECT COUNT(*) AS anzahl_startzeiten FROM start_times; -- erwartet: 56
-- SELECT date, COUNT(*) AS gebucht FROM start_times WHERE is_booked=1 GROUP BY date;
--   erwartet: 2026-09-12 -> 5, 2026-09-13 -> 23
-- SELECT `key`, `value` FROM settings WHERE `key` LIKE 'race_date%' OR `key`='reservation_start_date';
