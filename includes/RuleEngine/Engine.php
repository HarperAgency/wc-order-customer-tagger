<?php
/**
 * Engine — evaluates an array of Rules against a context, returning matched tag IDs.
 *
 * Pure PHP, zero WP/WC dependencies.
 */
declare(strict_types=1);

namespace HarperAgency\WCTagger\RuleEngine;

class Engine
{
    /** @param Rule[] $rules */
    public function __construct(private readonly array $rules = []) {}

    /**
     * Evaluate all rules against the given context.
     *
     * Returns a deduplicated array of tag IDs whose rules matched.
     *
     * @param  array<string, mixed> $context
     * @return array<int, int|string>
     */
    public function evaluate(array $context): array
    {
        $matched = [];

        foreach ($this->rules as $rule) {
            if ($rule->evaluate($context)) {
                $tagId = $rule->getTagId();
                if (!in_array($tagId, $matched, true)) {
                    $matched[] = $tagId;
                }
            }
        }

        return $matched;
    }
}
