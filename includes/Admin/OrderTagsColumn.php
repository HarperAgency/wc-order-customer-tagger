<?php
/**
 * Adds a "Tags" column to the WooCommerce orders list (both HPOS and legacy).
 *
 * @package ClarityMomentum\WCTagger\Admin
 */

declare(strict_types=1);

namespace ClarityMomentum\WCTagger\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Injects a Tags column into the WC orders list view.
 */
class OrderTagsColumn {

	/**
	 * Register column and asset hooks.
	 */
	public function register(): void {
		// HPOS order list.
		add_filter( 'woocommerce_shop_order_list_table_columns', array( $this, 'add_column' ) );
		add_action( 'woocommerce_shop_order_list_table_custom_column', array( $this, 'render_column' ), 10, 2 );

		// Legacy post-based order list.
		add_filter( 'manage_edit-shop_order_columns', array( $this, 'add_column' ) );
		add_action( 'manage_shop_order_posts_custom_column', array( $this, 'render_legacy_column' ), 10, 2 );

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Insert the Tags column into the column list.
	 *
	 * @param array $columns Existing columns array.
	 * @return array
	 */
	public function add_column( array $columns ): array {
		// Insert Tags column after 'order_status'.
		$new = array();
		foreach ( $columns as $key => $label ) {
			$new[ $key ] = $label;
			if ( 'order_status' === $key ) {
				$new['cm_tags'] = __( 'Tags', 'cm-order-customer-tagger' );
			}
		}
		// Fallback: wasn't inserted yet (column order differs).
		if ( ! isset( $new['cm_tags'] ) ) {
			$new['cm_tags'] = __( 'Tags', 'cm-order-customer-tagger' );
		}
		return $new;
	}

	/**
	 * HPOS column render — receives WC_Order directly.
	 *
	 * @param string    $column Column key.
	 * @param \WC_Order $order  WooCommerce order object.
	 */
	public function render_column( string $column, \WC_Order $order ): void {
		if ( 'cm_tags' !== $column ) {
			return;
		}
		$this->output_tags( $this->get_order_tags( $order->get_id() ), $order->get_id() );
	}

	/**
	 * Legacy column render — receives post ID.
	 *
	 * @param string $column  Column key.
	 * @param int    $post_id WP post ID for the order.
	 */
	public function render_legacy_column( string $column, int $post_id ): void {
		if ( 'cm_tags' !== $column ) {
			return;
		}
		$this->output_tags( $this->get_order_tags( $post_id ), $post_id );
	}

	/**
	 * Enqueue admin CSS on order list screens.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_assets( string $hook ): void {
		$screens = array( 'edit.php', 'woocommerce_page_wc-orders' );
		if ( ! in_array( $hook, $screens, true ) ) {
			return;
		}

		wp_enqueue_style(
			'cm-tagger-admin',
			CM_TAGGER_URL . 'admin/css/tagger-admin.css',
			array(),
			CM_TAGGER_VERSION
		);
	}

	/**
	 * Output the tags cell HTML for a given order.
	 *
	 * @param array $tags     Applied tag rows.
	 * @param int   $order_id WC order ID.
	 */
	private function output_tags( array $tags, int $order_id ): void {
		echo '<div class="cm-tagger-cell" data-type="order" data-id="' . absint( $order_id ) . '" title="' . esc_attr__( 'Click to edit tags', 'cm-order-customer-tagger' ) . '">';

		if ( empty( $tags ) ) {
			echo '<span class="cm-tagger-no-tags">—</span>';
		} else {
			echo '<div class="cm-tagger-column-tags">';
			foreach ( $tags as $tag ) {
				if ( ! empty( $tag['image_url'] ) ) {
					printf(
						'<img src="%s" alt="%s" title="%s" class="cm-tagger-icon-preview">',
						esc_url( $tag['image_url'] ),
						esc_attr( $tag['name'] ),
						esc_attr( $tag['name'] )
					);
				} else {
					printf(
						'<span class="cm-tagger-badge" style="background:%s" title="%s">%s</span>',
						esc_attr( $tag['color'] ),
						esc_attr( $tag['name'] ),
						esc_html( $tag['name'] )
					);
				}
			}
			echo '</div>';
		}

		echo '</div>';
	}

	/**
	 * Fetch applied tags for a given order from the DB.
	 *
	 * @param int $order_id WC order ID.
	 * @return array<int, array<string, mixed>>
	 */
	private function get_order_tags( int $order_id ): array {
		global $wpdb;
		$ot_table   = $wpdb->prefix . 'cm_tagger_order_tags';
		$tags_table = $wpdb->prefix . 'cm_tagger_tags';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$results = $wpdb->get_results(
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

		return $results ? $results : array();
	}

	// ── BC shims ──────────────────────────────────────────────────────────────

	/**
	 * BC shim for the add_column filter callback.
	 *
	 * @param array $columns Existing columns array.
	 * @return array
	 * @deprecated Use add_column() instead.
	 */
	public function addColumn( array $columns ): array { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
		return $this->add_column( $columns );
	}

	/**
	 * BC shim for the HPOS render action callback.
	 *
	 * @param string    $column Column key.
	 * @param \WC_Order $order  WooCommerce order object.
	 * @deprecated Use render_column() instead.
	 */
	public function renderColumn( string $column, \WC_Order $order ): void { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
		$this->render_column( $column, $order );
	}

	/**
	 * BC shim for the legacy render action callback.
	 *
	 * @param string $column  Column key.
	 * @param int    $post_id WP post ID for the order.
	 * @deprecated Use render_legacy_column() instead.
	 */
	public function renderLegacyColumn( string $column, int $post_id ): void { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
		$this->render_legacy_column( $column, $post_id );
	}

	/**
	 * BC shim for the enqueue_assets hook callback.
	 *
	 * @param string $hook Current admin page hook.
	 * @deprecated Use enqueue_assets() instead.
	 */
	public function enqueueAssets( string $hook ): void { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
		$this->enqueue_assets( $hook );
	}
}
