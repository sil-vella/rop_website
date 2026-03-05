-- Dutch.mt registrations: add name/surname (replace username with name + surname for party signup flow).

USE dutch_dashboard;

-- Rename username -> name, add surname. password_hash remains optional for other flows.
ALTER TABLE dutch_mt_registrations
  CHANGE COLUMN username name VARCHAR(255) NOT NULL,
  ADD COLUMN surname VARCHAR(255) NOT NULL DEFAULT '' AFTER name;
