<?php
/**
 * CRUD operations for the harper_tagger_tags table.
 * All public methods accept/return plain arrays — no WP objects leak out.
 */
declare(strict_types=1);

namespace HarperAgency\WCTagger\Db;

if (!defined('ABSPATH')) exit;

class TagRepository
{
    public const VALID_TYPES = ['order', 'customer', 'both'];

    private string $table;

    public function __construct()
    {
        global $wpdb;
        $this->table = $wpdb->prefix . 'harper_tagger_tags';
    }

    /** @return array<int, array<string, mixed>> */
    public function findAll(): array
    {
        global $wpdb;
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        return $wpdb->get_results("SELECT * FROM {$this->table} ORDER BY name ASC", ARRAY_A) ?: [];
    }

    /** @return array<string, mixed>|null */
    public function findById(int $id): ?array
    {
        global $wpdb;
        $row = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$this->table} WHERE id = %d", $id),
            ARRAY_A
        );
        return $row ?: null;
    }

    /** @return array<string, mixed>|null */
    public function findBySlug(string $slug): ?array
    {
        global $wpdb;
        $row = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$this->table} WHERE slug = %s", $slug),
            ARRAY_A
        );
        return $row ?: null;
    }

    /**
     * Insert a new tag. Returns the new id, or 0 on failure.
     *
     * @param array{name: string, color?: string, type?: string, image_url?: string|null} $data
     */
    public function insert(array $data): int
    {
        global $wpdb;
        $wpdb->insert($this->table, $this->sanitize($data));
        return (int) $wpdb->insert_id;
    }

    /**
     * Update an existing tag. Returns true on success.
     *
     * @param array{name?: string, color?: string, type?: string, image_url?: string|null} $data
     */
    public function update(int $id, array $data): bool
    {
        global $wpdb;
        $result = $wpdb->update(
            $this->table,
            $this->sanitize($data),
            ['id' => $id]
        );
        return $result !== false;
    }

    public function delete(int $id): bool
    {
        global $wpdb;
        return (bool) $wpdb->delete($this->table, ['id' => $id]);
    }

    public function countRulesForTag(int $tagId): int
    {
        global $wpdb;
        $rulesTable = $wpdb->prefix . 'harper_tagger_rules';
        return (int) $wpdb->get_var(
            $wpdb->prepare("SELECT COUNT(*) FROM {$rulesTable} WHERE tag_id = %d", $tagId)
        );
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Generate a unique slug from a name.
     * Falls back to appending -2, -3, etc. if the slug already exists.
     */
    public function uniqueSlug(string $name, int $excludeId = 0): string
    {
        $base = $this->slugify($name);
        $slug = $base;
        $i    = 2;

        while (true) {
            global $wpdb;
            $sql = $excludeId > 0
                ? $wpdb->prepare(
                    "SELECT id FROM {$this->table} WHERE slug = %s AND id != %d LIMIT 1",
                    $slug, $excludeId
                )
                : $wpdb->prepare(
                    "SELECT id FROM {$this->table} WHERE slug = %s LIMIT 1",
                    $slug
                );

            if (!$wpdb->get_var($sql)) {
                break;
            }
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }

    /** Convert a name to a URL-safe slug without requiring WP functions. */
    public function slugify(string $name): string
    {
        $slug = strtolower(trim($name));
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug) ?? $slug;
        return trim($slug, '-');
    }

    /** Validate and normalise a hex color string. Returns default on invalid input. */
    public function normaliseColor(string $color): string
    {
        $color = strtolower(trim($color));
        if (preg_match('/^#[0-9a-f]{6}$/', $color)) {
            return $color;
        }
        // Expand 3-char shorthand (#abc → #aabbcc)
        if (preg_match('/^#([0-9a-f])([0-9a-f])([0-9a-f])$/', $color, $m)) {
            return "#{$m[1]}{$m[1]}{$m[2]}{$m[2]}{$m[3]}{$m[3]}";
        }
        return '#3788d8';
    }

    /** @param array<string, mixed> $data */
    private function sanitize(array $data): array
    {
        $out = [];

        if (isset($data['name'])) {
            $out['name'] = substr(trim((string) $data['name']), 0, 100);
        }
        if (isset($data['slug'])) {
            $out['slug'] = substr($this->slugify((string) $data['slug']), 0, 100);
        }
        if (isset($data['color'])) {
            $out['color'] = $this->normaliseColor((string) $data['color']);
        }
        if (isset($data['type'])) {
            $type        = (string) $data['type'];
            $out['type'] = in_array($type, self::VALID_TYPES, true) ? $type : 'order';
        }
        if (array_key_exists('image_url', $data)) {
            $out['image_url'] = $data['image_url'] !== null
                ? substr(trim((string) $data['image_url']), 0, 500)
                : null;
        }

        return $out;
    }
}
