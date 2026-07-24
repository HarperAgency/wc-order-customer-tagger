<?php
/**
 * Tagger — loads rules from DB, runs the engine, persists tag assignments.
 *
 * Called by WC hooks. The only class allowed to touch both the rule engine
 * and the DB tag-assignment tables.
 *
 * @package ClarityMomentum\WCTagger\RuleEngine
 */

declare(strict_types=1);

namespace ClarityMomentum\WCTagger\RuleEngine;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use HarperAgency\RuleEngine\Condition;
use HarperAgency\RuleEngine\Rule;
use HarperAgency\RuleEngine\Engine;

/**
 * Evaluates and persists tag assignments for orders and customers.
 */
class Tagger {

	/**
	 * Shared context builder instance.
	 *
	 * @var ContextBuilder
	 */
	private ContextBuilder $ctx;

	/**
	 * Initialise the tagger with a ContextBuilder instance.
	 */
	public function __construct() {
		$this->ctx = new ContextBuilder();
	}

	/**
	 * Evaluate and persist tags for a WC_Order.
	 * Runs both order-level and customer-level rules.
	 *
	 * @param \WC_Order $order WooCommerce order object.
	 */
	public function tag_order( \WC_Order $order ): void {
		global $wpdb;
		$p = $wpdb->prefix;

		// ── Order rules ───────────────────────────────────────────────────────
		$order_rules = $this->load_rules( 'order' );
		if ( $order_rules ) {
			$context     = $this->ctx->from_order( $order );
			$engine      = new Engine( $order_rules );
			$matched_ids = $engine->evaluate( $context );
			$this->persist_order_tags( (int) $order->get_id(), $matched_ids );
		}

		// ── Customer rules ────────────────────────────────────────────────────
		$customer_id = (int) $order->get_customer_id();
		if ( $customer_id > 0 ) {
			$customer_rules = $this->load_rules( 'customer' );
			if ( $customer_rules ) {
				$customer    = new \WC_Customer( $customer_id );
				$context     = $this->ctx->from_customer( $customer );
				$engine      = new Engine( $customer_rules );
				$matched_ids = $engine->evaluate( $context );
				$this->persist_customer_tags( $customer_id, $matched_ids );
			}
		}
	}

	// ── Private ───────────────────────────────────────────────────────────────

	/**
	 * Load active rules for a given tag type ('order' or 'customer').
	 *
	 * @param string $tag_type Tag type to load rules for ('order' or 'customer').
	 * @return Rule[]
	 */
	private function load_rules( string $tag_type ): array {
		global $wpdb;
		$p = $wpdb->prefix;

		$rules_table = $p . 'cm_tagger_rules';
		$tags_table  = $p . 'cm_tagger_tags';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT r.id, r.tag_id, r.operator, r.conditions
                 FROM %i r
                 JOIN %i t ON t.id = r.tag_id
                 WHERE r.is_active = 1
                   AND t.type IN (%s, 'both')
                 ORDER BY r.priority ASC, r.id ASC",
				$rules_table,
				$tags_table,
				$tag_type
			),
			ARRAY_A
		);

		if ( ! $rows ) {
			return array();
		}

		$rules = array();
		foreach ( $rows as $row ) {
			$raw = json_decode( (string) $row['conditions'], true );
			if ( ! is_array( $raw ) ) {
				continue;
			}

			$conditions = array();
			foreach ( $raw as $c ) {
				if ( isset( $c['field'], $c['operator'], $c['value'] ) ) {
					$conditions[] = new Condition(
						(string) $c['field'],
						(string) $c['operator'],
						$c['value']
					);
				}
			}

			if ( $conditions ) {
				$rules[] = new Rule( $conditions, (string) $row['operator'], (int) $row['tag_id'] );
			}
		}

		return $rules;
	}

	/**
	 * Upsert order-to-tag assignments. Skips duplicates silently.
	 *
	 * @param int   $order_id The WC order ID.
	 * @param int[] $tag_ids  Array of tag IDs to assign.
	 */
	private function persist_order_tags( int $order_id, array $tag_ids ): void {
		global $wpdb;
		$table = $wpdb->prefix . 'cm_tagger_order_tags';

		foreach ( $tag_ids as $tag_id ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->query(
				$wpdb->prepare(
					"INSERT IGNORE INTO %i (order_id, tag_id, source) VALUES (%d, %d, 'rule')",
					$table,
					$order_id,
					$tag_id
				)
			);
		}
	}

	/**
	 * Upsert customer-to-tag assignments. Skips duplicates silently.
	 *
	 * @param int   $customer_id The WP user ID.
	 * @param int[] $tag_ids     Array of tag IDs to assign.
	 */
	private function persist_customer_tags( int $customer_id, array $tag_ids ): void {
		global $wpdb;
		$table = $wpdb->prefix . 'cm_tagger_customer_tags';

		foreach ( $tag_ids as $tag_id ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->query(
				$wpdb->prepare(
					"INSERT IGNORE INTO %i (customer_id, tag_id, source) VALUES (%d, %d, 'rule')",
					$table,
					$customer_id,
					$tag_id
				)
			);
		}
	}

	// ── BC shim ───────────────────────────────────────────────────────────────

	/**
	 * BC shim for callers using the old camelCase method name.
	 *
	 * @param \WC_Order $order WooCommerce order object.
	 * @deprecated Use tag_order() instead.
	 */
	public function tagOrder( \WC_Order $order ): void { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
		$this->tag_order( $order );
	}
}
