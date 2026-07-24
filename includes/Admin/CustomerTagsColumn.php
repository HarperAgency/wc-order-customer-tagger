<?php
/**
 * Adds a "Tags" column to the WP Users list (WooCommerce customers).
 *
 * @package ClarityMomentum\WCTagger\Admin
 */

declare(strict_types=1);

namespace ClarityMomentum\WCTagger\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Injects a Tags column into the WP users list view.
 */
class CustomerTagsColumn {

	/**
	 * Register column and asset hooks.
	 */
	public function register(): void {
		add_filter( 'manage_users_columns', array( $this, 'add_column' ) );
		add_filter( 'manage_users_custom_column', array( $this, 'render_column' ), 10, 3 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Insert the Tags column into the users list.
	 *
	 * @param array $columns Existing columns array.
	 * @return array
	 */
	public function add_column( array $columns ): array {
		$columns['cm_customer_tags'] = __( 'Tags', 'cm-order-customer-tagger' );
		return $columns;
	}

	/**
	 * Render the Tags column cell for a given user.
	 *
	 * @param string $output  Default column output (empty string for custom columns).
	 * @param string $column  Column key.
	 * @param int    $user_id WP user ID.
	 * @return string
	 */
	public function render_column( string $output, string $column, int $user_id ): string {
		if ( 'cm_customer_tags' !== $column ) {
			return $output;
		}

		$tags = $this->get_customer_tags( $user_id );

		$html = sprintf(
			'<div class="cm-tagger-cell" data-type="customer" data-id="%d" title="%s">',
			$user_id,
			esc_attr__( 'Click to edit tags', 'cm-order-customer-tagger' )
		);

		if ( empty( $tags ) ) {
			$html .= '<span class="cm-tagger-no-tags">—</span>';
		} else {
			$html .= '<div class="cm-tagger-column-tags">';
			foreach ( $tags as $tag ) {
				if ( ! empty( $tag['image_url'] ) ) {
					$html .= sprintf(
						'<img src="%s" alt="%s" title="%s" class="cm-tagger-icon-preview">',
						esc_url( $tag['image_url'] ),
						esc_attr( $tag['name'] ),
						esc_attr( $tag['name'] )
					);
				} else {
					$html .= sprintf(
						'<span class="cm-tagger-badge" style="background:%s" title="%s">%s</span>',
						esc_attr( $tag['color'] ),
						esc_attr( $tag['name'] ),
						esc_html( $tag['name'] )
					);
				}
			}
			$html .= '</div>';
		}

		$html .= '</div>';
		return $html;
	}

	/**
	 * Enqueue admin CSS on the users list screen.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_assets( string $hook ): void {
		if ( 'users.php' !== $hook ) {
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
	 * Fetch applied customer tags for a WP user.
	 *
	 * @param int $customer_id WP user ID.
	 * @return array<int, array<string, mixed>>
	 */
	private function get_customer_tags( int $customer_id ): array {
		global $wpdb;
		$ct_table   = $wpdb->prefix . 'cm_tagger_customer_tags';
		$tags_table = $wpdb->prefix . 'cm_tagger_tags';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$results = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT t.id, t.name, t.color, t.image_url
                   FROM %i ct
                   JOIN %i t ON t.id = ct.tag_id
                  WHERE ct.customer_id = %d
                  ORDER BY t.name ASC',
				$ct_table,
				$tags_table,
				$customer_id
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
	 * BC shim for the render_column filter callback.
	 *
	 * @param string $output  Default column output.
	 * @param string $column  Column key.
	 * @param int    $user_id WP user ID.
	 * @return string
	 * @deprecated Use render_column() instead.
	 */
	public function renderColumn( string $output, string $column, int $user_id ): string { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
		return $this->render_column( $output, $column, $user_id );
	}

	/**
	 * BC shim for the enqueue_assets action callback.
	 *
	 * @param string $hook Current admin page hook.
	 * @deprecated Use enqueue_assets() instead.
	 */
	public function enqueueAssets( string $hook ): void { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
		$this->enqueue_assets( $hook );
	}
}
