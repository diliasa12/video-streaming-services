-- Jalankan: mysql -u root -p gateway_db < src/config/migration.sql

CREATE DATABASE IF NOT EXISTS gateway_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE gateway_db;

CREATE TABLE IF NOT EXISTS users (
  id              BIGINT UNSIGNED  NOT NULL AUTO_INCREMENT,
  name            VARCHAR(100)     NOT NULL,
  email           VARCHAR(150)     NOT NULL,
  password        VARCHAR(255)     NOT NULL,
  role            ENUM('student','instructor','admin') NOT NULL DEFAULT 'student',
  avatar_url      VARCHAR(500)     NULL DEFAULT NULL,
  is_active       TINYINT(1)       NOT NULL DEFAULT 1,
  created_at      TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  UNIQUE KEY uq_users_email (email),
  INDEX idx_users_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS refresh_tokens (
  id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id    BIGINT UNSIGNED NOT NULL,
  token      VARCHAR(500)    NOT NULL,
  expires_at TIMESTAMP       NOT NULL,
  revoked    TINYINT(1)      NOT NULL DEFAULT 0,
  created_at TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  UNIQUE KEY uq_token (token(191)),
  INDEX idx_user_id (user_id),
  CONSTRAINT fk_refresh_user
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;