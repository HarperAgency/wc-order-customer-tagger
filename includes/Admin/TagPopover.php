<?php
/**
 * Inline tag editing popover for the Orders and Customers grid views.
 *
 * Registers AJAX endpoints and enqueues the popover JS/CSS on the relevant
 * admin screens. The JS handles all UI; PHP only persists the changes.
 *
 * @package ClarityMomentum\WCTagger\Admin
 */

declare(strict_types=1);

namespace ClarityMomentum\WCTagger\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Provides AJAX endpoints and assets for the inline tag editing popover.
 */
class TagPopover {

	private const NONCE_ACTION = 'cm_tagger_popover';

	/**
	 * Register AJAX and asset hooks.
	 */
	public function register(): void {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );

		// AJAX — logged-in admin users only (no nopriv).
		add_action( 'wp_ajax_cm_tagger_get_all_tags', array( $this, 'ajax_get_all_tags' ) );
		add_action( 'wp_ajax_cm_tagger_add_tag', array( $this, 'ajax_add_tag' ) );
		add_action( 'wp_ajax_cm_tagger_remove_tag', array( $this, 'ajax_remove_tag' ) );
	}

	/**
	 * Enqueue admin CSS and popover JS on grid screens.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_assets( string $hook ): void {
		$screens = array( 'edit.php', 'woocommerce_page_wc-orders', 'users.php' );
		if ( ! in_array( $hook, $screens, true ) ) {
			return;
		}

		wp_enqueue_style(
			'cm-tagger-admin',
			CM_TAGGER_URL . 'admin/css/tagger-admin.css',
			array(),
			CM_TAGGER_VERSION
		);

		wp_enqueue_script(
			'cm-tag-popover',
			CM_TAGGER_URL . 'admin/js/tag-popover.js',
			array( 'jquery' ),
			CM_TAGGER_VERSION,
			true
		);

		wp_localize_script(
			'cm-tag-popover',
			'cmTaggerPopover',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( self::NONCE_ACTION ),
				'i18n'    => array(
					'applied'    => __( 'Applied', 'cm-order-customer-tagger' ),
					'add'        => __( 'Add tag', 'cm-order-customer-tagger' ),
					'noTags'     => __( 'No tags defined yet.', 'cm-order-customer-tagger' ),
					'allApplied' => __( 'All tags applied.', 'cm-order-customer-tagger' ),
					'saving'     => __( 'Saving…', 'cm-order-customer-tagger' ),
					'error'      => __( 'Error saving tag. Please try again.', 'cm-order-customer-tagger' ),
				),
			)
		);
	}

	// ── AJAX: get all defined tags ─────────────────────────────────────────────

	/**
	 * Return all defined tags as JSON for the popover.
	 */
	public function ajax_get_all_tags(): void {
		$this->verify_request();

		global $wpdb;
		$tags_table = $wpdb->prefix . 'cm_tagger_tags';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$tags = $wpdb->get_results(
			$wpdb->prepare( 'SELECT id, name, color, image_url FROM %i ORDER BY name ASC', $tags_table ),
			ARRAY_A
		);
		$tags = $tags ? $tags : array();

		wp_send_json_success( array( 'tags' => $tags ) );
	}

	// ── AJAX: add a tag to an order or customer ────────────────────────────────

	/**
	 * Add a tag assignment via AJAX.
	 */
	public function ajax_add_tag(): void {
		$this->verify_request();

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified in verify_request() above
		$type = sanitize_key( wp_unslash( $_POST['type'] ?? '' ) );
		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
		$object_id = (int) ( $_POST['object_id'] ?? 0 );
		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
		$tag_id = (int) ( $_POST['tag_id'] ?? 0 );

		if ( ! in_array( $type, array( 'order', 'customer' ), true ) || ! $object_id || ! $tag_id ) {
			wp_send_json_error( array( 'message' => 'Invalid parameters.' ) );
		}

		global $wpdb;
		$table = 'order' === $type
			? $wpdb->prefix . 'cm_tagger_order_tags'
			: $wpdb->prefix . 'cm_tagger_customer_tags';

		$col = 'order' === $type ? 'order_id' : 'customer_id';

		// INSERT IGNORE prevents duplicate-key errors.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				"INSERT IGNORE INTO %i ({$col}, tag_id) VALUES (%d, %d)", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $col is hardcoded string, not user input
				$table,
				$object_id,
				$tag_id
			)
		);

		wp_send_json_success(
			array(
				'tags' => $this->get_applied_tags( $type, $object_id ),
			)
		);
	}

	// ── AJAX: remove a tag from an order or customer ───────────────────────────

	/**
	 * Remove a tag assignment via AJAX.
	 */
	public function ajax_remove_tag(): void {
		$this->verify_request();

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified in verify_request() above
		$type = sanitize_key( wp_unslash( $_POST['type'] ?? '' ) );
		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
		$object_id = (int) ( $_POST['object_id'] ?? 0 );
		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
		$tag_id = (int) ( $_POST['tag_id'] ?? 0 );

		if ( ! in_array( $type, array( 'order', 'customer' ), true ) || ! $object_id || ! $tag_id ) {
			wp_send_json_error( array( 'message' => 'Invalid parameters.' ) );
		}

		global $wpdb;
		$table = 'order' === $type
			? $wpdb->prefix . 'cm_tagger_order_tags'
			: $wpdb->prefix . 'cm_tagger_customer_tags';

		$col = 'order' === $type ? 'order_id' : 'customer_id';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->delete(
			$table,
			array(
				$col     => $object_id,
				'tag_id' => $tag_id,
			)
		);

		wp_send_json_success(
			array(
				'tags' => $this->get_applied_tags( $type, $object_id ),
			)
		);
	}

	// ── Helpers ────────────────────────────────────────────────────────────────

	/**
	 * Verify the AJAX nonce and check user capability.
	 */
	private function verify_request(): void {
		check_ajax_referer( self::NONCE_ACTION, 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) { // phpcs:ignore WordPress.WP.Capabilities.Unknown
			wp_send_json_error( array( 'message' => 'Insufficient permissions.' ), 403 );
		}
	}

	/**
	 * Fetch the currently applied tags for an order or customer.
	 *
	 * @param string $type      'order' or 'customer'.
	 * @param int    $object_id Order or customer ID.
	 * @return array<int, array<string, mixed>>
	 */
	private function get_applied_tags( string $type, int $object_id ): array {
		global $wpdb;

		$join_table = 'order' === $type
			? $wpdb->prefix . 'cm_tagger_order_tags'
			: $wpdb->prefix . 'cm_tagger_customer_tags';

		$join_col   = 'order' === $type ? 'order_id' : 'customer_id';
		$tags_table = $wpdb->prefix . 'cm_tagger_tags';

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		// $join_col is a hardcoded column name string ('order_id' or 'customer_id'), not user input.
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT t.id, t.name, t.color, t.image_url
                   FROM %i jt
                   JOIN %i t ON t.id = jt.tag_id
                  WHERE jt.{$join_col} = %d
                  ORDER BY t.name ASC",
				$join_table,
				$tags_table,
				$object_id
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return $results ? $results : array();
	}

	// ── BC shims ──────────────────────────────────────────────────────────────

	/**
	 * BC shim for test reflection compatibility — routes to get_applied_tags().
	 *
	 * @param string $type      'order' or 'customer'.
	 * @param int    $object_id Order or customer ID.
	 * @return array<int, array<string, mixed>>
	 * @deprecated Use get_applied_tags() instead.
	 */
	protected function getAppliedTags( string $type, int $object_id ): array { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
		return $this->get_applied_tags( $type, $object_id );
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

	/**
	 * BC shim for the AJAX get all tags callback.
	 *
	 * @deprecated Use ajax_get_all_tags() instead.
	 */
	public function ajaxGetAllTags(): void { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
		$this->ajax_get_all_tags();
	}

	/**
	 * BC shim for the AJAX add tag callback.
	 *
	 * @deprecated Use ajax_add_tag() instead.
	 */
	public function ajaxAddTag(): void { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
		$this->ajax_add_tag();
	}

	/**
	 * BC shim for the AJAX remove tag callback.
	 *
	 * @deprecated Use ajax_remove_tag() instead.
	 */
	public function ajaxRemoveTag(): void { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
		$this->ajax_remove_tag();
	}
}
