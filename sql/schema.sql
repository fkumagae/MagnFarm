-- schema.sql
-- Cria o banco de dados `magalface` e a tabela `users`.
-- Importar via phpMyAdmin ou mysql CLI.

CREATE DATABASE IF NOT EXISTS `magnfarm`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `magnfarm`;

-- Tabela de usuários mínima
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `email` VARCHAR(255) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `name` VARCHAR(100) DEFAULT NULL,
  `role` VARCHAR(20) NOT NULL DEFAULT 'user',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Observações:
-- - Use phpMyAdmin (Import) ou o cliente mysql para executar este arquivo.
-- - Não insira senhas em texto plano. A aplicação usará password_hash()/password_verify().
