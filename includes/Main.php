<?php
/**
 * Main plugin file.
 * PHP Version: 5.6
 * 
 * @category WordPress
 * @package  LicenseManagerForWooCommerce
 * @author   Dražen Bebić <drazen.bebic@outlook.com>
 * @license  GNUv3 https://www.gnu.org/licenses/gpl-3.0.en.html
 * @link     https://www.licensemanager.at/
 */

namespace LicenseManagerForWooCommerce;

defined('ABSPATH') || exit;

/**
 * LicenseManagerForWooCommerce
 *
 * @category WordPress
 * @package  LicenseManagerForWooCommerce
 * @author   Dražen Bebić <drazen.bebic@outlook.com>
 * @license  GNUv3 https://www.gnu.org/licenses/gpl-3.0.en.html
 * @version  Release: <2.0.1>
 * @link     https://www.licensemanager.at/
 */
final class Main
{
    /**
     * @var string
     */
    public $version = '2.0.1';

    /**
     * @var $this
     */
    protected static $instance = null;

    /**
     * Main constructor.
     */
    private function __construct()
    {
        $this->_defineConstants();
        $this->_initHooks();

        add_action('init', array($this, 'loadPluginTextDomain'));
        add_action('init', array($this, 'init'));

        new API\Authentication();
    }

    /**
     * Ensures only one instance of LicenseManagerForWooCommerce is loaded or can be loaded.
     *
     * @return $this
     */
    public static function instance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Define plugin constants.
     */
    private function _defineConstants()
    {
        if (!defined('ABSPATH_LENGTH')) {
            define('ABSPATH_LENGTH', strlen(ABSPATH));
        }

        define('LMFWC_VERSION',         $this->version);
        define('LMFWC_ABSPATH',         dirname(LMFWC_PLUGIN_FILE) . '/');
        define('LMFWC_PLUGIN_BASENAME', plugin_basename(LMFWC_PLUGIN_FILE));

        // Directories
        define('LMFWC_ASSETS_DIR',     LMFWC_ABSPATH       . 'assets/');
        define('LMFWC_LOG_DIR',        LMFWC_ABSPATH       . 'logs/');
        define('LMFWC_TEMPLATES_DIR',  LMFWC_ABSPATH       . 'templates/');
        define('LMFWC_MIGRATIONS_DIR', LMFWC_ABSPATH       . 'migrations/');
        define('LMFWC_CSS_DIR',        LMFWC_ASSETS_DIR    . 'css/');

        // URL's
        define('LMFWC_ASSETS_URL', LMFWC_PLUGIN_URL . 'assets/');
        define('LMFWC_ETC_URL',    LMFWC_ASSETS_URL . 'etc/');
        define('LMFWC_CSS_URL',    LMFWC_ASSETS_URL . 'css/');
        define('LMFWC_JS_URL',     LMFWC_ASSETS_URL . 'js/');
        define('LMFWC_IMG_URL',    LMFWC_ASSETS_URL . 'img/');
    }

    /**
     * Include JS and CSS files.
     */
    public function adminEnqueueScripts()
    {
        // Select2
        wp_register_style(
            'lmfwc_select2_cdn',
            'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.6-rc.0/css/select2.min.css'
        );
        wp_register_style(
            'lmfwc_select2',
            LMFWC_CSS_URL . 'select2.css'
        );
        wp_register_script(
            'lmfwc_select2_cdn',
            'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.6-rc.0/js/select2.min.js'
        );

        // CSS
        wp_enqueue_style('lmfwc_admin_css', LMFWC_CSS_URL . 'main.css');

        // JavaScript
        wp_enqueue_script('lmfwc_admin_js', LMFWC_JS_URL . 'script.js');

        if (isset($_GET['page']) && 
            (
                $_GET['page'] == AdminMenus::LICENSES_PAGE
                || $_GET['page'] == AdminMenus::GENERATORS_PAGE
                || $_GET['page'] == AdminMenus::SETTINGS_PAGE
            )
        ) {
            wp_enqueue_script('lmfwc_select2_cdn');
            wp_enqueue_style('lmfwc_select2_cdn');
            wp_enqueue_style('lmfwc_select2');
        }

        // Licenses page
        if (isset($_GET['page']) && $_GET['page'] == AdminMenus::LICENSES_PAGE) {
            wp_enqueue_script('lmfwc_licenses_page_js', LMFWC_JS_URL . 'licenses_page.js');

            wp_localize_script('lmfwc_licenses_page_js', 'i18n', array(
                'placeholderSearchOrders'   => __('Search by order ID or customer email', 'lmfwc'),
                'placeholderSearchProducts' => __('Search by product ID or product name', 'lmfwc')
            ));

            wp_localize_script('lmfwc_licenses_page_js', 'security', array(
                'dropdownSearch' => wp_create_nonce('lmfwc_dropdown_search')
            ));
        }

        // Generators page
        if (isset($_GET['page']) && $_GET['page'] == AdminMenus::GENERATORS_PAGE) {
            wp_enqueue_script('lmfwc_generators_page_js', LMFWC_JS_URL . 'generators_page.js');
        }

        // Settings page
        if (isset($_GET['page']) && $_GET['page'] == AdminMenus::SETTINGS_PAGE) {
            wp_enqueue_script('lmfwc_settings_page_js', LMFWC_JS_URL . 'settings_page.js');
        }

        // Script localization
        wp_localize_script(
            'lmfwc_admin_js', 'license', array(
                'show'     => wp_create_nonce('lmfwc_show_license_key'),
                'show_all' => wp_create_nonce('lmfwc_show_all_license_keys'),
            )
        );
    }

    /**
     * Add additional links to the plugin row meta.
     * 
     * @param array  $links Array of already present links
     * @param string $file  File name
     * 
     * @return array
     */
    public function pluginRowMeta($links, $file)
    {
        if (strpos($file, 'license-manager-for-woocommerce.php') !== false ) {
            $newLinks = array(
                'github' => sprintf(
                    '<a href="%s" target="_blank">%s</a>',
                    'https://github.com/drazenbebic/license-manager',
                    'GitHub'
                ),
                'docs' => sprintf(
                    '<a href="%s" target="_blank">%s</a>',
                    'https://www.licensemanager.at/docs/',
                    __('Documentation', 'lmfwc')
                ),
                'donate' => sprintf(
                    '<a href="%s" target="_blank">%s</a>',
                    'https://www.licensemanager.at/donate/',
                    __('Donate', 'lmfwc')
                )
            );
            
            $links = array_merge($links, $newLinks);
        }

        return $links;
    }

    /**
     * Hook into actions and filters.
     */
    private function _initHooks()
    {
        register_activation_hook(
            LMFWC_PLUGIN_FILE,
            array('\LicenseManagerForWooCommerce\Setup', 'install')
        );
        register_deactivation_hook(
            LMFWC_PLUGIN_FILE,
            array('\LicenseManagerForWooCommerce\Setup', 'deactivate')
        );
        register_uninstall_hook(
            LMFWC_PLUGIN_FILE,
            array('\LicenseManagerForWooCommerce\Setup', 'uninstall')
        );

        add_action('admin_enqueue_scripts', array($this, 'adminEnqueueScripts'));
        add_filter('plugin_row_meta', array($this, 'pluginRowMeta'), 10, 2);
    }

    /**
     * Adds the i18n translations to the plugin.
     */
    public function loadPluginTextDomain()
    {
        load_plugin_textdomain('lmfwc', false, 'license-manager-for-woocommerce/i18n/');
    }

    /**
     * Init LicenseManagerForWooCommerce when WordPress Initialises.
     */
    public function init()
    {
        Setup::migrate();

        $this->publicHooks();

        new Crypto();
        new Export();
        new AdminMenus();
        new AdminNotice();
        new Generator();
        new Repositories\PostMeta();
        new Repositories\Users();
        new FormHandler();
        new API\Setup();

        if ($this->isWooCommerceActive()) {
            new Integrations\WooCommerce\Controller();
        }
    }

    /**
     * Defines all public hooks
     */
    protected function publicHooks()
    {
        add_filter(
            'lmfwc_license_keys_table_heading',
            function($text) {
                $default = __('Your license key(s)', 'lmfwc');

                if (!$text) {
                    return $default;
                }

                return sanitize_text_field($text);
            },
            10,
            1
        );

        add_filter(
            'lmfwc_license_keys_table_valid_until',
            function($text) {
                $default = __('Valid until', 'lmfwc');

                if (!$text) {
                    return $default;
                }

                return sanitize_text_field($text);
            },
            10,
            1
        );
    }

    /**
     * Checks if WooCommerce is installed.
     *
     * @return bool
     */
    private function isWooCommerceActive()
    {
        return in_array(
            'woocommerce/woocommerce.php',
            apply_filters('active_plugins', get_option('active_plugins'))
        );
    }
}
