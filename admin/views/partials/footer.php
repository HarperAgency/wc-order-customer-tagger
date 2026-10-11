<?php
/**
 * Shared admin footer - CM branding and deep links.
 *
 * @package ClarityMomentum\WCTagger
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="cm-tagger-footer">
	<span class="cm-tagger-footer-brand">
		<a href="https://claritymomentum.io" target="_blank" rel="noopener">Clarity Momentum</a>
	</span>
	<span class="cm-tagger-footer-links">
		<a href="https://claritymomentum.io/products/cm-order-customer-tagger/" target="_blank" rel="noopener"><?php esc_html_e( 'Plugin docs', 'cm-order-customer-tagger' ); ?></a>
		&middot;
		<a href="https://claritymomentum.io/signalstream/" target="_blank" rel="noopener"><?php esc_html_e( 'Server-side tracking for WooCommerce', 'cm-order-customer-tagger' ); ?></a>
		&middot;
		<a href="https://claritymomentum.io/contact-us/" target="_blank" rel="noopener"><?php esc_html_e( 'Custom development', 'cm-order-customer-tagger' ); ?></a>
	</span>
</div>
