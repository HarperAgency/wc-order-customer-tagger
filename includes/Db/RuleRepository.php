<?php
/**
 * CRUD operations for the harper_tagger_rules table.
 * All public methods accept/return plain arrays — no WP objects leak out.
 */
declare(strict_types=1);

namespace HarperAgency\WCTagger\Db;

if (!defined('ABSPATH')) exit;

class RuleRepository
{
    public const VALID_TRIGGERS  = ['order_placed'];
    public const VALID_OPERATORS = ['AND', 'OR'];

    private string $table;

    public function __construct()
    {
        global $wpdb;
        $this->table = $wpdb->prefix . 'harper_tagger_rules';
    }

    /**
     * All rules for a given tag, ordered by priority ASC.
     *
     * @return array<int, array<string, mixed>>
     */
    public function findByTagId(int $tagId): array
    {
        global $wpdb;
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->table} WHERE tag_id = %d ORDER BY priority ASC, id ASC",
                $tagId
            ),
            ARRAY_A
        ) ?: [];

        return array_map([$this, 'decodeConditions'], $rows);
    }

    /**
     * Single rule with decoded conditions, or null if not found.
     *
     * @return array<string, mixed>|null
     */
    public function findById(int $id): ?array
    {
        global $wpdb;
        $row = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$this->table} WHERE id = %d", $id),
            ARRAY_A
        );

        return $row ? $this->decodeConditions($row) : null;
    }

    /**
     * Insert a new rule. Returns the new id, or 0 on failure.
     *
     * @param array<string, mixed> $data
     */
    public function insert(int $tagId, array $data): int
    {
        global $wpdb;
        $wpdb->insert($this->table, $this->sanitize($tagId, $data));
        return (int) $wpdb->insert_id;
    }

    /**
     * Update an existing rule. Returns true on success.
     *
     * @param array<string, mixed> $data
     */
    public function update(int $id, array $data): bool
    {
        global $wpdb;
        // Fetch tag_id from existing row so sanitize() can use it; fall back to 0.
        $existing = $wpdb->get_row(
            $wpdb->prepare("SELECT tag_id FROM {$this->table} WHERE id = %d", $id),
            ARRAY_A
        );
        $tagId = $existing ? (int) $existing['tag_id'] : 0;

        $result = $wpdb->update(
            $this->table,
            $this->sanitize($tagId, $data),
            ['id' => $id]
        );
        return $result !== false;
    }

    public function delete(int $id): bool
    {
        global $wpdb;
        return (bool) $wpdb->delete($this->table, ['id' => $id]);
    }

    public function countByTagId(int $tagId): int
    {
        global $wpdb;
        return (int) $wpdb->get_var(
            $wpdb->prepare("SELECT COUNT(*) FROM {$this->table} WHERE tag_id = %d", $tagId)
        );
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Decode the JSON conditions field in a rule row.
     *
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function decodeConditions(array $row): array
    {
        if (isset($row['conditions']) && is_string($row['conditions'])) {
            $decoded = json_decode($row['conditions'], true);
            $row['conditions'] = is_array($decoded) ? $decoded : [];
        }
        return $row;
    }

    /**
     * Sanitise and encode data for DB writes.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function sanitize(int $tagId, array $data): array
    {
        $out = [];

        if ($tagId > 0) {
            $out['tag_id'] = $tagId;
        }

        if (isset($data['label'])) {
            $out['label'] = substr(trim((string) $data['label']), 0, 100);
        }

        if (isset($data['rule_trigger'])) {
            $trigger = (string) $data['rule_trigger'];
            $out['rule_trigger'] = in_array($trigger, self::VALID_TRIGGERS, true)
                ? $trigger
                : 'order_placed';
        }

        if (isset($data['operator'])) {
            $op = strtoupper(trim((string) $data['operator']));
            $out['operator'] = in_array($op, self::VALID_OPERATORS, true) ? $op : 'AND';
        }

        if (isset($data['conditions'])) {
            $conditions = $data['conditions'];
            if (is_array($conditions)) {
                $out['conditions'] = (string) json_encode($conditions);
            } else {
                // Already a JSON string — validate round-trip.
                $decoded = json_decode((string) $conditions, true);
                $out['conditions'] = is_array($decoded)
                    ? (string) json_encode($decoded)
                    : '[]';
            }
        }

        if (isset($data['priority'])) {
            $out['priority'] = max(0, (int) $data['priority']);
        }

        if (isset($data['is_active'])) {
            $out['is_active'] = $data['is_active'] ? 1 : 0;
        }

        return $out;
    }
}
