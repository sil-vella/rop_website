-- Dutch.mt Dashboard database (MySQL/MariaDB).
-- Run once to create schema. Use a dedicated DB user with limited privileges.

CREATE DATABASE IF NOT EXISTS dutch_dashboard
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE dutch_dashboard;

-- Optional: audit log for dashboard actions (e.g. tournament creation)
CREATE TABLE IF NOT EXISTS audit_log (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  created_at  DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  user_id     VARCHAR(255) NULL,
  action      VARCHAR(64)  NOT NULL,
  payload     JSON         NULL,
  ip          VARCHAR(45)  NULL,
  INDEX idx_created (created_at),
  INDEX idx_user (user_id),
  INDEX idx_action (action)
) ENGINE=InnoDB;

-- Optional: cache table for dashboard data (e.g. tournament list cache)
CREATE TABLE IF NOT EXISTS cache (
  cache_key   VARCHAR(255) NOT NULL PRIMARY KEY,
  value       LONGTEXT     NULL,
  expires_at  DATETIME(6)  NOT NULL,
  INDEX idx_expires (expires_at)
) ENGINE=InnoDB;

-- Dashboard app users (local auth; no proxy to main app)
CREATE TABLE IF NOT EXISTS users (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username      VARCHAR(255) NOT NULL,
  email         VARCHAR(255) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role          VARCHAR(64)  NOT NULL DEFAULT 'user',
  created_at    DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  UNIQUE KEY uk_username (username),
  UNIQUE KEY uk_email (email),
  INDEX idx_username (username),
  INDEX idx_role (role)
) ENGINE=InnoDB;

-- Optional: dashboard sessions (if you store server-side session data)
CREATE TABLE IF NOT EXISTS sessions (
  id          VARCHAR(128) NOT NULL PRIMARY KEY,
  user_id     VARCHAR(255) NULL,
  payload     JSON         NULL,
  expires_at  DATETIME(6)  NOT NULL,
  INDEX idx_user (user_id),
  INDEX idx_expires (expires_at)
) ENGINE=InnoDB;

-- Grant to dashboard app user (adjust user/host as needed)
-- CREATE USER IF NOT EXISTS 'dutch_dash'@'%' IDENTIFIED BY 'your_password';
-- GRANT SELECT, INSERT, UPDATE, DELETE ON dutch_dashboard.* TO 'dutch_dash'@'%';
-- FLUSH PRIVILEGES;
