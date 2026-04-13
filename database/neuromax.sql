-- ============================================================
-- Neuromax – AI Face Transformation Platform
-- Database Schema + Seed Data
-- ============================================================
-- Run this SQL file in phpMyAdmin or MySQL CLI to set up the DB.
-- ============================================================

CREATE DATABASE IF NOT EXISTS `neuromax`
    DEFAULT CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `neuromax`;

-- ============================================================
-- USERS TABLE
-- Stores all registered users (regular users + admins)
-- ============================================================
CREATE TABLE IF NOT EXISTS `users` (
    `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name`       VARCHAR(100)  NOT NULL,
    `email`      VARCHAR(255)  NOT NULL UNIQUE,
    `password`   VARCHAR(255)  NOT NULL,
    `role`       ENUM('user', 'admin') NOT NULL DEFAULT 'user',
    `avatar`     VARCHAR(255)  DEFAULT NULL,
    `created_at` DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX `idx_users_email` (`email`),
    INDEX `idx_users_role`  (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- FACE_DATA TABLE
-- Stores face descriptors for biometric login (face-api.js)
-- Each descriptor is a JSON array of 128 floats
-- ============================================================
CREATE TABLE IF NOT EXISTS `face_data` (
    `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id`     INT UNSIGNED NOT NULL,
    `descriptor`  JSON         NOT NULL,
    `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT `fk_face_data_user`
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    INDEX `idx_face_data_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- SUBSCRIPTIONS TABLE
-- Defines available subscription plans
-- ============================================================
CREATE TABLE IF NOT EXISTS `subscriptions` (
    `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name`        VARCHAR(50)    NOT NULL,
    `price`       DECIMAL(10,2)  NOT NULL DEFAULT 0.00,
    `features`    JSON           NOT NULL,
    `max_jobs`    INT UNSIGNED   NOT NULL DEFAULT 10,
    `created_at`  DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- USER_SUBSCRIPTIONS TABLE
-- Links users to their chosen subscription plan
-- ============================================================
CREATE TABLE IF NOT EXISTS `user_subscriptions` (
    `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id`         INT UNSIGNED NOT NULL,
    `subscription_id` INT UNSIGNED NOT NULL,
    `start_date`      DATE         NOT NULL,
    `end_date`        DATE         DEFAULT NULL,
    `status`          ENUM('active', 'expired', 'cancelled') NOT NULL DEFAULT 'active',
    `created_at`      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT `fk_user_sub_user`
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT `fk_user_sub_plan`
        FOREIGN KEY (`subscription_id`) REFERENCES `subscriptions`(`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    INDEX `idx_user_sub_user`   (`user_id`),
    INDEX `idx_user_sub_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- JOBS TABLE
-- Tracks AI face-swap processing jobs submitted by users
-- source_path = the face image to use as replacement (source face)
-- file_path   = the target photo where the face gets swapped
-- ============================================================
CREATE TABLE IF NOT EXISTS `jobs` (
    `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id`     INT UNSIGNED NOT NULL,
    `source_path` VARCHAR(500) NOT NULL COMMENT 'Source face image (the face to swap in)',
    `file_path`   VARCHAR(500) NOT NULL COMMENT 'Target photo (face gets replaced here)',
    `result_path` VARCHAR(500) DEFAULT NULL,
    `effect`      VARCHAR(50)  NOT NULL DEFAULT 'faceswap',
    `status`      ENUM('pending', 'processing', 'completed', 'failed') NOT NULL DEFAULT 'pending',
    `error_msg`   TEXT         DEFAULT NULL,
    `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT `fk_jobs_user`
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    INDEX `idx_jobs_user`   (`user_id`),
    INDEX `idx_jobs_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- CONTACTS TABLE
-- Stores contact form submissions
-- ============================================================
CREATE TABLE IF NOT EXISTS `contacts` (
    `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name`       VARCHAR(100) NOT NULL,
    `email`      VARCHAR(255) NOT NULL,
    `subject`    VARCHAR(255) NOT NULL,
    `message`    TEXT         NOT NULL,
    `is_read`    TINYINT(1)   NOT NULL DEFAULT 0,
    `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- SEED DATA
-- ============================================================

-- Subscription Plans
INSERT INTO `subscriptions` (`id`, `name`, `price`, `features`, `max_jobs`) VALUES
(1, 'Basic',  0.00,  '["5 face swaps/month", "Standard quality", "720p output", "Email support"]', 5),
(2, 'Pro',    19.99, '["50 face swaps/month", "HD quality", "1080p output", "Priority support", "Face login"]', 50),
(3, 'Ultra',  49.99, '["Unlimited face swaps", "Max quality", "4K output", "24/7 priority support", "Face login", "API access", "Batch processing"]', 9999);

-- Default Admin Account
-- Password: Admin123!  (bcrypt hash)
INSERT INTO `users` (`name`, `email`, `password`, `role`) VALUES
('Admin', 'admin@neuromax.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Assign Basic plan to admin
INSERT INTO `user_subscriptions` (`user_id`, `subscription_id`, `start_date`, `status`) VALUES
(1, 1, CURDATE(), 'active');
