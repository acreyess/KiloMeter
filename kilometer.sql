CREATE DATABASE IF NOT EXISTS `kilometer`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `kilometer`;

-- -------------------------------------------------------------
-- users
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id`            INT          NOT NULL AUTO_INCREMENT,
  `username`      VARCHAR(50)  NOT NULL,
  `email`         VARCHAR(255) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `height_cm`     FLOAT        DEFAULT NULL,
  `weight_kg`     FLOAT        DEFAULT NULL,
  `age`           INT          DEFAULT NULL,
  `gender`        VARCHAR(20)  DEFAULT NULL,
  `created_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_username` (`username`),
  UNIQUE KEY `uq_email`    (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------------
-- bmi_records
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `bmi_records` (
  `id`         INT      NOT NULL AUTO_INCREMENT,
  `user_id`    INT      NOT NULL,
  `weight_kg`  FLOAT    NOT NULL,
  `height_cm`  FLOAT    NOT NULL,
  `bmi`        FLOAT    NOT NULL,
  `category`   VARCHAR(30) NOT NULL,
  `recorded_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_bmi_user` (`user_id`),
  CONSTRAINT `fk_bmi_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------------
-- goals
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `goals` (
  `id`           INT          NOT NULL AUTO_INCREMENT,
  `user_id`      INT          NOT NULL,
  `title`        VARCHAR(255) NOT NULL,
  `target_value` FLOAT        DEFAULT NULL,
  `unit`         VARCHAR(50)  DEFAULT NULL,
  `deadline`     DATE         DEFAULT NULL,
  `completed`    TINYINT(1)   NOT NULL DEFAULT 0,
  `created_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_goals_user` (`user_id`),
  CONSTRAINT `fk_goals_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------------
-- runs  (tracker)
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `runs` (
  `id`               INT          NOT NULL AUTO_INCREMENT,
  `user_id`          INT          NOT NULL,
  `distance_km`      FLOAT        NOT NULL,
  `duration_minutes` FLOAT        NOT NULL,
  `pace`             FLOAT        DEFAULT NULL COMMENT 'min/km',
  `run_date`         DATE         NOT NULL,
  `notes`            TEXT         DEFAULT NULL,
  `created_at`       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_runs_user` (`user_id`),
  CONSTRAINT `fk_runs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
