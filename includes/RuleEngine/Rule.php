<?php
/**
 * Rule — holds a list of Conditions and an AND|OR operator.
 *
 * Pure PHP, zero WP/WC dependencies.
 */
declare(strict_types=1);

namespace HarperAgency\WCTagger\RuleEngine;

class Rule
{
    public const OPERATOR_AND = 'AND';
    public const OPERATOR_OR  = 'OR';

    /**
     * @param Condition[] $conditions
     * @param string      $operator   Rule::OPERATOR_AND or Rule::OPERATOR_OR
     * @param int|string  $tagId      The tag identifier to apply when rule matches
     */
    public function __construct(
        private readonly array      $conditions,
        private readonly string     $operator,
        private readonly int|string $tagId,
    ) {
        if (!in_array($this->operator, [self::OPERATOR_AND, self::OPERATOR_OR], true)) {
            throw new \InvalidArgumentException(
                sprintf('Invalid operator "%s". Must be AND or OR.', $this->operator)
            );
        }
    }

    /**
     * Evaluate this rule against a context array.
     *
     * Returns true when all conditions match (AND) or any condition matches (OR).
     * An empty conditions list always returns false.
     *
     * @param array<string, mixed> $context
     */
    public function evaluate(array $context): bool
    {
        if (empty($this->conditions)) {
            return false;
        }

        if ($this->operator === self::OPERATOR_AND) {
            foreach ($this->conditions as $condition) {
                if (!$condition->evaluate($context)) {
                    return false;
                }
            }
            return true;
        }

        // OR
        foreach ($this->conditions as $condition) {
            if ($condition->evaluate($context)) {
                return true;
            }
        }
        return false;
    }

    // ── Accessors ─────────────────────────────────────────────────────────────

    /** @return Condition[] */
    public function getConditions(): array    { return $this->conditions; }
    public function getOperator(): string     { return $this->operator; }
    public function getTagId(): int|string    { return $this->tagId; }
}
