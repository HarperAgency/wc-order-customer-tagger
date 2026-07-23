<?php
/**
 * Renders WordPress admin notices from CM feed items and handles AJAX dismissal.
 *
 * @package ClarityMomentum\FeedConsumer
 */

declare(strict_types=1);

/**
 * Displays WP admin notices for unread CM feed items and persists dismiss
 * state via AJAX + user meta.
 */
class CM_Feed_Notice {

	/**
	 * Feed consumer instance.
	 *
	 * @var CM_Feed_Consumer
	 */
	private CM_Feed_Consumer $consumer;

	/**
	 * Maximum number of notices to display at once.
	 *
	 * @var int
	 */
	private int $max_visible;

	/**
	 * Constructor.
	 *
	 * @param CM_Feed_Consumer $consumer    Feed consumer instance.
	 * @param int              $max_visible Max notices to show at once. Default 2.
	 */
	public function __construct( CM_Feed_Consumer $consumer, int $max_visible = 2 ) {
		$this->consumer    = $consumer;
		$this->max_visible = $max_visible;
	}

	/**
	 * Registers all hooks.
	 */
	public function init(): void {
		add_action( 'admin_notices', array( $this, 'render_notices' ) );
		add_action( 'wp_ajax_cm_dismiss_feed_notice', array( $this, 'ajax_dismiss' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Echoes admin notices for the current user, capped at $max_visible.
	 * If more unread items exist beyond the cap, a queue notice is shown.
	 */
	public function render_notices(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) && ! current_user_can( 'manage_options' ) ) { // phpcs:ignore WordPress.WP.Capabilities.Unknown
			return;
		}

		$user_id = get_current_user_id();
		$items   = $this->consumer->get_unread_items( $user_id );

		if ( empty( $items ) ) {
			return;
		}

		$visible  = array_slice( $items, 0, $this->max_visible );
		$queued   = count( $items ) - count( $visible );

		foreach ( $visible as $item ) {
			echo $this->render_notice_html( $item ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		if ( $queued > 0 ) {
			echo '<div class="notice notice-info"><p>…and ' . absint( $queued ) . ' more Clarity Momentum notification' . ( 1 === $queued ? '' : 's' ) . '. Dismiss above to see them.</p></div>';
		}
	}

	/**
	 * Returns the HTML string for a single feed item notice.
	 *
	 * @param array<string, string> $item Feed item array.
	 * @return string
	 */
	public function render_notice_html( array $item ): string {
		$severity_map = array(
			'1' => 'notice-info',
			'2' => 'notice-warning',
			'3' => 'notice-error',
		);

		$notice_class = $severity_map[ (string) $item['severity'] ] ?? 'notice-info';
		$guid         = esc_attr( $item['guid'] );
		$title        = esc_html( $item['title'] );
		$description  = esc_html( $item['description'] );
		$link         = esc_url( $item['link'] );

		return sprintf(
			'<div class="notice %1$s is-dismissible cm-feed-notice" data-guid="%2$s"><p><strong>%3$s</strong> %4$s <a href="%5$s" target="_blank" rel="noopener">Details</a></p></div>',
			$notice_class,
			$guid,
			$title,
			$description,
			$link
		);
	}

	/**
	 * Enqueues jQuery and the inline dismiss script.
	 */
	public function enqueue_scripts(): void {
		wp_enqueue_script( 'jquery' );

		$nonce = wp_create_nonce( 'cm_feed_consumer_dismiss' );
		$js    = 'jQuery(function($){' .
			'$(document).on("click",".cm-feed-notice .notice-dismiss",function(){' .
			'var guid=$(this).closest(".cm-feed-notice").data("guid");' .
			'$.post(ajaxurl,{action:"cm_dismiss_feed_notice",guid:guid,nonce:"' . esc_js( $nonce ) . '"});' .
			'});' .
			'});';

		wp_add_inline_script( 'jquery', $js );
	}

	/**
	 * Handles the AJAX dismiss request.
	 */
	public function ajax_dismiss(): void {
		check_ajax_referer( 'cm_feed_consumer_dismiss', 'nonce' );

		$guid = sanitize_text_field( wp_unslash( $_POST['guid'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing

		$this->consumer->dismiss( $guid, get_current_user_id() );

		wp_send_json_success();
	}
}
