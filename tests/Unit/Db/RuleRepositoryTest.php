<?php
declare(strict_types=1);

namespace HarperAgency\WCTagger\Tests\Unit\Db;

use HarperAgency\WCTagger\Db\RuleRepository;
use PHPUnit\Framework\TestCase;

/**
 * Tests for RuleRepository.
 * DB calls are exercised through the $wpdb mock injected via the global.
 */
class RuleRepositoryTest extends TestCase
{
    // ── findByTagId ───────────────────────────────────────────────────────────

    public function testFindByTagIdReturnsArray(): void
    {
        $wpdb = $this->makeWpdb();
        $wpdb->prefix = 'wp_';

        $wpdb->expects($this->once())
             ->method('prepare')
             ->willReturn('PREPARED_SQL');

        $wpdb->expects($this->once())
             ->method('get_results')
             ->with('PREPARED_SQL', ARRAY_A)
             ->willReturn([
                 ['id' => 1, 'tag_id' => 5, 'label' => 'Test rule',
                  'rule_trigger' => 'order_placed', 'operator' => 'AND',
                  'conditions' => '[]', 'priority' => 10, 'is_active' => 1,
                  'created_at' => '2026-01-01 00:00:00'],
             ]);

        $GLOBALS['wpdb'] = $wpdb;
        $repo  = $this->makeRepo();
        $rules = $repo->findByTagId(5);

        $this->assertIsArray($rules);
        $this->assertCount(1, $rules);
        $this->assertSame('Test rule', $rules[0]['label']);
        // conditions should be decoded to an array
        $this->assertIsArray($rules[0]['conditions']);
    }

    public function testFindByTagIdReturnsEmptyArrayWhenNoRules(): void
    {
        $wpdb = $this->makeWpdb();
        $wpdb->prefix = 'wp_';
        $wpdb->method('prepare')->willReturn('SQL');
        $wpdb->method('get_results')->willReturn([]);

        $GLOBALS['wpdb'] = $wpdb;
        $this->assertSame([], $this->makeRepo()->findByTagId(99));
    }

    // ── findById ──────────────────────────────────────────────────────────────

    public function testFindByIdReturnsRowWithDecodedConditions(): void
    {
        $wpdb = $this->makeWpdb();
        $wpdb->prefix = 'wp_';
        $wpdb->method('prepare')->willReturn('SQL');
        $wpdb->method('get_row')->willReturn([
            'id' => 3, 'tag_id' => 2, 'label' => 'High value',
            'rule_trigger' => 'order_placed', 'operator' => 'AND',
            'conditions' => '[{"field":"order_total","operator":"gt","value":"500"}]',
            'priority' => 5, 'is_active' => 1, 'created_at' => '2026-01-01 00:00:00',
        ]);

        $GLOBALS['wpdb'] = $wpdb;
        $rule = $this->makeRepo()->findById(3);

        $this->assertNotNull($rule);
        $this->assertIsArray($rule['conditions']);
        $this->assertCount(1, $rule['conditions']);
        $this->assertSame('order_total', $rule['conditions'][0]['field']);
    }

    public function testFindByIdReturnsNullWhenNotFound(): void
    {
        $wpdb = $this->makeWpdb();
        $wpdb->prefix = 'wp_';
        $wpdb->method('prepare')->willReturn('SQL');
        $wpdb->method('get_row')->willReturn(null);

        $GLOBALS['wpdb'] = $wpdb;
        $this->assertNull($this->makeRepo()->findById(999));
    }

    // ── insert ────────────────────────────────────────────────────────────────

    public function testInsertReturnsInt(): void
    {
        $wpdb = $this->makeWpdb();
        $wpdb->prefix    = 'wp_';
        $wpdb->insert_id = 7;

        $wpdb->expects($this->once())
             ->method('insert')
             ->with(
                 'wp_harper_tagger_rules',
                 $this->callback(fn($d) =>
                     $d['tag_id']       === 2 &&
                     $d['label']        === 'My rule' &&
                     $d['operator']     === 'AND' &&
                     $d['is_active']    === 1
                 )
             );

        $GLOBALS['wpdb'] = $wpdb;
        $repo = $this->makeRepo();

        $id = $repo->insert(2, [
            'label'        => 'My rule',
            'rule_trigger' => 'order_placed',
            'operator'     => 'AND',
            'conditions'   => [],
            'priority'     => 10,
            'is_active'    => 1,
        ]);

        $this->assertSame(7, $id);
        $this->assertIsInt($id);
    }

    public function testInsertReturnsZeroOnWpdbFailure(): void
    {
        $wpdb = $this->makeWpdb();
        $wpdb->prefix    = 'wp_';
        $wpdb->insert_id = 0;
        $wpdb->method('insert')->willReturn(false);

        $GLOBALS['wpdb'] = $wpdb;
        $id = $this->makeRepo()->insert(1, ['label' => 'X', 'conditions' => []]);
        $this->assertSame(0, $id);
    }

    // ── update ────────────────────────────────────────────────────────────────

    public function testUpdateReturnsTrueOnSuccess(): void
    {
        $wpdb = $this->makeWpdb();
        $wpdb->prefix = 'wp_';

        // First call: get_row to fetch tag_id; second call: update
        $wpdb->method('prepare')->willReturn('SQL');
        $wpdb->method('get_row')->willReturn(['tag_id' => 3]);
        $wpdb->expects($this->once())
             ->method('update')
             ->willReturn(1);

        $GLOBALS['wpdb'] = $wpdb;
        $this->assertTrue($this->makeRepo()->update(4, ['label' => 'Updated']));
    }

    public function testUpdateReturnsFalseOnWpdbFailure(): void
    {
        $wpdb = $this->makeWpdb();
        $wpdb->prefix = 'wp_';
        $wpdb->method('prepare')->willReturn('SQL');
        $wpdb->method('get_row')->willReturn(['tag_id' => 1]);
        $wpdb->method('update')->willReturn(false);

        $GLOBALS['wpdb'] = $wpdb;
        $this->assertFalse($this->makeRepo()->update(4, ['label' => 'Updated']));
    }

    // ── delete ────────────────────────────────────────────────────────────────

    public function testDeleteReturnsTrue(): void
    {
        $wpdb = $this->makeWpdb();
        $wpdb->prefix = 'wp_';

        $wpdb->expects($this->once())
             ->method('delete')
             ->with('wp_harper_tagger_rules', ['id' => 3])
             ->willReturn(1);

        $GLOBALS['wpdb'] = $wpdb;
        $this->assertTrue($this->makeRepo()->delete(3));
    }

    public function testDeleteReturnsFalseOnWpdbFailure(): void
    {
        $wpdb = $this->makeWpdb();
        $wpdb->prefix = 'wp_';
        $wpdb->method('delete')->willReturn(false);

        $GLOBALS['wpdb'] = $wpdb;
        $this->assertFalse($this->makeRepo()->delete(3));
    }

    // ── countByTagId ──────────────────────────────────────────────────────────

    public function testCountByTagIdReturnsInt(): void
    {
        $wpdb = $this->makeWpdb();
        $wpdb->prefix = 'wp_';
        $wpdb->method('prepare')->willReturn('SQL');
        $wpdb->method('get_var')->willReturn('4');

        $GLOBALS['wpdb'] = $wpdb;
        $this->assertSame(4, $this->makeRepo()->countByTagId(5));
    }

    // ── Sanitize: operator normalisation ─────────────────────────────────────

    public function testInvalidOperatorDefaultsToAnd(): void
    {
        $wpdb = $this->makeWpdb();
        $wpdb->prefix    = 'wp_';
        $wpdb->insert_id = 1;

        $wpdb->expects($this->once())
             ->method('insert')
             ->with(
                 'wp_harper_tagger_rules',
                 $this->callback(fn($d) => $d['operator'] === 'AND')
             );

        $GLOBALS['wpdb'] = $wpdb;
        $this->makeRepo()->insert(1, ['label' => 'X', 'operator' => 'XOR', 'conditions' => []]);
    }

    public function testValidOperatorOrIsPreserved(): void
    {
        $wpdb = $this->makeWpdb();
        $wpdb->prefix    = 'wp_';
        $wpdb->insert_id = 1;

        $wpdb->expects($this->once())
             ->method('insert')
             ->with(
                 'wp_harper_tagger_rules',
                 $this->callback(fn($d) => $d['operator'] === 'OR')
             );

        $GLOBALS['wpdb'] = $wpdb;
        $this->makeRepo()->insert(1, ['label' => 'X', 'operator' => 'OR', 'conditions' => []]);
    }

    // ── Sanitize: conditions encoding ────────────────────────────────────────

    public function testConditionsEncodedAsJsonString(): void
    {
        $wpdb = $this->makeWpdb();
        $wpdb->prefix    = 'wp_';
        $wpdb->insert_id = 1;

        $conditions = [['field' => 'order_total', 'operator' => 'gt', 'value' => '500']];

        $wpdb->expects($this->once())
             ->method('insert')
             ->with(
                 'wp_harper_tagger_rules',
                 $this->callback(fn($d) =>
                     is_string($d['conditions']) &&
                     json_decode($d['conditions'], true) === $conditions
                 )
             );

        $GLOBALS['wpdb'] = $wpdb;
        $this->makeRepo()->insert(1, ['label' => 'X', 'conditions' => $conditions]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function makeRepo(): RuleRepository
    {
        if (!isset($GLOBALS['wpdb'])) {
            $wpdb = $this->makeWpdb();
            $wpdb->prefix = 'wp_';
            $GLOBALS['wpdb'] = $wpdb;
        }
        return new RuleRepository();
    }

    private function makeWpdb(): \wpdb&\PHPUnit\Framework\MockObject\MockObject
    {
        return $this->createMock(\wpdb::class);
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['wpdb']);
    }
}
