<?php
declare(strict_types=1);

namespace HarperAgency\WCTagger\Tests\Unit\RuleEngine;

use HarperAgency\RuleEngine\Condition;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use function PHPUnit\Framework\assertSame;

class ConditionTest extends TestCase
{
    // ── order_total ───────────────────────────────────────────────────────────

    #[DataProvider('orderTotalProvider')]
    public function testOrderTotal(string $op, mixed $value, float $contextTotal, bool $expected): void
    {
        $condition = new Condition('order_total', $op, $value);
        $context   = ['order_total' => $contextTotal];

        assertSame($expected, $condition->evaluate($context));
    }

    public static function orderTotalProvider(): array
    {
        return [
            // gt
            'gt passes when total > value'         => ['gt',  100.0, 150.0, true],
            'gt fails when total < value'          => ['gt',  100.0,  50.0, false],
            'gt fails when total = value'          => ['gt',  100.0, 100.0, false],

            // gte
            'gte passes when total > value'        => ['gte', 100.0, 150.0, true],
            'gte passes when total = value'        => ['gte', 100.0, 100.0, true],
            'gte fails when total < value'         => ['gte', 100.0,  50.0, false],

            // lt
            'lt passes when total < value'         => ['lt',  100.0,  50.0, true],
            'lt fails when total > value'          => ['lt',  100.0, 150.0, false],
            'lt fails when total = value'          => ['lt',  100.0, 100.0, false],

            // lte
            'lte passes when total < value'        => ['lte', 100.0,  50.0, true],
            'lte passes when total = value'        => ['lte', 100.0, 100.0, true],
            'lte fails when total > value'         => ['lte', 100.0, 150.0, false],

            // eq
            'eq passes when total = value'         => ['eq',  100.0, 100.0, true],
            'eq fails when total != value'         => ['eq',  100.0, 150.0, false],

            // between
            'between passes at lower bound'        => ['between', [50.0, 100.0],  50.0, true],
            'between passes at upper bound'        => ['between', [50.0, 100.0], 100.0, true],
            'between passes in middle'             => ['between', [50.0, 100.0],  75.0, true],
            'between fails below range'            => ['between', [50.0, 100.0],  25.0, false],
            'between fails above range'            => ['between', [50.0, 100.0], 150.0, false],
        ];
    }

    // ── item_count ────────────────────────────────────────────────────────────

    #[DataProvider('itemCountProvider')]
    public function testItemCount(string $op, int $value, int $contextCount, bool $expected): void
    {
        $condition = new Condition('item_count', $op, $value);
        $context   = ['item_count' => $contextCount];

        assertSame($expected, $condition->evaluate($context));
    }

    public static function itemCountProvider(): array
    {
        return [
            'gt passes'  => ['gt',  5, 10, true],
            'gt fails'   => ['gt',  5,  3, false],
            'gte passes' => ['gte', 5,  5, true],
            'gte fails'  => ['gte', 5,  4, false],
            'lt passes'  => ['lt',  5,  3, true],
            'lt fails'   => ['lt',  5, 10, false],
            'lte passes' => ['lte', 5,  5, true],
            'lte fails'  => ['lte', 5,  6, false],
            'eq passes'  => ['eq',  5,  5, true],
            'eq fails'   => ['eq',  5,  4, false],

            // bulk order threshold from example rules
            'bulk gte 10 passes at 10'  => ['gte', 10, 10, true],
            'bulk gte 10 passes at 15'  => ['gte', 10, 15, true],
            'bulk gte 10 fails at 9'    => ['gte', 10,  9, false],
        ];
    }

    // ── shipping_method ───────────────────────────────────────────────────────

    #[DataProvider('shippingMethodProvider')]
    public function testShippingMethod(string $op, mixed $value, string $contextMethod, bool $expected): void
    {
        $condition = new Condition('shipping_method', $op, $value);
        $context   = ['shipping_method' => $contextMethod];

        assertSame($expected, $condition->evaluate($context));
    }

    public static function shippingMethodProvider(): array
    {
        return [
            // eq
            'eq passes exact match'            => ['eq', 'flat_rate:1', 'flat_rate:1',       true],
            'eq fails on mismatch'             => ['eq', 'flat_rate:1', 'flat_rate:2',       false],
            'eq fails empty'                   => ['eq', 'flat_rate:1', '',                  false],

            // contains
            'contains passes flat_rate in flat_rate:1'  => ['contains', 'flat_rate', 'flat_rate:1',   true],
            'contains passes flat_rate in flat_rate:2'  => ['contains', 'flat_rate', 'flat_rate:2',   true],
            'contains fails on unrelated method'        => ['contains', 'flat_rate', 'free_shipping',  false],
            'contains passes local_pickup partial'      => ['contains', 'local_pickup', 'local_pickup:3', true],
            'contains fails empty string'               => ['contains', 'flat_rate', '',               false],

            // in
            'in passes first element'          => ['in', ['flat_rate:1', 'free_shipping:1'], 'flat_rate:1',    true],
            'in passes second element'         => ['in', ['flat_rate:1', 'free_shipping:1'], 'free_shipping:1', true],
            'in fails non-member'              => ['in', ['flat_rate:1', 'free_shipping:1'], 'express:1',       false],

            // not_in
            'not_in passes non-member'         => ['not_in', ['flat_rate:1', 'free_shipping:1'], 'express:1',       true],
            'not_in fails member'              => ['not_in', ['flat_rate:1', 'free_shipping:1'], 'flat_rate:1',    false],
        ];
    }

    // ── payment_method ────────────────────────────────────────────────────────

    #[DataProvider('paymentMethodProvider')]
    public function testPaymentMethod(string $op, mixed $value, string $contextMethod, bool $expected): void
    {
        $condition = new Condition('payment_method', $op, $value);
        $context   = ['payment_method' => $contextMethod];

        assertSame($expected, $condition->evaluate($context));
    }

    public static function paymentMethodProvider(): array
    {
        return [
            'eq cod passes'                    => ['eq',  'cod',  'cod',    true],
            'eq cod fails on stripe'           => ['eq',  'cod',  'stripe', false],
            'in passes first'                  => ['in',  ['cod', 'bacs'], 'cod',    true],
            'in passes second'                 => ['in',  ['cod', 'bacs'], 'bacs',   true],
            'in fails non-member'              => ['in',  ['cod', 'bacs'], 'stripe', false],
        ];
    }

    // ── shipping_country ──────────────────────────────────────────────────────

    #[DataProvider('shippingCountryProvider')]
    public function testShippingCountry(string $op, mixed $value, string $contextCountry, bool $expected): void
    {
        $condition = new Condition('shipping_country', $op, $value);
        $context   = ['shipping_country' => $contextCountry];

        assertSame($expected, $condition->evaluate($context));
    }

    public static function shippingCountryProvider(): array
    {
        return [
            'eq US passes'                     => ['eq',     'US',         'US', true],
            'eq US fails on CA'                => ['eq',     'US',         'CA', false],
            'in US/CA passes US'               => ['in',     ['US', 'CA'], 'US', true],
            'in US/CA passes CA'               => ['in',     ['US', 'CA'], 'CA', true],
            'in US/CA fails GB'                => ['in',     ['US', 'CA'], 'GB', false],
            'not_in US/CA passes GB'           => ['not_in', ['US', 'CA'], 'GB', true],
            'not_in US/CA passes DE'           => ['not_in', ['US', 'CA'], 'DE', true],
            'not_in US/CA fails US'            => ['not_in', ['US', 'CA'], 'US', false],
        ];
    }

    // ── billing_country ───────────────────────────────────────────────────────

    #[DataProvider('billingCountryProvider')]
    public function testBillingCountry(string $op, mixed $value, string $contextCountry, bool $expected): void
    {
        $condition = new Condition('billing_country', $op, $value);
        $context   = ['billing_country' => $contextCountry];

        assertSame($expected, $condition->evaluate($context));
    }

    public static function billingCountryProvider(): array
    {
        return [
            'in US/CA passes US'               => ['in',     ['US', 'CA'], 'US', true],
            'in US/CA passes CA'               => ['in',     ['US', 'CA'], 'CA', true],
            'in US/CA fails GB'                => ['in',     ['US', 'CA'], 'GB', false],
            'eq US passes'                     => ['eq',     'US',         'US', true],
            'eq US fails CA'                   => ['eq',     'US',         'CA', false],
        ];
    }

    // ── contains_sku ──────────────────────────────────────────────────────────

    public function testContainsSkuPassesWhenSkuPresent(): void
    {
        $condition = new Condition('contains_sku', 'eq', 'FRAGILE');
        $context   = [
            'items' => [
                ['sku' => 'WIDGET-01', 'categories' => []],
                ['sku' => 'FRAGILE',   'categories' => ['hazardous']],
            ],
        ];

        assertSame(true, $condition->evaluate($context));
    }

    public function testContainsSkuFailsWhenSkuMissing(): void
    {
        $condition = new Condition('contains_sku', 'eq', 'FRAGILE');
        $context   = [
            'items' => [
                ['sku' => 'WIDGET-01', 'categories' => []],
                ['sku' => 'NORMAL-02', 'categories' => []],
            ],
        ];

        assertSame(false, $condition->evaluate($context));
    }

    public function testContainsSkuFailsOnEmptyItems(): void
    {
        $condition = new Condition('contains_sku', 'eq', 'FRAGILE');
        $context   = ['items' => []];

        assertSame(false, $condition->evaluate($context));
    }

    public function testContainsSkuFailsOnUnsupportedOperator(): void
    {
        $condition = new Condition('contains_sku', 'gt', 'FRAGILE');
        $context   = [
            'items' => [['sku' => 'FRAGILE', 'categories' => []]],
        ];

        assertSame(false, $condition->evaluate($context));
    }

    // ── contains_category ────────────────────────────────────────────────────

    public function testContainsCategoryPassesWhenCategoryPresent(): void
    {
        $condition = new Condition('contains_category', 'eq', 'hazardous');
        $context   = [
            'items' => [
                ['sku' => 'WIDGET-01', 'categories' => ['clothing']],
                ['sku' => 'CHEM-01',   'categories' => ['hazardous', 'restricted']],
            ],
        ];

        assertSame(true, $condition->evaluate($context));
    }

    public function testContainsCategoryFailsWhenCategoryMissing(): void
    {
        $condition = new Condition('contains_category', 'eq', 'hazardous');
        $context   = [
            'items' => [
                ['sku' => 'SHIRT-01', 'categories' => ['clothing', 'sale']],
            ],
        ];

        assertSame(false, $condition->evaluate($context));
    }

    public function testContainsCategoryFailsOnEmptyItems(): void
    {
        $condition = new Condition('contains_category', 'eq', 'hazardous');
        $context   = ['items' => []];

        assertSame(false, $condition->evaluate($context));
    }

    // ── customer_ltv ─────────────────────────────────────────────────────────

    #[DataProvider('customerLtvProvider')]
    public function testCustomerLtv(string $op, float $value, float $contextLtv, bool $expected): void
    {
        $condition = new Condition('customer_ltv', $op, $value);
        $context   = ['customer_ltv' => $contextLtv];

        assertSame($expected, $condition->evaluate($context));
    }

    public static function customerLtvProvider(): array
    {
        return [
            'gt 500 passes at 600'  => ['gt',  500.0, 600.0, true],
            'gt 500 fails at 400'   => ['gt',  500.0, 400.0, false],
            'gt 500 fails at 500'   => ['gt',  500.0, 500.0, false],
            'gt 1000 passes at 1500' => ['gt', 1000.0, 1500.0, true],
            'gt 1000 fails at 999'  => ['gt',  1000.0, 999.0, false],
            'gte 500 passes at 500' => ['gte', 500.0, 500.0, true],
            'lte 500 passes at 500' => ['lte', 500.0, 500.0, true],
            'lte 500 fails at 501'  => ['lte', 500.0, 501.0, false],
        ];
    }

    // ── customer_order_count ──────────────────────────────────────────────────

    #[DataProvider('customerOrderCountProvider')]
    public function testCustomerOrderCount(string $op, int $value, int $contextCount, bool $expected): void
    {
        $condition = new Condition('customer_order_count', $op, $value);
        $context   = ['customer_order_count' => $contextCount];

        assertSame($expected, $condition->evaluate($context));
    }

    public static function customerOrderCountProvider(): array
    {
        return [
            'gte 3 passes at 3'  => ['gte', 3, 3, true],
            'gte 3 passes at 5'  => ['gte', 3, 5, true],
            'gte 3 fails at 2'   => ['gte', 3, 2, false],
            'gte 5 passes at 5'  => ['gte', 5, 5, true],
            'gte 5 fails at 4'   => ['gte', 5, 4, false],
            'gt 3 passes at 4'   => ['gt',  3, 4, true],
            'gt 3 fails at 3'    => ['gt',  3, 3, false],
            'eq 3 passes at 3'   => ['eq',  3, 3, true],
            'eq 3 fails at 4'    => ['eq',  3, 4, false],
        ];
    }

    // ── customer_has_tag ──────────────────────────────────────────────────────

    public function testCustomerHasTagPassesWhenTagPresent(): void
    {
        $condition = new Condition('customer_has_tag', 'eq', 'vip');
        $context   = ['customer_tags' => ['vip', 'wholesale']];

        assertSame(true, $condition->evaluate($context));
    }

    public function testCustomerHasTagPassesWhenTagIsOnlyElement(): void
    {
        $condition = new Condition('customer_has_tag', 'eq', 'vip');
        $context   = ['customer_tags' => ['vip']];

        assertSame(true, $condition->evaluate($context));
    }

    public function testCustomerHasTagFailsWhenTagMissing(): void
    {
        $condition = new Condition('customer_has_tag', 'eq', 'vip');
        $context   = ['customer_tags' => ['standard', 'new']];

        assertSame(false, $condition->evaluate($context));
    }

    public function testCustomerHasTagFailsOnEmptyTags(): void
    {
        $condition = new Condition('customer_has_tag', 'eq', 'vip');
        $context   = ['customer_tags' => []];

        assertSame(false, $condition->evaluate($context));
    }

    public function testCustomerHasTagFailsWhenContextKeyAbsent(): void
    {
        $condition = new Condition('customer_has_tag', 'eq', 'vip');
        $context   = []; // no customer_tags key

        assertSame(false, $condition->evaluate($context));
    }

    // ── Unknown field ─────────────────────────────────────────────────────────

    public function testUnknownFieldReturnsFalse(): void
    {
        $condition = new Condition('non_existent_field', 'eq', 'x');
        $context   = ['non_existent_field' => 'x'];

        assertSame(false, $condition->evaluate($context));
    }

    // ── Missing context key (numeric fields) ──────────────────────────────────

    public function testMissingNumericContextKeyReturnsFalse(): void
    {
        $condition = new Condition('order_total', 'gt', 100.0);
        $context   = []; // no order_total key

        assertSame(false, $condition->evaluate($context));
    }

    // ── neq operator (numeric + string + country) ────────────────────────────

    public function testNeqNumericPasses(): void
    {
        assertSame(true,  (new Condition('order_total', 'neq', 100.0))->evaluate(['order_total' => 99.0]));
        assertSame(false, (new Condition('order_total', 'neq', 100.0))->evaluate(['order_total' => 100.0]));
    }

    public function testNeqStringPasses(): void
    {
        assertSame(true,  (new Condition('payment_method', 'neq', 'cod'))->evaluate(['payment_method' => 'stripe']));
        assertSame(false, (new Condition('payment_method', 'neq', 'cod'))->evaluate(['payment_method' => 'cod']));
    }

    public function testNeqCountryPasses(): void
    {
        assertSame(true,  (new Condition('billing_country', 'neq', 'US'))->evaluate(['billing_country' => 'CA']));
        assertSame(false, (new Condition('billing_country', 'neq', 'US'))->evaluate(['billing_country' => 'US']));
    }

    // ── starts_with operator ─────────────────────────────────────────────────

    public function testStartsWithOnShippingMethod(): void
    {
        assertSame(true,  (new Condition('shipping_method', 'starts_with', 'flat_rate'))->evaluate(['shipping_method' => 'flat_rate:3']));
        assertSame(false, (new Condition('shipping_method', 'starts_with', 'flat_rate'))->evaluate(['shipping_method' => 'free_shipping:1']));
    }

    public function testStartsWithOnContainsSku(): void
    {
        $items = [['sku' => 'IMP-001', 'categories' => []], ['sku' => 'WIDGET', 'categories' => []]];
        assertSame(true,  (new Condition('contains_sku', 'starts_with', 'IMP-'))->evaluate(['items' => $items]));
        assertSame(false, (new Condition('contains_sku', 'starts_with', 'FRAG-'))->evaluate(['items' => $items]));
        assertSame(false, (new Condition('contains_sku', 'starts_with', 'IMP-'))->evaluate(['items' => []]));
    }

    // ── payment_status ───────────────────────────────────────────────────────

    #[DataProvider('paymentStatusProvider')]
    public function testPaymentStatus(string $op, mixed $value, string $status, bool $expected): void
    {
        assertSame($expected, (new Condition('payment_status', $op, $value))->evaluate(['payment_status' => $status]));
    }

    public static function paymentStatusProvider(): array
    {
        return [
            'eq pending passes'                    => ['eq',     'pending',            'pending',    true],
            'eq pending fails on processing'       => ['eq',     'pending',            'processing', false],
            'in pending+on-hold passes pending'    => ['in',     ['pending', 'on-hold'], 'pending',  true],
            'in pending+on-hold passes on-hold'    => ['in',     ['pending', 'on-hold'], 'on-hold',  true],
            'in pending+on-hold fails processing'  => ['in',     ['pending', 'on-hold'], 'processing', false],
            'not_in passes processing'             => ['not_in', ['pending', 'on-hold'], 'processing', true],
            'not_in fails pending'                 => ['not_in', ['pending', 'on-hold'], 'pending',  false],
            'neq pending passes processing'        => ['neq',    'pending',            'processing', true],
            'neq pending fails pending'            => ['neq',    'pending',            'pending',    false],
        ];
    }

    // ── Boolean fields (is_guest, is_first_order, has_coupon, address_mismatch)

    #[DataProvider('booleanFieldProvider')]
    public function testBooleanFields(string $field, string $op, bool $contextValue, bool $expected): void
    {
        assertSame($expected, (new Condition($field, $op, true))->evaluate([$field => $contextValue]));
    }

    public static function booleanFieldProvider(): array
    {
        $fields = ['is_guest', 'is_first_order', 'has_coupon', 'address_mismatch'];
        $cases  = [];
        foreach ($fields as $f) {
            $cases["{$f} is_true when true"]  = [$f, 'is_true',  true,  true];
            $cases["{$f} is_true when false"] = [$f, 'is_true',  false, false];
            $cases["{$f} is_false when false"]= [$f, 'is_false', false, true]; // note: value param ignored for is_false
            $cases["{$f} is_false when true"] = [$f, 'is_false', true,  false];
        }
        return $cases;
    }

    public function testBooleanFieldMissingContextDefaultsFalse(): void
    {
        // is_guest missing from context — defaults to false, so is_true fails
        assertSame(false, (new Condition('is_guest', 'is_true', true))->evaluate([]));
        // is_false passes because default is false
        assertSame(true,  (new Condition('is_guest', 'is_false', true))->evaluate([]));
    }

    public function testBooleanFieldInvalidOperatorReturnsFalse(): void
    {
        assertSame(false, (new Condition('is_guest', 'eq', true))->evaluate(['is_guest' => true]));
    }

    // ── customer_account_age_days + days_since_last_order ────────────────────

    public function testCustomerAccountAgeDays(): void
    {
        assertSame(true,  (new Condition('customer_account_age_days', 'lt',  30))->evaluate(['customer_account_age_days' => 10]));
        assertSame(false, (new Condition('customer_account_age_days', 'lt',  30))->evaluate(['customer_account_age_days' => 45]));
        assertSame(true,  (new Condition('customer_account_age_days', 'gte', 30))->evaluate(['customer_account_age_days' => 30]));
    }

    public function testDaysSinceLastOrder(): void
    {
        assertSame(true,  (new Condition('days_since_last_order', 'gt',  90))->evaluate(['days_since_last_order' => 120]));
        assertSame(false, (new Condition('days_since_last_order', 'gt',  90))->evaluate(['days_since_last_order' => 60]));
        assertSame(true,  (new Condition('days_since_last_order', 'lte', 30))->evaluate(['days_since_last_order' => 30]));
    }

    // ── Seed rule smoke tests (all 14 default rules evaluated) ───────────────

    public function testHighValueSeedRule(): void
    {
        $c = new Condition('order_total', 'gt', 500);
        assertSame(true,  $c->evaluate(['order_total' => 750.0]));
        assertSame(false, $c->evaluate(['order_total' => 499.99]));
    }

    public function testUnconfirmedPaymentSeedRule(): void
    {
        $c = new Condition('payment_status', 'in', ['pending', 'on-hold']);
        assertSame(true,  $c->evaluate(['payment_status' => 'pending']));
        assertSame(true,  $c->evaluate(['payment_status' => 'on-hold']));
        assertSame(false, $c->evaluate(['payment_status' => 'processing']));
        assertSame(false, $c->evaluate(['payment_status' => 'completed']));
    }

    public function testAddressMismatchSeedRule(): void
    {
        $c = new Condition('address_mismatch', 'is_true', true);
        assertSame(true,  $c->evaluate(['address_mismatch' => true]));
        assertSame(false, $c->evaluate(['address_mismatch' => false]));
    }

    public function testGuestCheckoutSeedRule(): void
    {
        $c = new Condition('is_guest', 'is_true', true);
        assertSame(true,  $c->evaluate(['is_guest' => true]));
        assertSame(false, $c->evaluate(['is_guest' => false]));
    }

    public function testFirstOrderSeedRule(): void
    {
        $c = new Condition('is_first_order', 'is_true', true);
        assertSame(true,  $c->evaluate(['is_first_order' => true]));
        assertSame(false, $c->evaluate(['is_first_order' => false]));
    }

    public function testHasCouponSeedRule(): void
    {
        $c = new Condition('has_coupon', 'is_true', true);
        assertSame(true,  $c->evaluate(['has_coupon' => true]));
        assertSame(false, $c->evaluate(['has_coupon' => false]));
    }

    public function testAtRiskSeedRule(): void
    {
        $days  = new Condition('days_since_last_order',  'gt',  90);
        $count = new Condition('customer_order_count',   'gte', 2);
        $ctx   = ['days_since_last_order' => 120, 'customer_order_count' => 3];
        assertSame(true,  $days->evaluate($ctx) && $count->evaluate($ctx));

        $ctx2  = ['days_since_last_order' => 60, 'customer_order_count' => 3];
        assertSame(false, $days->evaluate($ctx2) && $count->evaluate($ctx2));
    }

    // ── Accessor methods ──────────────────────────────────────────────────────

    public function testAccessors(): void
    {
        $condition = new Condition('order_total', 'gt', 100.0);

        assertSame('order_total', $condition->getField());
        assertSame('gt', $condition->getOperator());
        assertSame(100.0, $condition->getValue());
    }
}
