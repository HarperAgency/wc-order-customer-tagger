<?php
/**
 * Database schema — creates and upgrades the four tagger tables.
 *
 * @package ClarityMomentum\WCTagger\Db
 */

declare(strict_types=1);

namespace ClarityMomentum\WCTagger\Db;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Manages creation and upgrades for all plugin database tables.
 */
class Schema {

	private const VERSION_OPTION = 'cm_tagger_db_version';
	public const DB_VERSION      = '1.0';

	/**
	 * Create or update all plugin tables using dbDelta.
	 */
	public static function install(): void {
		global $wpdb;
		$c = $wpdb->get_charset_collate();
		$p = $wpdb->prefix;

		$sql = "
        CREATE TABLE {$p}cm_tagger_tags (
            id         BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name       VARCHAR(100)        NOT NULL,
            slug       VARCHAR(100)        NOT NULL,
            color      VARCHAR(7)          NOT NULL DEFAULT '#3788d8',
            type       VARCHAR(10)         NOT NULL DEFAULT 'order',
            image_url  VARCHAR(500)                 DEFAULT NULL,
            created_at DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug)
        ) {$c};

        CREATE TABLE {$p}cm_tagger_rules (
            id         BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            tag_id     BIGINT(20) UNSIGNED NOT NULL,
            label      VARCHAR(100)        NOT NULL DEFAULT '',
            rule_trigger VARCHAR(30)       NOT NULL DEFAULT 'order_placed',
            operator   VARCHAR(3)          NOT NULL DEFAULT 'AND',
            conditions LONGTEXT            NOT NULL,
            priority   SMALLINT UNSIGNED   NOT NULL DEFAULT 10,
            is_active  TINYINT(1)          NOT NULL DEFAULT 1,
            created_at DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY tag_id            (tag_id),
            KEY is_active_trigger (is_active, rule_trigger)
        ) {$c};

        CREATE TABLE {$p}cm_tagger_order_tags (
            id         BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            order_id   BIGINT(20) UNSIGNED NOT NULL,
            tag_id     BIGINT(20) UNSIGNED NOT NULL,
            source     VARCHAR(10)         NOT NULL DEFAULT 'rule',
            created_at DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY order_tag (order_id, tag_id),
            KEY tag_id (tag_id)
        ) {$c};

        CREATE TABLE {$p}cm_tagger_customer_tags (
            id          BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            customer_id BIGINT(20) UNSIGNED NOT NULL,
            tag_id      BIGINT(20) UNSIGNED NOT NULL,
            source      VARCHAR(10)         NOT NULL DEFAULT 'rule',
            created_at  DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY customer_tag (customer_id, tag_id),
            KEY tag_id (tag_id)
        ) {$c};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
		dbDelta( $sql );

		update_option( self::VERSION_OPTION, self::DB_VERSION );
	}

	/**
	 * Run install() only when the stored DB version is outdated.
	 */
	public static function maybe_upgrade(): void {
		if ( get_option( self::VERSION_OPTION ) !== self::DB_VERSION ) {
			self::install();
		}
	}

	/**
	 * Drop all plugin tables on uninstall.
	 */
	public static function uninstall(): void {
		global $wpdb;
		$p = $wpdb->prefix;
		foreach ( array(
			'cm_tagger_customer_tags',
			'cm_tagger_order_tags',
			'cm_tagger_rules',
			'cm_tagger_tags',
		) as $table ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->query( "DROP TABLE IF EXISTS {$p}{$table}" );
		}
		delete_option( self::VERSION_OPTION );
	}

	/**
	 * BC shim — callers in the main plugin file used maybeUpgrade().
	 *
	 * @deprecated Use maybe_upgrade() instead.
	 */
	public static function maybeUpgrade(): void { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
		self::maybe_upgrade();
	}
}
