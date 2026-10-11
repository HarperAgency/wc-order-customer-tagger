<?php
/**
 * Admin view: add / edit tag form.
 *
 * @package ClarityMomentum\WCTagger
 *
 * @var array<string, mixed>|null             $tag    Existing tag row, or null for new.
 * @var array<string, string>                 $errors Validation errors.
 * @var array<string, mixed>                  $posted Re-populated POST data after error.
 * @var array<int, array<string, mixed>>      $rules  Rules attached to this tag.
 */

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- view file included inside a function, variables are not global scope
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$cm_is_new   = ( null === $tag );
$cm_title    = $cm_is_new ? __( 'Add Tag', 'cm-order-customer-tagger' ) : __( 'Edit Tag', 'cm-order-customer-tagger' );
$cm_list_url = add_query_arg( array( 'page' => 'cm-tagger-tags' ), admin_url( 'admin.php' ) );

// Merge saved values with any re-posted data (re-posted wins after validation fail).
$cm_val    = array_merge(
	array(
		'name'      => '',
		'color'     => '#3788d8',
		'type'      => 'order',
		'image_url' => '',
	),
	$tag ? $tag : array(),
	$posted
);
$cm_tag_id = $cm_is_new ? 0 : (int) $tag['id'];
?>
<div class="wrap cm-tagger">
	<h1>
		<?php echo esc_html( $cm_title ); ?>
		<a href="<?php echo esc_url( $cm_list_url ); ?>" class="page-title-action">
			&larr; <?php esc_html_e( 'All Tags', 'cm-order-customer-tagger' ); ?>
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

	<form method="post" action="
	<?php
	echo esc_url(
		add_query_arg(
			array(
				'page'   => 'cm-tagger-tags',
				'action' => 'save',
			),
			admin_url( 'admin.php' )
		)
	);
	?>
	">
		<?php wp_nonce_field( 'cm_tagger_save_tag' ); ?>
		<input type="hidden" name="tag_id" value="<?php echo esc_attr( (string) $cm_tag_id ); ?>">

		<table class="form-table" role="presentation">
			<tr>
				<th scope="row">
					<label for="cm-tag-name"><?php esc_html_e( 'Name', 'cm-order-customer-tagger' ); ?> <span class="required">*</span></label>
				</th>
				<td>
					<input type="text" id="cm-tag-name" name="name" class="regular-text"
							value="<?php echo esc_attr( $cm_val['name'] ); ?>" maxlength="100" required>
					<p class="description"><?php esc_html_e( 'Displayed as a badge on orders and customers.', 'cm-order-customer-tagger' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="cm-tag-color"><?php esc_html_e( 'Badge Color', 'cm-order-customer-tagger' ); ?></label>
				</th>
				<td>
					<input type="text" id="cm-tag-color" name="color" class="cm-color-picker"
							value="<?php echo esc_attr( $cm_val['color'] ); ?>">
					<p class="description"><?php esc_html_e( 'Used when no custom image is set.', 'cm-order-customer-tagger' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="cm-tag-type"><?php esc_html_e( 'Applies To', 'cm-order-customer-tagger' ); ?></label>
				</th>
				<td>
					<select id="cm-tag-type" name="type">
						<option value="order"    <?php selected( $cm_val['type'], 'order' ); ?>><?php esc_html_e( 'Orders only', 'cm-order-customer-tagger' ); ?></option>
						<option value="customer" <?php selected( $cm_val['type'], 'customer' ); ?>><?php esc_html_e( 'Customers only', 'cm-order-customer-tagger' ); ?></option>
						<option value="both"     <?php selected( $cm_val['type'], 'both' ); ?>><?php esc_html_e( 'Both orders &amp; customers', 'cm-order-customer-tagger' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="cm-tag-image"><?php esc_html_e( 'Custom Icon URL', 'cm-order-customer-tagger' ); ?></label>
				</th>
				<td>
					<input type="url" id="cm-tag-image" name="image_url" class="regular-text"
							value="<?php echo esc_attr( $cm_val['image_url'] ?? '' ); ?>"
							placeholder="https://...">
					<p class="description">
						<?php esc_html_e( 'Optional PNG or SVG. Leave blank to use the color badge above.', 'cm-order-customer-tagger' ); ?>
					</p>
					<?php if ( ! empty( $cm_val['image_url'] ) ) : ?>
					<div class="cm-tagger-icon-preview-wrap">
						<img src="<?php echo esc_url( $cm_val['image_url'] ); ?>" alt="" class="cm-tagger-icon-preview">
					</div>
					<?php endif; ?>
				</td>
			</tr>
		</table>

		<hr>
		<h3><?php esc_html_e( 'Preview', 'cm-order-customer-tagger' ); ?></h3>
		<p>
			<?php if ( ! empty( $cm_val['image_url'] ) ) : ?>
				<img src="<?php echo esc_url( $cm_val['image_url'] ); ?>" alt="" class="cm-tagger-icon-preview">
			<?php else : ?>
				<span class="cm-tagger-badge" id="cm-badge-preview"
						style="background:<?php echo esc_attr( $cm_val['color'] ); ?>">
					<?php echo esc_html( $cm_val['name'] ? $cm_val['name'] : __( 'Preview', 'cm-order-customer-tagger' ) ); ?>
				</span>
			<?php endif; ?>
		</p>

		<?php submit_button( $cm_is_new ? __( 'Create Tag', 'cm-order-customer-tagger' ) : __( 'Update Tag', 'cm-order-customer-tagger' ) ); ?>
	</form>

	<?php if ( ! $cm_is_new ) : ?>
	<hr>
	<h2><?php esc_html_e( 'Rules for this tag', 'cm-order-customer-tagger' ); ?></h2>

		<?php
		$cm_add_rule_url = add_query_arg(
			array(
				'page'   => 'cm-tagger-tags',
				'action' => 'rule-add',
				'tag_id' => $cm_tag_id,
			),
			admin_url( 'admin.php' )
		);
		$cm_rules_url    = add_query_arg(
			array(
				'page'   => 'cm-tagger-tags',
				'action' => 'rules',
				'tag_id' => $cm_tag_id,
			),
			admin_url( 'admin.php' )
		);
		$cm_op_labels    = array(
			'AND' => __( 'AND', 'cm-order-customer-tagger' ),
			'OR'  => __( 'OR', 'cm-order-customer-tagger' ),
		);
		?>

		<?php if ( empty( $rules ) ) : ?>
	<p><?php esc_html_e( 'No rules yet.', 'cm-order-customer-tagger' ); ?>
		<a href="<?php echo esc_url( $cm_add_rule_url ); ?>"><?php esc_html_e( 'Add the first rule', 'cm-order-customer-tagger' ); ?></a>.
	</p>
	<?php else : ?>
	<table class="wp-list-table widefat fixed striped cm-tagger-list">
		<thead>
			<tr>
				<th style="width:40px"><?php esc_html_e( 'ID', 'cm-order-customer-tagger' ); ?></th>
				<th><?php esc_html_e( 'Label', 'cm-order-customer-tagger' ); ?></th>
				<th style="width:60px"><?php esc_html_e( 'Op', 'cm-order-customer-tagger' ); ?></th>
				<th style="width:70px"><?php esc_html_e( 'Priority', 'cm-order-customer-tagger' ); ?></th>
				<th style="width:70px"><?php esc_html_e( 'Active', 'cm-order-customer-tagger' ); ?></th>
				<th style="width:80px"><?php esc_html_e( 'Conditions', 'cm-order-customer-tagger' ); ?></th>
				<th><?php esc_html_e( 'Actions', 'cm-order-customer-tagger' ); ?></th>
			</tr>
		</thead>
		<tbody>
		<?php
		foreach ( $rules as $cm_rule ) :
			$cm_rule_id  = (int) $cm_rule['id'];
			$cm_edit_url = add_query_arg(
				array(
					'page'   => 'cm-tagger-tags',
					'action' => 'rule-edit',
					'tag_id' => $cm_tag_id,
					'id'     => $cm_rule_id,
				),
				admin_url( 'admin.php' )
			);
			$cm_del_url  = wp_nonce_url(
				add_query_arg(
					array(
						'page'   => 'cm-tagger-tags',
						'action' => 'rule-delete',
						'tag_id' => $cm_tag_id,
						'id'     => $cm_rule_id,
					),
					admin_url( 'admin.php' )
				),
				'cm_tagger_delete_rule_' . $cm_rule_id
			);
			?>
		<tr>
			<td><?php echo esc_html( (string) $cm_rule_id ); ?></td>
			<td><?php echo esc_html( $cm_rule['label'] ); ?></td>
			<td><?php echo esc_html( $cm_op_labels[ $cm_rule['operator'] ] ?? $cm_rule['operator'] ); ?></td>
			<td><?php echo esc_html( (string) $cm_rule['priority'] ); ?></td>
			<td><?php echo $cm_rule['is_active'] ? esc_html__( 'Yes', 'cm-order-customer-tagger' ) : esc_html__( 'No', 'cm-order-customer-tagger' ); ?></td>
			<td><?php echo esc_html( (string) count( (array) ( $cm_rule['conditions'] ?? array() ) ) ); ?></td>
			<td>
				<a href="<?php echo esc_url( $cm_edit_url ); ?>"><?php esc_html_e( 'Edit', 'cm-order-customer-tagger' ); ?></a>
				&nbsp;|&nbsp;
				<a href="<?php echo esc_url( $cm_del_url ); ?>"
					onclick="return confirm('<?php esc_attr_e( 'Delete this rule? This cannot be undone.', 'cm-order-customer-tagger' ); ?>')"
					class="cm-tagger-delete-link">
					<?php esc_html_e( 'Delete', 'cm-order-customer-tagger' ); ?>
				</a>
			</td>
		</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
	<?php endif; ?>

	<p>
		<a href="<?php echo esc_url( $cm_add_rule_url ); ?>" class="button button-secondary">
			<?php esc_html_e( '+ Add Rule', 'cm-order-customer-tagger' ); ?>
		</a>
		&nbsp;
		<a href="<?php echo esc_url( $cm_rules_url ); ?>" class="button button-secondary">
			<?php esc_html_e( 'Manage Rules', 'cm-order-customer-tagger' ); ?>
		</a>
	</p>
	<?php endif; ?>

	<?php require CM_TAGGER_DIR . 'admin/views/partials/footer.php'; ?>
</div>
