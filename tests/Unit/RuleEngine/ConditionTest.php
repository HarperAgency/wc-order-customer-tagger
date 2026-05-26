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

    // ── Accessor methods ──────────────────────────────────────────────────────

    public function testAccessors(): void
    {
        $condition = new Condition('order_total', 'gt', 100.0);

        assertSame('order_total', $condition->getField());
        assertSame('gt', $condition->getOperator());
        assertSame(100.0, $condition->getValue());
    }
}
