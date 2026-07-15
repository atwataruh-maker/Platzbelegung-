-- Stammdaten VfL Ramsdorf
-- Sommersaison-Belegung 1:1 aus dem echten Belegungsplan (Sommersaison-PDF)
-- uebernommen. Winter-/Hallenplan sind noch Platzhalter, bis der Verein den
-- echten Winter-/Hallenbelegungsplan liefert (siehe Kommentar weiter unten).

-- Demo-Login fuer beide Konten: Passwort "wechsel-mich" (bitte nach dem
-- ersten Login sofort aendern, z.B. ueber database/generate_password_hash.php).
INSERT INTO users (name, email, password_hash, role) VALUES
('Admin VfL Ramsdorf', 'admin@vfl-ramsdorf.de', '$2y$12$4WVKsqNEH.IXabPOSCytDO82Uyy1V/fK3mWhLcwCg0S9DAITxkBWO', 'admin'),
('Trainer Beispiel', 'trainer@vfl-ramsdorf.de', '$2y$12$4WVKsqNEH.IXabPOSCytDO82Uyy1V/fK3mWhLcwCg0S9DAITxkBWO', 'trainer');

-- Mannschaften (Namen exakt wie im Belegungsplan). preferred_size ist eine
-- grobe Einordnung Grossfeld/Kleinfeld und sollte vom Verein noch geprueft
-- werden, preferred_pitch_kind bewusst 'egal' gelassen, da im echten Plan
-- praktisch jede Mannschaft im Wochenverlauf zwischen Kunstrasen und Rasen
-- wechselt.
INSERT INTO teams (name, age_group, gender, priority, preferred_pitch_kind, preferred_size) VALUES
('VfL Ramsdorf I', 'Herren', 'm', 3, 'egal', 'gross'),
('VfL Ramsdorf II', 'Herren', 'm', 4, 'egal', 'gross'),
('VfL Ramsdorf III', 'Herren', 'm', 5, 'egal', 'gross'),
('VfL Ramsdorf Damen', 'Damen', 'w', 4, 'egal', 'gross'),
('Alte Herren', 'Senioren', 'm', 8, 'egal', 'egal'),
('A-Junioren', 'A-Jugend', 'gemischt', 4, 'egal', 'gross'),
('B-Junioren', 'B-Jugend', 'gemischt', 4, 'egal', 'gross'),
('C-Junioren', 'C-Jugend', 'gemischt', 5, 'egal', 'drittel'),
('C-Juniorinnen', 'C-Jugend', 'w', 5, 'egal', 'drittel'),
('D1-Junioren', 'D-Jugend', 'gemischt', 6, 'egal', 'drittel'),
('D2-Junioren', 'D-Jugend', 'gemischt', 6, 'egal', 'drittel'),
('D1-Juniorinnen', 'D-Jugend', 'w', 6, 'egal', 'drittel'),
('D2-Juniorinnen', 'D-Jugend', 'w', 6, 'egal', 'drittel'),
('E1-Junioren', 'E-Jugend', 'gemischt', 7, 'egal', 'drittel'),
('E2-Junioren', 'E-Jugend', 'gemischt', 7, 'egal', 'drittel'),
('E3-Junioren', 'E-Jugend', 'gemischt', 7, 'egal', 'drittel'),
('E1/E2-Juniorinnen', 'E-Jugend', 'w', 7, 'egal', 'drittel'),
('F1/F2-Junioren', 'F-Jugend', 'gemischt', 7, 'egal', 'drittel'),
('G/F-Juniorinnen', 'F/G-Jugend', 'w', 8, 'egal', 'drittel'),
('G-Junioren/G-F Juniorinnen', 'G-Jugend', 'gemischt', 8, 'egal', 'drittel'),
-- Kein Trainingsteam, sondern ein wiederkehrender Platzblocker fuer die
-- freitaegliche Reserve-/Jugend-Ligapartie (siehe Belegungsplan). Wird wie
-- eine Mannschaft in der Grundbelegung gefuehrt, damit an dieser Zeit
-- automatisch nichts anderes eingeplant werden kann.
('Reserve Ligaspiel Jugend (Platzblocker)', 'Ligaspiel', 'gemischt', 2, 'egal', 'egal');

-- Sommer-Plaetze (6 Flaechen exakt wie im Belegungsplan: KuRa 1, KuRa 2,
-- Rasen 1/3, Rasen 2/3, Rasen 3/3, Rasen Reserve)
INSERT INTO pitches (name, season_type, kind, size, sort_order) VALUES
('KuRa 1', 'summer', 'kunstrasen', 'gross', 10),
('KuRa 2', 'summer', 'kunstrasen', 'gross', 11),
('Rasen 1/3', 'summer', 'rasen', 'drittel', 20),
('Rasen 2/3', 'summer', 'rasen', 'drittel', 21),
('Rasen 3/3', 'summer', 'rasen', 'drittel', 22),
('Rasen Reserve', 'summer', 'rasen', 'drittel', 23);

-- Winter-Plaetze (Platzhalter, noch kein echter Winterplan geliefert)
INSERT INTO pitches (name, season_type, kind, size, sort_order) VALUES
('KuRa 1 (Winter)', 'winter', 'kunstrasen', 'gross', 30),
('KuRa 2 (Winter)', 'winter', 'kunstrasen', 'gross', 31),
('KuRa 3 (Winter)', 'winter', 'kunstrasen', 'gross', 32);

-- Hallen (Platzhalter, noch kein echter Hallenplan geliefert)
INSERT INTO pitches (name, season_type, kind, size, sort_order) VALUES
('Paulushalle', 'hall', 'halle', 'halle', 40),
('Walburgishalle', 'hall', 'halle', 'halle', 41);

-- Hinweis Teilflaechen-Modell (pitch_relations): Laut Belegungsplan aendert
-- sich die tatsaechliche Rasenaufteilung je nachdem, ob eine Grossfeld- oder
-- nur Kleinfeldmannschaften beteiligt sind (siehe die zwei Diagramme im
-- Plan). Das aktuelle MVP bildet nur eine statische Eltern-/Kind-Beziehung
-- ab und modelliert diese variable Geometrie noch nicht - hier bewusst
-- keine Relation angelegt, um keine falschen Annahmen zu seeden. Rasen 3/3
-- und Rasen Reserve werden freitags nachweislich gemeinsam von einer
-- Mannschaft genutzt (siehe Grundbelegung unten), das laesst sich mit dem
-- jetzigen Modell einfach ueber zwei Grundbelegungs-Eintraege abbilden.

-- Saisons
INSERT INTO seasons (name, type, start_date, end_date, is_active) VALUES
('Sommersaison', 'summer', '2026-04-01', '2026-10-31', 1),
('Wintersaison', 'winter', '2026-11-01', '2027-03-14', 0),
('Hallensaison', 'hall', '2026-11-01', '2027-03-31', 0);

-- Grundbelegung Sommersaison, 1:1 aus dem Belegungsplan uebernommen
-- (Montag bis Freitag, Samstag/Sonntag laut Plan ohne Training).
INSERT INTO base_schedules (team_id, pitch_id, season_id, weekday, start_time, end_time)
SELECT t.id, p.id, s.id, x.weekday, x.start_time, x.end_time
FROM (
    -- Montag
    SELECT 'D1-Junioren' AS team, 'Rasen 2/3' AS pitch, 1 AS weekday, '16:30:00' AS start_time, '18:00:00' AS end_time
    UNION ALL SELECT 'D2-Junioren', 'KuRa 1', 1, '17:30:00', '19:00:00'
    UNION ALL SELECT 'D1-Juniorinnen', 'KuRa 2', 1, '17:30:00', '19:00:00'
    UNION ALL SELECT 'D2-Juniorinnen', 'Rasen 1/3', 1, '18:00:00', '19:30:00'
    UNION ALL SELECT 'A-Junioren', 'KuRa 1', 1, '19:00:00', '20:30:00'
    UNION ALL SELECT 'B-Junioren', 'KuRa 2', 1, '19:00:00', '20:30:00'
    -- Dienstag
    UNION ALL SELECT 'G/F-Juniorinnen', 'KuRa 1', 2, '16:30:00', '17:30:00'
    UNION ALL SELECT 'E1-Junioren', 'Rasen 3/3', 2, '17:00:00', '18:30:00'
    UNION ALL SELECT 'E1/E2-Juniorinnen', 'KuRa 2', 2, '17:30:00', '19:00:00'
    UNION ALL SELECT 'E3-Junioren', 'Rasen 1/3', 2, '17:30:00', '19:00:00'
    UNION ALL SELECT 'C-Junioren', 'KuRa 1', 2, '18:00:00', '19:30:00'
    UNION ALL SELECT 'E2-Junioren', 'Rasen 2/3', 2, '18:00:00', '19:30:00'
    UNION ALL SELECT 'VfL Ramsdorf III', 'KuRa 2', 2, '19:00:00', '20:30:00'
    UNION ALL SELECT 'VfL Ramsdorf Damen', 'Rasen 1/3', 2, '19:00:00', '20:30:00'
    UNION ALL SELECT 'VfL Ramsdorf I', 'KuRa 1', 2, '19:30:00', '21:00:00'
    -- Mittwoch
    UNION ALL SELECT 'D1-Junioren', 'KuRa 2', 3, '16:30:00', '18:00:00'
    UNION ALL SELECT 'C-Juniorinnen', 'KuRa 1', 3, '17:30:00', '19:00:00'
    UNION ALL SELECT 'D1-Juniorinnen', 'Rasen 1/3', 3, '17:30:00', '19:00:00'
    UNION ALL SELECT 'F1/F2-Junioren', 'Rasen 3/3', 3, '17:30:00', '18:30:00'
    UNION ALL SELECT 'F1/F2-Junioren', 'Rasen Reserve', 3, '17:30:00', '18:30:00'
    UNION ALL SELECT 'D2-Juniorinnen', 'KuRa 2', 3, '18:00:00', '19:30:00'
    UNION ALL SELECT 'E2-Junioren', 'Rasen 2/3', 3, '18:00:00', '19:30:00'
    UNION ALL SELECT 'VfL Ramsdorf II', 'KuRa 1', 3, '19:00:00', '20:30:00'
    UNION ALL SELECT 'Alte Herren', 'KuRa 2', 3, '19:30:00', '21:00:00'
    -- Donnerstag
    UNION ALL SELECT 'D2-Junioren', 'Rasen 2/3', 4, '17:00:00', '18:30:00'
    UNION ALL SELECT 'E1-Junioren', 'KuRa 1', 4, '17:30:00', '19:00:00'
    UNION ALL SELECT 'E3-Junioren', 'KuRa 2', 4, '17:30:00', '19:00:00'
    UNION ALL SELECT 'C-Junioren', 'Rasen 1/3', 4, '17:30:00', '19:00:00'
    UNION ALL SELECT 'A-Junioren', 'Rasen 1/3', 4, '19:00:00', '20:30:00'
    UNION ALL SELECT 'B-Junioren', 'Rasen 2/3', 4, '19:00:00', '20:30:00'
    UNION ALL SELECT 'VfL Ramsdorf I', 'KuRa 1', 4, '19:30:00', '21:00:00'
    -- Freitag
    UNION ALL SELECT 'Reserve Ligaspiel Jugend (Platzblocker)', 'KuRa 1', 5, '16:30:00', '20:30:00'
    UNION ALL SELECT 'F1/F2-Junioren', 'KuRa 2', 5, '16:30:00', '17:30:00'
    UNION ALL SELECT 'G-Junioren/G-F Juniorinnen', 'Rasen 3/3', 5, '17:00:00', '18:00:00'
    UNION ALL SELECT 'G-Junioren/G-F Juniorinnen', 'Rasen Reserve', 5, '17:00:00', '18:00:00'
    UNION ALL SELECT 'Reserve Ligaspiel Jugend (Platzblocker)', 'KuRa 2', 5, '17:30:00', '19:00:00'
    UNION ALL SELECT 'E1/E2-Juniorinnen', 'Rasen 1/3', 5, '17:30:00', '19:00:00'
    UNION ALL SELECT 'C-Juniorinnen', 'Rasen 2/3', 5, '17:30:00', '19:00:00'
    UNION ALL SELECT 'VfL Ramsdorf Damen', 'KuRa 2', 5, '19:00:00', '20:30:00'
    UNION ALL SELECT 'VfL Ramsdorf II', 'Rasen 1/3', 5, '19:00:00', '20:30:00'
    UNION ALL SELECT 'VfL Ramsdorf III', 'Rasen 2/3', 5, '19:00:00', '20:30:00'
) AS x
JOIN teams t ON t.name = x.team
JOIN pitches p ON p.name = x.pitch AND p.season_type = 'summer'
JOIN seasons s ON s.type = 'summer';
