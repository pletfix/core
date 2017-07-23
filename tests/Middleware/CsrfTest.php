<?php

namespace Core\Tests\Middleware;

use Core\Exceptions\HttpException;
use Core\Middleware\Csrf;
use Core\Services\Delegate;
use Core\Services\DI;
use Core\Services\Request;
use Core\Services\Response;
use Core\Services\Session;
use Core\Testing\TestCase;

class CsrfTest extends TestCase
{
    /**
     * @var Csrf
     */
    private $middleware;

    private static $origSession;

    public static function setUpBeforeClass()
    {
        self::$origSession = DI::getInstance()->get('session');
    }

    public static function tearDownAfterClass()
    {
        DI::getInstance()->set('session', self::$origSession, true);
    }

    protected function setUp()
    {
        $this->middleware = new Csrf;
    }

    public function testNotPass()
    {
        $session = $this->getMockBuilder(Session::class)->setMethods(['get'])->getMock();
        $session->expects($this->once())->method('get')->with('_csrf_token')->willReturn('123456789');
        DI::getInstance()->set('session', $session, true);

        $request = $this->getMockBuilder(Request::class)->setMethods(['method', 'input'])->getMock();
        $request->expects($this->once())->method('method')->willReturn('POST');
        $request->expects($this->once())->method('input')->with('_token')->willReturn('wrong');

        $delegate = new Delegate;

        $this->expectException(HttpException::class);
        $this->middleware->process($request, $delegate);
    }

    public function testPass()
    {
        $session = $this->getMockBuilder(Session::class)->setMethods(['get'])->getMock();
        $session->expects($this->once())->method('get')->with('_csrf_token')->willReturn('123456789');
        DI::getInstance()->set('session', $session, true);

        $request = $this->getMockBuilder(Request::class)->setMethods(['method', 'input'])->getMock();
        $request->expects($this->once())->method('method')->willReturn('POST');
        $request->expects($this->once())->method('input')->with('_token')->willReturn('123456789');

        $delegate = $this->getMockBuilder(Delegate::class)->setMethods(['process'])->getMock();
        $delegate->expects($this->once())->method('process')->with($request)->willReturn(new Response);

        $response = $this->middleware->process($request, $delegate);
        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(HTTP_STATUS_OK, $response->getStatusCode());
    }
}