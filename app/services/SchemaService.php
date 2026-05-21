<?php

namespace App\Services;

use App\Core\Database;
use Throwable;

class SchemaService
{
    private static bool $ready = false;

    public static function ensureLatest(): void
    {
        if (self::$ready) { return; }
        self::$ready = true;
        self::ensureRememberTokensTable();
        self::addColumnIfMissing('users', 'two_factor_secret', 'VARCHAR(255) NULL DEFAULT NULL AFTER password');
        self::addColumnIfMissing('users', 'two_factor_enabled', 'TINYINT(1) NOT NULL DEFAULT 0 AFTER two_factor_secret');
        self::addColumnIfMissing('users', 'two_factor_confirmed_at', 'DATETIME NULL DEFAULT NULL AFTER two_factor_enabled');
        self::addColumnIfMissing('projects', 'gallery_images', 'LONGTEXT NULL DEFAULT NULL AFTER image_url');
        self::addColumnIfMissing('profiles', 'availability', 'VARCHAR(50) NOT NULL DEFAULT \'disponible\' AFTER location');
        self::addColumnIfMissing('profiles', 'presentation_video_url', 'VARCHAR(255) NULL DEFAULT NULL AFTER cv_url');
        self::addColumnIfMissing('profiles', 'twitter_url', 'VARCHAR(255) NULL DEFAULT NULL AFTER linkedin_url');
        self::addColumnIfMissing('profiles', 'instagram_url', 'VARCHAR(255) NULL DEFAULT NULL AFTER twitter_url');
        self::addColumnIfMissing('profiles', 'whatsapp_url', 'VARCHAR(255) NULL DEFAULT NULL AFTER instagram_url');
        self::addColumnIfMissing('profiles', 'facebook_url', 'VARCHAR(255) NULL DEFAULT NULL AFTER whatsapp_url');
        self::addColumnIfMissing('profiles', 'other_links', 'TEXT NULL DEFAULT NULL AFTER website_url');
        self::addColumnIfMissing('posts', 'category', 'VARCHAR(100) NOT NULL DEFAULT \'autre\' AFTER titre');
        self::addColumnIfMissing('posts', 'view_count', 'INT UNSIGNED NOT NULL DEFAULT 0 AFTER published_at');
        self::addColumnIfMissing('collaborations', 'portfolio_url', 'VARCHAR(255) NULL DEFAULT NULL AFTER linkedin_url');
        self::addColumnIfMissing('collaborations', 'github_url', 'VARCHAR(255) NULL DEFAULT NULL AFTER portfolio_url');
        self::addColumnIfMissing('themes', 'display_font_family', 'VARCHAR(255) NULL DEFAULT NULL AFTER text_color');
        self::addColumnIfMissing('themes', 'body_font_family', 'VARCHAR(255) NULL DEFAULT NULL AFTER display_font_family');
        self::addColumnIfMissing('notifications', 'unique_key', 'VARCHAR(190) NULL DEFAULT NULL AFTER type');
        self::addIndexIfMissing('notifications', 'idx_notifications_unique_key', 'ALTER TABLE notifications ADD KEY idx_notifications_unique_key (unique_key)');
        self::addColumnIfMissing('analytics', 'country_code', 'VARCHAR(10) NULL DEFAULT NULL AFTER country');
        self::backfillThemeFonts();
        self::upgradeSkillsLevelStorage();
    }

    private static function ensureRememberTokensTable(): void
    {
        Database::query('CREATE TABLE IF NOT EXISTS remember_tokens (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, user_id INT UNSIGNED NOT NULL, selector VARCHAR(32) NOT NULL, token_hash VARCHAR(255) NOT NULL, expires_at DATETIME NOT NULL, user_agent VARCHAR(255) NULL DEFAULT NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (id), UNIQUE KEY uk_remember_tokens_selector (selector), KEY idx_remember_tokens_user_id (user_id), KEY idx_remember_tokens_expires_at (expires_at), CONSTRAINT fk_remember_tokens_user_id FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE ON UPDATE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }

    private static function addColumnIfMissing(string $table, string $column, string $definition): void
    {
        if (self::columnExists($table, $column)) { return; }
        Database::query(sprintf('ALTER TABLE %s ADD COLUMN %s %s', $table, $column, $definition));
    }

    private static function addIndexIfMissing(string $table, string $index, string $statement): void
    {
        if (self::indexExists($table, $index)) { return; }
        Database::query($statement);
    }

    private static function backfillThemeFonts(): void
    {
        if (!self::columnExists('themes', 'display_font_family') || !self::columnExists('themes', 'body_font_family')) { return; }
        Database::query('UPDATE themes SET display_font_family = COALESCE(NULLIF(display_font_family, \'\'), font_family), body_font_family = COALESCE(NULLIF(body_font_family, \'\'), font_family) WHERE display_font_family IS NULL OR display_font_family = \'\' OR body_font_family IS NULL OR body_font_family = \'\'');
    }

    private static function upgradeSkillsLevelStorage(): void
    {
        if (!self::columnExists('skills', 'niveau')) { return; }
        try { Database::query('ALTER TABLE skills MODIFY COLUMN niveau VARCHAR(50) NOT NULL DEFAULT \'Intermediaire\''); } catch (Throwable) { return; }
        Database::query('UPDATE skills SET niveau = CASE WHEN CAST(niveau AS UNSIGNED) >= 85 THEN \'Expert\' WHEN CAST(niveau AS UNSIGNED) >= 60 THEN \'Avance\' WHEN CAST(niveau AS UNSIGNED) >= 30 THEN \'Intermediaire\' ELSE \'Notions\' END WHERE niveau REGEXP \'^[0-9]+$\'');
    }

    private static function columnExists(string $table, string $column): bool
    {
        $row = Database::query('SELECT COUNT(*) AS total FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?', [self::databaseName(), $table, $column])->fetch();
        return (int) ($row['total'] ?? 0) > 0;
    }

    private static function indexExists(string $table, string $index): bool
    {
        $row = Database::query('SELECT COUNT(*) AS total FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND INDEX_NAME = ?', [self::databaseName(), $table, $index])->fetch();
        return (int) ($row['total'] ?? 0) > 0;
    }

    private static function databaseName(): string
    {
        return (string) env('DB_NAME', 'portfolio_os');
    }
}