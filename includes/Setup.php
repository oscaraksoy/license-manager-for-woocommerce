<?php

namespace LicenseManagerForWooCommerce;

defined('ABSPATH') || exit;

/**
 * LicenseManagerForWooCommerce Setup.
 *
 * @version 1.1.0
 * @since 1.0.0
 */
class Setup
{
    /**
     * License table name.
     *
     * @since 1.0.0
     */
    const LICENSES_TABLE_NAME = 'lmfwc_licenses';

    /**
     * Generators table name.
     *
     * @since 1.0.0
     */
    const GENERATORS_TABLE_NAME = 'lmfwc_generators';

    /**
     * REST API keys table name.
     *
     * @since 1.0.0
     */
    const API_KEYS_TABLE_NAME = 'lmfwc_api_keys';

    /**
     * Database version.
     *
     * @since 1.0.0
     */
    const DB_VERSION = 101;

    /**
     * Installation script.
     *
     * @since 1.0.0
     */
    public static function install()
    {
        self::createTables();
        self::setDefaulOptions();
    }

    /**
     * Deactivation script.
     *
     * @since 1.0.0
     */
    public static function deactivate()
    {
        // Nothing for now...
    }

    /**
     * Uninstallation script.
     *
     * @since 1.0.0
     */
    public function uninstall()
    {
        global $wpdb;

        $tables = array(
            $wpdb->prefix . self::LICENSES_TABLE_NAME,
            $wpdb->prefix . self::GENERATORS_TABLE_NAME,
            $wpdb->prefix . self::API_KEYS_TABLE_NAME
        );

        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS {$table}");
        }
    }

    /**
     * Create the necessary database tables.
     *
     * @since 1.0.0
     */
    public static function createTables()
    {
        global $wpdb;

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $table1 = $wpdb->prefix . self::LICENSES_TABLE_NAME;
        $table2 = $wpdb->prefix . self::GENERATORS_TABLE_NAME;
        $table3 = $wpdb->prefix . self::API_KEYS_TABLE_NAME;

        $tables = "
            CREATE TABLE $table1 (
                `id` BIGINT(20) NOT NULL COMMENT 'Primary Key' AUTO_INCREMENT,
                `order_id` BIGINT(20) NULL DEFAULT NULL COMMENT 'WC_Order ID',
                `product_id` BIGINT(20) NULL DEFAULT NULL COMMENT 'WC_Product ID',
                `license_key` VARCHAR(4000) NOT NULL COMMENT 'Encrypted License Key',
                `hash` VARCHAR(255) NOT NULL COMMENT 'Hashed License Key ID',
                `created_at` DATETIME NOT NULL COMMENT 'Creation Date',
                `expires_at` DATETIME NULL DEFAULT NULL COMMENT 'Expiration Date',
                `valid_for` INT(32) NULL DEFAULT NULL COMMENT 'License Validity (in days)',
                `source` VARCHAR(255) NOT NULL COMMENT 'Import or Generator',
                `status` TINYINT(1) NOT NULL COMMENT 'Sold, Delivered, Active, Inactive',
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
            CREATE TABLE $table2 (
                `id` INT(20) NOT NULL AUTO_INCREMENT,
                `name` VARCHAR(255) NOT NULL,
                `charset` VARCHAR(255) NOT NULL,
                `chunks` INT(10) NOT NULL,
                `chunk_length` INT(10) NOT NULL,
                `separator` VARCHAR(255) NULL DEFAULT NULL,
                `prefix` VARCHAR(255) NULL DEFAULT NULL,
                `suffix` VARCHAR(255) NULL DEFAULT NULL,
                `expires_in` INT(10) NULL DEFAULT NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
            CREATE TABLE $table3 (
                `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                `user_id` BIGINT(20) UNSIGNED NOT NULL,
                `description` VARCHAR(200) NULL DEFAULT NULL,
                `permissions` VARCHAR(10) NOT NULL,
                `consumer_key` CHAR(64) NOT NULL,
                `consumer_secret` CHAR(43) NOT NULL,
                `nonces` LONGTEXT NULL,
                `truncated_key` CHAR(7) NOT NULL,
                `last_access` DATETIME NULL DEFAULT NULL,
                PRIMARY KEY (`id`),
                INDEX `consumer_key` (`consumer_key`),
                INDEX `consumer_secret` (`consumer_secret`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ";

        \dbDelta($tables);
    }

    public static function setDefaulOptions()
    {
        $uploads = wp_upload_dir(null, false);
        $defaults = array(
            'lmfwc_hide_license_keys' => 0,
            'lmfwc_auto_delivery' => 1
        );

        // The defaults for the Setting API.
        update_option('lmfwc_settings', $defaults);
        update_option('lmfwc_db_version', self::DB_VERSION);

        // Cryptographic secrets.
        if (!file_exists($uploads['basedir'] . 'lmfwc-files/.htaccess')) {
            @mkdir($uploads['basedir'] . '/lmfwc-files', 0775, true);
            $file_handle = @fopen($uploads['basedir'] . '/lmfwc-files/.htaccess', 'w');

            if ($file_handle) {
                fwrite($file_handle, 'deny from all');
                fclose($file_handle);
            }
        }

        if (!file_exists($uploads['basedir'] . '/lmfwc-files/defuse.txt')) {
            $defuse = \Defuse\Crypto\Key::createNewRandomKey();
            $file_handle = @fopen($uploads['basedir'] . '/lmfwc-files/defuse.txt', 'w');

            if ($file_handle) {
                fwrite($file_handle, $defuse->saveToAsciiSafeString());
                fclose($file_handle);
            }
        }

        if (!file_exists($uploads['basedir'] . '/lmfwc-files/secret.txt')) {
            $file_handle = @fopen($uploads['basedir'] . '/lmfwc-files/secret.txt', 'w');

            if ($file_handle) {
                fwrite($file_handle, bin2hex(openssl_random_pseudo_bytes(32)));
                fclose($file_handle);
            }
        }
    }
}