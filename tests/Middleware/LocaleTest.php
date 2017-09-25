<?php

namespace Core\Tests\Middleware;

use Core\Middleware\Locale;
use Core\Services\Cookie;
use Core\Services\Delegate;
use Core\Services\DI;
use Core\Services\Request;
use Core\Services\Response;
use Core\Testing\TestCase;

class LocaleTest extends TestCase
{
    /**
     * @var Locale
     */
    private $middleware;

    private static $origCookie;

    public static function setUpBeforeClass()
    {
        self::$origCookie = DI::getInstance()->get('cookie');
        DI::getInstance()->get('config')->set('locale.default', 'en');
    }

    public static function tearDownAfterClass()
    {
        DI::getInstance()->set('cookie', self::$origCookie, true);
    }

    protected function setUp()
    {
        $this->middleware = new Locale();
    }

    public function testProcess()
    {
        DI::getInstance()->get('config')->set('locale.supported', ['en' => 'English', 'de' => 'Deutsch']);

        $request = $this->getMockBuilder(Request::class)->setMethods(['segment'])->getMock();
        $request->expects($this->once())->method('segment')->with(0)->willReturn('en');
        DI::getInstance()->set('request', $request, true);

        $delegate = $this->getMockBuilder(Delegate::class)->setMethods(['process'])->getMock();
        $delegate->expects($this->once())->method('process')->with($request)->willReturn(new Response);

        $cookie = $this->getMockBuilder(Cookie::class)->setMethods(['get', 'setForever'])->getMock();
        $cookie->expects($this->once())->method('get')->with('locale')->willReturn('de');
        $cookie->expects($this->once())->method('setForever')->with('locale', 'en')->willReturnSelf();
        DI::getInstance()->set('cookie', $cookie, true);

        $response = $this->middleware->process($request, $delegate);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }
}