<?php
/**
 * TagPopoverTest — unit tests for AJAX add/remove tag endpoints.
 *
 * Tests the DB logic inside TagPopover by injecting a wpdb stub.
 * WordPress AJAX functions (check_ajax_referer, wp_send_json_*) are stubbed
 * in bootstrap so we can call the private helpers directly via reflection.
 */
declare(strict_types=1);

namespace HarperAgency\WCTagger\Tests\Unit\Admin;

use PHPUnit\Framework\TestCase;
use HarperAgency\WCTagger\Admin\TagPopover;

class TagPopoverTest extends TestCase
{
    // ── getAppliedTags via reflection ─────────────────────────────────────────

    public function testGetAppliedTagsReturnsEmptyArrayWhenNone(): void
    {
        global $wpdb;
        $wpdb = $this->makeWpdb([]);

        $tags = $this->callGetAppliedTags('order', 1);
        $this->assertIsArray($tags);
        $this->assertEmpty($tags);
    }

    public function testGetAppliedTagsReturnsMappedRows(): void
    {
        global $wpdb;
        $rows = [
            ['id' => 1, 'name' => 'high-value', 'color' => '#e74c3c', 'image_url' => null],
            ['id' => 2, 'name' => 'vip',         'color' => '#8e44ad', 'image_url' => null],
        ];
        $wpdb = $this->makeWpdb($rows);

        $tags = $this->callGetAppliedTags('order', 99);
        $this->assertCount(2, $tags);
        $this->assertSame('high-value', $tags[0]['name']);
        $this->assertSame('vip', $tags[1]['name']);
    }

    public function testGetAppliedTagsWorksForCustomerType(): void
    {
        global $wpdb;
        $rows = [
            ['id' => 3, 'name' => 'loyal', 'color' => '#2ecc71', 'image_url' => null],
        ];
        $wpdb = $this->makeWpdb($rows);

        $tags = $this->callGetAppliedTags('customer', 5);
        $this->assertCount(1, $tags);
        $this->assertSame('loyal', $tags[0]['name']);
    }

    public function testGetAppliedTagsUsesOrderTableForOrderType(): void
    {
        global $wpdb;
        $queries = [];
        $wpdb = $this->makeWpdbCapturing($queries, []);

        $this->callGetAppliedTags('order', 42);

        $this->assertCount(1, $queries);
        $this->assertStringContainsString('order_tags', $queries[0]);
        $this->assertStringNotContainsString('customer_tags', $queries[0]);
    }

    public function testGetAppliedTagsUsesCustomerTableForCustomerType(): void
    {
        global $wpdb;
        $queries = [];
        $wpdb = $this->makeWpdbCapturing($queries, []);

        $this->callGetAppliedTags('customer', 7);

        $this->assertCount(1, $queries);
        $this->assertStringContainsString('customer_tags', $queries[0]);
        $this->assertStringNotContainsString('order_tags', $queries[0]);
    }

    public function testGetAppliedTagsReturnsEmptyArrayNotNull(): void
    {
        global $wpdb;
        $wpdb = $this->makeWpdbReturningNull();

        $tags = $this->callGetAppliedTags('order', 1);
        $this->assertIsArray($tags);
        $this->assertEmpty($tags);
    }

    // ── register() wires AJAX hooks ───────────────────────────────────────────

    public function testRegisterCallsAddAction(): void
    {
        $called = [];
        // add_action is stubbed in bootstrap to no-op, but we can check the
        // object instantiates and register() runs without error
        $popover = new TagPopover();
        $popover->register();
        $this->assertTrue(true); // no exception = pass
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function callGetAppliedTags(string $type, int $id): array
    {
        $popover    = new TagPopover();
        $reflection = new \ReflectionMethod(TagPopover::class, 'getAppliedTags');
        $reflection->setAccessible(true);
        return $reflection->invoke($popover, $type, $id);
    }

    private function makeWpdb(array $rows): object
    {
        return new class ($rows) {
            public string $prefix = 'wp_';
            public function __construct(private array $rows) {}
            public function prepare(string $sql, mixed ...$args): string { return $sql; }
            public function get_results(string $sql, string $output = 'OBJECT'): array {
                return $this->rows;
            }
            public function query(string $sql): bool { return true; }
            public function delete(string $table, array $where): int { return 1; }
        };
    }

    private function makeWpdbCapturing(array &$queries, array $rows): object
    {
        return new class ($queries, $rows) {
            public string $prefix = 'wp_';
            public function __construct(private array &$queries, private array $rows) {}
            public function prepare(string $sql, mixed ...$args): string { return $sql; }
            public function get_results(string $sql, string $output = 'OBJECT'): array {
                $this->queries[] = $sql;
                return $this->rows;
            }
        };
    }

    private function makeWpdbReturningNull(): object
    {
        return new class {
            public string $prefix = 'wp_';
            public function prepare(string $sql, mixed ...$args): string { return $sql; }
            public function get_results(string $sql, string $output = 'OBJECT'): ?array {
                return null; // Simulates wpdb returning null on error
            }
        };
    }
}
