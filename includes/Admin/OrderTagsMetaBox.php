<?php
/**
 * Meta box: show applied tags on the WooCommerce order detail screen.
 * Supports both HPOS (woocommerce_page_wc-orders) and legacy (shop_order).
 *
 * @package ClarityMomentum\WCTagger\Admin
 */

declare(strict_types=1);

namespace ClarityMomentum\WCTagger\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers and renders the Order Tags meta box on the WC order edit screen.
 */
class OrderTagsMetaBox {

	/**
	 * Register the meta box and asset hooks.
	 */
	public function register(): void {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Register the meta box on order edit screens.
	 */
	public function add_meta_box(): void {
		$screens = array( 'shop_order', 'woocommerce_page_wc-orders' );

		foreach ( $screens as $screen ) {
			add_meta_box(
				'cm_tagger_order_tags',
				__( 'Order Tags', 'cm-order-customer-tagger' ),
				array( $this, 'render_meta_box' ),
				$screen,
				'side',
				'default'
			);
		}
	}

	/**
	 * Enqueue admin CSS on order edit screens.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_assets( string $hook ): void {
		$order_screens = array( 'post.php', 'post-new.php', 'woocommerce_page_wc-orders' );

		if ( ! in_array( $hook, $order_screens, true ) ) {
			// Also allow when the current screen post type is shop_order.
			$screen = get_current_screen();
			if ( ! $screen || 'shop_order' !== $screen->post_type ) {
				return;
			}
		}

		wp_enqueue_style(
			'cm-tagger-admin',
			CM_TAGGER_URL . 'admin/css/tagger-admin.css',
			array(),
			CM_TAGGER_VERSION
		);
	}

	/**
	 * Render callback — receives a WP_Post or WC_Order depending on HPOS mode.
	 *
	 * @param \WP_Post|\WC_Order $post_or_order Post or order object from WP.
	 */
	public function render_meta_box( $post_or_order ): void {
		// Resolve the order ID from either a WC_Order (HPOS) or a WP_Post (legacy).
		if ( $post_or_order instanceof \WC_Order ) {
			$order_id = $post_or_order->get_id();
		} else {
			// Legacy mode: the callback receives a WP_Post whose ID is the order ID.
			$order_id = $post_or_order->ID;
		}

		$tags = $order_id > 0 ? $this->get_order_tags( $order_id ) : array();

		require CM_TAGGER_DIR . 'admin/views/order-tags-metabox.php';
	}

	/**
	 * Fetch applied tags for an order.
	 *
	 * @param int $order_id WC order ID.
	 * @return array<int, array<string, mixed>>
	 */
	private function get_order_tags( int $order_id ): array {
		global $wpdb;
		$ot_table   = $wpdb->prefix . 'cm_tagger_order_tags';
		$tags_table = $wpdb->prefix . 'cm_tagger_tags';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT t.id, t.name, t.color, t.image_url
                   FROM %i ot
                   JOIN %i t ON t.id = ot.tag_id
                  WHERE ot.order_id = %d
                  ORDER BY t.name ASC',
				$ot_table,
				$tags_table,
				$order_id
			),
			ARRAY_A
		);

		return $rows ? $rows : array();
	}

	// ── BC shims ──────────────────────────────────────────────────────────────

	/**
	 * BC shim — registered as an add_meta_boxes callback.
	 *
	 * @deprecated Use add_meta_box() instead.
	 */
	public function addMetaBox(): void { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
		$this->add_meta_box();
	}

	/**
	 * BC shim — registered as an admin_enqueue_scripts callback.
	 *
	 * @param string $hook Current admin page hook.
	 * @deprecated Use enqueue_assets() instead.
	 */
	public function enqueueAssets( string $hook ): void { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
		$this->enqueue_assets( $hook );
	}

	/**
	 * BC shim — registered as a meta box render callback.
	 *
	 * @param \WP_Post|\WC_Order $post_or_order Post or order object from WP.
	 * @deprecated Use render_meta_box() instead.
	 */
	public function renderMetaBox( $post_or_order ): void { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
		$this->render_meta_box( $post_or_order );
	}
}
