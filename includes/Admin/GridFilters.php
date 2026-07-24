<?php
/**
 * GridFilters — tag filter dropdowns and retroactive rule evaluation bulk action.
 *
 * Wires up:
 *   - "Filter by tag" dropdown on the WC Orders list (HPOS + legacy)
 *   - "Filter by tag" dropdown on the WP Users list (WC customers)
 *   - "Re-run Tagger Rules" bulk action on the Orders list
 *   - Admin notice after bulk re-run showing order count processed
 *
 * @package ClarityMomentum\WCTagger\Admin
 */

declare(strict_types=1);

namespace ClarityMomentum\WCTagger\Admin;

use ClarityMomentum\WCTagger\RuleEngine\Tagger;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Adds tag filter dropdowns and bulk re-run to the WC orders and users grids.
 */
class GridFilters {

	/**
	 * Tagger instance for re-running rules on orders.
	 *
	 * @var Tagger
	 */
	private Tagger $tagger;

	/**
	 * Inject the Tagger dependency.
	 *
	 * @param Tagger $tagger Rule engine tagger.
	 */
	public function __construct( Tagger $tagger ) {
		$this->tagger = $tagger;
	}

	/**
	 * Register all filter and bulk action hooks.
	 */
	public function register(): void {
		// ── Orders: filter dropdown ───────────────────────────────────────────
		add_action( 'woocommerce_order_list_table_restrict_manage_orders', array( $this, 'render_order_filter_hpos' ) );
		add_action( 'restrict_manage_posts', array( $this, 'render_order_filter_legacy' ) );

		// ── Orders: apply filter to query ─────────────────────────────────────
		add_filter( 'woocommerce_orders_table_query_clauses', array( $this, 'apply_order_tag_filter_hpos' ), 10, 3 );
		add_filter( 'posts_join', array( $this, 'legacy_orders_join' ) );
		add_filter( 'posts_where', array( $this, 'legacy_orders_where' ) );

		// ── Customers: filter dropdown ────────────────────────────────────────
		add_action( 'restrict_manage_users', array( $this, 'render_customer_filter' ) );

		// ── Customers: apply filter ───────────────────────────────────────────
		add_filter( 'users_where', array( $this, 'apply_customer_tag_filter_where' ), 10, 2 );

		// ── Orders: bulk action registration (HPOS and legacy).
		add_filter( 'bulk_actions-woocommerce_page_wc-orders', array( $this, 'add_order_bulk_actions' ) );
		add_filter( 'bulk_actions-edit-shop_order', array( $this, 'add_order_bulk_actions' ) );

		// ── Orders: bulk action handler ───────────────────────────────────────
		add_filter( 'handle_bulk_actions-woocommerce_page_wc-orders', array( $this, 'handle_bulk_rerun' ), 10, 3 );
		add_filter( 'handle_bulk_actions-edit-shop_order', array( $this, 'handle_bulk_rerun' ), 10, 3 );

		// ── Admin notice after bulk re-run ────────────────────────────────────
		add_action( 'admin_notices', array( $this, 'show_bulk_rerun_notice' ) );
	}

	// ── Orders filter — HPOS ──────────────────────────────────────────────────

	/**
	 * Render the tag select dropdown for the HPOS orders list.
	 */
	public function render_order_filter_hpos(): void {
		$this->render_tag_select( 'cm_tag_id', 'order' );
	}

	// ── Orders filter — legacy post-based ─────────────────────────────────────

	/**
	 * Render the tag select dropdown for the legacy orders list.
	 */
	public function render_order_filter_legacy(): void {
		global $typenow;
		if ( 'shop_order' !== $typenow ) {
			return;
		}
		$this->render_tag_select( 'cm_tag_id', 'order' );
	}

	// ── Orders query — HPOS ───────────────────────────────────────────────────

	/**
	 * Apply the tag filter to HPOS order list query clauses.
	 *
	 * @param array<string,string> $clauses SQL clause fragments.
	 * @param mixed                $query   Query object (unused).
	 * @param array<string,mixed>  $args    Query args (unused).
	 * @return array<string,string>
	 */
	public function apply_order_tag_filter_hpos( array $clauses, $query, array $args ): array { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- filter GET param, nonce not applicable
		$tag_id = isset( $_GET['cm_tag_id'] ) ? absint( $_GET['cm_tag_id'] ) : 0;
		if ( ! $tag_id ) {
			return $clauses;
		}

		global $wpdb;
		$table = $wpdb->prefix . 'cm_tagger_order_tags';
		// wc_orders is the HPOS orders table; 'id' is its primary key.
		$clauses['where'] .= $wpdb->prepare(
			" AND EXISTS (
                SELECT 1 FROM %i ht
                WHERE ht.order_id = {$wpdb->prefix}wc_orders.id
                  AND ht.tag_id  = %d
             )", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $wpdb->prefix is safe
			$table,
			$tag_id
		);

		return $clauses;
	}

	// ── Orders query — legacy ─────────────────────────────────────────────────

	/**
	 * Add a JOIN for the tag filter on the legacy orders list.
	 *
	 * @param string $join Existing JOIN clause.
	 * @return string
	 */
	public function legacy_orders_join( string $join ): string {
		global $pagenow, $typenow;
		if ( 'edit.php' !== $pagenow || 'shop_order' !== $typenow ) {
			return $join;
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- filter GET param, nonce not applicable
		$tag_id = isset( $_GET['cm_tag_id'] ) ? absint( $_GET['cm_tag_id'] ) : 0;
		if ( ! $tag_id ) {
			return $join;
		}

		global $wpdb;
		$table = $wpdb->prefix . 'cm_tagger_order_tags';
		// $tag_id cast to int; table name is safe prefix plus hardcoded string.
		$join .= $wpdb->prepare(
			" LEFT JOIN %i ht_tag_filter ON ht_tag_filter.order_id = {$wpdb->posts}.ID AND ht_tag_filter.tag_id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $wpdb->posts is safe
			$table,
			$tag_id
		);
		return $join;
	}

	/**
	 * Add a WHERE clause for the tag filter on the legacy orders list.
	 *
	 * @param string $where Existing WHERE clause.
	 * @return string
	 */
	public function legacy_orders_where( string $where ): string {
		global $pagenow, $typenow;
		if ( 'edit.php' !== $pagenow || 'shop_order' !== $typenow ) {
			return $where;
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- filter GET param, nonce not applicable
		$tag_id = isset( $_GET['cm_tag_id'] ) ? absint( $_GET['cm_tag_id'] ) : 0;
		if ( ! $tag_id ) {
			return $where;
		}

		$where .= ' AND ht_tag_filter.order_id IS NOT NULL';
		return $where;
	}

	// ── Customers filter ──────────────────────────────────────────────────────

	/**
	 * Render the tag select dropdown for the users list.
	 *
	 * @param string $which 'top' or 'bottom'. Only render at the top to avoid duplicate IDs.
	 */
	public function render_customer_filter( string $which = 'top' ): void {
		if ( 'top' !== $which ) {
			return;
		}
		// users.php is not restricted to a post type — render unconditionally.
		// Non-customer users simply won't appear in the tag table.
		$this->render_tag_select( 'cm_customer_tag_id', 'customer' );
	}

	/**
	 * Apply the customer tag filter to the users WHERE clause.
	 *
	 * @param string         $where Existing WHERE clause.
	 * @param \WP_User_Query $query User query object (unused).
	 * @return string
	 */
	public function apply_customer_tag_filter_where( string $where, \WP_User_Query $query ): string { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		if ( ! is_admin() ) {
			return $where;
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- filter GET param, nonce not applicable
		$tag_id = isset( $_GET['cm_customer_tag_id'] ) ? absint( $_GET['cm_customer_tag_id'] ) : 0;
		if ( ! $tag_id ) {
			return $where;
		}

		global $wpdb;
		$table  = $wpdb->prefix . 'cm_tagger_customer_tags';
		$where .= $wpdb->prepare(
			" AND {$wpdb->users}.ID IN (
                SELECT customer_id FROM %i WHERE tag_id = %d
             )", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $wpdb->users is safe
			$table,
			$tag_id
		);

		return $where;
	}

	// ── Bulk action: register ─────────────────────────────────────────────────

	/**
	 * Register the "Re-run Tagger Rules" bulk action on the orders list.
	 *
	 * @param array<string,string> $actions Existing bulk actions.
	 * @return array<string,string>
	 */
	public function add_order_bulk_actions( array $actions ): array {
		$actions['cm_rerun_rules'] = __( 'Re-run Tagger Rules', 'cm-order-customer-tagger' );
		return $actions;
	}

	// ── Bulk action: handle ───────────────────────────────────────────────────

	/**
	 * Process the bulk re-run action.
	 *
	 * @param string $redirect_to URL to redirect to after processing.
	 * @param string $action      Bulk action name.
	 * @param int[]  $order_ids   Selected order IDs.
	 * @return string
	 */
	public function handle_bulk_rerun( string $redirect_to, string $action, array $order_ids ): string {
		if ( 'cm_rerun_rules' !== $action ) {
			return $redirect_to;
		}

		$count = 0;
		foreach ( $order_ids as $order_id ) {
			$order = wc_get_order( (int) $order_id );
			if ( $order instanceof \WC_Order ) {
				$this->tagger->tag_order( $order );
				++$count;
			}
		}

		return add_query_arg( 'cm_rerun_count', $count, $redirect_to );
	}

	// ── Admin notice ──────────────────────────────────────────────────────────

	/**
	 * Display the bulk re-run result notice.
	 */
	public function show_bulk_rerun_notice(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- display-only GET param
		$count = isset( $_GET['cm_rerun_count'] ) ? absint( $_GET['cm_rerun_count'] ) : 0;
		if ( $count <= 0 ) {
			return;
		}

		printf(
			'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
			esc_html(
				sprintf(
					/* translators: %d: number of orders processed */
					_n(
						'Tagger rules re-run on %d order.',
						'Tagger rules re-run on %d orders.',
						$count,
						'cm-order-customer-tagger'
					),
					$count
				)
			)
		);
	}

	// ── Shared helper ─────────────────────────────────────────────────────────

	/**
	 * Render a select dropdown of tags filtered by type.
	 * Preserves the current selection on page reload.
	 *
	 * @param string $param_name URL parameter name for the selected tag.
	 * @param string $tag_type   Tag type to filter by ('order' or 'customer').
	 */
	private function render_tag_select( string $param_name, string $tag_type ): void {
		global $wpdb;
		$tags_table = $wpdb->prefix . 'cm_tagger_tags';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT id, name FROM %i WHERE type = %s ORDER BY name ASC',
				$tags_table,
				$tag_type
			),
			ARRAY_A
		);

		if ( ! $rows ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- filter GET param
		$selected = isset( $_GET[ $param_name ] ) ? absint( $_GET[ $param_name ] ) : 0;
		echo '<select name="' . esc_attr( $param_name ) . '" id="' . esc_attr( $param_name ) . '" style="float:none;margin-left:6px;">';
		echo '<option value="">' . esc_html__( 'Filter by tag&hellip;', 'cm-order-customer-tagger' ) . '</option>';
		foreach ( $rows as $row ) {
			$id   = (int) $row['id'];
			$name = (string) $row['name'];
			echo '<option value="' . absint( $id ) . '"'
				. selected( $id, $selected, false )
				. '>' . esc_html( $name ) . '</option>';
		}
		echo '</select>';
	}

	// ── BC shims ──────────────────────────────────────────────────────────────

	/**
	 * BC shim for the HPOS order filter action callback.
	 *
	 * @deprecated Use render_order_filter_hpos() instead.
	 */
	public function renderOrderFilterHpos(): void { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
		$this->render_order_filter_hpos();
	}

	/**
	 * BC shim for the legacy order filter action callback.
	 *
	 * @deprecated Use render_order_filter_legacy() instead.
	 */
	public function renderOrderFilterLegacy(): void { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
		$this->render_order_filter_legacy();
	}

	/**
	 * BC shim for the HPOS query clauses filter callback.
	 *
	 * @param array<string,string> $clauses SQL clause fragments.
	 * @param mixed                $query   Query object.
	 * @param array<string,mixed>  $args    Query args.
	 * @return array<string,string>
	 * @deprecated Use apply_order_tag_filter_hpos() instead.
	 */
	public function applyOrderTagFilterHpos( array $clauses, $query, array $args ): array { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
		return $this->apply_order_tag_filter_hpos( $clauses, $query, $args );
	}

	/**
	 * BC shim for the legacy orders JOIN filter callback.
	 *
	 * @param string $join Existing JOIN clause.
	 * @return string
	 * @deprecated Use legacy_orders_join() instead.
	 */
	public function legacyOrdersJoin( string $join ): string { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
		return $this->legacy_orders_join( $join );
	}

	/**
	 * BC shim for the legacy orders WHERE filter callback.
	 *
	 * @param string $where Existing WHERE clause.
	 * @return string
	 * @deprecated Use legacy_orders_where() instead.
	 */
	public function legacyOrdersWhere( string $where ): string { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
		return $this->legacy_orders_where( $where );
	}

	/**
	 * BC shim for the customer filter action callback.
	 *
	 * @param string $which 'top' or 'bottom'.
	 * @deprecated Use render_customer_filter() instead.
	 */
	public function renderCustomerFilter( string $which = 'top' ): void { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
		$this->render_customer_filter( $which );
	}

	/**
	 * BC shim for the customer tag WHERE filter callback.
	 *
	 * @param string         $where Existing WHERE clause.
	 * @param \WP_User_Query $query User query object.
	 * @return string
	 * @deprecated Use apply_customer_tag_filter_where() instead.
	 */
	public function applyCustomerTagFilterWhere( string $where, \WP_User_Query $query ): string { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
		return $this->apply_customer_tag_filter_where( $where, $query );
	}

	/**
	 * BC shim for the bulk actions registration filter callback.
	 *
	 * @param array<string,string> $actions Existing bulk actions.
	 * @return array<string,string>
	 * @deprecated Use add_order_bulk_actions() instead.
	 */
	public function addOrderBulkActions( array $actions ): array { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
		return $this->add_order_bulk_actions( $actions );
	}

	/**
	 * BC shim for the bulk rerun handler filter callback.
	 *
	 * @param string $redirect_to URL to redirect to.
	 * @param string $action      Bulk action name.
	 * @param int[]  $order_ids   Selected order IDs.
	 * @return string
	 * @deprecated Use handle_bulk_rerun() instead.
	 */
	public function handleBulkRerun( string $redirect_to, string $action, array $order_ids ): string { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
		return $this->handle_bulk_rerun( $redirect_to, $action, $order_ids );
	}

	/**
	 * BC shim for the admin notices action callback.
	 *
	 * @deprecated Use show_bulk_rerun_notice() instead.
	 */
	public function showBulkRerunNotice(): void { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
		$this->show_bulk_rerun_notice();
	}
}
