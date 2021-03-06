<?php

namespace Core\Tests\Middleware;

use Core\Exceptions\HttpException;
use Core\Middleware\Role;
use Core\Services\Auth;
use Core\Services\Delegate;
use Core\Services\DI;
use Core\Services\Request;
use Core\Services\Response;
use Core\Testing\TestCase;

class RoleTest extends TestCase
{
    /**
     * @var Role
     */
    private $middleware;

    private static $origRequest;
    private static $origAuth;

    public static function setUpBeforeClass()
    {
        self::$origRequest = DI::getInstance()->get('request');
        self::$origAuth = DI::getInstance()->get('auth');
    }

    public static function tearDownAfterClass()
    {
        DI::getInstance()->set('request', self::$origRequest, true);
        DI::getInstance()->set('auth', self::$origAuth, true);
    }

    protected function setUp()
    {
        $this->middleware = new Role;
    }

    public function testIsNotLoggedIn()
    {
        $request = $this->getMockBuilder(Request::class)->setMethods(['baseUrl'])->getMock();
        $request->expects($this->any())->method('baseUrl')->willReturn('my_base_url');
        DI::getInstance()->set('request', $request, true);

        $delegate = new Delegate;

        $auth = $this->getMockBuilder(Auth::class)->setMethods(['isLoggedIn'])->getMock();
        $auth->expects($this->once())->method('isLoggedIn')->willReturn(false);
        DI::getInstance()->set('auth', $auth, true);

        $response = $this->middleware->process($request, $delegate, 'user');
        $this->assertInstanceOf(Response::class, $response);
        $this->assertRedirectedTo('my_base_url/auth/login');
    }

    public function testNotPass()
    {
        $request = new Request;
        $delegate = new Delegate;

        $auth = $this->getMockBuilder(Auth::class)->setMethods(['isLoggedIn', 'is'])->getMock();
        $auth->expects($this->once())->method('isLoggedIn')->willReturn(true);
        $auth->expects($this->once())->method('is')->willReturn(false);
        DI::getInstance()->set('auth', $auth, true);

        $this->expectException(HttpException::class);
        $this->middleware->process($request, $delegate, 'user');
    }

    public function testPass()
    {
        $request = new Request;

        $delegate = $this->getMockBuilder(Delegate::class)->setMethods(['process'])->getMock();
        $delegate->expects($this->once())->method('process')->with($request)->willReturn(new Response);

        $auth = $this->getMockBuilder(Auth::class)->setMethods(['isLoggedIn', 'is'])->getMock();
        $auth->expects($this->once())->method('isLoggedIn')->willReturn(true);
        $auth->expects($this->once())->method('is')->willReturn(true);
        DI::getInstance()->set('auth', $auth, true);

        $response = $this->middleware->process($request, $delegate, 'manage-user');
        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }
}