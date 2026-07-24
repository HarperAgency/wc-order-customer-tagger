<?php
/**
 * Customer profile section: show applied customer tags on the WP user edit screen.
 *
 * @package ClarityMomentum\WCTagger\Admin
 */

declare(strict_types=1);

namespace ClarityMomentum\WCTagger\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders a customer tags section on the WP user profile/edit page.
 */
class CustomerTagsProfile {

	/**
	 * Register profile section and asset hooks.
	 */
	public function register(): void {
		add_action( 'show_user_profile', array( $this, 'render_section' ) );
		add_action( 'edit_user_profile', array( $this, 'render_section' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Enqueue admin CSS on profile screens.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_assets( string $hook ): void {
		if ( ! in_array( $hook, array( 'profile.php', 'user-edit.php' ), true ) ) {
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
	 * Render the tag section on the user profile/edit page.
	 *
	 * @param \WP_User $user The user whose profile is being displayed.
	 */
	public function render_section( \WP_User $user ): void {
		$customer_id = $user->ID;
		$tags        = $customer_id > 0 ? $this->get_customer_tags( $customer_id ) : array();

		require CM_TAGGER_DIR . 'admin/views/customer-tags-profile.php';
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
		$rows = $wpdb->get_results(
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

		return $rows ? $rows : array();
	}

	// ── BC shims ──────────────────────────────────────────────────────────────

	/**
	 * BC shim for the render_section action callback.
	 *
	 * @param \WP_User $user The user whose profile is being displayed.
	 * @deprecated Use render_section() instead.
	 */
	public function renderSection( \WP_User $user ): void { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
		$this->render_section( $user );
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
