-- Platzbelegung VfL Ramsdorf - Datenbankschema
-- MySQL/MariaDB, utf8mb4

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- Benutzer & Rollen
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin','trainer','betreuer') NOT NULL DEFAULT 'trainer',
    active TINYINT(1) NOT NULL DEFAULT 1,
    password_reset_token VARCHAR(100) NULL,
    password_reset_expires DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- Mannschaften
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS teams (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    age_group VARCHAR(60) NULL,
    gender ENUM('m','w','gemischt') NOT NULL DEFAULT 'gemischt',
    trainer_user_id INT UNSIGNED NULL,
    co_trainer_user_id INT UNSIGNED NULL,
    preferred_duration_minutes INT UNSIGNED NOT NULL DEFAULT 60,
    preferred_pitch_kind ENUM('kunstrasen','rasen','halle','egal') NOT NULL DEFAULT 'egal',
    preferred_size ENUM('gross','drittel','egal') NOT NULL DEFAULT 'egal',
    priority INT NOT NULL DEFAULT 5,
    external_ref VARCHAR(120) NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_teams_trainer FOREIGN KEY (trainer_user_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_teams_co_trainer FOREIGN KEY (co_trainer_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- weitere Trainer/Betreuer je Mannschaft (n:m)
CREATE TABLE IF NOT EXISTS team_user (
    team_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (team_id, user_id),
    CONSTRAINT fk_tu_team FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE,
    CONSTRAINT fk_tu_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- Plaetze / Hallen / Teilflaechen
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS pitches (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    season_type ENUM('summer','winter','hall') NOT NULL,
    kind ENUM('kunstrasen','rasen','halle') NOT NULL,
    size ENUM('gross','drittel','halle') NOT NULL DEFAULT 'gross',
    sort_order INT NOT NULL DEFAULT 0,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Teilflaechen-Beziehung: ein Platz (parent) besteht aus mehreren Teilflaechen (child).
-- Belegung des parent blockiert automatisch alle childs und umgekehrt.
CREATE TABLE IF NOT EXISTS pitch_relations (
    parent_pitch_id INT UNSIGNED NOT NULL,
    child_pitch_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (parent_pitch_id, child_pitch_id),
    CONSTRAINT fk_pr_parent FOREIGN KEY (parent_pitch_id) REFERENCES pitches(id) ON DELETE CASCADE,
    CONSTRAINT fk_pr_child FOREIGN KEY (child_pitch_id) REFERENCES pitches(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Nachtraeglich ergaenzt: Standard-Heimplatz je Mannschaft, damit importierte
-- Heimspiele (z.B. aus DFBnet) automatisch einen Platz zugewiesen bekommen
-- und sofort die Platzsperre/Konflikterkennung greifen kann. FK erst hier
-- moeglich, da "pitches" erst an dieser Stelle existiert.
ALTER TABLE teams
    ADD COLUMN default_pitch_id INT UNSIGNED NULL AFTER preferred_size,
    ADD CONSTRAINT fk_teams_default_pitch FOREIGN KEY (default_pitch_id) REFERENCES pitches(id) ON DELETE SET NULL;

-- ---------------------------------------------------------------------
-- Saisons / Sondermodi
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS seasons (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    type ENUM('summer','winter','hall','holiday','tournament','closed') NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- Grundbelegung (wiederkehrendes Training)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS base_schedules (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    team_id INT UNSIGNED NOT NULL,
    pitch_id INT UNSIGNED NOT NULL,
    season_id INT UNSIGNED NOT NULL,
    weekday TINYINT UNSIGNED NOT NULL COMMENT '1=Montag ... 7=Sonntag',
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    note VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_bs_team FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE,
    CONSTRAINT fk_bs_pitch FOREIGN KEY (pitch_id) REFERENCES pitches(id) ON DELETE CASCADE,
    CONSTRAINT fk_bs_season FOREIGN KEY (season_id) REFERENCES seasons(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- Spiele
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS games (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    team_id INT UNSIGNED NOT NULL,
    opponent VARCHAR(150) NOT NULL,
    is_home TINYINT(1) NOT NULL DEFAULT 1,
    kickoff_datetime DATETIME NOT NULL,
    pitch_id INT UNSIGNED NULL,
    competition ENUM('pflichtspiel','pokal','nachholspiel','jugendspiel','freundschaft') NOT NULL DEFAULT 'pflichtspiel',
    duration_minutes INT UNSIGNED NULL COMMENT 'Spieldauer inkl. Halbzeit, NULL = Standardwert verwenden',
    lead_minutes INT UNSIGNED NULL COMMENT 'Vorlaufzeit, NULL = Standardwert verwenden',
    lag_minutes INT UNSIGNED NULL COMMENT 'Nachlaufzeit, NULL = Standardwert verwenden',
    status ENUM('scheduled','changed','cancelled') NOT NULL DEFAULT 'scheduled',
    source ENUM('manual','csv','dfbnet') NOT NULL DEFAULT 'manual',
    external_ref VARCHAR(150) NULL COMMENT 'z.B. DFBnet-Spielnummer, fuer Abgleich beim automatischen Import',
    notes TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_games_team FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE,
    CONSTRAINT fk_games_pitch FOREIGN KEY (pitch_id) REFERENCES pitches(id) ON DELETE SET NULL,
    INDEX idx_games_external_ref (external_ref)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- Platzsperren (durch Spiele automatisch erzeugt oder manuell: Pflege, Wetter, Turnier)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS pitch_blocks (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pitch_id INT UNSIGNED NOT NULL,
    start_datetime DATETIME NOT NULL,
    end_datetime DATETIME NOT NULL,
    reason ENUM('game','maintenance','weather','tournament','other') NOT NULL DEFAULT 'other',
    game_id INT UNSIGNED NULL,
    note VARCHAR(255) NULL,
    created_by INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_pb_pitch FOREIGN KEY (pitch_id) REFERENCES pitches(id) ON DELETE CASCADE,
    CONSTRAINT fk_pb_game FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE CASCADE,
    CONSTRAINT fk_pb_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_pb_pitch_time (pitch_id, start_datetime, end_datetime)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- Konflikte
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS conflicts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    conflict_type ENUM('training_vs_block','double_booking','no_alternative','pitch_size_mismatch','stale_block','unconfirmed_change') NOT NULL,
    pitch_id INT UNSIGNED NULL,
    occurrence_date DATE NOT NULL,
    start_datetime DATETIME NOT NULL,
    end_datetime DATETIME NOT NULL,
    base_schedule_id INT UNSIGNED NULL,
    game_id INT UNSIGNED NULL,
    severity ENUM('red','yellow','green','gray') NOT NULL DEFAULT 'red',
    status ENUM('open','resolved','ignored') NOT NULL DEFAULT 'open',
    description TEXT NULL,
    detected_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    resolved_at DATETIME NULL,
    resolved_by INT UNSIGNED NULL,
    CONSTRAINT fk_conf_pitch FOREIGN KEY (pitch_id) REFERENCES pitches(id) ON DELETE SET NULL,
    CONSTRAINT fk_conf_bs FOREIGN KEY (base_schedule_id) REFERENCES base_schedules(id) ON DELETE CASCADE,
    CONSTRAINT fk_conf_game FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE CASCADE,
    CONSTRAINT fk_conf_resolver FOREIGN KEY (resolved_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_conf_date_status (occurrence_date, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- Ausweichvorschlaege
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS fallback_suggestions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    conflict_id INT UNSIGNED NOT NULL,
    base_schedule_id INT UNSIGNED NULL,
    occurrence_date DATE NOT NULL,
    suggested_pitch_id INT UNSIGNED NOT NULL,
    suggested_start DATETIME NOT NULL,
    suggested_end DATETIME NOT NULL,
    status ENUM('pending','accepted','rejected') NOT NULL DEFAULT 'pending',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_fs_conflict FOREIGN KEY (conflict_id) REFERENCES conflicts(id) ON DELETE CASCADE,
    CONSTRAINT fk_fs_bs FOREIGN KEY (base_schedule_id) REFERENCES base_schedules(id) ON DELETE CASCADE,
    CONSTRAINT fk_fs_pitch FOREIGN KEY (suggested_pitch_id) REFERENCES pitches(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- Trainerbestaetigungen
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS trainer_confirmations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    fallback_suggestion_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    status ENUM('pending','confirmed','declined') NOT NULL DEFAULT 'pending',
    responded_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_tc_fs FOREIGN KEY (fallback_suggestion_id) REFERENCES fallback_suggestions(id) ON DELETE CASCADE,
    CONSTRAINT fk_tc_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- Benachrichtigungen
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS notifications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    channel ENUM('email') NOT NULL DEFAULT 'email',
    subject VARCHAR(200) NOT NULL,
    body TEXT NOT NULL,
    status ENUM('pending','sent','failed') NOT NULL DEFAULT 'pending',
    related_type VARCHAR(60) NULL,
    related_id INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    sent_at DATETIME NULL,
    CONSTRAINT fk_notif_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_notif_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- Aenderungsprotokoll
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS change_log (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    entity_type VARCHAR(60) NOT NULL,
    entity_id INT UNSIGNED NOT NULL,
    action VARCHAR(60) NOT NULL,
    description TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_log_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;
