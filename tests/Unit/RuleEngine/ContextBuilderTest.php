<?php
/**
 * ContextBuilderTest — unit tests for ContextBuilder::fromOrder() and fromCustomer().
 *
 * WooCommerce classes are stubbed in tests/bootstrap.php.
 * All external dependencies (WP functions, WC functions) are stubbed there too.
 */
declare(strict_types=1);

namespace ClarityMomentum\WCTagger\Tests\Unit\RuleEngine;

use PHPUnit\Framework\TestCase;
use ClarityMomentum\WCTagger\RuleEngine\ContextBuilder;

class ContextBuilderTest extends TestCase
{
    private ContextBuilder $cb;

    protected function setUp(): void
    {
        $this->cb = new ContextBuilder();
    }

    // ── fromOrder ─────────────────────────────────────────────────────────────

    /**
     * fromOrder() must return an array containing all expected context keys.
     */
    public function test_from_order_returns_expected_keys(): void
    {
        $order = $this->buildOrder();
        $ctx   = $this->cb->fromOrder($order);

        $expectedKeys = [
            'order_total', 'item_count', 'shipping_method', 'payment_method',
            'payment_status', 'shipping_country', 'billing_country',
            'is_guest', 'is_first_order', 'has_coupon', 'address_mismatch', 'items',
        ];

        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $ctx, "Context must contain key '{$key}'");
        }
    }

    /**
     * order_total must be cast to float.
     */
    public function test_order_total_is_float(): void
    {
        $order = $this->buildOrder(total: 99.99);
        $ctx   = $this->cb->fromOrder($order);

        $this->assertIsFloat($ctx['order_total']);
        $this->assertSame(99.99, $ctx['order_total']);
    }

    /**
     * item_count must be the sum of all line item quantities.
     */
    public function test_item_count_sums_quantities(): void
    {
        $items = [
            $this->buildItem(qty: 3),
            $this->buildItem(qty: 2),
            $this->buildItem(qty: 5),
        ];
        $order = $this->buildOrder(lineItems: $items);
        $ctx   = $this->cb->fromOrder($order);

        $this->assertSame(10, $ctx['item_count']);
    }

    /**
     * When customer_id is 0 (no account), is_guest must be true.
     */
    public function test_is_guest_true_when_no_customer_id(): void
    {
        $order = $this->buildOrder(customerId: 0);
        $ctx   = $this->cb->fromOrder($order);

        $this->assertTrue($ctx['is_guest']);
    }

    /**
     * When customer_id > 0, is_guest must be false.
     */
    public function test_is_guest_false_when_customer_id_set(): void
    {
        $order = $this->buildOrder(customerId: 42);
        $ctx   = $this->cb->fromOrder($order);

        $this->assertFalse($ctx['is_guest']);
    }

    /**
     * When coupons are present, has_coupon must be true.
     */
    public function test_has_coupon_true_when_coupons_present(): void
    {
        $order = $this->buildOrder(couponCodes: ['SAVE10', 'WELCOME']);
        $ctx   = $this->cb->fromOrder($order);

        $this->assertTrue($ctx['has_coupon']);
    }

    /**
     * When no coupons, has_coupon must be false.
     */
    public function test_has_coupon_false_when_no_coupons(): void
    {
        $order = $this->buildOrder(couponCodes: []);
        $ctx   = $this->cb->fromOrder($order);

        $this->assertFalse($ctx['has_coupon']);
    }

    /**
     * Billing city different from shipping city → address_mismatch = true.
     * (Uses billing_country vs shipping_country since those are the accessible fields.)
     */
    public function test_address_mismatch_detected(): void
    {
        $order = $this->buildOrder(
            billingCountry: 'US',
            shippingCountry: 'CA',
        );
        $ctx = $this->cb->fromOrder($order);

        $this->assertTrue($ctx['address_mismatch']);
    }

    /**
     * Matching billing and shipping addresses → address_mismatch = false.
     */
    public function test_address_match_when_same(): void
    {
        $order = $this->buildOrder(
            billingCountry: 'US',
            shippingCountry: 'US',
            billingState: 'CA',
            shippingState: 'CA',
            billingPostcode: '90210',
            shippingPostcode: '90210',
            billingAddress1: '123 Main St',
            shippingAddress1: '123 Main St',
        );
        $ctx = $this->cb->fromOrder($order);

        $this->assertFalse($ctx['address_mismatch']);
    }

    // ── fromCustomer ──────────────────────────────────────────────────────────

    /**
     * fromCustomer() must return an array containing all expected context keys.
     */
    public function test_from_customer_returns_expected_keys(): void
    {
        $customer = $this->buildCustomer();
        $ctx      = $this->cb->fromCustomer($customer);

        $expectedKeys = [
            'customer_ltv', 'customer_order_count', 'customer_account_age_days',
            'days_since_last_order', 'customer_tags',
        ];

        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $ctx, "Customer context must contain key '{$key}'");
        }
    }

    /**
     * customer_ltv must be float from get_total_spent().
     */
    public function test_customer_ltv_is_float(): void
    {
        $customer = $this->buildCustomer(totalSpent: 450.75);
        $ctx      = $this->cb->fromCustomer($customer);

        $this->assertIsFloat($ctx['customer_ltv']);
        $this->assertSame(450.75, $ctx['customer_ltv']);
    }

    /**
     * customer_order_count must be int from get_order_count().
     */
    public function test_customer_order_count_is_int(): void
    {
        $customer = $this->buildCustomer(orderCount: 7);
        $ctx      = $this->cb->fromCustomer($customer);

        $this->assertIsInt($ctx['customer_order_count']);
        $this->assertSame(7, $ctx['customer_order_count']);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function buildOrder(
        int    $orderId         = 1,
        float  $total           = 0.0,
        int    $customerId      = 0,
        array  $lineItems       = [],
        array  $couponCodes     = [],
        string $billingCountry  = '',
        string $shippingCountry = '',
        string $billingState    = '',
        string $shippingState   = '',
        string $billingPostcode = '',
        string $shippingPostcode = '',
        string $billingAddress1  = '',
        string $shippingAddress1 = '',
    ): \WC_Order {
        return new class (
            $orderId, $total, $customerId, $lineItems, $couponCodes,
            $billingCountry, $shippingCountry,
            $billingState, $shippingState,
            $billingPostcode, $shippingPostcode,
            $billingAddress1, $shippingAddress1,
        ) extends \WC_Order {
            public function __construct(
                private int    $orderId,
                private float  $total,
                private int    $customerId,
                private array  $lineItems,
                private array  $couponCodes,
                private string $billingCountry,
                private string $shippingCountry,
                private string $billingState,
                private string $shippingState,
                private string $billingPostcode,
                private string $shippingPostcode,
                private string $billingAddress1,
                private string $shippingAddress1,
            ) {}

            public function get_id(): int                          { return $this->orderId; }
            public function get_total(): float                     { return $this->total; }
            public function get_customer_id(): int                 { return $this->customerId; }
            public function get_coupon_codes(): array              { return $this->couponCodes; }
            public function get_billing_country(): string          { return $this->billingCountry; }
            public function get_shipping_country(): string         { return $this->shippingCountry; }
            public function get_billing_state(): string            { return $this->billingState; }
            public function get_shipping_state(): string           { return $this->shippingState; }
            public function get_billing_postcode(): string         { return $this->billingPostcode; }
            public function get_shipping_postcode(): string        { return $this->shippingPostcode; }
            public function get_billing_address_1(): string        { return $this->billingAddress1; }
            public function get_shipping_address_1(): string       { return $this->shippingAddress1; }
            public function get_payment_method(): string           { return 'stripe'; }
            public function get_status(): string                   { return 'processing'; }

            public function get_items(string $type = 'line_item'): array
            {
                if ($type === 'line_item') {
                    return $this->lineItems;
                }
                return []; // no shipping items by default
            }
        };
    }

    private function buildItem(int $qty = 1): \WC_Order_Item_Product
    {
        return new class ($qty) extends \WC_Order_Item_Product {
            public function __construct(private int $qty) {}
            public function get_quantity(): int        { return $this->qty; }
            public function get_product(): ?\WC_Product { return null; }
        };
    }

    private function buildCustomer(
        int   $customerId  = 1,
        float $totalSpent  = 0.0,
        int   $orderCount  = 0,
    ): \WC_Customer {
        return new class ($customerId, $totalSpent, $orderCount) extends \WC_Customer {
            public function __construct(
                private int   $customerId,
                private float $totalSpent,
                private int   $orderCount,
            ) {}
            public function get_id(): int            { return $this->customerId; }
            public function get_total_spent(): float { return $this->totalSpent; }
            public function get_order_count(): int   { return $this->orderCount; }
        };
    }
}
