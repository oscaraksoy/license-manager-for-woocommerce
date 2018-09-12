<?php

namespace LicenseManager\Classes;

use \LicenseManager\Classes\Abstracts\LicenseStatusEnum;

/**
 * LicenseManager Database.
 *
 * @version 1.0.0
 */

defined('ABSPATH') || exit;

/**
 * Database class.
 */
class Database
{
    private $crpyto;

    /**
     * Database Constructor.
     */
    public function __construct(
        \LicenseManager\Classes\Crypto $crypto
    ) {
        $this->crypto = $crypto;

        add_action('lima_save_generated_license_keys',   array($this, 'saveGeneratedLicenseKeys' ), 10, 1);
        add_action('lima_sell_imported_license_keys',    array($this, 'sellImportedLicenseKeys'  ), 10, 1);
        add_filter('lima_save_imported_license_keys',    array($this, 'saveImportedLicenseKeys'  ), 10, 1);
        add_filter('lima_save_added_license_key',        array($this, 'saveAddedLicenseKey'      ), 10, 1);
        add_filter('lima_license_key_exists',            array($this, 'licenseKeyExists'         ), 10, 1);
        add_filter('lima_import_license_keys',           array($this, 'importLicenseKeys'        ), 10, 1);
    }

    /**
     * Save the license keys for a given product to the database.
     *
     * @since 1.0.0
     *
     * @todo Convert to filter, return array of added licenses.
     *
     * @param int    $args['order_id']
     * @param int    $args['product_id']
     * @param array  $args['licenses']
     * @param int    $args['expires_in']
     * @param string $args['charset']
     * @param int    $args['chunk_length']
     * @param int    $args['chunks']
     * @param string $args['prefix']
     * @param string $args['separator']
     * @param string $args['suffix']
     */
    public function saveGeneratedLicenseKeys($args)
    {
        global $wpdb;

        $date         = new \DateTime();
        $created_at   = $date->format('Y-m-d H:i:s');
        $expires_at   = null;
        $invalid_keys = 0;

        // Set the expiration date if specified.
        if ($args['expires_in'] != null && is_numeric($args['expires_in'])) {
            $expires_at = $date->add(new \DateInterval('P' . $args['expires_in'] . 'D'))->format('Y-m-d H:i:s');
        }

        /**
         * @todo Update with proper status handling.
         */
        // Add the keys to the database table.
        foreach ($args['licenses'] as $license_key) {
            // Key exists, up the invalid keys count.
            if (apply_filters('lima_license_key_exists', $license_key)) {
                $invalid_keys++;
            // Key doesn't exist, add it to the database table.
            } else {
                // Save to database.
                $wpdb->insert(
                    $wpdb->prefix . Setup::LICENSES_TABLE_NAME,
                    array(
                        'order_id'    => $args['order_id'],
                        'product_id'  => $args['product_id'],
                        'license_key' => $this->crypto->encrypt($license_key),
                        'hash'        => $this->crypto->hash($license_key),
                        'created_at'  => $created_at,
                        'expires_at'  => $expires_at,
                        'source'      => 1,
                        'status'      => LicenseStatusEnum::SOLD
                    ),
                    array('%d', '%d', '%s', '%s', '%s', '%s', '%d')
                );
            }
        }

        // There have been duplicate keys, regenerate and add them.
        if ($invalid_keys > 0) {
            $new_keys = apply_filters('lima_create_license_keys', array(
                'amount'       => $invalid_keys,
                'charset'      => $args['charset'],
                'chunks'       => $args['chunks'],
                'chunk_length' => $args['chunk_length'],
                'separator'    => $args['separator'],
                'prefix'       => $args['prefix'],
                'suffix'       => $args['suffix'],
                'expires_in'   => $args['expires_in']
            ));
            $this->saveGeneratedLicenseKeys(array(
                'order_id'     => $args['order_id'],
                'product_id'   => $args['product_id'],
                'licenses'     => $new_keys['licenses'],
                'expires_in'   => $args['expires_in'],
                'charset'      => $args['charset'],
                'chunk_length' => $args['chunk_length'],
                'chunks'       => $args['chunks'],
                'prefix'       => $args['prefix'],
                'separator'    => $args['separator'],
                'suffix'       => $args['suffix']
            ));
        } else {
            // Keys have been generated and saved, this order is now complete.
            update_post_meta($args['order_id'], '_lima_order_status', 'complete');
        }
    }

    /**
     * Sell license keys already present in the database.
     *
     * @since 1.0.0
     *
     * @todo Add a 'valid_for' field in the license table and import forms. This is for how long manually added or
     * imported license keys are valid.
     *
     * @param array  $args['license_keys']
     * @param int    $args['order_id']
     * @param int    $args['amount']
     */
    public function sellImportedLicenseKeys($args)
    {
        global $wpdb;

        for ($i = 0; $i < $args['amount']; $i++) {
            $valid_for  = null;
            $expires_at = null;

            if (is_int($valid_for)) {
                $expires_at = $date->add(new \DateInterval('P' . $valid_for . 'D'))->format('Y-m-d H:i:s');
            }

            $wpdb->update(
                $wpdb->prefix . Setup::LICENSES_TABLE_NAME,
                array(
                    'order_id'   => intval($args['order_id']),
                    'expires_at' => $expires_at,
                    'status'     => LicenseStatusEnum::SOLD
                ),
                array('id' => $args['license_keys'][$i]->id),
                array('%d', '%s', '%d'),
                array('%d')
            );
        }
    }

    /**
     * Imports an array of un-encrypted license keys into the database.
     *
     * @since 1.0.0
     *
     * @param array   $args['license_keys']
     * @param boolean $args['activate'] 
     * @param int     $args['product_id']
     *
     * @return array
     */
    public function saveImportedLicenseKeys($args)
    {
        global $wpdb;

        $created_at = date('Y-m-d H:i:s');
        $result['added'] = $result['failed'] = 0;
        $args['activate'] ? $status = LicenseStatusEnum::ACTIVE : $status = LicenseStatusEnum::INACTIVE;

        // Add the keys to the database table.
        foreach ($args['license_keys'] as $license_key) {
            if ($wpdb->insert(
                    $wpdb->prefix . Setup::LICENSES_TABLE_NAME,
                    array(
                        'order_id'    => null,
                        'product_id'  => $args['product_id'],
                        'license_key' => $this->crypto->encrypt($license_key),
                        'hash'        => $this->crypto->hash($license_key),
                        'created_at'  => $created_at,
                        'expires_at'  => null,
                        'source'      => 2,
                        'status'      => $status
                    ),
                    array('%d', '%d', '%s', '%s', '%s', '%s', '%d')
                )
            ) {
                $result['added']++;
            } else {
                $result['failed']++;
            }
        }

        return $result;
    }

    /**
     * Saves an un-encrypted license keys into the database.
     *
     * @since 1.0.0
     *
     * @param string  $args['license_key']
     * @param boolean $args['activate']
     * @param int     $args['product_id']
     *
     * @return array
     */
    public function saveAddedLicenseKey($args)
    {
        global $wpdb;

        $created_at = date('Y-m-d H:i:s');
        $args['activate'] ? $status = LicenseStatusEnum::ACTIVE : $status = LicenseStatusEnum::INACTIVE;

        return $wpdb->insert(
            $wpdb->prefix . Setup::LICENSES_TABLE_NAME,
            array(
                'order_id'    => null,
                'product_id'  => $args['product_id'],
                'license_key' => $this->crypto->encrypt($args['license_key']),
                'hash'        => $this->crypto->hash($args['license_key']),
                'created_at'  => $created_at,
                'expires_at'  => null,
                'source'      => 3,
                'status'      => $status
            ),
            array('%d', '%d', '%s', '%s', '%s', '%s', '%d')
        );
    }

    /**
     * Check if the license key already exists in the database.
     *
     * @since 1.0.0
     *
     * @param string $license_key - License key to be checked (plain text).
     *
     * @return boolean
     */
    public function licenseKeyExists($license_key)
    {
        global $wpdb;

        $table = $wpdb->prefix . Setup::LICENSES_TABLE_NAME;
        $sql   = "SELECT license_key FROM `{$table}` WHERE hash = '%s';";

        return $wpdb->get_var($wpdb->prepare($sql, $this->crypto->hash($license_key))) != null;
    }

    /**
     * Retrieves all license keys related to a specific order.
     *
     * @since 1.0.0
     *
     * @param int $order_id
     *
     * @return array
     */
    public static function getLicenseKeysByOrderId($order_id)
    {
        global $wpdb;
        $table = $wpdb->prefix . Setup::LICENSES_TABLE_NAME;

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table WHERE order_id = %d",
                intval($order_id)
            ),
            OBJECT
        );
    }

    /**
     * Retrieves all license keys related to a specific product.
     *
     * @since 1.0.0
     *
     * @param int $product_id
     * @param int $status
     *
     * @return array
     */
    public static function getLicenseKeysByProductId($product_id, $status)
    {
        global $wpdb;
        $table = $wpdb->prefix . Setup::LICENSES_TABLE_NAME;

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table WHERE product_id = %d AND status = %d",
                intval($product_id),
                intval($status)
            ),
            OBJECT
        );
    }

    public static function getGenerators()
    {
        global $wpdb;
        $table = $wpdb->prefix . Setup::GENERATORS_TABLE_NAME;

        return $wpdb->get_results("SELECT * FROM $table", OBJECT);
    }

    public static function getGenerator($id)
    {
        global $wpdb;
        $table = $wpdb->prefix . Setup::GENERATORS_TABLE_NAME;

        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id), OBJECT);
    }

    public static function getLicenseKey($id)
    {
        global $wpdb;

        $table       = $wpdb->prefix . Setup::LICENSES_TABLE_NAME;
        $crypto      = new Crypto();
        $license_key = $wpdb->get_var($wpdb->prepare("SELECT license_key FROM $table WHERE id = %d", $id));

        if ($license_key) {
            return $crypto->decrypt($license_key);
        } else {
            return $license_key;
        }

        return $license_key;
    }
}