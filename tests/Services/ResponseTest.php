<?php

namespace Core\Tests\Services;

use Core\Services\Contracts\Response;
use Core\Services\DI;
use Core\Services\View;
use Core\Testing\TestCase;

require_once __DIR__ . '/../_data/fakes/headers_sent.php.fake';

class ResponseTest extends TestCase
{
    /**
     * @var Response
     */
    private $r;

    private static $origView;

    public static function setUpBeforeClass()
    {
        self::$origView = DI::getInstance()->get('view');
    }

    public static function tearDownAfterClass()
    {
        DI::getInstance()->set('view', get_class(self::$origView), false); // Note, that a view is not shared!
    }

    protected function setUp()
    {
        $this->r = new \Core\Services\Response();
    }

    public function testOutputAndClear()
    {
        $this->r->clear();
        $this->assertInstanceOf(Response::class, $this->r->output('bla', HTTP_STATUS_ACCEPTED, ['foo' => 'bar']));
        $this->assertSame('bla', $this->r->getContent());
        $this->assertSame(HTTP_STATUS_ACCEPTED, $this->r->getStatusCode());
        $this->assertSame(['foo' => 'bar'], $this->r->getHeader());
        $this->assertInstanceOf(Response::class, $this->r->clear());
        $this->assertEmpty($this->r->getContent());
        $this->assertSame(HTTP_STATUS_OK, $this->r->getStatusCode());
        $this->assertEmpty($this->r->getHeader());
    }

    public function testView()
    {
        $view = $this->getMockBuilder(View::class)->setMethods(['render'])->getMock();
        $view->expects($this->once())->method('render')->willReturn('blub');
        DI::getInstance()->set('view', $view, true);

        $this->r->clear();
        $result = $this->r->view('fake');
        $this->assertInstanceOf(Response::class, $result);
        $this->assertSame('blub', $result->getContent());
    }

    public function testRedirect()
    {
        $this->r->clear();
        $this->assertInstanceOf(Response::class, $this->r->redirect('https://example.de', HTTP_STATUS_FOUND, ['foo2' => 'bar2']));
        $this->assertSame(HTTP_STATUS_FOUND, $this->r->getStatusCode());
        $this->assertSame(['foo2' => 'bar2', 'location' => 'https://example.de'], $this->r->getHeader());
        $this->r->clear();
    }

    public function testStatusCodeAndText()
    {
        $this->r->clear();
        $this->assertInstanceOf(Response::class, $this->r->status(HTTP_STATUS_ACCEPTED));
        $this->assertSame(HTTP_STATUS_ACCEPTED, $this->r->getStatusCode());
        $this->assertSame('Accepted', $this->r->getStatusText());
    }

    public function testHeader()
    {
        $this->r->clear();
        $this->assertInstanceOf(Response::class, $this->r->header('a', 'A'));
        $this->assertInstanceOf(Response::class, $this->r->header(['b' => 'B', 'c' => 'C']));
        $this->assertSame(['a' => 'A', 'b' => 'B', 'c' => 'C'], $this->r->getHeader());
        $this->assertSame('B', $this->r->getHeader('b'));
    }

    public function testWrite()
    {
        $this->r->clear();
        $this->assertInstanceOf(Response::class, $this->r->output('Hello'));
        $this->assertInstanceOf(Response::class, $this->r->write(' World!'));
        $this->assertSame('Hello World!', $this->r->getContent());
    }

    public function testCache()
    {
        $this->r->clear();
        $this->assertInstanceOf(Response::class, $this->r->cache(false));
        $this->assertSame([
            'Expires' => 'Mon, 26 Jul 1997 05:00:00 GMT',
            'Cache-Control' => [
                'no-store, no-cache, must-revalidate',
                'post-check=0, pre-check=0',
                'max-age=0'
            ],
            'Pragma' => 'no-cache'
        ], $this->r->getHeader());

        $this->r->clear();
        $this->r->header('Pragma', 'no-cache');
        $this->assertInstanceOf(Response::class, $this->r->cache('2017-06-26 04:05:06'));
        $h = $this->r->getHeader();
        $this->assertArrayHasKey('Expires', $h);
        $this->assertSame(gmdate('D, d M Y H:i:s', strtotime('2017-06-26 04:05:06')) . ' GMT', $h['Expires']);
        $this->assertArrayHasKey('Cache-Control', $h);
        $this->assertStringStartsWith('max-age=', $h['Cache-Control']);
        $this->assertArrayNotHasKey('Pragma', $h);
    }

    public function testSend()
    {
        $this->r->clear();
        $this->r->output('Rolling Stones', HTTP_STATUS_OK, ['foo' => 'bar', 'blub' => ['a' => 'A']]);
        ob_start();
        try {
            $this->r->send();
        }
        finally {
            $out = ob_get_clean();
        }
        $this->assertSame('Rolling Stones', $out);
    }
}
