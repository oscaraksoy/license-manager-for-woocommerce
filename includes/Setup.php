<?php

namespace LicenseManagerForWooCommerce;

use function dbDelta;

defined('ABSPATH') || exit;

class Setup
{
    /**
     * @var string
     */
    const LICENSES_TABLE_NAME = 'lmfwc_licenses';

    /**
     * @var string
     */
    const GENERATORS_TABLE_NAME = 'lmfwc_generators';

    /**
     * @var string
     */
    const API_KEYS_TABLE_NAME = 'lmfwc_api_keys';

    /**
     * @var int
     */
    const DB_VERSION = 103;

    /**
     * Installation script.
     */
    public static function install()
    {
        self::createTables();
        self::setDefaultFilesAndFolders();
        self::setDefaultOptions();
    }

    /**
     * Deactivation script.
     */
    public static function deactivate()
    {
        // Nothing for now...
    }

    /**
     * Uninstall script.
     */
    public static function uninstall()
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
     * Migration script.
     */
    public static function migrate()
    {
        $current_db_version = get_option('lmfwc_db_version');

        if ($current_db_version != self::DB_VERSION) {
            if ($current_db_version < self::DB_VERSION) {
                Migration::up($current_db_version);
            }

            if ($current_db_version > self::DB_VERSION) {
                Migration::down($current_db_version);
            }
        }
    }

    /**
     * Create the necessary database tables.
     */
    public static function createTables()
    {
        global $wpdb;

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $table1 = $wpdb->prefix . self::LICENSES_TABLE_NAME;
        $table2 = $wpdb->prefix . self::GENERATORS_TABLE_NAME;
        $table3 = $wpdb->prefix . self::API_KEYS_TABLE_NAME;

        $table1_sql = "
            CREATE TABLE IF NOT EXISTS $table1 (
                `id` BIGINT(20) NOT NULL COMMENT 'Primary Key' AUTO_INCREMENT,
                `order_id` BIGINT(20) NULL DEFAULT NULL COMMENT 'WC_Order ID',
                `product_id` BIGINT(20) NULL DEFAULT NULL COMMENT 'WC_Product or WC_Product_Variation ID',
                `license_key` LONGTEXT NOT NULL COMMENT 'Encrypted License Key',
                `hash` LONGTEXT NOT NULL COMMENT 'Hashed License Key ID',
                `expires_at` DATETIME NULL DEFAULT NULL COMMENT 'Expiration Date',
                `valid_for` INT(32) NULL DEFAULT NULL COMMENT 'License Validity (in days)',
                `source` VARCHAR(255) NOT NULL COMMENT 'Import or Generator',
                `status` TINYINT(1) NOT NULL COMMENT 'Sold, Delivered, Active, Inactive',
                `times_activated` INT(10) NULL DEFAULT NULL COMMENT 'Number of activations',
                `times_activated_max` INT(10) NULL DEFAULT NULL COMMENT 'Maximum number of activations',
                `created_at` DATETIME NULL COMMENT 'Creation Date',
                `created_by` BIGINT(20) NULL DEFAULT NULL COMMENT 'WP User ID',
                `updated_at` DATETIME NULL DEFAULT NULL COMMENT 'Update Date',
                `updated_by` BIGINT(20) NULL DEFAULT NULL COMMENT 'WP User ID',
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ";

        dbDelta($table1_sql);

        $table2_sql = "
            CREATE TABLE IF NOT EXISTS $table2 (
                `id` INT(20) NOT NULL AUTO_INCREMENT,
                `name` VARCHAR(255) NOT NULL,
                `charset` VARCHAR(255) NOT NULL,
                `chunks` INT(10) NOT NULL,
                `chunk_length` INT(10) NOT NULL,
                `times_activated_max` INT(10) NULL DEFAULT NULL COMMENT 'Maximum number of activations',
                `separator` VARCHAR(255) NULL DEFAULT NULL,
                `prefix` VARCHAR(255) NULL DEFAULT NULL,
                `suffix` VARCHAR(255) NULL DEFAULT NULL,
                `expires_in` INT(10) NULL DEFAULT NULL,
                `created_at` DATETIME NULL COMMENT 'Creation Date',
                `created_by` BIGINT(20) NULL DEFAULT NULL COMMENT 'WP User ID',
                `updated_at` DATETIME NULL DEFAULT NULL COMMENT 'Update Date',
                `updated_by` BIGINT(20) NULL DEFAULT NULL COMMENT 'WP User ID',
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ";

        dbDelta($table2_sql);

        $table3_sql = "
            CREATE TABLE IF NOT EXISTS $table3 (
                `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                `user_id` BIGINT(20) UNSIGNED NOT NULL,
                `description` VARCHAR(200) NULL DEFAULT NULL,
                `permissions` VARCHAR(10) NOT NULL,
                `consumer_key` CHAR(64) NOT NULL,
                `consumer_secret` CHAR(43) NOT NULL,
                `nonces` LONGTEXT NULL,
                `truncated_key` CHAR(7) NOT NULL,
                `last_access` DATETIME NULL DEFAULT NULL,
                `created_at` DATETIME NULL COMMENT 'Creation Date',
                `created_by` BIGINT(20) NULL DEFAULT NULL COMMENT 'WP User ID',
                `updated_at` DATETIME NULL DEFAULT NULL COMMENT 'Update Date',
                `updated_by` BIGINT(20) NULL DEFAULT NULL COMMENT 'WP User ID',
                PRIMARY KEY (`id`),
                INDEX `consumer_key` (`consumer_key`),
                INDEX `consumer_secret` (`consumer_secret`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ";

        dbDelta($table3_sql);
    }

    public static function setDefaultFilesAndFolders()
    {
        /* When the cryptographic secrets are loaded into these constants, no other files are needed */
        if (defined('LMFWC_PLUGIN_SECRET') && defined('LMFWC_PLUGIN_DEFUSE')) {
            return;
        }

        $uploads = wp_upload_dir(null, false);
        $dir_lmfwc = $uploads['basedir'] . '/lmfwc-files';
        $file_htaccess = $dir_lmfwc . '/.htaccess';
        $file_defuse = $dir_lmfwc . '/defuse.txt';
        $file_secret = $dir_lmfwc . '/secret.txt';

        $old_mask = umask(0);

        // wp-contents/lmfwc-files/
        if (!file_exists($dir_lmfwc)) {
            @mkdir($dir_lmfwc, 0775, true);
        } else {
            $perms_dir_lmfwc = substr(sprintf('%o', fileperms($dir_lmfwc)), -4);

            if ($perms_dir_lmfwc != '0775') {
                @chmod($perms_dir_lmfwc, 0775);
            }
        }

        // wp-contents/lmfwc-files/.htaccess
        if (!file_exists($file_htaccess)) {
            $file_handle = @fopen($file_htaccess, 'w');

            if ($file_handle) {
                fwrite($file_handle, 'deny from all');
                fclose($file_handle);
            }

            @chmod($file_htaccess, 0664);
        } else {
            $perms_file_htaccess = substr(sprintf('%o', fileperms($file_htaccess)), -4);

            if ($perms_file_htaccess != '0664') {
                @chmod($perms_file_htaccess, 0664);
            }
        }

        // wp-contents/lmfwc-files/defuse.txt
        if (!file_exists($file_defuse)) {
            $defuse = \Defuse\Crypto\Key::createNewRandomKey();
            $file_handle = @fopen($file_defuse, 'w');

            if ($file_handle) {
                fwrite($file_handle, $defuse->saveToAsciiSafeString());
                fclose($file_handle);
            }

            @chmod($file_defuse, 0664);
        } else {
            $perms_file_defuse = substr(sprintf('%o', fileperms($file_defuse)), -4);

            if ($perms_file_defuse != '0664') {
                @chmod($perms_file_defuse, 0664);
            }
        }

        // wp-contents/lmfwc-files/secret.txt
        if (!file_exists($file_secret)) {
            $file_handle = @fopen($file_secret, 'w');

            if ($file_handle) {
                fwrite($file_handle, bin2hex(openssl_random_pseudo_bytes(32)));
                fclose($file_handle);
            }

            @chmod($file_secret, 0664);
        } else {
            $perms_file_secret = substr(sprintf('%o', fileperms($file_secret)), -4);

            if ($perms_file_secret != '0664') {
                @chmod($perms_file_secret, 0664);
            }
        }

        umask($old_mask);
    }

    public static function setDefaultOptions()
    {
        $defaults = array(
            'lmfwc_hide_license_keys' => 0,
            'lmfwc_auto_delivery' => 1
        );

        // The defaults for the Setting API.
        update_option('lmfwc_settings', $defaults);
        update_option('lmfwc_db_version', self::DB_VERSION);
    }
}