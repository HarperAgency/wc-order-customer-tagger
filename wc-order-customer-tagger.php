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
 * Text Domain:       cm-order-customer-tagger
 * Domain Path:       /languages
 * WC requires at least: 7.0
 * WC tested up to:      10.7
 *
 * @package ClarityMomentum\WCTagger
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'CM_TAGGER_VERSION', '1.3.0' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound
define( 'CM_TAGGER_FILE', __FILE__ ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound
define( 'CM_TAGGER_DIR', plugin_dir_path( __FILE__ ) ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound
define( 'CM_TAGGER_URL', plugin_dir_url( __FILE__ ) ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound

// ── Autoloader ────────────────────────────────────────────────────────────────

if ( file_exists( CM_TAGGER_DIR . 'vendor/autoload.php' ) ) {
	require_once CM_TAGGER_DIR . 'vendor/autoload.php';
} else {
	spl_autoload_register(
		function ( string $class_name ): void { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
			$prefix = 'ClarityMomentum\\WCTagger\\';
			if ( strncmp( $class_name, $prefix, strlen( $prefix ) ) !== 0 ) {
				return;
			}
			$relative = str_replace( '\\', DIRECTORY_SEPARATOR, substr( $class_name, strlen( $prefix ) ) );
			$file     = CM_TAGGER_DIR . 'includes/' . $relative . '.php';
			if ( file_exists( $file ) ) {
				require_once $file;
			}
		}
	);
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

register_activation_hook(
	__FILE__,
	function (): void {
		try {
			ClarityMomentum\WCTagger\Db\Schema::install();
			ClarityMomentum\WCTagger\Db\Seeder::maybeRun();
		} catch ( \Throwable $e ) {
			// Surface DB errors as a deactivation message rather than a white screen.
			deactivate_plugins( plugin_basename( __FILE__ ) );
			wp_die(
				esc_html__( 'CM Order & Customer Tagger could not be activated: ', 'cm-order-customer-tagger' )
				. esc_html( $e->getMessage() ),
				esc_html__( 'Plugin Activation Error', 'cm-order-customer-tagger' ),
				array( 'back_link' => true )
			);
		}
	}
);

add_action(
	'plugins_loaded',
	function (): void {
		if ( ! cm_tagger_wc_active() ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
			add_action(
				'admin_notices',
				function (): void {
					echo '<div class="notice notice-error"><p>'
					. esc_html__( 'CM Order & Customer Tagger requires WooCommerce to be installed and active.', 'cm-order-customer-tagger' )
					. '</p></div>';
				}
			);
			return;
		}

		ClarityMomentum\WCTagger\Db\Schema::maybeUpgrade();

		$repo       = new ClarityMomentum\WCTagger\Db\TagRepository();
		$rule_repo  = new ClarityMomentum\WCTagger\Db\RuleRepository();
		$tags_admin = new ClarityMomentum\WCTagger\Admin\TagsAdmin( $repo, $rule_repo );
		$tags_admin->register();

		$order_meta = new ClarityMomentum\WCTagger\Admin\OrderTagsMetaBox();
		$order_meta->register();

		$order_column = new ClarityMomentum\WCTagger\Admin\OrderTagsColumn();
		$order_column->register();

		$customer_profile = new ClarityMomentum\WCTagger\Admin\CustomerTagsProfile();
		$customer_profile->register();

		$customer_column = new ClarityMomentum\WCTagger\Admin\CustomerTagsColumn();
		$customer_column->register();

		$popover = new ClarityMomentum\WCTagger\Admin\TagPopover();
		$popover->register();

		// Grid filters and bulk re-run.
		$tagger       = new ClarityMomentum\WCTagger\RuleEngine\Tagger();
		$grid_filters = new ClarityMomentum\WCTagger\Admin\GridFilters( $tagger );
		$grid_filters->register();

		// Rule engine hooks.

		// New order created at checkout.
		add_action(
			'woocommerce_checkout_order_created',
			function ( \WC_Order $order ) use ( $tagger ): void {
				$tagger->tag_order( $order );
			}
		);

		// Order status changes (e.g. pending to processing after payment confirmation).
		add_action(
			'woocommerce_order_status_changed',
			function ( int $order_id, string $from, string $to ) use ( $tagger ): void { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
				$order = wc_get_order( $order_id );
				if ( $order instanceof \WC_Order ) {
					$tagger->tag_order( $order );
				}
			},
			10,
			3
		);

		// CM Feed Consumer.
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
	}
);

// ── HPOS compatibility declaration ───────────────────────────────────────────

add_action(
	'before_woocommerce_init',
	function (): void {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
				'custom_order_tables',
				__FILE__,
				true
			);
		}
	}
);

// ── Helpers ───────────────────────────────────────────────────────────────────

/**
 * Check whether WooCommerce is active.
 *
 * @return bool
 */
function cm_tagger_wc_active(): bool { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
	return class_exists( 'WooCommerce' );
}
