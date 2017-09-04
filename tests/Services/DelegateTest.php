<?php

namespace Core\Tests\Services;

use Core\Exceptions\FatalErrorException;
use Core\Services\Contracts\Response;
use Core\Services\Delegate;
use Core\Services\Request;
use Core\Testing\TestCase;
use InvalidArgumentException;

require_once __DIR__ . '/../_data/classes/AppMiddlewareWithoutParams.php.stub';
require_once __DIR__ . '/../_data/classes/CoreMiddlewareWithParams.php.stub';
require_once __DIR__ . '/../_data/classes/PluginDummyMiddleware.php.stub';

class DelegateTest extends TestCase
{
    /**
     * @var Delegate
     */
    private $d;

    protected function setUp()
    {
        $this->d = new Delegate(__DIR__ . '/../_data/plugin_manifest/middleware.php');
    }

    public function testAbsoluteMiddlewarePathAndProcessReturnsString()
    {
        $this->assertInstanceOf(Delegate::class, $this->d->setMiddleware(['\App\Middleware\MiddlewareWithoutParams', '\Core\Middleware\MiddlewareWithParams:X,Y']));
        $this->assertInstanceOf(Delegate::class, $this->d->setAction(function ($a, $b) {
            return $a . $b;
        }, ['A', 'B']));
        $response = $this->d->process(new Request());
        $this->assertInstanceOf(Response::class, $response);
        $content = $response->getContent();
        $this->assertSame('M1M2XYzAB', $content);
    }

    public function testRelativeMiddlewarePathAndProcessReturnsResponse()
    {
        $this->d->setMiddleware(['\App\Middleware\MiddlewareWithoutParams', '\Core\Middleware\MiddlewareWithParams:X,Y']);
        $this->d->setAction(function($a, $b) {
            $r = new \Core\Services\Response();
            $r->output($a . $b);
            return $r;
        }, ['A', 'B']);
        $response = $this->d->process(new Request());
        $content = $response->getContent();
        $this->assertSame('M1M2XYzAB', $content);
    }

    public function testMiddlewareProvidedByCore()
    {
        $this->d->setMiddleware(['Auth']);
        $this->d->setAction(function() {
            return null;
        }, []);
        $this->assertInstanceOf(Response::class, $this->d->process(new Request()));
    }

    public function testMiddlewareProvidedByPlugin()
    {
        $this->d->setMiddleware(['Dummy']);
        $this->d->setAction(function() {
            return 'Z';
        }, []);
        $response = $this->d->process(new Request());
        $content = $response->getContent();
        $this->assertSame('XZ', $content);
    }

    public function testPluginClassNotFound()
    {
        $d = new Delegate(__DIR__ . '/../_data/plugin_manifest/middleware2.php');
        $d->setMiddleware(['MiddlewareWithParams:X,Y']);
        $this->assertInstanceOf(Delegate::class, $d->setAction(function() {
            return null;
        }, []));
        $this->expectException(InvalidArgumentException::class);
        $response = $d->process(new Request());
    }
}