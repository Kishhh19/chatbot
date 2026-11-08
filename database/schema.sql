-- ============================================================
-- AI Chatbot Platform - Complete Database Schema
-- Import this into phpMyAdmin or run via MySQL CLI
-- ============================================================

CREATE DATABASE IF NOT EXISTS `chatbot_db`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `chatbot_db`;

-- ============================================================
-- USERS
-- ============================================================
CREATE TABLE IF NOT EXISTS `users` (
    `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `username`     VARCHAR(50)  NOT NULL UNIQUE,
    `email`        VARCHAR(255) NOT NULL UNIQUE,
    `password`     VARCHAR(255) NOT NULL,
    `display_name` VARCHAR(100) NOT NULL,
    `role`         ENUM('admin','user') NOT NULL DEFAULT 'user',
    `is_active`    TINYINT(1) NOT NULL DEFAULT 1,
    `avatar`       VARCHAR(255) DEFAULT NULL,
    `last_login`   DATETIME DEFAULT NULL,
    `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_email` (`email`),
    INDEX `idx_role`  (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- SESSIONS (server-side session store — optional extra)
-- ============================================================
CREATE TABLE IF NOT EXISTS `user_sessions` (
    `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id`    INT UNSIGNED NOT NULL,
    `token`      VARCHAR(255) NOT NULL UNIQUE,
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `user_agent` TEXT DEFAULT NULL,
    `expires_at` DATETIME NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_token` (`token`),
    INDEX `idx_user`  (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- CONVERSATIONS
-- ============================================================
CREATE TABLE IF NOT EXISTS `conversations` (
    `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id`    INT UNSIGNED NOT NULL,
    `title`      VARCHAR(255) NOT NULL DEFAULT 'New Conversation',
    `is_pinned`  TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_updated` (`updated_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- MESSAGES
-- ============================================================
CREATE TABLE IF NOT EXISTS `messages` (
    `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `conversation_id` INT UNSIGNED NOT NULL,
    `role`            ENUM('user','assistant','system') NOT NULL,
    `content`         LONGTEXT NOT NULL,
    `sources`         JSON DEFAULT NULL,
    `tokens_used`     INT UNSIGNED DEFAULT NULL,
    `model_used`      VARCHAR(100) DEFAULT NULL,
    `created_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`conversation_id`) REFERENCES `conversations`(`id`) ON DELETE CASCADE,
    INDEX `idx_conversation` (`conversation_id`),
    INDEX `idx_created`      (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- KNOWLEDGE CATEGORIES
-- ============================================================
CREATE TABLE IF NOT EXISTS `knowledge_categories` (
    `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name`        VARCHAR(100) NOT NULL UNIQUE,
    `description` TEXT DEFAULT NULL,
    `color`       VARCHAR(7) DEFAULT '#6366f1',
    `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- KNOWLEDGE DOCUMENTS (uploaded files)
-- ============================================================
CREATE TABLE IF NOT EXISTS `knowledge_documents` (
    `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `title`         VARCHAR(255) NOT NULL,
    `description`   TEXT DEFAULT NULL,
    `category_id`   INT UNSIGNED DEFAULT NULL,
    `original_name` VARCHAR(255) NOT NULL,
    `stored_name`   VARCHAR(255) NOT NULL UNIQUE,
    `file_path`     VARCHAR(500) NOT NULL,
    `file_type`     VARCHAR(50) NOT NULL,
    `file_size`     INT UNSIGNED NOT NULL,
    `mime_type`     VARCHAR(100) NOT NULL,
    `status`        ENUM('pending','processing','indexed','failed') NOT NULL DEFAULT 'pending',
    `chunk_count`   INT UNSIGNED NOT NULL DEFAULT 0,
    `error_message` TEXT DEFAULT NULL,
    `uploaded_by`   INT UNSIGNED NOT NULL,
    `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`category_id`)  REFERENCES `knowledge_categories`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`uploaded_by`)  REFERENCES `users`(`id`) ON DELETE RESTRICT,
    INDEX `idx_status`   (`status`),
    INDEX `idx_category` (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- KNOWLEDGE ITEMS (manually entered)
-- ============================================================
CREATE TABLE IF NOT EXISTS `knowledge_items` (
    `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `title`       VARCHAR(255) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `content`     LONGTEXT NOT NULL,
    `category_id` INT UNSIGNED DEFAULT NULL,
    `tags`        VARCHAR(500) DEFAULT NULL,
    `is_active`   TINYINT(1) NOT NULL DEFAULT 1,
    `source_type` ENUM('manual','document') NOT NULL DEFAULT 'manual',
    `document_id` INT UNSIGNED DEFAULT NULL,
    `created_by`  INT UNSIGNED NOT NULL,
    `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`category_id`) REFERENCES `knowledge_categories`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`document_id`) REFERENCES `knowledge_documents`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`created_by`)  REFERENCES `users`(`id`) ON DELETE RESTRICT,
    INDEX `idx_category`   (`category_id`),
    INDEX `idx_document`   (`document_id`),
    INDEX `idx_source`     (`source_type`),
    INDEX `idx_active`     (`is_active`),
    FULLTEXT INDEX `ft_knowledge` (`title`, `content`, `tags`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- KNOWLEDGE CHUNKS (document chunks for RAG)
-- ============================================================
CREATE TABLE IF NOT EXISTS `knowledge_chunks` (
    `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `document_id` INT UNSIGNED DEFAULT NULL,
    `item_id`     INT UNSIGNED DEFAULT NULL,
    `chunk_index` INT UNSIGNED NOT NULL DEFAULT 0,
    `content`     LONGTEXT NOT NULL,
    `char_count`  INT UNSIGNED NOT NULL DEFAULT 0,
    `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`document_id`) REFERENCES `knowledge_documents`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`item_id`)     REFERENCES `knowledge_items`(`id`) ON DELETE CASCADE,
    INDEX `idx_document` (`document_id`),
    INDEX `idx_item`     (`item_id`),
    FULLTEXT INDEX `ft_chunk` (`content`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- KNOWLEDGE TAGS
-- ============================================================
CREATE TABLE IF NOT EXISTS `knowledge_tags` (
    `id`   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `knowledge_item_tags` (
    `item_id` INT UNSIGNED NOT NULL,
    `tag_id`  INT UNSIGNED NOT NULL,
    PRIMARY KEY (`item_id`, `tag_id`),
    FOREIGN KEY (`item_id`) REFERENCES `knowledge_items`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`tag_id`)  REFERENCES `knowledge_tags`(`id`)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- CHATBOT SETTINGS
-- ============================================================
CREATE TABLE IF NOT EXISTS `chatbot_settings` (
    `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `setting_key`   VARCHAR(100) NOT NULL UNIQUE,
    `setting_value` LONGTEXT DEFAULT NULL,
    `setting_type`  ENUM('string','integer','float','boolean','json') NOT NULL DEFAULT 'string',
    `description`   VARCHAR(500) DEFAULT NULL,
    `updated_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- API SETTINGS
-- ============================================================
CREATE TABLE IF NOT EXISTS `api_settings` (
    `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `provider`    VARCHAR(50) NOT NULL DEFAULT 'openai',
    `api_key`     TEXT NOT NULL,
    `api_url`     VARCHAR(500) DEFAULT NULL,
    `model`       VARCHAR(100) NOT NULL DEFAULT 'gpt-4o-mini',
    `is_active`   TINYINT(1) NOT NULL DEFAULT 1,
    `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- RATE LIMITING
-- ============================================================
CREATE TABLE IF NOT EXISTS `rate_limits` (
    `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `identifier` VARCHAR(255) NOT NULL,
    `action`     VARCHAR(100) NOT NULL,
    `count`      INT UNSIGNED NOT NULL DEFAULT 1,
    `window_start` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_identifier_action` (`identifier`, `action`),
    INDEX `idx_window` (`window_start`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- AUDIT LOGS
-- ============================================================
CREATE TABLE IF NOT EXISTS `audit_logs` (
    `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id`    INT UNSIGNED DEFAULT NULL,
    `action`     VARCHAR(100) NOT NULL,
    `entity`     VARCHAR(100) DEFAULT NULL,
    `entity_id`  INT UNSIGNED DEFAULT NULL,
    `details`    JSON DEFAULT NULL,
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_user`   (`user_id`),
    INDEX `idx_action` (`action`),
    INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- DEFAULT DATA
-- ============================================================

-- Default chatbot settings
INSERT INTO `chatbot_settings` (`setting_key`, `setting_value`, `setting_type`, `description`) VALUES
('bot_name',           'Sabinox',       'string',  'Chatbot display name'),
('bot_description',    'Sabino coffee shop FAQ and help assistant.', 'string', 'Short description'),
('bot_personality',    'friendly',       'string',  'Bot personality style'),
('system_instructions','You are Sabinox, the friendly assistant for Sabino coffee shop. Answer questions about the shop accurately. Address: 693 D. Fajardo Street, Salapungan, Angeles City, Pampanga. Phone: +63 916 653 9270. Hours: Monday to Saturday, opening around 7:00 AM or 9:00 AM depending on the day; Sunday closed. Closing times are unconfirmed. The sample menu and prices are illustrative Philippine cafe examples, not confirmed Sabino offerings. Never present them as official. Ask guests to contact the shop for exact hours, menu, prices, availability, orders, and policies.', 'string', 'System prompt'),
('knowledge_mode',     'hybrid',             'string',  'knowledge_only or hybrid'),
('temperature',        '0.7',                'float',   'AI temperature (0-2)'),
('max_tokens',         '2000',               'integer', 'Max response tokens'),
('context_chunks',     '5',                  'integer', 'Max RAG chunks per request'),
('welcome_message',    'Hi! I\'m Sabinox. Ask about Sabino\'s location, reported hours, contact details, or sample menu.', 'string', 'Welcome message shown to users'),
('show_sources',       'true',               'boolean', 'Show knowledge sources in responses'),
('allow_regenerate',   'true',               'boolean', 'Allow message regeneration')
ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`);

-- Default categories
INSERT INTO `knowledge_categories` (`name`, `description`, `color`) VALUES
('General',        'General knowledge',          '#6366f1'),
('FAQ',            'Frequently asked questions', '#10b981'),
('Policies',       'Policies and procedures',   '#f59e0b'),
('Documentation',  'Technical documentation',   '#3b82f6'),
('Products',       'Products and services',     '#ec4899')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);
