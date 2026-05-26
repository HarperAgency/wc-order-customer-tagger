<?php
declare(strict_types=1);

namespace HarperAgency\WCTagger\Tests\Unit\RuleEngine;

use HarperAgency\WCTagger\RuleEngine\Condition;
use HarperAgency\WCTagger\RuleEngine\Rule;
use PHPUnit\Framework\TestCase;
use function PHPUnit\Framework\assertSame;

class RuleTest extends TestCase
{
    // ── AND rules ─────────────────────────────────────────────────────────────

    public function testAndRulePassesWhenAllConditionsPass(): void
    {
        $rule = new Rule(
            [
                new Condition('order_total', 'gt', 100.0),
                new Condition('item_count',  'gte', 2),
            ],
            Rule::OPERATOR_AND,
            'high-value',
        );

        $context = ['order_total' => 200.0, 'item_count' => 3];

        assertSame(true, $rule->evaluate($context));
    }

    public function testAndRuleFailsWhenFirstConditionFails(): void
    {
        $rule = new Rule(
            [
                new Condition('order_total', 'gt', 100.0),
                new Condition('item_count',  'gte', 2),
            ],
            Rule::OPERATOR_AND,
            'high-value',
        );

        $context = ['order_total' => 50.0, 'item_count' => 3]; // order_total fails

        assertSame(false, $rule->evaluate($context));
    }

    public function testAndRuleFailsWhenSecondConditionFails(): void
    {
        $rule = new Rule(
            [
                new Condition('order_total', 'gt', 100.0),
                new Condition('item_count',  'gte', 2),
            ],
            Rule::OPERATOR_AND,
            'high-value',
        );

        $context = ['order_total' => 200.0, 'item_count' => 1]; // item_count fails

        assertSame(false, $rule->evaluate($context));
    }

    public function testAndRuleFailsWhenAllConditionsFail(): void
    {
        $rule = new Rule(
            [
                new Condition('order_total', 'gt', 100.0),
                new Condition('item_count',  'gte', 2),
            ],
            Rule::OPERATOR_AND,
            'high-value',
        );

        $context = ['order_total' => 50.0, 'item_count' => 1];

        assertSame(false, $rule->evaluate($context));
    }

    // ── OR rules ──────────────────────────────────────────────────────────────

    public function testOrRulePassesWhenFirstConditionPasses(): void
    {
        $rule = new Rule(
            [
                new Condition('order_total',    'gt',  100.0),
                new Condition('payment_method', 'eq',  'cod'),
            ],
            Rule::OPERATOR_OR,
            'flag',
        );

        $context = ['order_total' => 200.0, 'payment_method' => 'stripe'];

        assertSame(true, $rule->evaluate($context));
    }

    public function testOrRulePassesWhenSecondConditionPasses(): void
    {
        $rule = new Rule(
            [
                new Condition('order_total',    'gt', 100.0),
                new Condition('payment_method', 'eq', 'cod'),
            ],
            Rule::OPERATOR_OR,
            'flag',
        );

        $context = ['order_total' => 50.0, 'payment_method' => 'cod'];

        assertSame(true, $rule->evaluate($context));
    }

    public function testOrRulePassesWhenAllConditionsPass(): void
    {
        $rule = new Rule(
            [
                new Condition('order_total',    'gt', 100.0),
                new Condition('payment_method', 'eq', 'cod'),
            ],
            Rule::OPERATOR_OR,
            'flag',
        );

        $context = ['order_total' => 200.0, 'payment_method' => 'cod'];

        assertSame(true, $rule->evaluate($context));
    }

    public function testOrRuleFailsWhenAllConditionsFail(): void
    {
        $rule = new Rule(
            [
                new Condition('order_total',    'gt', 100.0),
                new Condition('payment_method', 'eq', 'cod'),
            ],
            Rule::OPERATOR_OR,
            'flag',
        );

        $context = ['order_total' => 50.0, 'payment_method' => 'stripe'];

        assertSame(false, $rule->evaluate($context));
    }

    // ── Edge cases ────────────────────────────────────────────────────────────

    public function testEmptyConditionsReturnsFalse(): void
    {
        $ruleAnd = new Rule([], Rule::OPERATOR_AND, 'tag-and');
        $ruleOr  = new Rule([], Rule::OPERATOR_OR,  'tag-or');

        assertSame(false, $ruleAnd->evaluate(['order_total' => 999.0]));
        assertSame(false, $ruleOr->evaluate(['order_total'  => 999.0]));
    }

    public function testSingleConditionAndBehavesSameAsBareCondition(): void
    {
        $rule = new Rule(
            [new Condition('order_total', 'gt', 100.0)],
            Rule::OPERATOR_AND,
            'single',
        );

        assertSame(true,  $rule->evaluate(['order_total' => 200.0]));
        assertSame(false, $rule->evaluate(['order_total' =>  50.0]));
    }

    public function testSingleConditionOrBehavesSameAsBareCondition(): void
    {
        $rule = new Rule(
            [new Condition('order_total', 'gt', 100.0)],
            Rule::OPERATOR_OR,
            'single',
        );

        assertSame(true,  $rule->evaluate(['order_total' => 200.0]));
        assertSame(false, $rule->evaluate(['order_total' =>  50.0]));
    }

    // ── Invalid operator ──────────────────────────────────────────────────────

    public function testInvalidOperatorThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Rule([], 'XOR', 'tag');
    }

    // ── Accessors ─────────────────────────────────────────────────────────────

    public function testAccessors(): void
    {
        $conditions = [new Condition('order_total', 'gt', 100.0)];
        $rule       = new Rule($conditions, Rule::OPERATOR_AND, 42);

        assertSame($conditions,           $rule->getConditions());
        assertSame(Rule::OPERATOR_AND,    $rule->getOperator());
        assertSame(42,                    $rule->getTagId());
    }

    public function testTagIdCanBeString(): void
    {
        $rule = new Rule(
            [new Condition('order_total', 'gt', 0.0)],
            Rule::OPERATOR_AND,
            'high-value',
        );

        assertSame('high-value', $rule->getTagId());
    }

    // ── Realistic example rules ───────────────────────────────────────────────

    public function testHighValueOrderRule(): void
    {
        // Example rule 1: order_total > 500
        $rule = new Rule(
            [new Condition('order_total', 'gt', 500.0)],
            Rule::OPERATOR_AND,
            'high-value',
        );

        assertSame(true,  $rule->evaluate(['order_total' => 750.0]));
        assertSame(false, $rule->evaluate(['order_total' => 499.99]));
    }

    public function testVipCustomerRule(): void
    {
        // Example rule 6: customer_ltv > 1000 AND customer_order_count >= 5
        $rule = new Rule(
            [
                new Condition('customer_ltv',         'gt',  1000.0),
                new Condition('customer_order_count', 'gte', 5),
            ],
            Rule::OPERATOR_AND,
            'vip',
        );

        assertSame(true,  $rule->evaluate(['customer_ltv' => 1500.0, 'customer_order_count' => 7]));
        assertSame(false, $rule->evaluate(['customer_ltv' => 1500.0, 'customer_order_count' => 3])); // count fails
        assertSame(false, $rule->evaluate(['customer_ltv' =>  500.0, 'customer_order_count' => 7])); // ltv fails
        assertSame(false, $rule->evaluate(['customer_ltv' =>  500.0, 'customer_order_count' => 2])); // both fail
    }

    public function testInternationalShippingRule(): void
    {
        // Example rule 4: shipping_country NOT IN ["US","CA"]
        $rule = new Rule(
            [new Condition('shipping_country', 'not_in', ['US', 'CA'])],
            Rule::OPERATOR_AND,
            'international',
        );

        assertSame(true,  $rule->evaluate(['shipping_country' => 'GB']));
        assertSame(true,  $rule->evaluate(['shipping_country' => 'DE']));
        assertSame(false, $rule->evaluate(['shipping_country' => 'US']));
        assertSame(false, $rule->evaluate(['shipping_country' => 'CA']));
    }
}
