<?php
/**
 * Condition — evaluates a single condition against a data context.
 *
 * Pure PHP, zero WP/WC dependencies.
 */
declare(strict_types=1);

namespace HarperAgency\WCTagger\RuleEngine;

class Condition
{
    /**
     * @param string       $field    The context key to evaluate (e.g. "order_total")
     * @param string       $operator Comparison operator (gt, gte, lt, lte, eq, between, contains, in, not_in)
     * @param mixed        $value    Scalar or array depending on operator
     */
    public function __construct(
        private readonly string $field,
        private readonly string $operator,
        private readonly mixed  $value,
    ) {}

    /**
     * Evaluate this condition against a context array.
     *
     * @param array<string, mixed> $context
     */
    public function evaluate(array $context): bool
    {
        return match ($this->field) {
            'order_total',
            'item_count',
            'customer_ltv',
            'customer_order_count' => $this->evaluateNumeric($context[$this->field] ?? null),

            'shipping_method',
            'payment_method'       => $this->evaluateStringField($context[$this->field] ?? ''),

            'shipping_country',
            'billing_country'      => $this->evaluateCountry($context[$this->field] ?? ''),

            'contains_sku'         => $this->evaluateContainsSku($context['items'] ?? []),
            'contains_category'    => $this->evaluateContainsCategory($context['items'] ?? []),

            'customer_has_tag'     => $this->evaluateCustomerHasTag($context['customer_tags'] ?? []),

            default                => false,
        };
    }

    // ── Numeric evaluation ────────────────────────────────────────────────────

    private function evaluateNumeric(mixed $contextValue): bool
    {
        if ($contextValue === null) {
            return false;
        }

        $v = (float) $contextValue;

        return match ($this->operator) {
            'gt'      => $v >  (float) $this->value,
            'gte'     => $v >= (float) $this->value,
            'lt'      => $v <  (float) $this->value,
            'lte'     => $v <= (float) $this->value,
            'eq'      => $v === (float) $this->value,
            'between' => $this->evaluateBetween($v),
            default   => false,
        };
    }

    /**
     * @param float $v
     */
    private function evaluateBetween(float $v): bool
    {
        if (!is_array($this->value) || count($this->value) !== 2) {
            return false;
        }

        [$min, $max] = $this->value;
        return $v >= (float) $min && $v <= (float) $max;
    }

    // ── String field evaluation (shipping_method, payment_method) ─────────────

    private function evaluateStringField(string $contextValue): bool
    {
        return match ($this->operator) {
            'eq'       => $contextValue === (string) $this->value,
            'contains' => str_contains($contextValue, (string) $this->value),
            'in'       => is_array($this->value) && in_array($contextValue, $this->value, true),
            'not_in'   => !is_array($this->value) || !in_array($contextValue, $this->value, true),
            default    => false,
        };
    }

    // ── Country evaluation ────────────────────────────────────────────────────

    private function evaluateCountry(string $contextValue): bool
    {
        return match ($this->operator) {
            'eq'     => $contextValue === (string) $this->value,
            'in'     => is_array($this->value) && in_array($contextValue, $this->value, true),
            'not_in' => !is_array($this->value) || !in_array($contextValue, $this->value, true),
            default  => false,
        };
    }

    // ── Item-based evaluation ─────────────────────────────────────────────────

    /**
     * @param array<int, array{sku: string, categories: string[]}> $items
     */
    private function evaluateContainsSku(array $items): bool
    {
        if ($this->operator !== 'eq') {
            return false;
        }

        $target = (string) $this->value;
        foreach ($items as $item) {
            if (($item['sku'] ?? '') === $target) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<int, array{sku: string, categories: string[]}> $items
     */
    private function evaluateContainsCategory(array $items): bool
    {
        if ($this->operator !== 'eq') {
            return false;
        }

        $target = (string) $this->value;
        foreach ($items as $item) {
            if (in_array($target, $item['categories'] ?? [], true)) {
                return true;
            }
        }

        return false;
    }

    // ── Customer tag evaluation ───────────────────────────────────────────────

    /**
     * @param string[] $customerTags
     */
    private function evaluateCustomerHasTag(array $customerTags): bool
    {
        if ($this->operator !== 'eq') {
            return false;
        }

        return in_array((string) $this->value, $customerTags, true);
    }

    // ── Accessors (useful for tests / serialisation) ──────────────────────────

    public function getField(): string    { return $this->field; }
    public function getOperator(): string { return $this->operator; }
    public function getValue(): mixed     { return $this->value; }
}
