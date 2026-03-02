-- Bootstrap: create schema_migrations table so we can track which migrations have been applied.
-- Run once per database. Safe to run multiple times (IF NOT EXISTS).

USE dutch_dashboard;

CREATE TABLE IF NOT EXISTS schema_migrations (
  migration_id VARCHAR(255) NOT NULL PRIMARY KEY,
  applied_at    DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6)
) ENGINE=InnoDB;
