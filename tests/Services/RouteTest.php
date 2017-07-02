<?php

namespace Core\Tests\Services {

    use Core\Controllers\DummyController;
    use Core\Exceptions\HttpException;
    use Core\Middleware\Contracts\Middleware;
    use Core\Services\Contracts\Delegate;
    use Core\Services\Contracts\Response;
    use Core\Services\Contracts\Request;
    use Core\Services\Route;
    use Core\Testing\TestCase;
    use InvalidArgumentException;
    use RuntimeException;

    class RouteTest extends TestCase
    {
        public function testDispatchController()
        {
            $request = $this->getMockBuilder(\Core\Services\Request::class)->setMethods(['method', 'path'])->getMock();
            $request->expects($this->any())->method('method')->willReturn('POST');
            $request->expects($this->any())->method('path')->willReturn('dummy/foo/bar');

            $delegate = $this->getMockBuilder(\Core\Services\Delegate::class)->setMethods(['setMiddleware', 'setAction', 'process'])->getMock();
            $delegate->expects($this->once())->method('setMiddleware')->with([])->willReturn($delegate);
            $delegate->expects($this->once())->method('setAction')->with([new DummyController, 'foo'], ['bar'])->willReturn($delegate);
            $delegate->expects($this->once())->method('process')->with($request)->willReturn(new \Core\Services\Response());
            di()->set('delegate', $delegate, true);

            $r = new Route();
            $this->assertInstanceOf(Route::class, $r->post('dummy/foo/{param}', 'DummyController@foo'));
            $response = $r->dispatch($request);
            $this->assertInstanceOf(Response::class, $response);
        }

        public function testDispatchClosure()
        {
            $f = function ($param) {
                return $param . '!';
            };

            $request = $this->getMockBuilder(\Core\Services\Request::class)->setMethods(['method', 'path'])->getMock();
            $request->expects($this->any())->method('method')->willReturn('POST');
            $request->expects($this->any())->method('path')->willReturn('dummy/foo/bar');

            $delegate = $this->getMockBuilder(\Core\Services\Delegate::class)->setMethods(['setMiddleware', 'setAction', 'process'])->getMock();
            $delegate->expects($this->once())->method('setMiddleware')->with([])->willReturn($delegate);
            $delegate->expects($this->once())->method('setAction')->with($f, ['bar'])->willReturn($delegate);
            $delegate->expects($this->once())->method('process')->with($request)->willReturn(new \Core\Services\Response());
            di()->set('delegate', $delegate, true);

            $r = new Route();
            $this->assertInstanceOf(Route::class, $r->post('dummy/foo/{param}', $f));
            $response = $r->dispatch($request);
            $this->assertInstanceOf(Response::class, $response);
        }

        public function testDispatchMalformed()
        {
            $request = $this->getMockBuilder(\Core\Services\Request::class)->setMethods(['method', 'path'])->getMock();
            $request->expects($this->any())->method('method')->willReturn('POST');
            $request->expects($this->any())->method('path')->willReturn('dummy/foo/bar');

            di()->set('delegate', \Core\Services\Delegate::class, true);

            $r = new Route();
            $this->assertInstanceOf(Route::class, $r->post('dummy/foo/{param}', null));
            $this->expectException(RuntimeException::class);
            $r->dispatch($request);
        }

        public function test404()
        {
            $request = $this->getMockBuilder(\Core\Services\Request::class)->setMethods(['method', 'path'])->getMock();
            $request->expects($this->any())->method('method')->willReturn('POST');
            $request->expects($this->any())->method('path')->willReturn('wrong');

            $r = new Route();
            $this->assertInstanceOf(Route::class, $r->post('foo/bar/{param}', 'DummyController@foo'));
            $this->expectException(HttpException::class);
            $r->dispatch($request);
        }

        public function testMiddleware()
        {
            $r = new Route();
            $this->assertInstanceOf(Route::class, $r->middleware('\Core\Tests\Services\Middleware3'));
            $this->assertInstanceOf(Route::class, $r->post('foo1/bar', 'DummyController@foo1'));
            $this->assertInstanceOf(Route::class, $r->middleware('\Core\Tests\Services\Middleware4', function () use ($r) {
                $r->post('foo2/bar/{param}', 'DummyController@foo2');
            }));

            $routes = $r->getRoutes();
            $this->assertTrue(is_array($routes));
            $this->assertSame(2, count($routes));
            $this->assertInstanceOf(\stdClass::class, $routes[0]);
            $this->assertObjectHasAttribute('middleware', $routes[0]);
            $this->assertSame(['\Core\Tests\Services\Middleware3'], $routes[0]->middleware);
            $this->assertSame(['\Core\Tests\Services\Middleware3', '\Core\Tests\Services\Middleware4'], $routes[1]->middleware);
        }

        public function testAddRoutes()
        {
            $r = new Route();
            $this->assertInstanceOf(Route::class, $r->get('foo1/bar', 'DummyController@foo1'));
            $this->assertInstanceOf(Route::class, $r->head('foo1/bar', 'DummyController@foo1'));
            $this->assertInstanceOf(Route::class, $r->post('foo1/bar', 'DummyController@foo1'));
            $this->assertInstanceOf(Route::class, $r->put('foo1/bar', 'DummyController@foo1'));
            $this->assertInstanceOf(Route::class, $r->patch('foo1/bar', 'DummyController@foo1'));
            $this->assertInstanceOf(Route::class, $r->delete('foo1/bar', 'DummyController@foo1'));
            $this->assertInstanceOf(Route::class, $r->options('foo1/bar', 'DummyController@foo1'));
            $this->assertInstanceOf(Route::class, $r->multi(['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], 'foo1/bar', 'DummyController@foo1'));
            $this->assertInstanceOf(Route::class, $r->any('foo1/bar', 'DummyController@foo1'));
            $this->assertInstanceOf(Route::class, $r->resource('foo1/bar', 'DummyController'));

            $this->expectException(InvalidArgumentException::class);
            $r->multi(['WRONG'], 'foo1/bar', 'DummyController@foo1');
        }
    }

    class Middleware3 implements Middleware
    {
        public function process(Request $request, Delegate $delegate)
        {
            $response = $delegate->process($request);
            $response->output('M1' . $response->getContent());

            return $response;
        }
    }

    class Middleware4 implements Middleware
    {
        public function process(Request $request, Delegate $delegate, $x = 'x', $y = 'y', $z = 'z')
        {
            $response = $delegate->process($request);
            $response->output('M2' . $x . $y . $z . $response->getContent());

            return $response;
        }
    }
}

namespace Core\Controllers {
    class DummyController
    {
        public function foo($param = null)
        {
            return $param . '!';
        }
    }
}