-- Add role column to users (run on existing dutch_dashboard DB).
-- New installs get it from init.sql.

USE dutch_dashboard;

ALTER TABLE users
  ADD COLUMN IF NOT EXISTS role VARCHAR(64) NOT NULL DEFAULT 'user' AFTER password_hash,
  ADD INDEX IF NOT EXISTS idx_role (role);
