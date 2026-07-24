<?php
/**
 * CRUD operations for the cm_tagger_rules table.
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
 * Repository for rule records in the cm_tagger_rules table.
 */
class RuleRepository {

	/**
	 * Allowed trigger values.
	 *
	 * @var string[]
	 */
	public const VALID_TRIGGERS = array( 'order_placed' );

	/**
	 * Allowed operator values.
	 *
	 * @var string[]
	 */
	public const VALID_OPERATORS = array( 'AND', 'OR' );

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
		$this->table = $wpdb->prefix . 'cm_tagger_rules';
	}

	/**
	 * All rules for a given tag, ordered by priority ASC.
	 *
	 * @param int $tag_id Parent tag ID.
	 * @return array<int, array<string, mixed>>
	 */
	public function find_by_tag_id( int $tag_id ): array {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM %i WHERE tag_id = %d ORDER BY priority ASC, id ASC',
				$this->table,
				$tag_id
			),
			ARRAY_A
		);
		$rows = $rows ? $rows : array();

		return array_map( array( $this, 'decode_conditions' ), $rows );
	}

	/**
	 * Single rule with decoded conditions, or null if not found.
	 *
	 * @param int $id Rule ID.
	 * @return array<string, mixed>|null
	 */
	public function find_by_id( int $id ): ?array {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$row = $wpdb->get_row(
			$wpdb->prepare( 'SELECT * FROM %i WHERE id = %d', $this->table, $id ),
			ARRAY_A
		);

		return $row ? $this->decode_conditions( $row ) : null;
	}

	/**
	 * Insert a new rule. Returns the new id, or 0 on failure.
	 *
	 * @param int   $tag_id Parent tag ID.
	 * @param array $data   Rule data (label, rule_trigger, operator, conditions, priority, is_active).
	 * @return int
	 */
	public function insert( int $tag_id, array $data ): int {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- wpdb->insert() is the canonical WP insert method.
		$wpdb->insert( $this->table, $this->sanitize( $tag_id, $data ) );
		return (int) $wpdb->insert_id;
	}

	/**
	 * Update an existing rule. Returns true on success.
	 *
	 * @param int   $id   Rule ID.
	 * @param array $data Fields to update.
	 * @return bool
	 */
	public function update( int $id, array $data ): bool {
		global $wpdb;
		// Fetch tag_id from existing row so sanitize() can use it; fall back to 0.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$existing = $wpdb->get_row(
			$wpdb->prepare( 'SELECT tag_id FROM %i WHERE id = %d', $this->table, $id ),
			ARRAY_A
		);
		$tag_id   = $existing ? (int) $existing['tag_id'] : 0;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->update(
			$this->table,
			$this->sanitize( $tag_id, $data ),
			array( 'id' => $id )
		);
		return false !== $result;
	}

	/**
	 * Delete a rule by ID. Returns true when a row was removed.
	 *
	 * @param int $id Rule ID.
	 * @return bool
	 */
	public function delete( int $id ): bool {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return (bool) $wpdb->delete( $this->table, array( 'id' => $id ) );
	}

	/**
	 * Count rules for a given tag.
	 *
	 * @param int $tag_id Parent tag ID.
	 * @return int
	 */
	public function count_by_tag_id( int $tag_id ): int {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return (int) $wpdb->get_var(
			$wpdb->prepare( 'SELECT COUNT(*) FROM %i WHERE tag_id = %d', $this->table, $tag_id )
		);
	}

	// ── Helpers ───────────────────────────────────────────────────────────────

	/**
	 * Decode the JSON conditions field in a rule row.
	 *
	 * @param array<string, mixed> $row Raw DB row.
	 * @return array<string, mixed>
	 */
	private function decode_conditions( array $row ): array {
		if ( isset( $row['conditions'] ) && is_string( $row['conditions'] ) ) {
			$decoded           = json_decode( $row['conditions'], true );
			$row['conditions'] = is_array( $decoded ) ? $decoded : array();
		}
		return $row;
	}

	/**
	 * Sanitise and encode data for DB writes.
	 *
	 * @param int   $tag_id Parent tag ID.
	 * @param array $data   Raw input data.
	 * @return array<string, mixed>
	 */
	private function sanitize( int $tag_id, array $data ): array {
		$out = array();

		if ( $tag_id > 0 ) {
			$out['tag_id'] = $tag_id;
		}

		if ( isset( $data['label'] ) ) {
			$out['label'] = substr( trim( (string) $data['label'] ), 0, 100 );
		}

		if ( isset( $data['rule_trigger'] ) ) {
			$trigger             = (string) $data['rule_trigger'];
			$out['rule_trigger'] = in_array( $trigger, self::VALID_TRIGGERS, true )
				? $trigger
				: 'order_placed';
		}

		if ( isset( $data['operator'] ) ) {
			$op              = strtoupper( trim( (string) $data['operator'] ) );
			$out['operator'] = in_array( $op, self::VALID_OPERATORS, true ) ? $op : 'AND';
		}

		if ( isset( $data['conditions'] ) ) {
			$conditions = $data['conditions'];
			if ( is_array( $conditions ) ) {
				$out['conditions'] = (string) wp_json_encode( $conditions );
			} else {
				// Already a JSON string — validate round-trip.
				$decoded           = json_decode( (string) $conditions, true );
				$out['conditions'] = is_array( $decoded )
					? (string) wp_json_encode( $decoded )
					: '[]';
			}
		}

		if ( isset( $data['priority'] ) ) {
			$out['priority'] = max( 0, (int) $data['priority'] );
		}

		if ( isset( $data['is_active'] ) ) {
			$out['is_active'] = $data['is_active'] ? 1 : 0;
		}

		return $out;
	}

	// ── BC shims ──────────────────────────────────────────────────────────────

	/**
	 * BC shim for callers using the old camelCase method name.
	 *
	 * @param int $tag_id Parent tag ID.
	 * @return array<int, array<string, mixed>>
	 * @deprecated Use find_by_tag_id() instead.
	 */
	public function findByTagId( int $tag_id ): array { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
		return $this->find_by_tag_id( $tag_id );
	}

	/**
	 * BC shim for callers using the old camelCase method name.
	 *
	 * @param int $id Rule ID.
	 * @return array<string, mixed>|null
	 * @deprecated Use find_by_id() instead.
	 */
	public function findById( int $id ): ?array { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
		return $this->find_by_id( $id );
	}

	/**
	 * BC shim for callers using the old camelCase method name.
	 *
	 * @param int $tag_id Parent tag ID.
	 * @return int
	 * @deprecated Use count_by_tag_id() instead.
	 */
	public function countByTagId( int $tag_id ): int { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
		return $this->count_by_tag_id( $tag_id );
	}
}
