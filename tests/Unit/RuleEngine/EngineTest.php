<?php
declare(strict_types=1);

namespace HarperAgency\WCTagger\Tests\Unit\RuleEngine;

use HarperAgency\RuleEngine\Condition;
use HarperAgency\RuleEngine\Engine;
use HarperAgency\RuleEngine\Rule;
use PHPUnit\Framework\TestCase;
use function PHPUnit\Framework\assertSame;

class EngineTest extends TestCase
{
    // ── Helpers ───────────────────────────────────────────────────────────────

    /** Build a Rule that always passes (order_total > 0). */
    private function alwaysPassRule(int|string $tagId, string $operator = Rule::OPERATOR_AND): Rule
    {
        return new Rule(
            [new Condition('order_total', 'gt', 0.0)],
            $operator,
            $tagId,
        );
    }

    /** Build a Rule that always fails (order_total > 999999). */
    private function alwaysFailRule(int|string $tagId): Rule
    {
        return new Rule(
            [new Condition('order_total', 'gt', 999_999.0)],
            Rule::OPERATOR_AND,
            $tagId,
        );
    }

    private function baseContext(): array
    {
        return ['order_total' => 100.0];
    }

    // ── No rules ──────────────────────────────────────────────────────────────

    public function testNoRulesReturnsEmptyArray(): void
    {
        $engine = new Engine([]);

        assertSame([], $engine->evaluate($this->baseContext()));
    }

    // ── Single rule ───────────────────────────────────────────────────────────

    public function testOneRuleMatchesReturnsItsTagId(): void
    {
        $engine = new Engine([$this->alwaysPassRule('high-value')]);

        assertSame(['high-value'], $engine->evaluate($this->baseContext()));
    }

    public function testOneRuleDoesNotMatchReturnsEmptyArray(): void
    {
        $engine = new Engine([$this->alwaysFailRule('high-value')]);

        assertSame([], $engine->evaluate($this->baseContext()));
    }

    // ── Multiple rules ────────────────────────────────────────────────────────

    public function testMultipleRulesOneMatchesReturnsOnlyMatchedTagId(): void
    {
        $engine = new Engine([
            $this->alwaysPassRule('matched'),
            $this->alwaysFailRule('not-matched'),
        ]);

        assertSame(['matched'], $engine->evaluate($this->baseContext()));
    }

    public function testMultipleRulesAllMatchReturnsAllTagIds(): void
    {
        $engine = new Engine([
            $this->alwaysPassRule('tag-a'),
            $this->alwaysPassRule('tag-b'),
            $this->alwaysPassRule('tag-c'),
        ]);

        assertSame(['tag-a', 'tag-b', 'tag-c'], $engine->evaluate($this->baseContext()));
    }

    public function testMultipleRulesNoneMatchReturnsEmptyArray(): void
    {
        $engine = new Engine([
            $this->alwaysFailRule('tag-a'),
            $this->alwaysFailRule('tag-b'),
        ]);

        assertSame([], $engine->evaluate($this->baseContext()));
    }

    // ── Deduplication ─────────────────────────────────────────────────────────

    public function testDuplicateTagIdsFromMultipleRulesAreDeduped(): void
    {
        $engine = new Engine([
            $this->alwaysPassRule('vip'),
            $this->alwaysPassRule('vip'), // second rule also produces "vip"
            $this->alwaysPassRule('high-value'),
        ]);

        $result = $engine->evaluate($this->baseContext());

        assertSame(['vip', 'high-value'], $result);
    }

    public function testDuplicateIntTagIdsAreDeduped(): void
    {
        $engine = new Engine([
            $this->alwaysPassRule(42),
            $this->alwaysPassRule(42),
            $this->alwaysPassRule(99),
        ]);

        assertSame([42, 99], $engine->evaluate($this->baseContext()));
    }

    // ── OR operator ───────────────────────────────────────────────────────────

    public function testRuleWithOrOperatorMatchingPartiallyReturnsTag(): void
    {
        // OR rule: order_total > 999 OR payment_method eq "cod"
        // Context: order_total = 50 (fails), payment_method = "cod" (passes)
        $rule = new Rule(
            [
                new Condition('order_total',    'gt', 999.0),
                new Condition('payment_method', 'eq', 'cod'),
            ],
            Rule::OPERATOR_OR,
            'cod',
        );

        $engine  = new Engine([$rule]);
        $context = ['order_total' => 50.0, 'payment_method' => 'cod'];

        assertSame(['cod'], $engine->evaluate($context));
    }

    public function testRuleWithOrOperatorAllFailingReturnsEmpty(): void
    {
        $rule = new Rule(
            [
                new Condition('order_total',    'gt', 999.0),
                new Condition('payment_method', 'eq', 'cod'),
            ],
            Rule::OPERATOR_OR,
            'cod',
        );

        $engine  = new Engine([$rule]);
        $context = ['order_total' => 50.0, 'payment_method' => 'stripe'];

        assertSame([], $engine->evaluate($context));
    }

    // ── AND operator failing one condition ────────────────────────────────────

    public function testRuleWithAndOperatorFailingOneConditionTagNotReturned(): void
    {
        // VIP: customer_ltv > 1000 AND customer_order_count >= 5
        // order count passes but ltv fails — tag must NOT be returned
        $rule = new Rule(
            [
                new Condition('customer_ltv',         'gt',  1000.0),
                new Condition('customer_order_count', 'gte', 5),
            ],
            Rule::OPERATOR_AND,
            'vip',
        );

        $engine  = new Engine([$rule]);
        $context = ['customer_ltv' => 500.0, 'customer_order_count' => 10];

        assertSame([], $engine->evaluate($context));
    }

    // ── Realistic multi-rule scenario ─────────────────────────────────────────

    public function testRealisticOrderScenario(): void
    {
        // Rules that match on a $750 COD order with 12 items shipped to GB
        $rules = [
            // Rule 1: high-value (order_total > 500) — should match
            new Rule(
                [new Condition('order_total', 'gt', 500.0)],
                Rule::OPERATOR_AND,
                'high-value',
            ),
            // Rule 2: cod (payment_method eq "cod") — should match
            new Rule(
                [new Condition('payment_method', 'eq', 'cod')],
                Rule::OPERATOR_AND,
                'cod',
            ),
            // Rule 3: pickup (shipping_method contains "local_pickup") — should NOT match
            new Rule(
                [new Condition('shipping_method', 'contains', 'local_pickup')],
                Rule::OPERATOR_AND,
                'pickup',
            ),
            // Rule 4: international (shipping_country NOT IN ["US","CA"]) — should match
            new Rule(
                [new Condition('shipping_country', 'not_in', ['US', 'CA'])],
                Rule::OPERATOR_AND,
                'international',
            ),
            // Rule 5: bulk (item_count >= 10) — should match
            new Rule(
                [new Condition('item_count', 'gte', 10)],
                Rule::OPERATOR_AND,
                'bulk',
            ),
        ];

        $engine  = new Engine($rules);
        $context = [
            'order_total'      => 750.0,
            'payment_method'   => 'cod',
            'shipping_method'  => 'flat_rate:1',
            'shipping_country' => 'GB',
            'item_count'       => 12,
        ];

        $result = $engine->evaluate($context);

        assertSame(['high-value', 'cod', 'international', 'bulk'], $result);
    }

    public function testRealisticVipCustomerScenario(): void
    {
        // Rule 6: VIP (customer_ltv > 1000 AND customer_order_count >= 5)
        // Rule 7: Repeat buyer (customer_order_count >= 3)
        $rules = [
            new Rule(
                [
                    new Condition('customer_ltv',         'gt',  1000.0),
                    new Condition('customer_order_count', 'gte', 5),
                ],
                Rule::OPERATOR_AND,
                'vip',
            ),
            new Rule(
                [new Condition('customer_order_count', 'gte', 3)],
                Rule::OPERATOR_AND,
                'repeat-buyer',
            ),
        ];

        $engine = new Engine($rules);

        // Customer qualifies for both
        $vipContext = ['customer_ltv' => 2000.0, 'customer_order_count' => 8];
        assertSame(['vip', 'repeat-buyer'], $engine->evaluate($vipContext));

        // Customer only qualifies for repeat-buyer (ltv too low)
        $repeatContext = ['customer_ltv' => 200.0, 'customer_order_count' => 5];
        assertSame(['repeat-buyer'], $engine->evaluate($repeatContext));

        // Customer qualifies for neither
        $newContext = ['customer_ltv' => 50.0, 'customer_order_count' => 1];
        assertSame([], $engine->evaluate($newContext));
    }
}
