<?php

namespace Core\Tests\Services;

use Core\Services\Contracts\Response;
use Core\Services\DI;
use Core\Services\Request;
use Core\Services\View;
use Core\Testing\TestCase;

require_once __DIR__ . '/../_data/fakes/headers_sent.php.fake';

class ResponseTest extends TestCase
{
    /**
     * @var Response
     */
    private $response;

    private static $origRequest;
    private static $origView;

    public static function setUpBeforeClass()
    {
        self::$origRequest = DI::getInstance()->get('request');
        self::$origView = DI::getInstance()->get('view');
    }

    public static function tearDownAfterClass()
    {
        DI::getInstance()->set('request', self::$origRequest, true);
        DI::getInstance()->set('view', get_class(self::$origView), false); // Note, that a view is not shared!
    }

    protected function setUp()
    {
        $this->response = new \Core\Services\Response();
    }

    public function testOutputAndClear()
    {
        $this->response->clear();
        $this->assertInstanceOf(Response::class, $this->response->output('bla', Response::HTTP_ACCEPTED, ['foo' => 'bar']));
        $this->assertSame('bla', $this->response->getContent());
        $this->assertSame(Response::HTTP_ACCEPTED, $this->response->getStatusCode());
        $this->assertSame(['foo' => 'bar'], $this->response->getHeader());
        $this->assertInstanceOf(Response::class, $this->response->clear());
        $this->assertEmpty($this->response->getContent());
        $this->assertSame(Response::HTTP_OK, $this->response->getStatusCode());
        $this->assertEmpty($this->response->getHeader());
    }

    public function testView()
    {
        $view = $this->getMockBuilder(View::class)->setMethods(['render'])->getMock();
        $view->expects($this->once())->method('render')->willReturn('blub');
        DI::getInstance()->set('view', $view, true);

        $this->response->clear();
        $result = $this->response->view('fake');
        $this->assertInstanceOf(Response::class, $result);
        $this->assertSame('blub', $result->getContent());
    }

    public function testRedirect()
    {
        $this->response->clear();
        $this->assertInstanceOf(Response::class, $this->response->redirect('https://example.de', Response::HTTP_FOUND, ['foo2' => 'bar2']));
        $this->assertSame(Response::HTTP_FOUND, $this->response->getStatusCode());
        $this->assertSame(['foo2' => 'bar2', 'location' => 'https://example.de'], $this->response->getHeader());
        $this->response->clear();
    }

    public function testStatusCodeAndText()
    {
        $this->response->clear();
        $this->assertInstanceOf(Response::class, $this->response->status(Response::HTTP_ACCEPTED));
        $this->assertSame(Response::HTTP_ACCEPTED, $this->response->getStatusCode());
        $this->assertSame('Accepted', $this->response->getStatusText());
    }

    public function testHeader()
    {
        $this->response->clear();
        $this->assertInstanceOf(Response::class, $this->response->header('a', 'A'));
        $this->assertInstanceOf(Response::class, $this->response->header(['b' => 'B', 'c' => 'C']));
        $this->assertSame(['a' => 'A', 'b' => 'B', 'c' => 'C'], $this->response->getHeader());
        $this->assertSame('B', $this->response->getHeader('b'));
    }

    public function testJson()
    {
        $this->response->clear();
        $this->assertInstanceOf(Response::class, $this->response->json(['foo' => 'bar']));
        $this->assertSame('{"foo":"bar"}', $this->response->getContent());
    }

    public function testDownload()
    {
        $this->response->clear();
        $this->assertInstanceOf(Response::class, $this->response->download(__DIR__ . '/../_data/images/logo_50x50.png', 'logo.png'));
        $this->assertSame('attachment; filename="logo.png"', $this->response->getHeader('Content-Disposition'));
        $this->assertSame('image/png', $this->response->getHeader('Content-Type'));
        $this->assertSame('bytes', $this->response->getHeader('Accept-Ranges'));
        $this->assertSame('public', $this->response->getHeader('Cache-Control'));
        $this->assertSame('Wed, 21 Jun 2017 15:36:45 GMT', $this->response->getHeader('Last-Modified'));
        $this->assertSame('PNG', substr($this->response->getContent(), 1, 3));
    }

    public function testFile()
    {
        $this->response->clear();
        $this->assertInstanceOf(Response::class, $this->response->file(__DIR__ . '/../_data/images/logo_50x50.png'));
        $this->assertArrayNotHasKey('Content-Disposition', $this->response->getHeader());
        $this->assertSame('image/png', $this->response->getHeader('Content-Type'));
        $this->assertSame('bytes', $this->response->getHeader('Accept-Ranges'));
        $this->assertSame('public', $this->response->getHeader('Cache-Control'));
        $this->assertSame('Wed, 21 Jun 2017 15:36:45 GMT', $this->response->getHeader('Last-Modified'));
        $this->assertSame('PNG', substr($this->response->getContent(), 1, 3));
    }

    public function testWrite()
    {
        $this->response->clear();
        $this->assertInstanceOf(Response::class, $this->response->output('Hello'));
        $this->assertInstanceOf(Response::class, $this->response->write(' World!'));
        $this->assertSame('Hello World!', $this->response->getContent());
    }

    public function testCache()
    {
        $this->response->clear();
        $this->assertInstanceOf(Response::class, $this->response->cache(false));
        $this->assertSame([
            'Expires' => 'Mon, 26 Jul 1997 05:00:00 GMT',
            'Cache-Control' => [
                'no-store, no-cache, must-revalidate',
                'post-check=0, pre-check=0',
                'max-age=0'
            ],
            'Pragma' => 'no-cache'
        ], $this->response->getHeader());

        $this->response->clear();
        $this->response->header('Pragma', 'no-cache');
        $this->assertInstanceOf(Response::class, $this->response->cache('2017-06-26 04:05:06'));
        $h = $this->response->getHeader();
        $this->assertArrayHasKey('Expires', $h);
        $this->assertSame(gmdate('D, d M Y H:i:s', strtotime('2017-06-26 04:05:06')) . ' GMT', $h['Expires']);
        $this->assertArrayHasKey('Cache-Control', $h);
        $this->assertStringStartsWith('max-age=', $h['Cache-Control']);
        $this->assertArrayNotHasKey('Pragma', $h);
    }

    public function testSend()
    {
        $this->response->clear();
        $this->response->output('Rolling Stones', Response::HTTP_OK, ['foo' => 'bar', 'blub' => ['a' => 'A']]);
        ob_start();
        try {
            $this->response->send();
        }
        finally {
            $out = ob_get_clean();
        }
        $this->assertSame('Rolling Stones', $out);
    }

    public function testWithFlash()
    {
        $request = $this->getMockBuilder(Request::class)->setMethods(['baseUrl'])->getMock();
        $request->expects($this->any())->method('baseUrl')->willReturn('my_base_url');
        DI::getInstance()->set('request', $request, true);

        $this->assertInstanceOf(Response::class, $this->response->withFlash('foo', 'bar'));
        $this->assertSame('bar', flash()->age()->get('foo'));

        $this->assertInstanceOf(Response::class, $this->response->withFlash('a', ['fuu' => 'buu'])->withFlash('a', ['foo' => 'boo']));
        $this->assertSame(['fuu' => 'buu', 'foo' => 'boo'], flash()->age()->get('a'));
    }

    public function testWithInput()
    {
        $request = $this->getMockBuilder(Request::class)->setMethods(['baseUrl'])->getMock();
        $request->expects($this->any())->method('baseUrl')->willReturn('my_base_url');
        DI::getInstance()->set('request', $request, true);

        $_POST = ['fuu' => 'buu'];
        $_GET  = [];
        $this->assertInstanceOf(Response::class, $this->response->withInput());
        $this->assertSame(['fuu' => 'buu'], flash()->age()->get('input'));

        $this->assertInstanceOf(Response::class, $this->response->withInput(['foo' => 'bar']));
        $this->assertSame(['foo' => 'bar'], flash()->age()->get('input'));
    }

    public function testWithMessage()
    {
        $request = $this->getMockBuilder(Request::class)->setMethods(['baseUrl'])->getMock();
        $request->expects($this->any())->method('baseUrl')->willReturn('my_base_url');
        DI::getInstance()->set('request', $request, true);

        $this->assertInstanceOf(Response::class, $this->response->withMessage('hello'));
        $this->assertSame('hello', flash()->age()->get('message'));
    }

    public function testWithErrors()
    {
        $request = $this->getMockBuilder(Request::class)->setMethods(['baseUrl'])->getMock();
        $request->expects($this->any())->method('baseUrl')->willReturn('my_base_url');
        DI::getInstance()->set('request', $request, true);

        $this->assertInstanceOf(Response::class, $this->response->withErrors(['email' => 'Invalid email format.']));
        $this->assertSame(['email' => 'Invalid email format.'], flash()->age()->get('errors'));
    }

    public function testWithError()
    {
        $request = $this->getMockBuilder(Request::class)->setMethods(['baseUrl'])->getMock();
        $request->expects($this->any())->method('baseUrl')->willReturn('my_base_url');
        DI::getInstance()->set('request', $request, true);

        $this->assertInstanceOf(Response::class, $this->response->withError('Invalid email format.', 'email'));
        $this->assertSame(['email' => 'Invalid email format.'], flash()->age()->get('errors'));
    }
}
