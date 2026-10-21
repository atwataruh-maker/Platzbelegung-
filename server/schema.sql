CREATE TABLE IF NOT EXISTS vfl_admins (
 username VARCHAR(80) CHARACTER SET ascii COLLATE ascii_bin PRIMARY KEY,
 password_hash VARCHAR(255) NOT NULL,
 created_at VARCHAR(32) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS vfl_helper_requests (
 id VARCHAR(80) CHARACTER SET ascii COLLATE ascii_bin PRIMARY KEY,
 title VARCHAR(120) NOT NULL,
 description TEXT NOT NULL,
 category VARCHAR(40) NOT NULL,
 hours DECIMAL(8,2) NULL,
 location VARCHAR(160) NOT NULL DEFAULT '',
 timeframe VARCHAR(160) NOT NULL,
 contact VARCHAR(200) NOT NULL DEFAULT '',
 status VARCHAR(12) NOT NULL DEFAULT 'open',
 is_example TINYINT NOT NULL DEFAULT 0,
 created_by VARCHAR(80) NOT NULL,
 created_at VARCHAR(32) NOT NULL,
 completed_at VARCHAR(32) NULL,
 INDEX status_date (status,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS vfl_login_limits (
 bucket CHAR(64) CHARACTER SET ascii COLLATE ascii_bin PRIMARY KEY,
 attempts INT NOT NULL DEFAULT 1,
 expires BIGINT NOT NULL,
 INDEX expiry (expires)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
