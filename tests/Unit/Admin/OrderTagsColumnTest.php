<?php
/**
 * OrderTagsColumnTest — unit tests for the Orders list table Tags column.
 */
declare(strict_types=1);

namespace HarperAgency\WCTagger\Tests\Unit\Admin;

use PHPUnit\Framework\TestCase;
use HarperAgency\WCTagger\Admin\OrderTagsColumn;

class OrderTagsColumnTest extends TestCase
{
    private OrderTagsColumn $col;

    protected function setUp(): void
    {
        $this->col = new OrderTagsColumn();
    }

    // ── addColumn ─────────────────────────────────────────────────────────────

    public function testAddsHarperTagsKey(): void
    {
        $result = $this->col->addColumn(['order_status' => 'Status', 'order_total' => 'Total']);
        $this->assertArrayHasKey('harper_tags', $result);
    }

    public function testColumnLabelIsCorrect(): void
    {
        $result = $this->col->addColumn(['order_status' => 'Status']);
        $this->assertSame('Tags', $result['harper_tags']);
    }

    public function testColumnInsertedAfterOrderStatus(): void
    {
        $result = $this->col->addColumn([
            'order_number' => 'Order',
            'order_status' => 'Status',
            'order_total'  => 'Total',
        ]);
        $keys = array_keys($result);
        $statusPos = array_search('order_status', $keys, true);
        $tagsPos   = array_search('harper_tags', $keys, true);
        $this->assertSame($statusPos + 1, $tagsPos,
            'Tags column should immediately follow order_status');
    }

    public function testColumnAddedEvenWhenOrderStatusAbsent(): void
    {
        // Fallback: if order_status is not in columns, Tags is still appended
        $result = $this->col->addColumn(['order_number' => 'Order', 'order_total' => 'Total']);
        $this->assertArrayHasKey('harper_tags', $result);
    }

    public function testExistingColumnsPreserved(): void
    {
        $original = ['order_number' => 'Order', 'order_status' => 'Status', 'order_total' => 'Total'];
        $result   = $this->col->addColumn($original);
        foreach (array_keys($original) as $key) {
            $this->assertArrayHasKey($key, $result, "Original column '$key' must be preserved");
        }
    }

    // ── renderColumn (HPOS) ───────────────────────────────────────────────────

    public function testRenderColumnIgnoresOtherColumns(): void
    {
        global $wpdb;
        $wpdb = $this->makeWpdb([]);

        $order = $this->makeOrder(1);
        ob_start();
        $this->col->renderColumn('order_total', $order);
        $output = ob_get_clean();
        $this->assertSame('', $output, 'Non-harper_tags columns should produce no output');
    }

    public function testRenderColumnHasClickableWrapper(): void
    {
        global $wpdb;
        $wpdb = $this->makeWpdb([]);

        $order = $this->makeOrder(42);
        ob_start();
        $this->col->renderColumn('harper_tags', $order);
        $output = ob_get_clean();
        $this->assertStringContainsString('harper-tagger-cell', $output);
        $this->assertStringContainsString('data-type="order"', $output);
        $this->assertStringContainsString('data-id="42"', $output);
    }

    public function testRenderColumnShowsDashWhenNoTags(): void
    {
        global $wpdb;
        $wpdb = $this->makeWpdb([]);

        $order = $this->makeOrder(42);
        ob_start();
        $this->col->renderColumn('harper_tags', $order);
        $output = ob_get_clean();
        $this->assertStringContainsString('—', $output);
    }

    public function testRenderColumnShowsBadgeForColorTag(): void
    {
        global $wpdb;
        $wpdb = $this->makeWpdb([
            ['id' => 1, 'name' => 'high-value', 'color' => '#e74c3c', 'image_url' => null],
        ]);

        $order = $this->makeOrder(5);
        ob_start();
        $this->col->renderColumn('harper_tags', $order);
        $output = ob_get_clean();
        $this->assertStringContainsString('high-value', $output);
        $this->assertStringContainsString('#e74c3c', $output);
        $this->assertStringContainsString('harper-tagger-badge', $output);
    }

    public function testRenderColumnShowsImgForIconTag(): void
    {
        global $wpdb;
        $wpdb = $this->makeWpdb([
            ['id' => 2, 'name' => 'vip', 'color' => '#000', 'image_url' => 'https://example.com/star.png'],
        ]);

        $order = $this->makeOrder(6);
        ob_start();
        $this->col->renderColumn('harper_tags', $order);
        $output = ob_get_clean();
        $this->assertStringContainsString('<img', $output);
        $this->assertStringContainsString('star.png', $output);
        $this->assertStringNotContainsString('harper-tagger-badge', $output);
    }

    public function testRenderColumnShowsMultipleTags(): void
    {
        global $wpdb;
        $wpdb = $this->makeWpdb([
            ['id' => 1, 'name' => 'high-value', 'color' => '#e74c3c', 'image_url' => null],
            ['id' => 2, 'name' => 'repeat',     'color' => '#2ecc71', 'image_url' => null],
        ]);

        $order = $this->makeOrder(7);
        ob_start();
        $this->col->renderColumn('harper_tags', $order);
        $output = ob_get_clean();
        $this->assertStringContainsString('high-value', $output);
        $this->assertStringContainsString('repeat', $output);
    }

    // ── renderLegacyColumn ────────────────────────────────────────────────────

    public function testLegacyColumnIgnoresOtherColumns(): void
    {
        global $wpdb;
        $wpdb = $this->makeWpdb([]);

        ob_start();
        $this->col->renderLegacyColumn('order_total', 99);
        $output = ob_get_clean();
        $this->assertSame('', $output);
    }

    public function testLegacyColumnHasClickableWrapper(): void
    {
        global $wpdb;
        $wpdb = $this->makeWpdb([]);

        ob_start();
        $this->col->renderLegacyColumn('harper_tags', 99);
        $output = ob_get_clean();
        $this->assertStringContainsString('harper-tagger-cell', $output);
        $this->assertStringContainsString('data-type="order"', $output);
        $this->assertStringContainsString('data-id="99"', $output);
    }

    public function testLegacyColumnShowsDashWhenNoTags(): void
    {
        global $wpdb;
        $wpdb = $this->makeWpdb([]);

        ob_start();
        $this->col->renderLegacyColumn('harper_tags', 99);
        $output = ob_get_clean();
        $this->assertStringContainsString('—', $output);
    }

    public function testLegacyColumnShowsBadge(): void
    {
        global $wpdb;
        $wpdb = $this->makeWpdb([
            ['id' => 1, 'name' => 'rush', 'color' => '#f39c12', 'image_url' => null],
        ]);

        ob_start();
        $this->col->renderLegacyColumn('harper_tags', 10);
        $output = ob_get_clean();
        $this->assertStringContainsString('rush', $output);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function makeWpdb(array $rows): object
    {
        return new class ($rows) {
            public string $prefix = 'wp_';
            public function __construct(private array $rows) {}
            public function prepare(string $sql, mixed ...$args): string { return $sql; }
            public function get_results(string $sql, string $output = 'OBJECT'): array {
                return $this->rows;
            }
        };
    }

    private function makeOrder(int $id): \WC_Order
    {
        return new class ($id) extends \WC_Order {
            public function __construct(private int $id) {}
            public function get_id(): int { return $this->id; }
        };
    }
}
