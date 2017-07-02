<?php

namespace Core\Tests\Services;

use Core\Middleware\Contracts\Middleware;
use Core\Services\Contracts\Response;
use Core\Services\Contracts\Delegate;
use Core\Services\Contracts\Request;
use Core\Testing\TestCase;

class DelegateTest extends TestCase
{
    public function testBase()
    {
        $d = new \Core\Services\Delegate;;
        $this->assertInstanceOf(Delegate::class, $d->setMiddleware(['Csrf', '\Core\Tests\Services\Middleware1', '\Core\Tests\Services\Middleware2:X,Y']));
        $this->assertInstanceOf(Delegate::class, $d->setAction(function($a, $b) {
            return $a . $b;
        }, ['A', 'B']));
        $response = $d->process(new \Core\Services\Request());
        $this->assertInstanceOf(Response::class, $response);
        $content = $response->getContent();
        $this->assertSame('M1M2XYzAB', $content);

        $d = new \Core\Services\Delegate;;
        $this->assertInstanceOf(Delegate::class, $d->setMiddleware(['Csrf', '\Core\Tests\Services\Middleware1', '\Core\Tests\Services\Middleware2:X,Y']));
        $d->setAction(function($a, $b) {
            $r = new \Core\Services\Response();
            $r->output($a . $b);
            return $r;
        }, ['A', 'B']);
        $response = $d->process(new \Core\Services\Request());
        $this->assertInstanceOf(Response::class, $response);
        $content = $response->getContent();
        $this->assertSame('M1M2XYzAB', $content);

    }
}

class Middleware1 implements Middleware
{
    public function process(Request $request, Delegate $delegate)
    {
        $response = $delegate->process($request);
        $response->output('M1' . $response->getContent());

        return $response;
    }
}

class Middleware2 implements Middleware
{
    public function process(Request $request, Delegate $delegate, $x = 'x', $y = 'y', $z = 'z')
    {
        $response = $delegate->process($request);
        $response->output('M2' . $x . $y . $z . $response->getContent());

        return $response;
    }
}
