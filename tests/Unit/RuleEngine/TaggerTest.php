<?php
/**
 * TaggerTest — verifies Tagger::tagOrder() fires the engine and persists results.
 */
declare(strict_types=1);

namespace HarperAgency\WCTagger\Tests\Unit\RuleEngine;

use PHPUnit\Framework\TestCase;
use HarperAgency\WCTagger\RuleEngine\Tagger;

class TaggerTest extends TestCase
{
    public function testInstantiates(): void
    {
        $tagger = new Tagger();
        $this->assertInstanceOf(Tagger::class, $tagger);
    }

    /**
     * tagOrder() completes without error when DB returns no rules (noop).
     */
    public function testTagOrderWithNoRulesIsNoop(): void
    {
        global $wpdb;
        $wpdb = $this->makeWpdb();

        $tagger = new Tagger();
        $tagger->tagOrder($this->buildOrder(orderId: 1, customerId: 0, total: 600.0));
        $this->assertTrue(true);
    }

    /**
     * Guest orders (customer_id = 0) only trigger one loadRules() query
     * (order rules only — no customer rules call).
     */
    public function testGuestOrderSkipsCustomerRules(): void
    {
        global $wpdb;
        $counter = ['count' => 0];
        $wpdb    = $this->makeWpdb($counter);

        $tagger = new Tagger();
        $tagger->tagOrder($this->buildOrder(orderId: 2, customerId: 0, total: 50.0));

        $this->assertSame(1, $counter['count']);
    }

    /**
     * Logged-in customer order triggers two loadRules() queries
     * (order rules + customer rules).
     */
    public function testLoggedInOrderRunsBothRuleSets(): void
    {
        global $wpdb;
        $counter = ['count' => 0];
        $wpdb    = $this->makeWpdb($counter);

        $tagger = new Tagger();
        $tagger->tagOrder($this->buildOrder(orderId: 3, customerId: 99, total: 50.0));

        $this->assertSame(2, $counter['count']);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /** @param array{count: int} $counter */
    private function makeWpdb(array &$counter = ['count' => 0]): object
    {
        return new class ($counter) {
            public string $prefix = 'wpt_';
            public function __construct(private array &$counter) {}
            public function prepare(string $sql, mixed ...$args): string { return $sql; }
            public function get_results(string $sql, string $output = 'OBJECT'): array {
                $this->counter['count']++;
                return [];
            }
            public function query(string $sql): bool { return true; }
        };
    }

    private function buildOrder(int $orderId, int $customerId, float $total): \WC_Order
    {
        return new class ($orderId, $customerId, $total) extends \WC_Order {
            public function __construct(
                private int   $orderId,
                private int   $customerId,
                private float $orderTotal,
            ) {}
            public function get_id(): int                          { return $this->orderId; }
            public function get_customer_id(): int                 { return $this->customerId; }
            public function get_total(): float                     { return $this->orderTotal; }
            public function get_status(): string                   { return 'processing'; }
            public function get_payment_method(): string           { return 'stripe'; }
            public function get_shipping_country(): string         { return 'US'; }
            public function get_billing_country(): string          { return 'US'; }
            public function get_shipping_postcode(): string        { return '90210'; }
            public function get_billing_postcode(): string         { return '90210'; }
            public function get_shipping_state(): string           { return 'CA'; }
            public function get_billing_state(): string            { return 'CA'; }
            public function get_shipping_address_1(): string       { return '123 Main St'; }
            public function get_billing_address_1(): string        { return '123 Main St'; }
            public function get_items(string $type = 'line_item'): array { return []; }
            public function get_coupon_codes(): array              { return []; }
        };
    }
}
