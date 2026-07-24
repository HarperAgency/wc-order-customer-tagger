<?php
/**
 * Meta box view: tags applied to the current order.
 *
 * @package ClarityMomentum\WCTagger
 *
 * @var array<int, array<string, mixed>> $tags Applied tag rows (id, name, color, image_url).
 */

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- view file included inside a function, variables are not global scope
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="cm-tagger-metabox">
	<?php if ( empty( $tags ) ) : ?>
		<p class="cm-tagger-no-tags"><?php esc_html_e( 'No tags applied.', 'cm-order-customer-tagger' ); ?></p>
	<?php else : ?>
		<div class="cm-tagger-metabox-tags">
			<?php foreach ( $tags as $cm_tag ) : ?>
				<?php if ( ! empty( $cm_tag['image_url'] ) ) : ?>
					<img src="<?php echo esc_url( $cm_tag['image_url'] ); ?>"
						alt="<?php echo esc_attr( $cm_tag['name'] ); ?>"
						title="<?php echo esc_attr( $cm_tag['name'] ); ?>"
						class="cm-tagger-icon-preview">
				<?php else : ?>
					<span class="cm-tagger-badge"
							style="background:<?php echo esc_attr( $cm_tag['color'] ); ?>"
							title="<?php echo esc_attr( $cm_tag['name'] ); ?>">
						<?php echo esc_html( $cm_tag['name'] ); ?>
					</span>
				<?php endif; ?>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</div>
