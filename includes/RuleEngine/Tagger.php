<?php
/**
 * Tagger — loads rules from DB, runs the engine, persists tag assignments.
 *
 * Called by WC hooks. The only class allowed to touch both the rule engine
 * and the DB tag-assignment tables.
 */
declare(strict_types=1);

namespace HarperAgency\WCTagger\RuleEngine;

if (!defined('ABSPATH')) exit;

use HarperAgency\RuleEngine\Condition;
use HarperAgency\RuleEngine\Rule;
use HarperAgency\RuleEngine\Engine;

class Tagger
{
    private ContextBuilder $ctx;

    public function __construct()
    {
        $this->ctx = new ContextBuilder();
    }

    /**
     * Evaluate and persist tags for a WC_Order.
     * Runs both order-level and customer-level rules.
     */
    public function tagOrder(\WC_Order $order): void
    {
        global $wpdb;
        $p = $wpdb->prefix;

        // ── Order rules ───────────────────────────────────────────────────────
        $orderRules = $this->loadRules('order');
        if ($orderRules) {
            $context    = $this->ctx->fromOrder($order);
            $engine     = new Engine($orderRules);
            $matchedIds = $engine->evaluate($context);
            $this->persistOrderTags((int) $order->get_id(), $matchedIds);
        }

        // ── Customer rules ────────────────────────────────────────────────────
        $customerId = (int) $order->get_customer_id();
        if ($customerId > 0) {
            $customerRules = $this->loadRules('customer');
            if ($customerRules) {
                $customer = new \WC_Customer($customerId);
                $context  = $this->ctx->fromCustomer($customer);
                $engine   = new Engine($customerRules);
                $matchedIds = $engine->evaluate($context);
                $this->persistCustomerTags($customerId, $matchedIds);
            }
        }
    }

    // ── Private ───────────────────────────────────────────────────────────────

    /**
     * Load active rules for a given tag type ('order' or 'customer').
     *
     * @return Rule[]
     */
    private function loadRules(string $tagType): array
    {
        global $wpdb;
        $p = $wpdb->prefix;

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT r.id, r.tag_id, r.operator, r.conditions
                 FROM {$p}harper_tagger_rules r
                 JOIN {$p}harper_tagger_tags  t ON t.id = r.tag_id
                 WHERE r.is_active = 1
                   AND t.type IN (%s, 'both')
                 ORDER BY r.priority ASC, r.id ASC",
                $tagType
            ),
            ARRAY_A
        );

        if (!$rows) {
            return [];
        }

        $rules = [];
        foreach ($rows as $row) {
            $raw = json_decode((string) $row['conditions'], true);
            if (!is_array($raw)) {
                continue;
            }

            $conditions = [];
            foreach ($raw as $c) {
                if (isset($c['field'], $c['operator'], $c['value'])) {
                    $conditions[] = new Condition(
                        (string) $c['field'],
                        (string) $c['operator'],
                        $c['value']
                    );
                }
            }

            if ($conditions) {
                $rules[] = new Rule($conditions, (string) $row['operator'], (int) $row['tag_id']);
            }
        }

        return $rules;
    }

    /**
     * Upsert order→tag assignments. Skips duplicates silently.
     *
     * @param int[] $tagIds
     */
    private function persistOrderTags(int $orderId, array $tagIds): void
    {
        global $wpdb;
        $table = $wpdb->prefix . 'harper_tagger_order_tags';

        foreach ($tagIds as $tagId) {
            $wpdb->query(
                $wpdb->prepare(
                    "INSERT IGNORE INTO {$table} (order_id, tag_id, source)
                     VALUES (%d, %d, 'rule')",
                    $orderId,
                    $tagId
                )
            );
        }
    }

    /**
     * Upsert customer→tag assignments. Skips duplicates silently.
     *
     * @param int[] $tagIds
     */
    private function persistCustomerTags(int $customerId, array $tagIds): void
    {
        global $wpdb;
        $table = $wpdb->prefix . 'harper_tagger_customer_tags';

        foreach ($tagIds as $tagId) {
            $wpdb->query(
                $wpdb->prepare(
                    "INSERT IGNORE INTO {$table} (customer_id, tag_id, source)
                     VALUES (%d, %d, 'rule')",
                    $customerId,
                    $tagId
                )
            );
        }
    }
}
