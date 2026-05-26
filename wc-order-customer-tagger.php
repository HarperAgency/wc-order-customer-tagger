<?php
/**
 * Plugin Name:       Harper Order & Customer Tagger
 * Plugin URI:        https://harper.agency
 * Description:       Auto-tag orders and customers using configurable rule-based conditions.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      8.1
 * Author:            Harper Agency
 * Author URI:        https://harper.agency
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wc-order-customer-tagger
 * Domain Path:       /languages
 * WC requires at least: 7.0
 * WC tested up to:      9.0
 */
declare(strict_types=1);

if (!defined('ABSPATH')) exit;

define('WC_TAGGER_VERSION', '1.0.0');
define('WC_TAGGER_FILE',    __FILE__);
define('WC_TAGGER_DIR',     plugin_dir_path(__FILE__));
define('WC_TAGGER_URL',     plugin_dir_url(__FILE__));

// ── Autoloader ────────────────────────────────────────────────────────────────

if (file_exists(WC_TAGGER_DIR . 'vendor/autoload.php')) {
    require_once WC_TAGGER_DIR . 'vendor/autoload.php';
} else {
    spl_autoload_register(function (string $class): void {
        $prefix = 'HarperAgency\\WCTagger\\';
        if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
            return;
        }
        $relative = str_replace('\\', DIRECTORY_SEPARATOR, substr($class, strlen($prefix)));
        $file = WC_TAGGER_DIR . 'includes/' . $relative . '.php';
        if (file_exists($file)) {
            require_once $file;
        }
    });
}

// ── Boot ──────────────────────────────────────────────────────────────────────

add_action('plugins_loaded', function (): void {
    if (!wc_tagger_wc_active()) {
        add_action('admin_notices', function (): void {
            echo '<div class="notice notice-error"><p>'
               . esc_html__('Harper Order & Customer Tagger requires WooCommerce to be installed and active.', 'wc-order-customer-tagger')
               . '</p></div>';
        });
        return;
    }

    load_plugin_textdomain('wc-order-customer-tagger', false, dirname(plugin_basename(__FILE__)) . '/languages');
});

// ── HPOS compatibility declaration ───────────────────────────────────────────

add_action('before_woocommerce_init', function (): void {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
            'custom_order_tables',
            __FILE__,
            true
        );
    }
});

// ── Helpers ───────────────────────────────────────────────────────────────────

function wc_tagger_wc_active(): bool
{
    return class_exists('WooCommerce');
}
