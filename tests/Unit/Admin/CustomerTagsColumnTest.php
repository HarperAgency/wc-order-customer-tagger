<?php
/**
 * CustomerTagsColumnTest — unit tests for the Users list table Tags column.
 */
declare(strict_types=1);

namespace HarperAgency\WCTagger\Tests\Unit\Admin;

use PHPUnit\Framework\TestCase;
use HarperAgency\WCTagger\Admin\CustomerTagsColumn;

class CustomerTagsColumnTest extends TestCase
{
    private CustomerTagsColumn $col;

    protected function setUp(): void
    {
        $this->col = new CustomerTagsColumn();
    }

    // ── addColumn ─────────────────────────────────────────────────────────────

    public function testAddsHarperCustomerTagsKey(): void
    {
        $result = $this->col->addColumn(['username' => 'Username', 'email' => 'Email']);
        $this->assertArrayHasKey('harper_customer_tags', $result);
    }

    public function testColumnLabelIsCorrect(): void
    {
        $result = $this->col->addColumn([]);
        $this->assertSame('Tags', $result['harper_customer_tags']);
    }

    public function testExistingColumnsPreserved(): void
    {
        $original = ['username' => 'Username', 'email' => 'Email', 'role' => 'Role'];
        $result   = $this->col->addColumn($original);
        foreach (array_keys($original) as $key) {
            $this->assertArrayHasKey($key, $result, "Original column '$key' must be preserved");
        }
    }

    // ── renderColumn ──────────────────────────────────────────────────────────

    public function testIgnoresOtherColumns(): void
    {
        global $wpdb;
        $wpdb = $this->makeWpdb([]);

        $result = $this->col->renderColumn('', 'email', 1);
        $this->assertSame('', $result, 'Non-harper_customer_tags columns should pass through empty');
    }

    public function testHasClickableWrapper(): void
    {
        global $wpdb;
        $wpdb = $this->makeWpdb([]);

        $result = $this->col->renderColumn('', 'harper_customer_tags', 7);
        $this->assertStringContainsString('harper-tagger-cell', $result);
        $this->assertStringContainsString('data-type="customer"', $result);
        $this->assertStringContainsString('data-id="7"', $result);
    }

    public function testShowsDashWhenNoTags(): void
    {
        global $wpdb;
        $wpdb = $this->makeWpdb([]);

        $result = $this->col->renderColumn('', 'harper_customer_tags', 1);
        $this->assertStringContainsString('—', $result);
    }

    public function testShowsBadgeForColorTag(): void
    {
        global $wpdb;
        $wpdb = $this->makeWpdb([
            ['id' => 1, 'name' => 'loyal', 'color' => '#8e44ad', 'image_url' => null],
        ]);

        $result = $this->col->renderColumn('', 'harper_customer_tags', 5);
        $this->assertStringContainsString('loyal', $result);
        $this->assertStringContainsString('#8e44ad', $result);
        $this->assertStringContainsString('harper-tagger-badge', $result);
    }

    public function testShowsImgForIconTag(): void
    {
        global $wpdb;
        $wpdb = $this->makeWpdb([
            ['id' => 3, 'name' => 'vip', 'color' => '#000', 'image_url' => 'https://example.com/crown.png'],
        ]);

        $result = $this->col->renderColumn('', 'harper_customer_tags', 7);
        $this->assertStringContainsString('<img', $result);
        $this->assertStringContainsString('crown.png', $result);
        $this->assertStringNotContainsString('harper-tagger-badge', $result);
    }

    public function testShowsMultipleTags(): void
    {
        global $wpdb;
        $wpdb = $this->makeWpdb([
            ['id' => 1, 'name' => 'loyal',      'color' => '#8e44ad', 'image_url' => null],
            ['id' => 2, 'name' => 'high-spend',  'color' => '#e74c3c', 'image_url' => null],
        ]);

        $result = $this->col->renderColumn('', 'harper_customer_tags', 9);
        $this->assertStringContainsString('loyal', $result);
        $this->assertStringContainsString('high-spend', $result);
    }

    public function testReturnsStringNotVoid(): void
    {
        global $wpdb;
        $wpdb = $this->makeWpdb([]);

        $result = $this->col->renderColumn('', 'harper_customer_tags', 1);
        $this->assertIsString($result);
    }

    public function testOutputIsXssClean(): void
    {
        global $wpdb;
        $wpdb = $this->makeWpdb([
            ['id' => 1, 'name' => '<script>alert(1)</script>', 'color' => '#000', 'image_url' => null],
        ]);

        $result = $this->col->renderColumn('', 'harper_customer_tags', 1);
        $this->assertStringNotContainsString('<script>', $result,
            'Tag name must be escaped before output');
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
}
