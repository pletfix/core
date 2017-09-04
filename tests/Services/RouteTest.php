<?php

namespace Core\Tests\Services;

use Core\Exceptions\HttpException;
use Core\Services\Contracts\Response;
use Core\Services\Delegate;
use Core\Services\DI;
use Core\Services\Request;
use Core\Services\Route;
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
     * @var Route;
     */
    private $route;

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
        $this->route = new Route(__DIR__ . '/../_data/plugin_manifest/controllers.php');
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

        $this->assertInstanceOf(Route::class, $this->route->post('dummy/foo/{param}', '\App\Controllers\DummyController@foo'));
        $response = $this->route->dispatch($request);
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

        $this->assertInstanceOf(Route::class, $this->route->post('dummy/foo/{param}', 'DummyController@foo'));
        $response = $this->route->dispatch($request);
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

        $this->assertInstanceOf(Route::class, $this->route->post('dummy/foo/{param}', $f));
        $response = $this->route->dispatch($request);
        $this->assertInstanceOf(Response::class, $response);
    }

    public function testDispatchMalformed()
    {
        $request = $this->getMockBuilder(Request::class)->setMethods(['method', 'path'])->getMock();
        $request->expects($this->any())->method('method')->willReturn('POST');
        $request->expects($this->any())->method('path')->willReturn('dummy/foo/bar');

        DI::getInstance()->set('delegate', Delegate::class, true);

        $this->assertInstanceOf(Route::class, $this->route->post('dummy/foo/{param}', null));
        $this->expectException(RuntimeException::class);
        $this->route->dispatch($request);
    }

    public function test404()
    {
        $request = $this->getMockBuilder(Request::class)->setMethods(['method', 'path'])->getMock();
        $request->expects($this->any())->method('method')->willReturn('POST');
        $request->expects($this->any())->method('path')->willReturn('wrong');

        $this->assertInstanceOf(Route::class, $this->route->post('foo/bar/{param}', 'CoreDummyController@foo'));
        $this->expectException(HttpException::class);
        $this->route->dispatch($request);
    }

    public function testMiddleware()
    {
        $this->assertInstanceOf(Route::class, $this->route->middleware('\App\Middleware\MiddlewareWithoutParams'));
        $this->assertInstanceOf(Route::class, $this->route->post('foo1/bar', 'CoreDummyController@foo1'));
        $this->assertInstanceOf(Route::class, $this->route->middleware('\Core\Middleware\MiddlewareWithParams', function () {
            $this->route->post('foo2/bar/{param}', 'CoreDummyController@foo2');
        }));

        $routes = $this->route->getRoutes();
        $this->assertTrue(is_array($routes));
        $this->assertSame(2, count($routes));
        $this->assertInstanceOf(\stdClass::class, $routes[0]);
        $this->assertObjectHasAttribute('middleware', $routes[0]);
        $this->assertSame(['\App\Middleware\MiddlewareWithoutParams'], $routes[0]->middleware);
        $this->assertSame(['\App\Middleware\MiddlewareWithoutParams', '\Core\Middleware\MiddlewareWithParams'], $routes[1]->middleware);
    }

    public function testAddRoutes()
    {
        $this->assertInstanceOf(Route::class, $this->route->get('foo1/bar', 'CoreDummyController@foo1'));
        $this->assertInstanceOf(Route::class, $this->route->head('foo1/bar', 'CoreDummyController@foo1'));
        $this->assertInstanceOf(Route::class, $this->route->post('foo1/bar', 'CoreDummyController@foo1'));
        $this->assertInstanceOf(Route::class, $this->route->put('foo1/bar', 'CoreDummyController@foo1'));
        $this->assertInstanceOf(Route::class, $this->route->patch('foo1/bar', 'CoreDummyController@foo1'));
        $this->assertInstanceOf(Route::class, $this->route->delete('foo1/bar', 'CoreDummyController@foo1'));
        $this->assertInstanceOf(Route::class, $this->route->options('foo1/bar', 'CoreDummyController@foo1'));
        $this->assertInstanceOf(Route::class, $this->route->multi(['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], 'foo1/bar', 'CoreDummyController@foo1'));
        $this->assertInstanceOf(Route::class, $this->route->any('foo1/bar', 'CoreDummyController@foo1'));
        $this->assertInstanceOf(Route::class, $this->route->resource('foo1/bar', 'CoreDummyController'));

        $this->expectException(InvalidArgumentException::class);
        $this->route->multi(['WRONG'], 'foo1/bar', 'CoreDummyController@foo1');
    }

    public function testPluginManifestNotExists()
    {
        $route = new Route(__DIR__ . '/../_data/plugin_manifest/controllers2.php');

        $request = $this->getMockBuilder(Request::class)->setMethods(['method', 'path'])->getMock();
        $request->expects($this->any())->method('method')->willReturn('POST');
        $request->expects($this->any())->method('path')->willReturn('dummy/foo/bar');

        $delegate = $this->getMockBuilder(Delegate::class)->setMethods(['setMiddleware', 'setAction', 'process'])->getMock();
        $delegate->expects($this->once())->method('setMiddleware')->with([])->willReturn($delegate);

        /** @noinspection PhpUndefinedNamespaceInspection, PhpUndefinedClassInspection, PhpUnnecessaryFullyQualifiedNameInspection */
        $delegate->expects($this->once())->method('setAction')->with([new \App\Controllers\DummyController, 'foo'], ['bar'])->willReturn($delegate);
        $delegate->expects($this->once())->method('process')->with($request)->willReturn(new \Core\Services\Response());
        DI::getInstance()->set('delegate', $delegate, true);

        $this->assertInstanceOf(Route::class, $route->post('dummy/foo/{param}', '\App\Controllers\DummyController@foo'));
        $response = $route->dispatch($request);
        $this->assertInstanceOf(Response::class, $response);
    }

    public function testPluginClassNotFound()
    {
        $request = $this->getMockBuilder(Request::class)->setMethods(['method', 'path'])->getMock();
        $request->expects($this->any())->method('method')->willReturn('POST');
        $request->expects($this->any())->method('path')->willReturn('dummy/foo/bar');

        DI::getInstance()->set('delegate', Delegate::class, true);

        $this->assertInstanceOf(Route::class, $this->route->post('dummy/foo/{param}', 'XYZController@foo'));
        $this->expectException(InvalidArgumentException::class);
        $this->route->dispatch($request);
    }
}