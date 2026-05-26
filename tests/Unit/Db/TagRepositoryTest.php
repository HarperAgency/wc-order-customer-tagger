<?php
declare(strict_types=1);

namespace HarperAgency\WCTagger\Tests\Unit\Db;

use HarperAgency\WCTagger\Db\TagRepository;
use PHPUnit\Framework\TestCase;

/**
 * Tests for TagRepository — slug generation, color normalisation,
 * and sanitize logic. DB calls are exercised through the $wpdb mock
 * injected via the global.
 */
class TagRepositoryTest extends TestCase
{
    // ── slugify ───────────────────────────────────────────────────────────────

    /** @dataProvider slugifyProvider */
    public function testSlugify(string $input, string $expected): void
    {
        $repo = $this->makeRepo();
        $this->assertSame($expected, $repo->slugify($input));
    }

    public static function slugifyProvider(): array
    {
        return [
            'lowercase'           => ['High Value',    'high-value'],
            'trim spaces'         => ['  bulk  ',      'bulk'],
            'special chars'       => ['VIP & Repeat!', 'vip-repeat'],
            'multiple dashes'     => ['cash  on  delivery', 'cash-on-delivery'],
            'already a slug'      => ['cod',            'cod'],
            'numbers ok'          => ['top-10',         'top-10'],
            'leading/trailing -'  => ['-test-',         'test'],
        ];
    }

    // ── normaliseColor ────────────────────────────────────────────────────────

    /** @dataProvider colorProvider */
    public function testNormaliseColor(string $input, string $expected): void
    {
        $repo = $this->makeRepo();
        $this->assertSame($expected, $repo->normaliseColor($input));
    }

    public static function colorProvider(): array
    {
        return [
            'valid 6-char'         => ['#3788d8', '#3788d8'],
            'uppercase normalised' => ['#3788D8', '#3788d8'],
            'expand 3-char'        => ['#abc',    '#aabbcc'],
            'expand uppercase 3'   => ['#FFF',    '#ffffff'],
            'missing hash'         => ['3788d8',  '#3788d8'],
            'invalid string'       => ['red',     '#3788d8'],
            'empty string'         => ['',        '#3788d8'],
            'too short'            => ['#123',    '#112233'],  // valid 3-char expands
        ];
    }

    // ── insert / findById via wpdb mock ───────────────────────────────────────

    public function testInsertCallsWpdbInsertAndReturnsId(): void
    {
        $wpdb = $this->makeWpdb();
        $wpdb->insert_id = 42;
        $wpdb->prefix    = 'wp_';

        $wpdb->expects($this->once())
             ->method('insert')
             ->with(
                 'wp_harper_tagger_tags',
                 $this->callback(fn($d) =>
                     $d['name']  === 'High Value' &&
                     $d['slug']  === 'high-value' &&
                     $d['color'] === '#e67e22' &&
                     $d['type']  === 'order'
                 )
             );

        $GLOBALS['wpdb'] = $wpdb;
        $repo = $this->makeRepo();

        $id = $repo->insert([
            'name'  => 'High Value',
            'slug'  => 'high-value',
            'color' => '#e67e22',
            'type'  => 'order',
        ]);

        $this->assertSame(42, $id);
    }

    public function testUpdateCallsWpdbUpdate(): void
    {
        $wpdb = $this->makeWpdb();
        $wpdb->prefix = 'wp_';

        $wpdb->expects($this->once())
             ->method('update')
             ->with('wp_harper_tagger_tags', ['name' => 'Updated'], ['id' => 7])
             ->willReturn(1);

        $GLOBALS['wpdb'] = $wpdb;
        $repo = $this->makeRepo();

        $this->assertTrue($repo->update(7, ['name' => 'Updated']));
    }

    public function testUpdateReturnsFalseOnWpdbFailure(): void
    {
        $wpdb = $this->makeWpdb();
        $wpdb->prefix = 'wp_';

        $wpdb->method('update')->willReturn(false);

        $GLOBALS['wpdb'] = $wpdb;
        $this->assertFalse($this->makeRepo()->update(99, ['name' => 'X']));
    }

    public function testDeleteCallsWpdbDelete(): void
    {
        $wpdb = $this->makeWpdb();
        $wpdb->prefix = 'wp_';

        $wpdb->expects($this->once())
             ->method('delete')
             ->with('wp_harper_tagger_tags', ['id' => 5])
             ->willReturn(1);

        $GLOBALS['wpdb'] = $wpdb;
        $this->assertTrue($this->makeRepo()->delete(5));
    }

    public function testDeleteReturnsFalseOnWpdbFailure(): void
    {
        $wpdb = $this->makeWpdb();
        $wpdb->prefix = 'wp_';
        $wpdb->method('delete')->willReturn(false);

        $GLOBALS['wpdb'] = $wpdb;
        $this->assertFalse($this->makeRepo()->delete(5));
    }

    // ── type validation in sanitize ───────────────────────────────────────────

    public function testInvalidTypeDefaultsToOrder(): void
    {
        $wpdb = $this->makeWpdb();
        $wpdb->prefix = 'wp_';
        $wpdb->insert_id = 1;

        $wpdb->expects($this->once())
             ->method('insert')
             ->with('wp_harper_tagger_tags',
                 $this->callback(fn($d) => $d['type'] === 'order')
             );

        $GLOBALS['wpdb'] = $wpdb;
        $this->makeRepo()->insert(['name' => 'Test', 'type' => 'nonsense']);
    }

    /** @dataProvider validTypesProvider */
    public function testValidTypesArePassedThrough(string $type): void
    {
        $wpdb = $this->makeWpdb();
        $wpdb->prefix = 'wp_';
        $wpdb->insert_id = 1;

        $wpdb->expects($this->once())
             ->method('insert')
             ->with('wp_harper_tagger_tags',
                 $this->callback(fn($d) => $d['type'] === $type)
             );

        $GLOBALS['wpdb'] = $wpdb;
        $this->makeRepo()->insert(['name' => 'Test', 'type' => $type]);
    }

    public static function validTypesProvider(): array
    {
        return [['order'], ['customer'], ['both']];
    }

    // ── image_url null handling ───────────────────────────────────────────────

    public function testNullImageUrlPassedThrough(): void
    {
        $wpdb = $this->makeWpdb();
        $wpdb->prefix = 'wp_';
        $wpdb->insert_id = 1;

        $wpdb->expects($this->once())
             ->method('insert')
             ->with('wp_harper_tagger_tags',
                 $this->callback(fn($d) => array_key_exists('image_url', $d) && $d['image_url'] === null)
             );

        $GLOBALS['wpdb'] = $wpdb;
        $this->makeRepo()->insert(['name' => 'Test', 'image_url' => null]);
    }

    public function testImageUrlTruncatedAt500Chars(): void
    {
        $wpdb = $this->makeWpdb();
        $wpdb->prefix = 'wp_';
        $wpdb->insert_id = 1;
        $longUrl = 'https://example.com/' . str_repeat('a', 490);

        $wpdb->expects($this->once())
             ->method('insert')
             ->with('wp_harper_tagger_tags',
                 $this->callback(fn($d) => strlen($d['image_url']) === 500)
             );

        $GLOBALS['wpdb'] = $wpdb;
        $this->makeRepo()->insert(['name' => 'Test', 'image_url' => $longUrl]);
    }

    // ── name truncation ───────────────────────────────────────────────────────

    public function testNameTruncatedAt100Chars(): void
    {
        $wpdb = $this->makeWpdb();
        $wpdb->prefix = 'wp_';
        $wpdb->insert_id = 1;
        $longName = str_repeat('a', 150);

        $wpdb->expects($this->once())
             ->method('insert')
             ->with('wp_harper_tagger_tags',
                 $this->callback(fn($d) => strlen($d['name']) === 100)
             );

        $GLOBALS['wpdb'] = $wpdb;
        $this->makeRepo()->insert(['name' => $longName]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function makeRepo(): TagRepository
    {
        if (!isset($GLOBALS['wpdb'])) {
            $wpdb = $this->makeWpdb();
            $wpdb->prefix = 'wp_';
            $GLOBALS['wpdb'] = $wpdb;
        }
        return new TagRepository();
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
