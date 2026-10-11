<?php
/**
 * Admin view: rules list for a tag.
 *
 * @package ClarityMomentum\WCTagger
 *
 * @var array<string, mixed>                 $tag    Parent tag row.
 * @var array<int, array<string, mixed>>     $rules  Rules for this tag.
 * @var array{message: string, type: string}|null $notice Admin notice or null.
 */

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- view file included inside a function, variables are not global scope
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$cm_tag_id       = (int) $tag['id'];
$cm_tag_edit_url = add_query_arg(
	array(
		'page'   => 'cm-tagger-tags',
		'action' => 'edit',
		'id'     => $cm_tag_id,
	),
	admin_url( 'admin.php' )
);
$cm_add_rule_url = add_query_arg(
	array(
		'page'   => 'cm-tagger-tags',
		'action' => 'rule-add',
		'tag_id' => $cm_tag_id,
	),
	admin_url( 'admin.php' )
);

$cm_op_labels = array(
	'AND' => __( 'AND', 'cm-order-customer-tagger' ),
	'OR'  => __( 'OR', 'cm-order-customer-tagger' ),
);
?>
<div class="wrap cm-tagger">
	<h1 class="wp-heading-inline">
		<?php
		/* translators: %s: tag name */
		printf( esc_html__( 'Rules — %s', 'cm-order-customer-tagger' ), esc_html( $tag['name'] ) );
		?>
	</h1>
	<a href="<?php echo esc_url( $cm_add_rule_url ); ?>" class="page-title-action">
		<?php esc_html_e( 'Add Rule', 'cm-order-customer-tagger' ); ?>
	</a>
	<a href="<?php echo esc_url( $cm_tag_edit_url ); ?>" class="page-title-action">
		&larr; <?php esc_html_e( 'Back to Tag', 'cm-order-customer-tagger' ); ?>
	</a>
	<hr class="wp-header-end">

	<?php if ( $notice ) : ?>
	<div class="notice notice-<?php echo esc_attr( $notice['type'] ); ?> is-dismissible">
		<p><?php echo esc_html( $notice['message'] ); ?></p>
	</div>
	<?php endif; ?>

	<?php if ( empty( $rules ) ) : ?>
	<p>
		<?php esc_html_e( 'No rules yet.', 'cm-order-customer-tagger' ); ?>
		<a href="<?php echo esc_url( $cm_add_rule_url ); ?>"><?php esc_html_e( 'Add the first rule', 'cm-order-customer-tagger' ); ?></a>.
	</p>
	<?php else : ?>
	<table class="wp-list-table widefat fixed striped cm-tagger-list">
		<thead>
			<tr>
				<th style="width:40px"><?php esc_html_e( 'ID', 'cm-order-customer-tagger' ); ?></th>
				<th><?php esc_html_e( 'Label', 'cm-order-customer-tagger' ); ?></th>
				<th style="width:60px"><?php esc_html_e( 'Logic', 'cm-order-customer-tagger' ); ?></th>
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
			<td><strong><?php echo esc_html( $cm_rule['label'] ); ?></strong></td>
			<td><?php echo esc_html( $cm_op_labels[ $cm_rule['operator'] ] ?? esc_html( $cm_rule['operator'] ) ); ?></td>
			<td><?php echo esc_html( (string) $cm_rule['priority'] ); ?></td>
			<td>
				<?php if ( $cm_rule['is_active'] ) : ?>
					<span class="cm-tagger-badge" style="background:#00a32a"><?php esc_html_e( 'Yes', 'cm-order-customer-tagger' ); ?></span>
				<?php else : ?>
					<span class="cm-tagger-badge" style="background:#999"><?php esc_html_e( 'No', 'cm-order-customer-tagger' ); ?></span>
				<?php endif; ?>
			</td>
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

	<?php require CM_TAGGER_DIR . 'admin/views/partials/footer.php'; ?>
</div>
