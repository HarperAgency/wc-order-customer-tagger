<?php
/**
 * CRUD operations for the cm_tagger_tags table.
 * All public methods accept/return plain arrays — no WP objects leak out.
 *
 * @package ClarityMomentum\WCTagger\Db
 */

declare(strict_types=1);

namespace ClarityMomentum\WCTagger\Db;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Repository for tag records in the cm_tagger_tags table.
 */
class TagRepository {

	/**
	 * Allowed values for the tag type column.
	 *
	 * @var string[]
	 */
	public const VALID_TYPES = array( 'order', 'customer', 'both' );

	/**
	 * Fully-qualified table name including WP prefix.
	 *
	 * @var string
	 */
	private string $table;

	/**
	 * Initialise the repository and resolve the table name.
	 */
	public function __construct() {
		global $wpdb;
		$this->table = $wpdb->prefix . 'cm_tagger_tags';
	}

	/**
	 * Return all tags ordered by name.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function find_all(): array {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$results = $wpdb->get_results(
			$wpdb->prepare( 'SELECT * FROM %i ORDER BY name ASC', $this->table ),
			ARRAY_A
		);
		return $results ? $results : array();
	}

	/**
	 * Find a single tag by primary key.
	 *
	 * @param int $id Tag ID.
	 * @return array<string, mixed>|null
	 */
	public function find_by_id( int $id ): ?array {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$row = $wpdb->get_row(
			$wpdb->prepare( 'SELECT * FROM %i WHERE id = %d', $this->table, $id ),
			ARRAY_A
		);
		return $row ? $row : null;
	}

	/**
	 * Find a single tag by its slug.
	 *
	 * @param string $slug URL-safe slug.
	 * @return array<string, mixed>|null
	 */
	public function find_by_slug( string $slug ): ?array {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$row = $wpdb->get_row(
			$wpdb->prepare( 'SELECT * FROM %i WHERE slug = %s', $this->table, $slug ),
			ARRAY_A
		);
		return $row ? $row : null;
	}

	/**
	 * Insert a new tag. Returns the new id, or 0 on failure.
	 *
	 * @param array{name: string, color?: string, type?: string, image_url?: string|null} $data Tag data.
	 * @return int
	 */
	public function insert( array $data ): int {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- wpdb->insert() is the canonical WP insert method.
		$wpdb->insert( $this->table, $this->sanitize( $data ) );
		return (int) $wpdb->insert_id;
	}

	/**
	 * Update an existing tag. Returns true on success.
	 *
	 * @param int   $id   Tag ID.
	 * @param array $data Fields to update (name, color, type, image_url).
	 * @return bool
	 */
	public function update( int $id, array $data ): bool {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->update(
			$this->table,
			$this->sanitize( $data ),
			array( 'id' => $id )
		);
		return false !== $result;
	}

	/**
	 * Delete a tag by ID. Returns true when a row was removed.
	 *
	 * @param int $id Tag ID.
	 * @return bool
	 */
	public function delete( int $id ): bool {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return (bool) $wpdb->delete( $this->table, array( 'id' => $id ) );
	}

	/**
	 * Count the number of rules referencing a given tag.
	 *
	 * @param int $tag_id Tag ID.
	 * @return int
	 */
	public function count_rules_for_tag( int $tag_id ): int {
		global $wpdb;
		$rules_table = $wpdb->prefix . 'cm_tagger_rules';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return (int) $wpdb->get_var(
			$wpdb->prepare( 'SELECT COUNT(*) FROM %i WHERE tag_id = %d', $rules_table, $tag_id )
		);
	}

	// ── Helpers ───────────────────────────────────────────────────────────────

	/**
	 * Generate a unique slug from a name.
	 * Falls back to appending -2, -3, etc. if the slug already exists.
	 *
	 * @param string $name      Source name for the slug.
	 * @param int    $exclude_id Exclude this ID when checking uniqueness (for edits).
	 * @return string
	 */
	public function unique_slug( string $name, int $exclude_id = 0 ): string {
		$base = $this->slugify( $name );
		$slug = $base;
		$i    = 2;

		while ( true ) {
			global $wpdb;
			$sql = $exclude_id > 0
				? $wpdb->prepare(
					'SELECT id FROM %i WHERE slug = %s AND id != %d LIMIT 1',
					$this->table,
					$slug,
					$exclude_id
				)
				: $wpdb->prepare(
					'SELECT id FROM %i WHERE slug = %s LIMIT 1',
					$this->table,
					$slug
				);

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
			if ( ! $wpdb->get_var( $sql ) ) {
				break;
			}
			$slug = "{$base}-{$i}";
			++$i;
		}

		return $slug;
	}

	/**
	 * Convert a name to a URL-safe slug without requiring WP functions.
	 *
	 * @param string $name Input name.
	 * @return string
	 */
	public function slugify( string $name ): string {
		$slug = strtolower( trim( $name ) );
		$slug = preg_replace( '/[^a-z0-9]+/', '-', $slug ) ?? $slug;
		return trim( $slug, '-' );
	}

	/**
	 * Validate and normalise a hex color string. Returns default on invalid input.
	 *
	 * @param string $color Raw color string.
	 * @return string
	 */
	public function normalise_color( string $color ): string {
		$color = strtolower( trim( $color ) );
		if ( preg_match( '/^#[0-9a-f]{6}$/', $color ) ) {
			return $color;
		}
		// Expand 3-char shorthand (#abc to #aabbcc).
		if ( preg_match( '/^#([0-9a-f])([0-9a-f])([0-9a-f])$/', $color, $m ) ) {
			return "#{$m[1]}{$m[1]}{$m[2]}{$m[2]}{$m[3]}{$m[3]}";
		}
		return '#3788d8';
	}

	/**
	 * Sanitise and normalise tag data before a DB write.
	 *
	 * @param array<string, mixed> $data Raw data array.
	 * @return array<string, mixed>
	 */
	private function sanitize( array $data ): array {
		$out = array();

		if ( isset( $data['name'] ) ) {
			$out['name'] = substr( trim( (string) $data['name'] ), 0, 100 );
		}
		if ( isset( $data['slug'] ) ) {
			$out['slug'] = substr( $this->slugify( (string) $data['slug'] ), 0, 100 );
		}
		if ( isset( $data['color'] ) ) {
			$out['color'] = $this->normalise_color( (string) $data['color'] );
		}
		if ( isset( $data['type'] ) ) {
			$type        = (string) $data['type'];
			$out['type'] = in_array( $type, self::VALID_TYPES, true ) ? $type : 'order';
		}
		if ( array_key_exists( 'image_url', $data ) ) {
			$out['image_url'] = null !== $data['image_url']
				? substr( trim( (string) $data['image_url'] ), 0, 500 )
				: null;
		}

		return $out;
	}

	// ── BC shims ──────────────────────────────────────────────────────────────

	/**
	 * BC shim for callers using the old camelCase method name.
	 *
	 * @return array<int, array<string, mixed>>
	 * @deprecated Use find_all() instead.
	 */
	public function findAll(): array { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
		return $this->find_all();
	}

	/**
	 * BC shim for callers using the old camelCase method name.
	 *
	 * @param int $id Tag ID.
	 * @return array<string, mixed>|null
	 * @deprecated Use find_by_id() instead.
	 */
	public function findById( int $id ): ?array { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
		return $this->find_by_id( $id );
	}

	/**
	 * BC shim for callers using the old camelCase method name.
	 *
	 * @param string $slug URL-safe slug.
	 * @return array<string, mixed>|null
	 * @deprecated Use find_by_slug() instead.
	 */
	public function findBySlug( string $slug ): ?array { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
		return $this->find_by_slug( $slug );
	}

	/**
	 * BC shim for callers using the old camelCase method name.
	 *
	 * @param int $tag_id Tag ID.
	 * @return int
	 * @deprecated Use count_rules_for_tag() instead.
	 */
	public function countRulesForTag( int $tag_id ): int { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
		return $this->count_rules_for_tag( $tag_id );
	}

	/**
	 * BC shim for callers using the old camelCase method name.
	 *
	 * @param string $name       Source name.
	 * @param int    $exclude_id ID to exclude from the uniqueness check.
	 * @return string
	 * @deprecated Use unique_slug() instead.
	 */
	public function uniqueSlug( string $name, int $exclude_id = 0 ): string { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
		return $this->unique_slug( $name, $exclude_id );
	}

	/**
	 * BC shim for callers using the old camelCase method name.
	 *
	 * @param string $color Raw color string.
	 * @return string
	 * @deprecated Use normalise_color() instead.
	 */
	public function normaliseColor( string $color ): string { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
		return $this->normalise_color( $color );
	}
}
