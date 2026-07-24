<?php
/**
 * Fetches, caches, parses, and filters the Clarity Momentum module feed.
 *
 * @package ClarityMomentum\FeedConsumer
 */

declare(strict_types=1);

/**
 * Reads the CM module feed XML, caches results in a WP transient, filters
 * items by module/platform slug, and tracks per-user dismissed GUIDs in
 * user meta.
 */
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound -- bundled library, class name intentionally uses CM_ prefix
class CM_Feed_Consumer {

	/**
	 * Module slug, e.g. amazon-shipping-woocommerce.
	 *
	 * @var string
	 */
	private string $module;

	/**
	 * Platform slug, e.g. woocommerce.
	 *
	 * @var string
	 */
	private string $platform;

	/**
	 * Feed URL.
	 *
	 * @var string
	 */
	private string $feed_url;

	/**
	 * Transient TTL in seconds.
	 *
	 * @var int
	 */
	private int $cache_ttl;

	/**
	 * Constructor.
	 *
	 * @param array{module: string, platform: string, feed_url?: string, cache_ttl?: int} $config Configuration array.
	 */
	public function __construct( array $config ) {
		$this->module    = $config['module'];
		$this->platform  = $config['platform'];
		$this->feed_url  = $config['feed_url'] ?? 'https://claritymomentum.io/cm-module-feed.xml';
		$this->cache_ttl = $config['cache_ttl'] ?? 3600;
	}

	/**
	 * Returns filtered feed items, using transient cache where available.
	 *
	 * @return array<int, array<string, string>>
	 */
	public function get_items(): array {
		$cache_key = $this->cache_key();
		$cached    = get_transient( $cache_key );

		if ( false === $cached ) {
			$cached = $this->fetch_and_cache();
		}

		return $this->filter_items( $cached );
	}

	/**
	 * Fetches the feed URL, parses it, stores raw items in transient.
	 *
	 * @return array<int, array<string, string>>
	 */
	public function fetch_and_cache(): array {
		$response = wp_remote_get( $this->feed_url );

		if ( is_wp_error( $response ) ) {
			return array();
		}

		$code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== (int) $code ) {
			return array();
		}

		$body  = wp_remote_retrieve_body( $response );
		$items = $this->parse_xml( $body );

		set_transient( $this->cache_key(), $items, $this->cache_ttl );

		return $items;
	}

	/**
	 * Parses RSS XML string and returns an array of item arrays.
	 *
	 * @param string $xml Raw RSS XML string.
	 * @return array<int, array<string, string>>
	 */
	public function parse_xml( string $xml ): array {
		if ( '' === trim( $xml ) ) {
			return array();
		}

		libxml_use_internal_errors( true );
		$rss = simplexml_load_string( $xml );
		libxml_clear_errors();

		if ( false === $rss ) {
			return array();
		}

		$rss->registerXPathNamespace( 'cm', 'https://claritymomentum.io/feed/ns' );

		$xml_items = $rss->xpath( '//item' );
		if ( ! is_array( $xml_items ) || array() === $xml_items ) {
			return array();
		}

		$items = array();
		foreach ( $xml_items as $item ) {
			$item->registerXPathNamespace( 'cm', 'https://claritymomentum.io/feed/ns' );

			$severity_nodes = $item->xpath( 'cm:severity' );
			$platform_nodes = $item->xpath( 'cm:platform' );
			$module_nodes   = $item->xpath( 'cm:module' );

			$items[] = array(
				'guid'        => (string) ( $item->guid ?? '' ),
				'title'       => (string) ( $item->title ?? '' ),
				'description' => (string) ( $item->description ?? '' ),
				'link'        => (string) ( $item->link ?? '' ),
				'pub_date'    => isset( $item->pubDate ) ? (string) $item->pubDate : '', // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				'severity'    => isset( $severity_nodes[0] ) ? (string) $severity_nodes[0] : '1',
				'platform'    => isset( $platform_nodes[0] ) ? (string) $platform_nodes[0] : 'ALL',
				'module'      => isset( $module_nodes[0] ) ? (string) $module_nodes[0] : 'ALL',
			);
		}

		return $items;
	}

	/**
	 * Filters items to those matching this consumer's module and platform.
	 *
	 * @param array<int, array<string, string>> $items Raw item list.
	 * @return array<int, array<string, string>>
	 */
	public function filter_items( array $items ): array {
		return array_values(
			array_filter(
				$items,
				function ( array $item ): bool {
					$module_match   = ( 'ALL' === $item['module'] || $item['module'] === $this->module );
					$platform_list  = array_map( 'trim', explode( ',', $item['platform'] ) );
					$platform_match = ( 'ALL' === $item['platform'] || in_array( $this->platform, $platform_list, true ) );

					return $module_match && $platform_match;
				}
			)
		);
	}

	/**
	 * Returns true if the given GUID is in the dismissed list.
	 *
	 * @param string   $guid      The GUID to check.
	 * @param string[] $dismissed Array of dismissed GUIDs.
	 * @return bool
	 */
	public function is_dismissed( string $guid, array $dismissed ): bool {
		return in_array( $guid, $dismissed, true );
	}

	/**
	 * Gets the list of dismissed GUIDs for a user.
	 *
	 * @param int $user_id WordPress user ID.
	 * @return string[]
	 */
	public function get_dismissed( int $user_id ): array {
		$value = get_user_meta( $user_id, '_cm_feed_dismissed', true );
		return is_array( $value ) ? $value : array();
	}

	/**
	 * Adds a GUID to the user's dismissed list.
	 *
	 * @param string $guid    The GUID to dismiss.
	 * @param int    $user_id WordPress user ID.
	 */
	public function dismiss( string $guid, int $user_id ): void {
		$dismissed = $this->get_dismissed( $user_id );
		if ( ! in_array( $guid, $dismissed, true ) ) {
			$dismissed[] = $guid;
		}
		update_user_meta( $user_id, '_cm_feed_dismissed', $dismissed );
	}

	/**
	 * Returns items that have not yet been dismissed by the given user,
	 * sorted by severity descending (3 first) then by pub_date descending.
	 *
	 * @param int $user_id WordPress user ID.
	 * @return array<int, array<string, string>>
	 */
	public function get_unread_items( int $user_id ): array {
		$items     = $this->get_items();
		$dismissed = $this->get_dismissed( $user_id );

		$unread = array_values(
			array_filter(
				$items,
				fn( array $item ) => ! $this->is_dismissed( $item['guid'], $dismissed )
			)
		);

		usort(
			$unread,
			function ( array $a, array $b ): int {
				$sev = (int) $b['severity'] - (int) $a['severity'];
				if ( 0 !== $sev ) {
					return $sev;
				}
				return strcmp( $b['pub_date'], $a['pub_date'] );
			}
		);

		return $unread;
	}

	/**
	 * Transient cache key derived from the feed URL.
	 *
	 * @return string
	 */
	public function cache_key(): string {
		return 'cm_feed_' . substr( md5( $this->feed_url ), 0, 16 );
	}
}
