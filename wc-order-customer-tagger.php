<?php
/**
 * Plugin Name:       CM Order & Customer Tagger
 * Plugin URI:        https://claritymomentum.io/products/cm-order-customer-tagger/
 * Description:       Automatically tag WooCommerce orders and customers using flexible, configurable rules. Surface insights in your order and customer lists at a glance.
 * Version:           1.3.0
 * Requires at least: 6.0
 * Requires PHP:      8.1
 * Author:            Clarity Momentum
 * Author URI:        https://claritymomentum.io
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wc-order-customer-tagger
 * Domain Path:       /languages
 * WC requires at least: 7.0
 * WC tested up to:      10.7
 */
declare(strict_types=1);

if (!defined('ABSPATH')) exit;

define('CM_TAGGER_VERSION', '1.3.0');
define('CM_TAGGER_FILE',    __FILE__);
define('CM_TAGGER_DIR',     plugin_dir_path(__FILE__));
define('CM_TAGGER_URL',     plugin_dir_url(__FILE__));

// ── Autoloader ────────────────────────────────────────────────────────────────

if (file_exists(CM_TAGGER_DIR . 'vendor/autoload.php')) {
    require_once CM_TAGGER_DIR . 'vendor/autoload.php';
} else {
    spl_autoload_register(function (string $class): void {
        $prefix = 'ClarityMomentum\\WCTagger\\';
        if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
            return;
        }
        $relative = str_replace('\\', DIRECTORY_SEPARATOR, substr($class, strlen($prefix)));
        $file = CM_TAGGER_DIR . 'includes/' . $relative . '.php';
        if (file_exists($file)) {
            require_once $file;
        }
    });
}

// ── CM Feed Consumer ──────────────────────────────────────────────────────────

if ( file_exists( CM_TAGGER_DIR . 'lib/cm-feed-consumer/version.php' ) ) {
    require_once CM_TAGGER_DIR . 'lib/cm-feed-consumer/version.php';
}

// ── Boot ──────────────────────────────────────────────────────────────────────

register_deactivation_hook(
    __FILE__,
    function (): void {
        wp_clear_scheduled_hook( 'cm_feed_consumer_refresh' );
    }
);

register_activation_hook(__FILE__, function (): void {
    try {
        ClarityMomentum\WCTagger\Db\Schema::install();
        ClarityMomentum\WCTagger\Db\Seeder::maybeRun();
    } catch (\Throwable $e) {
        // Surface DB errors as a deactivation message rather than a white screen
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(
            esc_html__('CM Order & Customer Tagger could not be activated: ', 'wc-order-customer-tagger')
            . esc_html($e->getMessage()),
            esc_html__('Plugin Activation Error', 'wc-order-customer-tagger'),
            ['back_link' => true]
        );
    }
});

add_action('plugins_loaded', function (): void {
    if (!wc_tagger_wc_active()) {
        add_action('admin_notices', function (): void {
            echo '<div class="notice notice-error"><p>'
               . esc_html__('CM Order & Customer Tagger requires WooCommerce to be installed and active.', 'wc-order-customer-tagger')
               . '</p></div>';
        });
        return;
    }

    load_plugin_textdomain('wc-order-customer-tagger', false, dirname(plugin_basename(__FILE__)) . '/languages');

    ClarityMomentum\WCTagger\Db\Schema::maybeUpgrade();

    $repo      = new ClarityMomentum\WCTagger\Db\TagRepository();
    $ruleRepo  = new ClarityMomentum\WCTagger\Db\RuleRepository();
    $tagsAdmin = new ClarityMomentum\WCTagger\Admin\TagsAdmin($repo, $ruleRepo);
    $tagsAdmin->register();

    $orderMeta = new ClarityMomentum\WCTagger\Admin\OrderTagsMetaBox();
    $orderMeta->register();

    $orderColumn = new ClarityMomentum\WCTagger\Admin\OrderTagsColumn();
    $orderColumn->register();

    $customerProfile = new ClarityMomentum\WCTagger\Admin\CustomerTagsProfile();
    $customerProfile->register();

    $customerColumn = new ClarityMomentum\WCTagger\Admin\CustomerTagsColumn();
    $customerColumn->register();

    $popover = new ClarityMomentum\WCTagger\Admin\TagPopover();
    $popover->register();

    // ── Grid filters + bulk re-run ────────────────────────────────────────────
    $tagger      = new ClarityMomentum\WCTagger\RuleEngine\Tagger();
    $gridFilters = new ClarityMomentum\WCTagger\Admin\GridFilters($tagger);
    $gridFilters->register();

    // ── Rule engine hooks ─────────────────────────────────────────────────────

    // New order created at checkout
    add_action('woocommerce_checkout_order_created', function (\WC_Order $order) use ($tagger): void {
        $tagger->tagOrder($order);
    });

    // Order status changes (e.g. pending → processing after payment confirmation)
    add_action('woocommerce_order_status_changed', function (int $orderId, string $from, string $to) use ($tagger): void {
        $order = wc_get_order($orderId);
        if ($order instanceof \WC_Order) {
            $tagger->tagOrder($order);
        }
    }, 10, 3);

    // ── CM Feed Consumer ─────────────────────────────────────────────────────
    if ( class_exists( 'CM_Feed_Consumer' ) ) {
        $cm_consumer = new CM_Feed_Consumer(
            array(
                'module'   => 'cm-order-customer-tagger',
                'platform' => 'woocommerce',
            )
        );
        ( new CM_Feed_Notice( $cm_consumer ) )->init();
        ( new CM_Feed_Scheduler( $cm_consumer ) )->init();
    }
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
