-- Beispiel-Stammdaten VfL Ramsdorf (Basis: Anforderungsliste)
-- Passwort fuer beide Demo-Benutzer: "wechsel-mich" (siehe README)

-- Demo-Login fuer beide Konten: Passwort "wechsel-mich" (bitte nach dem
-- ersten Login sofort aendern, z.B. ueber database/generate_password_hash.php).
INSERT INTO users (name, email, password_hash, role) VALUES
('Admin VfL Ramsdorf', 'admin@vfl-ramsdorf.de', '$2y$12$4WVKsqNEH.IXabPOSCytDO82Uyy1V/fK3mWhLcwCg0S9DAITxkBWO', 'admin'),
('Trainer Beispiel', 'trainer@vfl-ramsdorf.de', '$2y$12$4WVKsqNEH.IXabPOSCytDO82Uyy1V/fK3mWhLcwCg0S9DAITxkBWO', 'trainer');

INSERT INTO teams (name, age_group, gender, priority, preferred_pitch_kind, preferred_size) VALUES
('VfL Ramsdorf I', 'Herren', 'm', 3, 'rasen', 'gross'),
('VfL Ramsdorf II', 'Herren', 'm', 4, 'rasen', 'gross'),
('VfL Ramsdorf III', 'Herren', 'm', 5, 'rasen', 'gross'),
('Alte Herren', 'Senioren', 'm', 8, 'egal', 'egal'),
('A-Junioren', 'A-Jugend', 'gemischt', 4, 'kunstrasen', 'gross'),
('B-Junioren', 'B-Jugend', 'gemischt', 4, 'kunstrasen', 'gross'),
('C-Junioren', 'C-Jugend', 'gemischt', 4, 'kunstrasen', 'gross'),
('D1-Junioren', 'D-Jugend', 'gemischt', 5, 'kunstrasen', 'drittel'),
('D2-Junioren', 'D-Jugend', 'gemischt', 5, 'kunstrasen', 'drittel'),
('D-Juniorinnen', 'D-Jugend', 'w', 5, 'kunstrasen', 'drittel'),
('E-Junioren', 'E-Jugend', 'gemischt', 6, 'kunstrasen', 'drittel'),
('E-Juniorinnen', 'E-Jugend', 'w', 6, 'kunstrasen', 'drittel'),
('F-Junioren', 'F-Jugend', 'gemischt', 6, 'kunstrasen', 'drittel'),
('F-Juniorinnen', 'F-Jugend', 'w', 6, 'kunstrasen', 'drittel'),
('G-Junioren', 'G-Jugend', 'gemischt', 7, 'kunstrasen', 'drittel'),
('SF Nordvelen (Gast)', 'Gastmannschaft', 'gemischt', 9, 'egal', 'egal');

-- Sommer-Plaetze
INSERT INTO pitches (name, season_type, kind, size, sort_order) VALUES
('KuRa 1', 'summer', 'kunstrasen', 'gross', 10),
('KuRa 2', 'summer', 'kunstrasen', 'gross', 11),
('Rasen Hauptplatz', 'summer', 'rasen', 'gross', 20),
('Rasen 1/3', 'summer', 'rasen', 'drittel', 21),
('Rasen 2/3', 'summer', 'rasen', 'drittel', 22),
('Rasen 3/3', 'summer', 'rasen', 'drittel', 23);

-- Winter-Plaetze (Kunstrasen dreigeteilt)
INSERT INTO pitches (name, season_type, kind, size, sort_order) VALUES
('KuRa 1 (Winter)', 'winter', 'kunstrasen', 'gross', 30),
('KuRa 2 (Winter)', 'winter', 'kunstrasen', 'gross', 31),
('KuRa 3 (Winter)', 'winter', 'kunstrasen', 'gross', 32);

-- Hallen
INSERT INTO pitches (name, season_type, kind, size, sort_order) VALUES
('Paulushalle', 'hall', 'halle', 'halle', 40),
('Walburgishalle', 'hall', 'halle', 'halle', 41);

-- Teilflaechen-Beziehung: Rasen Hauptplatz besteht aus den drei Dritteln.
-- Belegt jemand den Hauptplatz komplett (z.B. fuer ein Spiel), werden
-- automatisch alle drei Drittel mitgesperrt.
INSERT INTO pitch_relations (parent_pitch_id, child_pitch_id)
SELECT p1.id, p2.id FROM pitches p1, pitches p2
WHERE p1.name = 'Rasen Hauptplatz' AND p2.name IN ('Rasen 1/3', 'Rasen 2/3', 'Rasen 3/3');

-- Saisons
INSERT INTO seasons (name, type, start_date, end_date, is_active) VALUES
('Sommersaison', 'summer', '2026-04-01', '2026-10-31', 1),
('Wintersaison', 'winter', '2026-11-01', '2027-03-14', 0),
('Hallensaison', 'hall', '2026-11-01', '2027-03-31', 0);

-- Beispiel-Grundbelegung (Auszug aus der PDF-Belegung)
INSERT INTO base_schedules (team_id, pitch_id, season_id, weekday, start_time, end_time)
SELECT t.id, p.id, s.id, 1, '17:00:00', '18:00:00'
FROM teams t, pitches p, seasons s
WHERE t.name = 'D2-Junioren' AND p.name = 'KuRa 1' AND s.type = 'summer';

INSERT INTO base_schedules (team_id, pitch_id, season_id, weekday, start_time, end_time)
SELECT t.id, p.id, s.id, 1, '19:00:00', '20:00:00'
FROM teams t, pitches p, seasons s
WHERE t.name = 'A-Junioren' AND p.name = 'KuRa 1' AND s.type = 'summer';
