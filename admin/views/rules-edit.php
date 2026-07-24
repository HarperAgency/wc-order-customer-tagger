<?php
/**
 * Admin view: add / edit rule form.
 *
 * @package ClarityMomentum\WCTagger
 *
 * @var array<string, mixed>             $tag    Parent tag row.
 * @var array<string, mixed>|null        $rule   Existing rule row, or null for new.
 * @var array<string, string>            $errors Validation errors.
 * @var array<string, mixed>             $posted Re-populated POST data after error.
 */

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- view file included inside a function, variables are not global scope
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$cm_tag_id   = (int) $tag['id'];
$cm_is_new   = ( null === $rule );
$cm_title    = $cm_is_new ? __( 'Add Rule', 'cm-order-customer-tagger' ) : __( 'Edit Rule', 'cm-order-customer-tagger' );
$cm_back_url = add_query_arg(
	array(
		'page'   => 'cm-tagger-tags',
		'action' => 'rules',
		'tag_id' => $cm_tag_id,
	),
	admin_url( 'admin.php' )
);
$cm_rule_id  = $cm_is_new ? 0 : (int) $rule['id'];

// Merge existing data with any re-posted data (re-posted wins).
$cm_default_conditions = array();
$cm_val                = array_merge(
	array(
		'label'        => '',
		'rule_trigger' => 'order_placed',
		'operator'     => 'AND',
		'priority'     => 10,
		'is_active'    => 1,
	),
	$rule ? $rule : array(),
	$posted
);

// Conditions to pre-populate: from rule row (already decoded array) or posted JSON.
if ( ! empty( $posted['conditionsRaw'] ) ) {
	$cm_existing_conditions = json_decode( stripslashes( (string) $posted['conditionsRaw'] ), true );
	$cm_existing_conditions = $cm_existing_conditions ? $cm_existing_conditions : array();
} elseif ( ! empty( $rule['conditions'] ) && is_array( $rule['conditions'] ) ) {
	$cm_existing_conditions = $rule['conditions'];
} else {
	$cm_existing_conditions = $cm_default_conditions;
}

// Field definitions: [type, operators[]].
// phpcs:ignore Squiz.Commenting.InlineComment.InvalidEndChar
$cm_field_defs = array(
	'order_total'               => array(
		'type'  => 'numeric',
		'label' => __( 'Order total', 'cm-order-customer-tagger' ),
	),
	'item_count'                => array(
		'type'  => 'numeric',
		'label' => __( 'Item count', 'cm-order-customer-tagger' ),
	),
	'customer_ltv'              => array(
		'type'  => 'numeric',
		'label' => __( 'Customer LTV', 'cm-order-customer-tagger' ),
	),
	'customer_order_count'      => array(
		'type'  => 'numeric',
		'label' => __( 'Customer order count', 'cm-order-customer-tagger' ),
	),
	'days_since_last_order'     => array(
		'type'  => 'numeric',
		'label' => __( 'Days since last order', 'cm-order-customer-tagger' ),
	),
	'customer_account_age_days' => array(
		'type'  => 'numeric',
		'label' => __( 'Customer account age (days)', 'cm-order-customer-tagger' ),
	),
	'payment_status'            => array(
		'type'  => 'string',
		'label' => __( 'Payment status', 'cm-order-customer-tagger' ),
	),
	'payment_method'            => array(
		'type'  => 'string',
		'label' => __( 'Payment method', 'cm-order-customer-tagger' ),
	),
	'shipping_method'           => array(
		'type'  => 'string',
		'label' => __( 'Shipping method', 'cm-order-customer-tagger' ),
	),
	'shipping_country'          => array(
		'type'  => 'string',
		'label' => __( 'Shipping country', 'cm-order-customer-tagger' ),
	),
	'billing_country'           => array(
		'type'  => 'string',
		'label' => __( 'Billing country', 'cm-order-customer-tagger' ),
	),
	'is_guest'                  => array(
		'type'  => 'boolean',
		'label' => __( 'Is guest', 'cm-order-customer-tagger' ),
	),
	'is_first_order'            => array(
		'type'  => 'boolean',
		'label' => __( 'Is first order', 'cm-order-customer-tagger' ),
	),
	'has_coupon'                => array(
		'type'  => 'boolean',
		'label' => __( 'Has coupon', 'cm-order-customer-tagger' ),
	),
	'address_mismatch'          => array(
		'type'  => 'boolean',
		'label' => __( 'Address mismatch', 'cm-order-customer-tagger' ),
	),
);

$cm_operators_by_type = array(
	'numeric' => array(
		'gt'  => __( 'greater than (>)', 'cm-order-customer-tagger' ),
		'gte' => __( 'greater than or equal (>=)', 'cm-order-customer-tagger' ),
		'lt'  => __( 'less than (<)', 'cm-order-customer-tagger' ),
		'lte' => __( 'less than or equal (<=)', 'cm-order-customer-tagger' ),
		'eq'  => __( 'equals (=)', 'cm-order-customer-tagger' ),
	),
	'string'  => array(
		'eq'       => __( 'equals', 'cm-order-customer-tagger' ),
		'contains' => __( 'contains', 'cm-order-customer-tagger' ),
		'in'       => __( 'in list', 'cm-order-customer-tagger' ),
		'not_in'   => __( 'not in list', 'cm-order-customer-tagger' ),
	),
	'boolean' => array(
		'is_true'  => __( 'is true', 'cm-order-customer-tagger' ),
		'is_false' => __( 'is false', 'cm-order-customer-tagger' ),
	),
);
?>
<div class="wrap cm-tagger">
	<h1>
		<?php echo esc_html( $cm_title ); ?>
		<span class="cm-tagger-subtitle">
			<?php
			/* translators: %s: tag name */
			printf( esc_html__( 'for tag: %s', 'cm-order-customer-tagger' ), esc_html( $tag['name'] ) );
			?>
		</span>
		<a href="<?php echo esc_url( $cm_back_url ); ?>" class="page-title-action">
			&larr; <?php esc_html_e( 'Back to Rules', 'cm-order-customer-tagger' ); ?>
		</a>
	</h1>
	<hr class="wp-header-end">

	<?php if ( $errors ) : ?>
	<div class="notice notice-error">
		<ul>
		<?php
		foreach ( $errors as $cm_err ) :
			?>
			<li><?php echo esc_html( $cm_err ); ?></li><?php endforeach; ?></ul>
	</div>
	<?php endif; ?>

	<form method="post" id="cm-rule-form"
			action="
			<?php
			echo esc_url(
				add_query_arg(
					array(
						'page'   => 'cm-tagger-tags',
						'action' => 'rule-save',
					),
					admin_url( 'admin.php' )
				)
			);
			?>
			">
		<?php wp_nonce_field( 'cm_tagger_save_rule' ); ?>
		<input type="hidden" name="rule_id"  value="<?php echo esc_attr( (string) $cm_rule_id ); ?>">
		<input type="hidden" name="tag_id"   value="<?php echo esc_attr( (string) $cm_tag_id ); ?>">
		<input type="hidden" name="conditions_json" id="cm-conditions-json" value="">

		<table class="form-table" role="presentation">
			<tr>
				<th scope="row">
					<label for="cm-rule-label"><?php esc_html_e( 'Label', 'cm-order-customer-tagger' ); ?> <span class="required">*</span></label>
				</th>
				<td>
					<input type="text" id="cm-rule-label" name="label" class="regular-text"
							value="<?php echo esc_attr( (string) $cm_val['label'] ); ?>" maxlength="100" required>
					<p class="description"><?php esc_html_e( 'Human-readable description, e.g. "Order total over $500".', 'cm-order-customer-tagger' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Logic', 'cm-order-customer-tagger' ); ?></th>
				<td>
					<fieldset>
						<label>
							<input type="radio" name="operator" value="AND"
									<?php checked( $cm_val['operator'], 'AND' ); ?>>
							<?php esc_html_e( 'AND — all conditions must match', 'cm-order-customer-tagger' ); ?>
						</label>
						<br>
						<label>
							<input type="radio" name="operator" value="OR"
									<?php checked( $cm_val['operator'], 'OR' ); ?>>
							<?php esc_html_e( 'OR — any condition must match', 'cm-order-customer-tagger' ); ?>
						</label>
					</fieldset>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="cm-rule-priority"><?php esc_html_e( 'Priority', 'cm-order-customer-tagger' ); ?></label>
				</th>
				<td>
					<input type="number" id="cm-rule-priority" name="priority" min="0" max="9999"
							value="<?php echo esc_attr( (string) (int) $cm_val['priority'] ); ?>" class="small-text">
					<p class="description"><?php esc_html_e( 'Lower numbers run first. Default: 10.', 'cm-order-customer-tagger' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Active', 'cm-order-customer-tagger' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="is_active" value="1"
								<?php checked( ! empty( $cm_val['is_active'] ) ); ?>>
						<?php esc_html_e( 'Enable this rule', 'cm-order-customer-tagger' ); ?>
					</label>
				</td>
			</tr>
		</table>

		<hr>
		<h2><?php esc_html_e( 'Conditions', 'cm-order-customer-tagger' ); ?></h2>
		<p class="description">
			<?php esc_html_e( 'For "in list" / "not in list" operators, enter comma-separated values. Boolean operators need no value.', 'cm-order-customer-tagger' ); ?>
		</p>

		<?php
		// Build JS field-type map for operator switching.
		$cm_field_type_map = array();
		foreach ( $cm_field_defs as $cm_key => $cm_def ) {
			$cm_field_type_map[ $cm_key ] = $cm_def['type'];
		}
		$cm_field_type_map_json    = wp_json_encode( $cm_field_type_map );
		$cm_operators_by_type_json = wp_json_encode( $cm_operators_by_type );
		?>

		<div id="cm-conditions-wrapper">
			<?php
			$cm_conditions_to_render = ! empty( $cm_existing_conditions ) ? $cm_existing_conditions : array(
				array(
					'field'    => '',
					'operator' => '',
					'value'    => '',
				),
			);
			foreach ( $cm_conditions_to_render as $cm_idx => $cm_cond ) :
				$cm_cond_field = (string) ( $cm_cond['field'] ?? '' );
				$cm_cond_op    = (string) ( $cm_cond['operator'] ?? '' );
				// Value may be array (from in/not_in) or string.
				$cm_cond_val   = isset( $cm_cond['value'] ) && is_array( $cm_cond['value'] )
					? implode( ', ', $cm_cond['value'] )
					: (string) ( $cm_cond['value'] ?? '' );
				$cm_cond_type  = isset( $cm_field_defs[ $cm_cond_field ] ) ? $cm_field_defs[ $cm_cond_field ]['type'] : 'numeric';
				$cm_boolean_op = in_array( $cm_cond_op, array( 'is_true', 'is_false' ), true );
				?>
			<div class="cm-condition-row" data-idx="<?php echo esc_attr( (string) $cm_idx ); ?>">
				<select class="cm-cond-field" name="cond_field[]" aria-label="<?php esc_attr_e( 'Field', 'cm-order-customer-tagger' ); ?>">
					<option value=""><?php esc_html_e( '— select field —', 'cm-order-customer-tagger' ); ?></option>
					<?php foreach ( $cm_field_defs as $cm_fkey => $cm_fdef ) : ?>
					<option value="<?php echo esc_attr( $cm_fkey ); ?>"
							data-type="<?php echo esc_attr( $cm_fdef['type'] ); ?>"
							<?php selected( $cm_cond_field, $cm_fkey ); ?>>
						<?php echo esc_html( $cm_fdef['label'] ); ?>
					</option>
					<?php endforeach; ?>
				</select>

				<select class="cm-cond-operator" name="cond_operator[]" aria-label="<?php esc_attr_e( 'Operator', 'cm-order-customer-tagger' ); ?>">
					<?php
					// Output all operators grouped by current type so the selected one is visible.
					$cm_all_ops_for_type = $cm_operators_by_type[ $cm_cond_type ] ?? $cm_operators_by_type['numeric'];
					foreach ( $cm_all_ops_for_type as $cm_op_key => $cm_op_label ) :
						?>
					<option value="<?php echo esc_attr( $cm_op_key ); ?>" <?php selected( $cm_cond_op, $cm_op_key ); ?>>
						<?php echo esc_html( $cm_op_label ); ?>
					</option>
					<?php endforeach; ?>
				</select>

				<input type="text"
						class="cm-cond-value"
						name="cond_value[]"
						value="<?php echo esc_attr( $cm_cond_val ); ?>"
						placeholder="<?php esc_attr_e( 'value', 'cm-order-customer-tagger' ); ?>"
						aria-label="<?php esc_attr_e( 'Value', 'cm-order-customer-tagger' ); ?>"
						<?php echo $cm_boolean_op ? 'style="display:none"' : ''; ?>>

				<button type="button" class="button cm-remove-condition"
						aria-label="<?php esc_attr_e( 'Remove condition', 'cm-order-customer-tagger' ); ?>">
					&times;
				</button>
			</div>
			<?php endforeach; ?>
		</div>

		<p>
			<button type="button" id="cm-add-condition" class="button button-secondary">
				<?php esc_html_e( '+ Add Condition', 'cm-order-customer-tagger' ); ?>
			</button>
		</p>

		<?php submit_button( $cm_is_new ? __( 'Create Rule', 'cm-order-customer-tagger' ) : __( 'Update Rule', 'cm-order-customer-tagger' ) ); ?>
	</form>
</div>

<script type="application/json" id="cm-field-type-map"><?php echo $cm_field_type_map_json; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_json_encode output is safe ?></script>
<script type="application/json" id="cm-operators-by-type"><?php echo $cm_operators_by_type_json; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_json_encode output is safe ?></script>
