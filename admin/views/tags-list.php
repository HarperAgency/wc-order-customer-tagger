<?php
/**
 * Admin view: tag list table.
 *
 * @package ClarityMomentum\WCTagger
 *
 * @var array<int, array<string, mixed>> $tags   All tag rows.
 * @var array{message: string, type: string}|null $notice Admin notice or null.
 */

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- view file included inside a function, variables are not global scope
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$add_url = add_query_arg(
	array(
		'page'   => 'cm-tagger-tags',
		'action' => 'add',
	),
	admin_url( 'admin.php' )
);
?>
<div class="wrap cm-tagger">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Order &amp; Customer Tags', 'cm-order-customer-tagger' ); ?></h1>
	<a href="<?php echo esc_url( $add_url ); ?>" class="page-title-action">
		<?php esc_html_e( 'Add Tag', 'cm-order-customer-tagger' ); ?>
	</a>
	<hr class="wp-header-end">

	<?php if ( $notice ) : ?>
	<div class="notice notice-<?php echo esc_attr( $notice['type'] ); ?> is-dismissible">
		<p><?php echo esc_html( $notice['message'] ); ?></p>
	</div>
	<?php endif; ?>

	<?php if ( empty( $tags ) ) : ?>
	<p><?php esc_html_e( 'No tags yet. Create your first tag to get started.', 'cm-order-customer-tagger' ); ?></p>
	<?php else : ?>
	<table class="wp-list-table widefat fixed striped cm-tagger-list">
		<thead>
			<tr>
				<th style="width:40px"><?php esc_html_e( 'ID', 'cm-order-customer-tagger' ); ?></th>
				<th><?php esc_html_e( 'Badge', 'cm-order-customer-tagger' ); ?></th>
				<th><?php esc_html_e( 'Name', 'cm-order-customer-tagger' ); ?></th>
				<th><?php esc_html_e( 'Slug', 'cm-order-customer-tagger' ); ?></th>
				<th><?php esc_html_e( 'Type', 'cm-order-customer-tagger' ); ?></th>
				<th style="width:80px"><?php esc_html_e( 'Rules', 'cm-order-customer-tagger' ); ?></th>
				<th><?php esc_html_e( 'Created', 'cm-order-customer-tagger' ); ?></th>
				<th><?php esc_html_e( 'Actions', 'cm-order-customer-tagger' ); ?></th>
			</tr>
		</thead>
		<tbody>
		<?php
		foreach ( $tags as $cm_tag ) :
			$cm_id          = (int) $cm_tag['id'];
			$cm_edit_url    = add_query_arg(
				array(
					'page'   => 'cm-tagger-tags',
					'action' => 'edit',
					'id'     => $cm_id,
				),
				admin_url( 'admin.php' )
			);
			$cm_del_url     = wp_nonce_url(
				add_query_arg(
					array(
						'page'   => 'cm-tagger-tags',
						'action' => 'delete',
						'id'     => $cm_id,
					),
					admin_url( 'admin.php' )
				),
				'cm_tagger_delete_tag_' . $cm_id
			);
			$cm_type_labels = array(
				'order'    => __( 'Order', 'cm-order-customer-tagger' ),
				'customer' => __( 'Customer', 'cm-order-customer-tagger' ),
				'both'     => __( 'Both', 'cm-order-customer-tagger' ),
			);
			?>
		<tr>
			<td><?php echo esc_html( (string) $cm_id ); ?></td>
			<td>
				<?php if ( ! empty( $cm_tag['image_url'] ) ) : ?>
					<img src="<?php echo esc_url( $cm_tag['image_url'] ); ?>" alt="" class="cm-tagger-icon-preview">
				<?php else : ?>
					<span class="cm-tagger-badge" style="background:<?php echo esc_attr( $cm_tag['color'] ); ?>">
						<?php echo esc_html( $cm_tag['name'] ); ?>
					</span>
				<?php endif; ?>
			</td>
			<td><strong><?php echo esc_html( $cm_tag['name'] ); ?></strong></td>
			<td><code><?php echo esc_html( $cm_tag['slug'] ); ?></code></td>
			<td><?php echo esc_html( $cm_type_labels[ $cm_tag['type'] ] ?? $cm_tag['type'] ); ?></td>
			<td><?php echo esc_html( (string) $cm_tag['rule_count'] ); ?></td>
			<td><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $cm_tag['created_at'] ) ) ); ?></td>
			<td>
				<a href="<?php echo esc_url( $cm_edit_url ); ?>"><?php esc_html_e( 'Edit', 'cm-order-customer-tagger' ); ?></a>
				&nbsp;|&nbsp;
				<a href="<?php echo esc_url( $cm_del_url ); ?>"
					onclick="return confirm('<?php esc_attr_e( 'Delete this tag? This cannot be undone.', 'cm-order-customer-tagger' ); ?>')"
					class="cm-tagger-delete-link">
					<?php esc_html_e( 'Delete', 'cm-order-customer-tagger' ); ?>
				</a>
			</td>
		</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
	<?php endif; ?>
</div>
