<?php
/**
 * Admin page: WooCommerce — Order & Customer Tags.
 * Handles list, add, edit, and delete actions for tags and rules.
 *
 * @package ClarityMomentum\WCTagger\Admin
 */

declare(strict_types=1);

namespace ClarityMomentum\WCTagger\Admin;

use ClarityMomentum\WCTagger\Db\TagRepository;
use ClarityMomentum\WCTagger\Db\RuleRepository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers and manages the WooCommerce Order & Customer Tags admin pages.
 */
class TagsAdmin {

	/**
	 * Tag repository instance.
	 *
	 * @var TagRepository
	 */
	private TagRepository $repo;

	/**
	 * Rule repository instance.
	 *
	 * @var RuleRepository
	 */
	private RuleRepository $rule_repo;

	/**
	 * Inject dependencies.
	 *
	 * @param TagRepository  $repo      Tag repository.
	 * @param RuleRepository $rule_repo Rule repository.
	 */
	public function __construct( TagRepository $repo, RuleRepository $rule_repo ) {
		$this->repo      = $repo;
		$this->rule_repo = $rule_repo;
	}

	/**
	 * Register admin menu and asset hooks.
	 */
	public function register(): void {
		add_action( 'admin_menu', array( $this, 'add_menu_page' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Add the plugin's submenu page under WooCommerce.
	 */
	public function add_menu_page(): void {
		add_submenu_page(
			'woocommerce',
			__( 'Order & Customer Tags', 'cm-order-customer-tagger' ),
			__( 'Order & Cust. Tags', 'cm-order-customer-tagger' ),
			'manage_woocommerce', // phpcs:ignore WordPress.WP.Capabilities.Unknown
			'cm-tagger-tags',
			array( $this, 'dispatch' )
		);
	}

	/**
	 * Enqueue admin styles and scripts on the plugin page.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_assets( string $hook ): void {
		if ( 'woocommerce_page_cm-tagger-tags' !== $hook ) {
			return;
		}
		wp_enqueue_style(
			'cm-tagger-admin',
			CM_TAGGER_URL . 'admin/css/tagger-admin.css',
			array(),
			CM_TAGGER_VERSION
		);
		// WP colour picker.
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );
		wp_enqueue_script(
			'cm-tagger-admin',
			CM_TAGGER_URL . 'admin/js/tagger-admin.js',
			array( 'wp-color-picker' ),
			CM_TAGGER_VERSION,
			true
		);
	}

	// ── Dispatcher ────────────────────────────────────────────────────────────

	/**
	 * Route the request to the appropriate page or handler.
	 */
	public function dispatch(): void {
		$action = sanitize_key( wp_unslash( $_REQUEST['action'] ?? 'list' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$id     = isset( $_GET['id'] ) ? absint( wp_unslash( $_GET['id'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$tag_id = isset( $_GET['tag_id'] ) ? absint( wp_unslash( $_GET['tag_id'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		match ( $action ) {
			'add'         => $this->page_edit( null ),
			'edit'        => $this->page_edit( $id ),
			'save'        => $this->handle_save(),
			'delete'      => $this->handle_delete( $id ),
			'rules'       => $this->page_rules_list( $tag_id ),
			'rule-add'    => $this->page_rule_edit( $tag_id, null ),
			'rule-edit'   => $this->page_rule_edit( $tag_id, $id ),
			'rule-save'   => $this->handle_rule_save(),
			'rule-delete' => $this->handle_rule_delete( $id ),
			default       => $this->page_list(),
		};
	}

	// ── Tag list page ─────────────────────────────────────────────────────────

	/**
	 * Render the tags list page.
	 */
	private function page_list(): void {
		$tags = $this->repo->find_all();

		// Append rule counts.
		foreach ( $tags as &$tag ) {
			$tag['rule_count'] = $this->repo->count_rules_for_tag( (int) $tag['id'] );
		}
		unset( $tag );

		$notice = $this->pop_notice();
		require CM_TAGGER_DIR . 'admin/views/tags-list.php';
	}

	// ── Tag edit / add page ───────────────────────────────────────────────────

	/**
	 * Render the tag add/edit form.
	 *
	 * @param int|null $id Tag ID when editing, null when adding.
	 */
	private function page_edit( ?int $id ): void {
		$tag = $id ? $this->repo->find_by_id( $id ) : null;

		if ( $id && ! $tag ) {
			wp_die( esc_html__( 'Tag not found.', 'cm-order-customer-tagger' ) );
		}

		$rules  = $id ? $this->rule_repo->find_by_tag_id( $id ) : array();
		$errors = $this->get_errors();
		$posted = $this->get_posted(); // Repopulate form after validation failure.
		require CM_TAGGER_DIR . 'admin/views/tags-edit.php';
	}

	// ── Tag save handler ──────────────────────────────────────────────────────

	/**
	 * Process the tag save form submission.
	 */
	private function handle_save(): void {
		check_admin_referer( 'cm_tagger_save_tag' );

		$id    = isset( $_POST['tag_id'] ) ? absint( wp_unslash( $_POST['tag_id'] ) ) : 0;
		$name  = trim( sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) ) );
		$color = sanitize_hex_color( wp_unslash( $_POST['color'] ?? '' ) );
		$color = $color ? $color : '#3788d8';
		$type  = sanitize_key( wp_unslash( $_POST['type'] ?? 'order' ) );
		$image = isset( $_POST['image_url'] ) ? esc_url_raw( trim( sanitize_text_field( wp_unslash( $_POST['image_url'] ) ) ) ) : null;
		$image = $image ? $image : null;

		// ── Validate ──────────────────────────────────────────────────────────
		$errors = array();

		if ( '' === $name ) {
			$errors[] = __( 'Tag name is required.', 'cm-order-customer-tagger' );
		} elseif ( strlen( $name ) > 100 ) {
			$errors[] = __( 'Tag name must be 100 characters or fewer.', 'cm-order-customer-tagger' );
		}

		if ( ! in_array( $type, TagRepository::VALID_TYPES, true ) ) {
			$errors[] = __( 'Invalid tag type.', 'cm-order-customer-tagger' );
		}

		if ( $errors ) {
			$this->stash_errors( $errors );
			$this->stash_posted( compact( 'name', 'color', 'type', 'image' ) );
			$redirect = $id
				? add_query_arg(
					array(
						'action' => 'edit',
						'id'     => $id,
					)
				)
				: add_query_arg( array( 'action' => 'add' ) );
			wp_safe_redirect( $redirect );
			exit;
		}

		// ── Persist ───────────────────────────────────────────────────────────
		$slug = $this->repo->unique_slug( $name, $id );
		$data = compact( 'name', 'slug', 'color', 'type' ) + array( 'image_url' => $image );

		if ( $id > 0 ) {
			$this->repo->update( $id, $data );
			$this->stash_notice( __( 'Tag updated.', 'cm-order-customer-tagger' ), 'updated' );
		} else {
			$id = $this->repo->insert( $data );
			$this->stash_notice( __( 'Tag created.', 'cm-order-customer-tagger' ), 'updated' );
		}

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'   => 'cm-tagger-tags',
					'action' => 'edit',
					'id'     => $id,
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	// ── Tag delete handler ────────────────────────────────────────────────────

	/**
	 * Process the tag delete action.
	 *
	 * @param int $id Tag ID to delete.
	 */
	private function handle_delete( int $id ): void {
		check_admin_referer( 'cm_tagger_delete_tag_' . $id );

		if ( $id > 0 ) {
			$this->repo->delete( $id );
		}

		$this->stash_notice( __( 'Tag deleted.', 'cm-order-customer-tagger' ), 'updated' );
		wp_safe_redirect( add_query_arg( array( 'page' => 'cm-tagger-tags' ), admin_url( 'admin.php' ) ) );
		exit;
	}

	// ── Rules list page ───────────────────────────────────────────────────────

	/**
	 * Render the rules list for a given tag.
	 *
	 * @param int $tag_id Parent tag ID.
	 */
	private function page_rules_list( int $tag_id ): void {
		$tag = $tag_id ? $this->repo->find_by_id( $tag_id ) : null;

		if ( ! $tag ) {
			wp_die( esc_html__( 'Tag not found.', 'cm-order-customer-tagger' ) );
		}

		$rules  = $this->rule_repo->find_by_tag_id( $tag_id );
		$notice = $this->pop_notice();
		require CM_TAGGER_DIR . 'admin/views/rules-list.php';
	}

	// ── Rule edit / add page ──────────────────────────────────────────────────

	/**
	 * Render the rule add/edit form.
	 *
	 * @param int      $tag_id  Parent tag ID.
	 * @param int|null $rule_id Rule ID when editing, null when adding.
	 */
	private function page_rule_edit( int $tag_id, ?int $rule_id ): void {
		$tag = $tag_id ? $this->repo->find_by_id( $tag_id ) : null;

		if ( ! $tag ) {
			wp_die( esc_html__( 'Tag not found.', 'cm-order-customer-tagger' ) );
		}

		$rule   = $rule_id ? $this->rule_repo->find_by_id( $rule_id ) : null;
		$errors = $this->get_errors();
		$posted = $this->get_posted();
		require CM_TAGGER_DIR . 'admin/views/rules-edit.php';
	}

	// ── Rule save handler ─────────────────────────────────────────────────────

	/**
	 * Process the rule save form submission.
	 */
	private function handle_rule_save(): void {
		check_admin_referer( 'cm_tagger_save_rule' );

		$rule_id   = isset( $_POST['rule_id'] ) ? absint( wp_unslash( $_POST['rule_id'] ) ) : 0;
		$tag_id    = isset( $_POST['tag_id'] ) ? absint( wp_unslash( $_POST['tag_id'] ) ) : 0;
		$label     = trim( sanitize_text_field( wp_unslash( $_POST['label'] ?? '' ) ) );
		$trigger   = sanitize_key( wp_unslash( $_POST['rule_trigger'] ?? 'order_placed' ) );
		$op        = strtoupper( sanitize_key( wp_unslash( $_POST['operator'] ?? 'and' ) ) );
		$priority  = isset( $_POST['priority'] ) ? absint( wp_unslash( $_POST['priority'] ) ) : 10;
		$is_active = isset( $_POST['is_active'] ) ? 1 : 0;

		// Decode conditions from hidden JSON field.
		$conditions_raw = sanitize_textarea_field( wp_unslash( $_POST['conditions_json'] ?? '[]' ) );
		$conditions_raw = stripslashes( $conditions_raw );
		$conditions     = json_decode( $conditions_raw, true );
		if ( ! is_array( $conditions ) ) {
			$conditions = array();
		}

		// ── Validate ──────────────────────────────────────────────────────────
		$errors = array();

		if ( ! $this->repo->find_by_id( $tag_id ) ) {
			$errors[] = __( 'Invalid tag.', 'cm-order-customer-tagger' );
		}

		if ( '' === $label ) {
			$errors[] = __( 'Rule label is required.', 'cm-order-customer-tagger' );
		} elseif ( strlen( $label ) > 100 ) {
			$errors[] = __( 'Rule label must be 100 characters or fewer.', 'cm-order-customer-tagger' );
		}

		if ( ! in_array( $op, RuleRepository::VALID_OPERATORS, true ) ) {
			$errors[] = __( 'Invalid operator.', 'cm-order-customer-tagger' );
		}

		if ( $errors ) {
			$this->stash_errors( $errors );
			$this->stash_posted( compact( 'label', 'trigger', 'op', 'priority', 'is_active', 'conditions_raw' ) );
			$redirect = $rule_id
				? add_query_arg(
					array(
						'page'   => 'cm-tagger-tags',
						'action' => 'rule-edit',
						'tag_id' => $tag_id,
						'id'     => $rule_id,
					),
					admin_url( 'admin.php' )
				)
				: add_query_arg(
					array(
						'page'   => 'cm-tagger-tags',
						'action' => 'rule-add',
						'tag_id' => $tag_id,
					),
					admin_url( 'admin.php' )
				);
			wp_safe_redirect( $redirect );
			exit;
		}

		// Sanitize each condition.
		$conditions = $this->sanitize_conditions( $conditions );

		$data = array(
			'label'        => $label,
			'rule_trigger' => $trigger,
			'operator'     => $op,
			'conditions'   => $conditions,
			'priority'     => $priority,
			'is_active'    => $is_active,
		);

		if ( $rule_id > 0 ) {
			$this->rule_repo->update( $rule_id, $data );
			$this->stash_notice( __( 'Rule updated.', 'cm-order-customer-tagger' ), 'updated' );
		} else {
			$this->rule_repo->insert( $tag_id, $data );
			$this->stash_notice( __( 'Rule created.', 'cm-order-customer-tagger' ), 'updated' );
		}

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'   => 'cm-tagger-tags',
					'action' => 'rules',
					'tag_id' => $tag_id,
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	// ── Rule delete handler ───────────────────────────────────────────────────

	/**
	 * Process the rule delete action.
	 *
	 * @param int $id Rule ID to delete.
	 */
	private function handle_rule_delete( int $id ): void {
		$tag_id = isset( $_GET['tag_id'] ) ? absint( wp_unslash( $_GET['tag_id'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		check_admin_referer( 'cm_tagger_delete_rule_' . $id );

		if ( $id > 0 ) {
			$this->rule_repo->delete( $id );
		}

		$this->stash_notice( __( 'Rule deleted.', 'cm-order-customer-tagger' ), 'updated' );
		wp_safe_redirect(
			add_query_arg(
				array(
					'page'   => 'cm-tagger-tags',
					'action' => 'rules',
					'tag_id' => $tag_id,
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	// ── Transient helpers ─────────────────────────────────────────────────────

	/**
	 * Store a notice in a short-lived transient for display after redirect.
	 *
	 * @param string $message Notice text.
	 * @param string $type    Notice class (e.g. 'updated', 'error').
	 */
	private function stash_notice( string $message, string $type ): void { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- params used via compact()
		set_transient( 'cm_tagger_notice_' . get_current_user_id(), compact( 'message', 'type' ), 60 );
	}

	/**
	 * Retrieve and delete the stored notice transient.
	 *
	 * @return array{message: string, type: string}|null
	 */
	private function pop_notice(): ?array {
		$key    = 'cm_tagger_notice_' . get_current_user_id();
		$notice = get_transient( $key );
		delete_transient( $key );
		return $notice ? $notice : null;
	}

	/**
	 * Store validation errors in a short-lived transient.
	 *
	 * @param array $errors Array of error messages.
	 */
	private function stash_errors( array $errors ): void {
		set_transient( 'cm_tagger_errors_' . get_current_user_id(), $errors, 60 );
	}

	/**
	 * Retrieve and delete the stored validation errors transient.
	 *
	 * @return array
	 */
	private function get_errors(): array {
		$key    = 'cm_tagger_errors_' . get_current_user_id();
		$errors = get_transient( $key );
		delete_transient( $key );
		return $errors ? $errors : array();
	}

	/**
	 * Store posted form data in a short-lived transient for repopulation.
	 *
	 * @param array $data Posted form data.
	 */
	private function stash_posted( array $data ): void {
		set_transient( 'cm_tagger_posted_' . get_current_user_id(), $data, 60 );
	}

	/**
	 * Retrieve and delete the stored posted form data transient.
	 *
	 * @return array
	 */
	private function get_posted(): array {
		$key    = 'cm_tagger_posted_' . get_current_user_id();
		$posted = get_transient( $key );
		delete_transient( $key );
		return $posted ? $posted : array();
	}

	// ── Condition sanitiser ───────────────────────────────────────────────────

	/**
	 * Sanitise raw decoded conditions array from JSON POST data.
	 *
	 * @param array<mixed> $conditions Raw decoded conditions.
	 * @return array<int, array{field: string, operator: string, value: mixed}>
	 */
	private function sanitize_conditions( array $conditions ): array {
		$valid = array();
		foreach ( $conditions as $c ) {
			if ( ! is_array( $c ) ) {
				continue;
			}
			$field    = sanitize_key( (string) ( $c['field'] ?? '' ) );
			$operator = sanitize_key( (string) ( $c['operator'] ?? '' ) );
			$value    = $c['value'] ?? '';

			if ( '' === $field || '' === $operator ) {
				continue;
			}

			// For in/not_in, value may be a comma-separated string — convert to array.
			if ( in_array( $operator, array( 'in', 'not_in' ), true ) && is_string( $value ) ) {
				$value = array_values( array_filter( array_map( 'trim', explode( ',', $value ) ) ) );
			}

			// For boolean operators, value is irrelevant — normalise to empty string.
			if ( in_array( $operator, array( 'is_true', 'is_false' ), true ) ) {
				$value = '';
			}

			$valid[] = array(
				'field'    => $field,
				'operator' => $operator,
				'value'    => is_array( $value )
					? array_map( 'sanitize_text_field', $value )
					: sanitize_text_field( (string) $value ),
			);
		}
		return $valid;
	}

	// ── BC shims ──────────────────────────────────────────────────────────────

	/**
	 * BC shim — the main plugin file calls addMenuPage() as a registered callback.
	 *
	 * @deprecated Use add_menu_page() instead.
	 */
	public function addMenuPage(): void { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
		$this->add_menu_page();
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
}
