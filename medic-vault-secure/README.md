# medic-vault-secure

Secure PHP medical vault — defensive-programming alternative assessment deliverable.

This project demonstrates the fixed implementations of three originally vulnerable files.

## Security fixes applied

| Vulnerability | Original pattern | Fix |
|---|---|---|
| SQL Injection | `"... WHERE name LIKE '%{$_GET['keyword']}%'"` | PDO prepared statement + `bindValue` |
| Reflected XSS | `echo $_GET['keyword']` unescaped | `htmlspecialchars($x, ENT_QUOTES, 'UTF-8')` on every output path including the "no results" branch |
| Byte-length bypass | `strlen($key) > 256` | `mb_strlen($key, 'UTF-8') > 256` — counts characters, not bytes |
| Weak hashing | `md5($key)` static comparison | `password_hash(PASSWORD_ARGON2ID)` + `password_verify()` |
| Weak cipher | AES-128-ECB, hardcoded key | AES-256-GCM, key from env var, fresh `random_bytes(12)` IV per call |

## Requirements

- PHP 8.2+ with `openssl` and `mbstring` extensions
- Composer
- MySQL 8+ (for web endpoints; tests run without a database)

## Setup

```bash
# 1. Install dependencies
composer install

# 2. Configure environment
cp .env.example .env
# Edit .env — generate VAULT_KEY with:
#   php -r "echo base64_encode(random_bytes(32)), PHP_EOL;"

# 3. Import schema
mysql -u root -p < schema.sql

# 4. Seed demo staff accounts (requires .env with valid DB credentials)
php scripts/seed_staff.php

# 5. Run the test suite
./vendor/bin/phpunit tests
```

## Project structure

```
src/
  db_config.php       — PDO factory (env-var driven, utf8mb4, ERRMODE_EXCEPTION)
  search.php          — Secure patient search endpoint
  auth.php            — Secure staff key authentication endpoint
  crypto_vault.php    — AES-256-GCM encrypt/decrypt module
scripts/
  seed_staff.php      — Seeds two demo Argon2id credentials (dr_faizal, dr_sharifah)
tests/
  CryptoVaultTest.php — Round-trip, tamper detection, IV randomness
  AuthTest.php        — mb_strlen boundary, Argon2id verify success/failure
schema.sql
```

## Demo credentials (after seeding)

| Username | Key |
|---|---|
| dr_faizal | FaizalSecureKey!2024 |
| dr_sharifah | SharafahSecureKey!2024 |
