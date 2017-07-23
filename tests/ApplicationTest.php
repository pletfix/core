<?php

namespace Core\Tests;

use Core\Application;
use Core\Console;
use Core\Services\DI;
use Core\Services\Response;
use Core\Services\Route;
use Core\Testing\TestCase;

class ApplicationTest extends TestCase
{
    private static $origResponse;
    private static $origRoute;

    public static function setUpBeforeClass()
    {
        self::$origResponse = DI::getInstance()->get('response');
        self::$origRoute = DI::getInstance()->get('route');
    }

    public static function tearDownAfterClass()
    {
        DI::getInstance()->set('response', self::$origResponse, true);
        DI::getInstance()->set('route', self::$origRoute, true);
    }

    public function testVersion()
    {
        $this->assertSame(Application::VERSION, Application::version());
        $this->assertSame(Console::version(), Application::version());
    }

    public function testRun()
    {
        $response = $this->getMockBuilder(Response::class)->setMethods(['send'])->getMock();
        $response->expects($this->once())->method('send');
        DI::getInstance()->set('response', $response, true);

        $route = $this->getMockBuilder(Route::class)->setMethods(['dispatch'])->getMock();
        $route->expects($this->once())
            ->method('dispatch')
            //->with(DI::getInstance()->get('request'))
            ->willReturn($response);
        DI::getInstance()->set('route', $route, true);

        $this->setPrivateProperty(Application::class, 'basePath', __DIR__ .'/_data');
        Application::run();
    }

    public function testRoute()
    {
        $this->assertInstanceOf(\Core\Services\Contracts\Route::class, Application::route());
    }
}