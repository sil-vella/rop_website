-- Dutch.mt: events and registrations (one table per concept; multiple events, many registrations per event).
-- Each registration stores its own user data (username, email, optional password_hash) so public signups
-- don't require a dashboard user. Same person can register for multiple events (one row per event).

USE dutch_dashboard;

-- Events (tournaments / signup targets). Create events via admin or API; form sends event slug as tournament_id.
CREATE TABLE IF NOT EXISTS dutch_mt_events (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug        VARCHAR(64)  NOT NULL COMMENT 'Unique identifier from form (tournament_id)',
  name        VARCHAR(255) NOT NULL,
  description TEXT         NULL,
  opens_at    DATETIME(6)  NULL COMMENT 'When registration opens',
  closes_at   DATETIME(6)  NULL COMMENT 'When registration closes',
  created_at  DATETIME(6)  NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  updated_at  DATETIME(6)  NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
  UNIQUE KEY uk_slug (slug),
  INDEX idx_opens (opens_at),
  INDEX idx_closes (closes_at)
) ENGINE=InnoDB;

-- Registrations: one row per person per event. User data stored here (no FK to users table).
CREATE TABLE IF NOT EXISTS dutch_mt_registrations (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  event_id      INT UNSIGNED NOT NULL,
  username      VARCHAR(255) NOT NULL,
  email         VARCHAR(255) NOT NULL,
  password_hash VARCHAR(255) NULL COMMENT 'Optional; for future login or verification',
  registered_at DATETIME(6)  NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  CONSTRAINT fk_reg_event FOREIGN KEY (event_id) REFERENCES dutch_mt_events (id) ON DELETE CASCADE,
  UNIQUE KEY uk_event_email (event_id, email) COMMENT 'One registration per email per event',
  INDEX idx_event (event_id),
  INDEX idx_email (email)
) ENGINE=InnoDB;
