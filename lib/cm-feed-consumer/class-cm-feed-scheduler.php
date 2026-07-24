<?php
/**
 * Manages the WP-Cron event that refreshes the CM feed cache hourly.
 *
 * @package ClarityMomentum\FeedConsumer
 */

declare(strict_types=1);

/**
 * Registers and manages the hourly WP-Cron event for feed cache refresh.
 */
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound -- bundled library, class name intentionally uses CM_ prefix
class CM_Feed_Scheduler {

	/**
	 * Feed consumer instance.
	 *
	 * @var CM_Feed_Consumer
	 */
	private CM_Feed_Consumer $consumer;

	/**
	 * Constructor.
	 *
	 * @param CM_Feed_Consumer $consumer Feed consumer instance.
	 */
	public function __construct( CM_Feed_Consumer $consumer ) {
		$this->consumer = $consumer;
	}

	/**
	 * Registers hooks for scheduling and handling the refresh event.
	 */
	public function init(): void {
		add_action( 'wp_loaded', array( $this, 'schedule' ) );
		add_action( 'cm_feed_consumer_refresh', array( $this, 'refresh' ) );
	}

	/**
	 * Schedules the hourly cron event if not already scheduled.
	 */
	public function schedule(): void {
		if ( ! wp_next_scheduled( 'cm_feed_consumer_refresh' ) ) {
			wp_schedule_event( time(), 'hourly', 'cm_feed_consumer_refresh' );
		}
	}

	/**
	 * Clears the scheduled cron event. Call on plugin deactivation.
	 */
	public function unschedule(): void {
		wp_clear_scheduled_hook( 'cm_feed_consumer_refresh' );
	}

	/**
	 * Deletes the cached feed transient and re-fetches.
	 */
	public function refresh(): void {
		delete_transient( $this->consumer->cache_key() );
		$this->consumer->fetch_and_cache();
	}
}
