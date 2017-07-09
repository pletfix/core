<?php

namespace Core\Tests\Services;

use Core\Services\Collection;
use Core\Services\View;
use Core\Testing\TestCase;
use InvalidArgumentException;

class ViewTest extends TestCase
{
    /**
     * @var View
     */
    private $v;

    public static function tearDownAfterClass()
    {
        @unlink(storage_path('cache/views/25f6fa9de10774bc3375d7d93551a017.phtml')); // 'foo.bar'
        @unlink(storage_path('cache/views/2433f35a285a859f5ef56f295e2dc295.phtml')); // 'layout'
        @unlink(storage_path('cache/views/d52e835e93f8e0d8e92d887b724c45c5.phtml')); // 'foo.invalid'
    }

    protected function setUp()
    {
        $this->v = new View(__DIR__ . '/views');
    }

    public function testExists()
    {
        $this->assertTrue($this->v->exists('foo.bar'));
        $this->assertFalse($this->v->exists('wrong'));
    }

    public function testRender()
    {
        $s = "<html>\n<head>\n    <title>Test</title>\n</head>\n<body>\n        You talking to me?\n</body>\n</html>";
        $this->assertSame($s, trim($this->v->render('foo.bar')));
        $this->assertSame($s, trim($this->v->render('foo.bar', ['a' => 'A'])));
        $this->assertSame($s, trim($this->v->render('foo.bar', new Collection(['a' => 'A']))));
    }

    public function testCreateCache()
    {
        $cachedFile = storage_path('cache/views/25f6fa9de10774bc3375d7d93551a017.phtml'); // 'foo.bar'
        @unlink($cachedFile);
        $this->v->render('foo.bar');
        $this->assertSame(filemtime($cachedFile), filemtime(__DIR__ . '/views/foo/bar.blade.php'));
    }

    public function testUpdateCache()
    {
        $cachedFile = storage_path('cache/views/25f6fa9de10774bc3375d7d93551a017.phtml'); // 'foo.bar'
        @touch($cachedFile);
        $this->v->render('foo.bar');
        $this->assertSame(filemtime($cachedFile), filemtime(__DIR__ . '/views/foo/bar.blade.php'));
    }

    public function testRenderNotExistView()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->v->render('wrong');
    }

    public function testRenderInvalidView()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->v->render('foo.invalid');
    }

    // todo plugin testen (Manifest laden)
}