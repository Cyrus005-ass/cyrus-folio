CREATE DATABASE IF NOT EXISTS `portfolio_os`
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

USE `portfolio_os`;

CREATE TABLE IF NOT EXISTS `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(150) NOT NULL,
  `email` VARCHAR(190) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `role` VARCHAR(50) NOT NULL DEFAULT 'super_admin',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `last_login_at` DATETIME NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_users_email` (`email`),
  KEY `idx_users_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `remember_tokens` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `selector` VARCHAR(32) NOT NULL,
  `token_hash` VARCHAR(255) NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `user_agent` VARCHAR(255) NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_remember_tokens_selector` (`selector`),
  KEY `idx_remember_tokens_user_id` (`user_id`),
  KEY `idx_remember_tokens_expires_at` (`expires_at`),
  CONSTRAINT `fk_remember_tokens_user_id`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `projects` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `titre` VARCHAR(190) NOT NULL,
  `slug` VARCHAR(190) NOT NULL,
  `description` TEXT NULL,
  `contenu` LONGTEXT NULL,
  `technologies` TEXT NULL,
  `image_url` VARCHAR(255) NULL DEFAULT NULL,
  `gallery_images` LONGTEXT NULL DEFAULT NULL,
  `video_url` VARCHAR(255) NULL DEFAULT NULL,
  `github_url` VARCHAR(255) NULL DEFAULT NULL,
  `demo_url` VARCHAR(255) NULL DEFAULT NULL,
  `statut` VARCHAR(50) NOT NULL DEFAULT 'brouillon',
  `est_mis_en_avant` TINYINT(1) NOT NULL DEFAULT 0,
  `ordre` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_projects_slug` (`slug`),
  KEY `idx_projects_statut` (`statut`),
  KEY `idx_projects_featured` (`est_mis_en_avant`),
  KEY `idx_projects_order_created` (`ordre`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `skills` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nom` VARCHAR(150) NOT NULL,
  `categorie` VARCHAR(100) NOT NULL DEFAULT 'autre',
  `niveau` VARCHAR(50) NOT NULL DEFAULT 'Intermediaire',
  `icone` VARCHAR(255) NULL DEFAULT NULL,
  `description` TEXT NULL,
  `est_active` TINYINT(1) NOT NULL DEFAULT 1,
  `ordre` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_skills_category_order` (`categorie`, `ordre`),
  KEY `idx_skills_active` (`est_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `profiles` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `full_name` VARCHAR(150) NOT NULL,
  `title` VARCHAR(190) NOT NULL DEFAULT '',
  `bio` TEXT NULL,
  `email` VARCHAR(190) NOT NULL,
  `phone` VARCHAR(50) NULL DEFAULT NULL,
  `location` VARCHAR(150) NULL DEFAULT NULL,
  `availability` VARCHAR(50) NOT NULL DEFAULT 'disponible',
  `avatar_url` VARCHAR(255) NULL DEFAULT NULL,
  `cv_url` VARCHAR(255) NULL DEFAULT NULL,
  `presentation_video_url` VARCHAR(255) NULL DEFAULT NULL,
  `github_url` VARCHAR(255) NULL DEFAULT NULL,
  `linkedin_url` VARCHAR(255) NULL DEFAULT NULL,
  `twitter_url` VARCHAR(255) NULL DEFAULT NULL,
  `instagram_url` VARCHAR(255) NULL DEFAULT NULL,
  `whatsapp_url` VARCHAR(255) NULL DEFAULT NULL,
  `facebook_url` VARCHAR(255) NULL DEFAULT NULL,
  `website_url` VARCHAR(255) NULL DEFAULT NULL,
  `other_links` TEXT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_profiles_user_id` (`user_id`),
  KEY `idx_profiles_email` (`email`),
  CONSTRAINT `fk_profiles_user_id`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `certifications` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `titre` VARCHAR(190) NOT NULL,
  `organisme` VARCHAR(190) NOT NULL,
  `categorie` VARCHAR(100) NOT NULL DEFAULT 'autre',
  `date_obtention` DATE NOT NULL,
  `date_expiration` DATE NULL DEFAULT NULL,
  `credential_id` VARCHAR(190) NULL DEFAULT NULL,
  `badge_url` VARCHAR(255) NULL DEFAULT NULL,
  `lien_verification` VARCHAR(255) NULL DEFAULT NULL,
  `est_active` TINYINT(1) NOT NULL DEFAULT 1,
  `ordre` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_certifications_active` (`est_active`),
  KEY `idx_certifications_order_obtention` (`ordre`, `date_obtention`),
  KEY `idx_certifications_expiration` (`date_expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `posts` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `titre` VARCHAR(190) NOT NULL,
  `category` VARCHAR(100) NOT NULL DEFAULT 'autre',
  `slug` VARCHAR(190) NOT NULL,
  `extrait` TEXT NULL,
  `contenu` LONGTEXT NULL,
  `tags` TEXT NULL,
  `image_url` VARCHAR(255) NULL DEFAULT NULL,
  `statut` VARCHAR(50) NOT NULL DEFAULT 'brouillon',
  `published_at` DATETIME NULL DEFAULT NULL,
  `view_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_posts_slug` (`slug`),
  KEY `idx_posts_statut` (`statut`),
  KEY `idx_posts_published_at` (`published_at`),
  KEY `idx_posts_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `contacts` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nom` VARCHAR(150) NOT NULL,
  `email` VARCHAR(190) NOT NULL,
  `sujet` VARCHAR(190) NOT NULL,
  `message` TEXT NOT NULL,
  `ip_address` VARCHAR(45) NULL DEFAULT NULL,
  `user_agent` TEXT NULL,
  `statut` VARCHAR(50) NOT NULL DEFAULT 'nouveau',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_contacts_email` (`email`),
  KEY `idx_contacts_statut_created` (`statut`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `notifications` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `type` VARCHAR(100) NOT NULL,
  `unique_key` VARCHAR(190) NULL DEFAULT NULL,
  `titre` VARCHAR(190) NOT NULL,
  `message` TEXT NOT NULL,
  `lien` VARCHAR(255) NULL DEFAULT NULL,
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `read_at` DATETIME NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_notifications_unique_key` (`unique_key`),
  KEY `idx_notifications_is_read` (`is_read`),
  KEY `idx_notifications_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `chatbot_knowledge` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `question` TEXT NOT NULL,
  `answer` LONGTEXT NOT NULL,
  `keywords` VARCHAR(255) NULL DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_chatbot_knowledge_is_active` (`is_active`),
  KEY `idx_chatbot_knowledge_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `collaborations` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_id` INT UNSIGNED NULL DEFAULT NULL,
  `nom_membre` VARCHAR(150) NOT NULL,
  `role` VARCHAR(150) NOT NULL,
  `email` VARCHAR(190) NULL DEFAULT NULL,
  `linkedin_url` VARCHAR(255) NULL DEFAULT NULL,
  `portfolio_url` VARCHAR(255) NULL DEFAULT NULL,
  `github_url` VARCHAR(255) NULL DEFAULT NULL,
  `contribution` TEXT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_collaborations_project_id` (`project_id`),
  KEY `idx_collaborations_created_at` (`created_at`),
  CONSTRAINT `fk_collaborations_project_id`
    FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`)
    ON DELETE SET NULL
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `themes` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nom` VARCHAR(100) NOT NULL DEFAULT 'Default',
  `primary_color` VARCHAR(20) NOT NULL DEFAULT '#2563eb',
  `secondary_color` VARCHAR(20) NOT NULL DEFAULT '#111827',
  `accent_color` VARCHAR(20) NOT NULL DEFAULT '#f59e0b',
  `background_color` VARCHAR(20) NOT NULL DEFAULT '#f8fafc',
  `text_color` VARCHAR(20) NOT NULL DEFAULT '#111827',
  `display_font_family` VARCHAR(255) NULL DEFAULT NULL,
  `body_font_family` VARCHAR(255) NULL DEFAULT NULL,
  `font_family` VARCHAR(255) NOT NULL DEFAULT 'Source Sans 3, Segoe UI, sans-serif',
  `animations_enabled` TINYINT(1) NOT NULL DEFAULT 1,
  `is_active` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_themes_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `analytics` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `session_id` VARCHAR(64) NOT NULL,
  `page` VARCHAR(255) NOT NULL,
  `referrer` TEXT NULL,
  `ip_address` VARCHAR(45) NULL DEFAULT NULL,
  `user_agent` TEXT NULL,
  `device` VARCHAR(50) NOT NULL DEFAULT 'desktop',
  `country` VARCHAR(100) NULL DEFAULT NULL,
  `country_code` VARCHAR(10) NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_analytics_session_id` (`session_id`),
  KEY `idx_analytics_page` (`page`),
  KEY `idx_analytics_device` (`device`),
  KEY `idx_analytics_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `activities` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NULL DEFAULT NULL,
  `action` VARCHAR(100) NOT NULL,
  `description` TEXT NOT NULL,
  `ip_address` VARCHAR(45) NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_activities_user_id` (`user_id`),
  KEY `idx_activities_action` (`action`),
  KEY `idx_activities_created_at` (`created_at`),
  CONSTRAINT `fk_activities_user_id`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE SET NULL
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
