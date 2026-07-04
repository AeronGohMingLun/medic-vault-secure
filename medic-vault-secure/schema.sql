-- MedicVault Secure Schema
-- Matches the original assignment schema exactly (patient_records / staff_credentials).
-- auth_key_hash stores PASSWORD_ARGON2ID hashes produced by password_hash().
-- NEVER store MD5 or any other fast/unsalted hash here.

CREATE TABLE IF NOT EXISTS patient_records (
    id               INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
    name             VARCHAR(255)  NOT NULL,
    illness_history  TEXT          NOT NULL,
    created_at       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS staff_credentials (
    id            INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
    username      VARCHAR(100)  NOT NULL UNIQUE,
    auth_key_hash VARCHAR(255)  NOT NULL,          -- Argon2id hash, never MD5
    role          VARCHAR(50)   NOT NULL DEFAULT 'Staff',
    created_at    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

INSERT INTO patient_records (name, illness_history) VALUES
('John Doe',      'DIAGNOSIS: Stage-2 Carcinoma. TREATMENT: Chemotherapy cycle 1. STATUS: Critical.'),
('Jane Smith',    'DIAGNOSIS: Stage-2 Carcinoma. TREATMENT: Radiation therapy. STATUS: Stable.'),
('Robert Thorne', 'DIAGNOSIS: Acute Type-2 Diabetes. TREATMENT: Insulin regimen. STATUS: Managed.'),
('Siti Aminah',   'DIAGNOSIS: Acute Type-2 Diabetes. TREATMENT: Metformin regimen. STATUS: Monitored.');
