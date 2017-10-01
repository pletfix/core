<?php

namespace Core\Tests\Services;

use Core\Exceptions\HttpException;
use Core\Services\Contracts\Response;
use Core\Services\Delegate;
use Core\Services\DI;
use Core\Services\Request;
use Core\Services\Router;
use Core\Testing\TestCase;
use InvalidArgumentException;
use RuntimeException;

require_once __DIR__ . '/../_data/classes/PluginDummyController.php.stub';
require_once __DIR__ . '/../_data/classes/AppDummyController.php.stub';
require_once __DIR__ . '/../_data/classes/AppMiddlewareWithoutParams.php.stub';
require_once __DIR__ . '/../_data/classes/CoreMiddlewareWithParams.php.stub';

class RouteTest extends TestCase
{
    /**
     * @var Router;
     */
    private $router;

    private static $origDelegate;

    public static function setUpBeforeClass()
    {
        self::$origDelegate = DI::getInstance()->get('delegate');
    }

    public static function tearDownAfterClass()
    {
        DI::getInstance()->set('delegate', self::$origDelegate, true);
    }

    protected function setUp()
    {
        $this->router = new Router(__DIR__ . '/../_data/plugin_manifest/controllers.php');
    }

    public function testDispatchController()
    {
        $request = $this->getMockBuilder(Request::class)->setMethods(['method', 'path'])->getMock();
        $request->expects($this->any())->method('method')->willReturn('POST');
        $request->expects($this->any())->method('path')->willReturn('dummy/foo/bar');

        $delegate = $this->getMockBuilder(Delegate::class)->setMethods(['setMiddleware', 'setAction', 'process'])->getMock();
        $delegate->expects($this->once())->method('setMiddleware')->with([])->willReturn($delegate);

        /** @noinspection PhpUndefinedNamespaceInspection, PhpUndefinedClassInspection, PhpUnnecessaryFullyQualifiedNameInspection */
        $delegate->expects($this->once())->method('setAction')->with([new \App\Controllers\DummyController, 'foo'], ['bar'])->willReturn($delegate);
        $delegate->expects($this->once())->method('process')->with($request)->willReturn(new \Core\Services\Response());
        DI::getInstance()->set('delegate', $delegate, true);

        $this->assertInstanceOf(Router::class, $this->router->post('dummy/foo/{param}', '\App\Controllers\DummyController@foo'));
        $response = $this->router->dispatch($request);
        $this->assertInstanceOf(Response::class, $response);
    }

    public function testDispatchControllerFromPlugin()
    {
        $request = $this->getMockBuilder(Request::class)->setMethods(['method', 'path'])->getMock();
        $request->expects($this->any())->method('method')->willReturn('POST');
        $request->expects($this->any())->method('path')->willReturn('dummy/foo/bar');

        $delegate = $this->getMockBuilder(Delegate::class)->setMethods(['setMiddleware', 'setAction', 'process'])->getMock();
        $delegate->expects($this->once())->method('setMiddleware')->with([])->willReturn($delegate);

        /** @noinspection PhpUndefinedNamespaceInspection, PhpUndefinedClassInspection, PhpUnnecessaryFullyQualifiedNameInspection */
        $delegate->expects($this->once())->method('setAction')->with([new \Pletfix\Test\Controllers\DummyController, 'foo'], ['bar'])->willReturn($delegate);
        $delegate->expects($this->once())->method('process')->with($request)->willReturn(new \Core\Services\Response());
        DI::getInstance()->set('delegate', $delegate, true);

        $this->assertInstanceOf(Router::class, $this->router->post('dummy/foo/{param}', 'DummyController@foo'));
        $response = $this->router->dispatch($request);
        $this->assertInstanceOf(Response::class, $response);
    }

    public function testDispatchClosure()
    {
        $f = function ($param) {
            return $param . '!';
        };

        $request = $this->getMockBuilder(Request::class)->setMethods(['method', 'path'])->getMock();
        $request->expects($this->any())->method('method')->willReturn('POST');
        $request->expects($this->any())->method('path')->willReturn('dummy/foo/bar');

        $delegate = $this->getMockBuilder(Delegate::class)->setMethods(['setMiddleware', 'setAction', 'process'])->getMock();
        $delegate->expects($this->once())->method('setMiddleware')->with([])->willReturn($delegate);
        $delegate->expects($this->once())->method('setAction')->with($f, ['bar'])->willReturn($delegate);
        $delegate->expects($this->once())->method('process')->with($request)->willReturn(new \Core\Services\Response());
        DI::getInstance()->set('delegate', $delegate, true);

        $this->assertInstanceOf(Router::class, $this->router->post('dummy/foo/{param}', $f));
        $response = $this->router->dispatch($request);
        $this->assertInstanceOf(Response::class, $response);
    }

    public function testDispatchMalformed()
    {
        $request = $this->getMockBuilder(Request::class)->setMethods(['method', 'path'])->getMock();
        $request->expects($this->any())->method('method')->willReturn('POST');
        $request->expects($this->any())->method('path')->willReturn('dummy/foo/bar');

        DI::getInstance()->set('delegate', Delegate::class, true);

        $this->assertInstanceOf(Router::class, $this->router->post('dummy/foo/{param}', null));
        $this->expectException(RuntimeException::class);
        $this->router->dispatch($request);
    }

    public function test404()
    {
        $request = $this->getMockBuilder(Request::class)->setMethods(['method', 'path'])->getMock();
        $request->expects($this->any())->method('method')->willReturn('POST');
        $request->expects($this->any())->method('path')->willReturn('wrong');

        $this->assertInstanceOf(Router::class, $this->router->post('foo/bar/{param}', 'CoreDummyController@foo'));
        $this->expectException(HttpException::class);
        $this->router->dispatch($request);
    }

    public function testPrefix()
    {
        $this->assertInstanceOf(Router::class, $this->router->prefix('pre1'));
        $this->assertInstanceOf(Router::class, $this->router->post('foo1/bar', 'CoreDummyController@foo1'));
        $this->assertInstanceOf(Router::class, $this->router->prefix('pre2', function () {
            $this->router->post('foo2/bar/{param}', 'CoreDummyController@foo2');
        }));

        $routes = $this->router->getRoutes();
        $this->assertTrue(is_array($routes));
        $this->assertSame(2, count($routes));
        $this->assertInstanceOf(\stdClass::class, $routes[0]);
        $this->assertSame('pre1/foo1/bar', $routes[0]->path);
        $this->assertSame('pre1/pre2/foo2/bar/{param}', $routes[1]->path);
    }

    public function testMiddleware()
    {
        $this->assertInstanceOf(Router::class, $this->router->middleware('\App\Middleware\MiddlewareWithoutParams'));
        $this->assertInstanceOf(Router::class, $this->router->post('foo1/bar', 'CoreDummyController@foo1'));
        $this->assertInstanceOf(Router::class, $this->router->middleware('\Core\Middleware\MiddlewareWithParams', function () {
            $this->router->post('foo2/bar/{param}', 'CoreDummyController@foo2');
        }));

        $routes = $this->router->getRoutes();
        $this->assertTrue(is_array($routes));
        $this->assertSame(2, count($routes));
        $this->assertInstanceOf(\stdClass::class, $routes[0]);
        $this->assertObjectHasAttribute('middleware', $routes[0]);
        $this->assertSame(['\App\Middleware\MiddlewareWithoutParams'], $routes[0]->middleware);
        $this->assertSame(['\App\Middleware\MiddlewareWithoutParams', '\Core\Middleware\MiddlewareWithParams'], $routes[1]->middleware);
    }

    public function testAddRoutes()
    {
        $this->assertInstanceOf(Router::class, $this->router->get('foo1/bar', 'CoreDummyController@foo1'));
        $this->assertInstanceOf(Router::class, $this->router->head('foo1/bar', 'CoreDummyController@foo1'));
        $this->assertInstanceOf(Router::class, $this->router->post('foo1/bar', 'CoreDummyController@foo1'));
        $this->assertInstanceOf(Router::class, $this->router->put('foo1/bar', 'CoreDummyController@foo1'));
        $this->assertInstanceOf(Router::class, $this->router->patch('foo1/bar', 'CoreDummyController@foo1'));
        $this->assertInstanceOf(Router::class, $this->router->delete('foo1/bar', 'CoreDummyController@foo1'));
        $this->assertInstanceOf(Router::class, $this->router->options('foo1/bar', 'CoreDummyController@foo1'));
        $this->assertInstanceOf(Router::class, $this->router->multi(['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], 'foo1/bar', 'CoreDummyController@foo1'));
        $this->assertInstanceOf(Router::class, $this->router->any('foo1/bar', 'CoreDummyController@foo1'));
        $this->assertInstanceOf(Router::class, $this->router->resource('foo1/bar', 'CoreDummyController'));

        $this->expectException(InvalidArgumentException::class);
        $this->router->multi(['WRONG'], 'foo1/bar', 'CoreDummyController@foo1');
    }

    public function testPluginManifestNotExists()
    {
        $router = new Router(__DIR__ . '/../_data/plugin_manifest/controllers2.php');

        $request = $this->getMockBuilder(Request::class)->setMethods(['method', 'path'])->getMock();
        $request->expects($this->any())->method('method')->willReturn('POST');
        $request->expects($this->any())->method('path')->willReturn('dummy/foo/bar');

        $delegate = $this->getMockBuilder(Delegate::class)->setMethods(['setMiddleware', 'setAction', 'process'])->getMock();
        $delegate->expects($this->once())->method('setMiddleware')->with([])->willReturn($delegate);

        /** @noinspection PhpUndefinedNamespaceInspection, PhpUndefinedClassInspection, PhpUnnecessaryFullyQualifiedNameInspection */
        $delegate->expects($this->once())->method('setAction')->with([new \App\Controllers\DummyController, 'foo'], ['bar'])->willReturn($delegate);
        $delegate->expects($this->once())->method('process')->with($request)->willReturn(new \Core\Services\Response());
        DI::getInstance()->set('delegate', $delegate, true);

        $this->assertInstanceOf(Router::class, $router->post('dummy/foo/{param}', '\App\Controllers\DummyController@foo'));
        $response = $router->dispatch($request);
        $this->assertInstanceOf(Response::class, $response);
    }

    public function testPluginClassNotFound()
    {
        $request = $this->getMockBuilder(Request::class)->setMethods(['method', 'path'])->getMock();
        $request->expects($this->any())->method('method')->willReturn('POST');
        $request->expects($this->any())->method('path')->willReturn('dummy/foo/bar');

        DI::getInstance()->set('delegate', Delegate::class, true);

        $this->assertInstanceOf(Router::class, $this->router->post('dummy/foo/{param}', 'XYZController@foo'));
        $this->expectException(InvalidArgumentException::class);
        $this->router->dispatch($request);
    }
}