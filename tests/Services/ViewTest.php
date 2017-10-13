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
    private $view;

    public static function setUpBeforeClass()
    {
        View::clearManifestCache();
    }

    public static function tearDownAfterClass()
    {
        View::clearManifestCache();
        @unlink(storage_path('cache/views/' . md5(__DIR__ . '/../_data/views/foo/bar.blade.php') . '.phtml'));
        @unlink(storage_path('cache/views/' . md5(__DIR__ . '/../_data/views/foo/invalid.blade.php') . '.phtml'));
        @unlink(storage_path('cache/views/' . md5(__DIR__ . '/../_data/views/layout.blade.php') . '.phtml'));
        @unlink(storage_path('cache/views/' . md5(__DIR__ . '/../_data/plugins/test/views/foo/baz.blade.php') . '.phtml'));
    }

    protected function setUp()
    {
        $this->view = new View(__DIR__ . '/../_data/views', __DIR__ . '/../_data/plugin_manifest/views.php');
    }

    public function testExists()
    {
        $this->assertTrue($this->view->exists('foo.bar'));
        $this->assertFalse($this->view->exists('wrong'));
    }

    public function testRender()
    {
        $s = "<html>\n<head>\n    <title>Test</title>\n</head>\n<body>\n        You talking to me?\n</body>\n</html>";
        $this->assertSame($s, trim($this->view->render('foo.bar')));
        $this->assertSame($s, trim($this->view->render('foo.bar', ['a' => 'A'])));
        $this->assertSame($s, trim($this->view->render('foo.bar', new Collection(['a' => 'A']))));
    }

    public function testRenderFromPlugin()
    {
        $this->assertSame('<h3>Dummy View</h3>', trim($this->view->render('~test.foo.baz')));
    }

    public function testClearManifestCache()
    {
        $this->view->render('~test.foo.baz');
        $manifest = $this->getPrivateProperty($this->view, 'manifest');
        $this->assertNotEmpty($manifest);
        View::clearManifestCache();
        $manifest = $this->getPrivateProperty($this->view, 'manifest');
        $this->assertEmpty($manifest);
    }

    public function testCreateCache()
    {
        $cacheFile = storage_path('cache/views/' . md5(__DIR__ . '/../_data/views/foo/bar.blade.php') . '.phtml');
        @unlink($cacheFile);
        $this->view->render('foo.bar');
        $this->assertSame(filemtime($cacheFile), filemtime(__DIR__ . '/../_data/views/foo/bar.blade.php'));
    }

    public function testUpdateCache()
    {
        $cacheFile = storage_path('cache/views/' . md5(__DIR__ . '/../_data/views/foo/bar.blade.php') . '.phtml');
        touch($cacheFile);
        $this->view->render('foo.bar');
        $this->assertSame(filemtime($cacheFile), filemtime(__DIR__ . '/../_data/views/foo/bar.blade.php'));
    }

    public function testRenderNotExistView()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->view->render('wrong');
    }

    public function testRenderInvalidView()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->view->render('foo.invalid');
    }
}