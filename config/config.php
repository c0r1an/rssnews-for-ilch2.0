<?php

/**
 * @copyright Ilch 2
 * @package ilch
 */

namespace Modules\Rssnews\Config;

class Config extends \Ilch\Config\Install
{
    public $config = [
        'key' => 'rssnews',
        'version' => '1.0.0',
        'icon_small' => 'fa-solid fa-rss',
        'author' => 'Thomas Stantin',
        'link' => '',
        'languages' => [
            'de_DE' => [
                'name' => 'RSS News',
                'description' => 'RSS-Feeds aggregieren, bereinigen und optional als News spiegeln.',
            ],
            'en_EN' => [
                'name' => 'RSS News',
                'description' => 'Aggregate RSS feeds, sanitize content, and optionally mirror as news.',
            ],
        ],
        'ilchCore' => '2.2.0',
        'phpVersion' => '7.3',
    ];

    public function install()
    {
        $this->db()->queryMulti($this->getInstallSql());

        $databaseConfig = new \Ilch\Config\Database($this->db());
        $databaseConfig
            ->set('rssnews_defaultInterval', '900')
            ->set('rssnews_cronInterval', '900')
            ->set('rssnews_postMode', 'aggregator')
            ->set('rssnews_frontendLayout', 'list')
            ->set('rssnews_articleCatId', '1')
            ->set('rssnews_readAccess', '1,2,3')
            ->set('rssnews_cronToken', substr(sha1(generateUUID()), 0, 32))
            ->set('rssnews_lastAutoFetchCheck', '0');
    }

    public function uninstall()
    {
        $this->db()->queryMulti('DROP TABLE IF EXISTS `[prefix]_rssnews_logs`;
            DROP TABLE IF EXISTS `[prefix]_rssnews_items`;
            DROP TABLE IF EXISTS `[prefix]_rssnews_feeds`;');
    }

    public function getInstallSql(): string
    {
        return 'CREATE TABLE IF NOT EXISTS `[prefix]_rssnews_feeds` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `title` VARCHAR(255) NOT NULL,
                `feed_url` VARCHAR(1024) NOT NULL,
                `category` VARCHAR(255) NOT NULL DEFAULT "",
                `tags` VARCHAR(255) NOT NULL DEFAULT "",
                `update_interval` INT(11) NOT NULL DEFAULT 900,
                `max_items` INT(11) NOT NULL DEFAULT 10,
                `post_mode` VARCHAR(20) NOT NULL DEFAULT "aggregator",
                `article_cat_id` INT(11) NOT NULL DEFAULT 1,
                `read_access` VARCHAR(255) NOT NULL DEFAULT "1,2,3",
                `is_active` TINYINT(1) NOT NULL DEFAULT 1,
                `last_fetch_at` DATETIME NULL DEFAULT NULL,
                `last_success_at` DATETIME NULL DEFAULT NULL,
                `last_error` MEDIUMTEXT NULL DEFAULT NULL,
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=1;

            CREATE TABLE IF NOT EXISTS `[prefix]_rssnews_items` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `feed_id` INT(11) NOT NULL,
                `dedupe_hash` CHAR(40) NOT NULL,
                `source_guid` VARCHAR(1024) NOT NULL DEFAULT "",
                `source_link` VARCHAR(1024) NOT NULL DEFAULT "",
                `source_title` VARCHAR(255) NOT NULL DEFAULT "",
                `title` VARCHAR(255) NOT NULL,
                `teaser` VARCHAR(255) NOT NULL DEFAULT "",
                `content` MEDIUMTEXT NOT NULL,
                `author` VARCHAR(255) NOT NULL DEFAULT "",
                `published_at` DATETIME NOT NULL,
                `category` VARCHAR(255) NOT NULL DEFAULT "",
                `tags` VARCHAR(255) NOT NULL DEFAULT "",
                `mirror_mode` VARCHAR(20) NOT NULL DEFAULT "aggregator",
                `mirror_article_id` INT(11) NOT NULL DEFAULT 0,
                `created_at` DATETIME NOT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uniq_[prefix]_rssnews_items_dedupe` (`dedupe_hash`),
                KEY `idx_[prefix]_rssnews_items_feed` (`feed_id`),
                CONSTRAINT `fk_[prefix]_rssnews_items_feed` FOREIGN KEY (`feed_id`) REFERENCES `[prefix]_rssnews_feeds` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=1;

            CREATE TABLE IF NOT EXISTS `[prefix]_rssnews_logs` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `feed_id` INT(11) NOT NULL DEFAULT 0,
                `status` VARCHAR(20) NOT NULL DEFAULT "success",
                `message` MEDIUMTEXT NOT NULL,
                `fetched_count` INT(11) NOT NULL DEFAULT 0,
                `imported_count` INT(11) NOT NULL DEFAULT 0,
                `skipped_count` INT(11) NOT NULL DEFAULT 0,
                `created_at` DATETIME NOT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_[prefix]_rssnews_logs_feed` (`feed_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=1;';
    }

    public function getUpdate(string $installedVersion): string
    {
        return '"' . $this->config['key'] . '" Update-function executed.';
    }
}
