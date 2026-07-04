-- MedicVault Secure Schema
-- auth_key_hash stores PASSWORD_ARGON2ID hashes produced by password_hash().
-- NEVER store MD5 or any other fast/unsalted hash here.

CREATE TABLE IF NOT EXISTS patient_records (
    id                  INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    patient_name        VARCHAR(255)    NOT NULL,
    diagnosis           TEXT            NOT NULL,
    attending_physician VARCHAR(255)    NOT NULL,
    created_at          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS staff_credentials (
    id            INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
    username      VARCHAR(100)  NOT NULL UNIQUE,
    auth_key_hash VARCHAR(255)  NOT NULL,          -- Argon2id hash, never MD5
    created_at    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;
