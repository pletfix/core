<?php

namespace Core\Tests;

use Core\Application;
use Core\Services\Command;
use Core\Services\CommandFactory;
use Core\Services\DI;
use Core\Services\Response;
use Core\Services\Router;
use Core\Testing\TestCase;

class ApplicationTest extends TestCase
{
    private static $origResponse;
    private static $origRouter;
    private static $origFactory;

    public static function setUpBeforeClass()
    {
        self::$origResponse = DI::getInstance()->get('response');
        self::$origRouter = DI::getInstance()->get('router');
        self::$origFactory = DI::getInstance()->get('command-factory');
    }

    public static function tearDownAfterClass()
    {
        DI::getInstance()->set('response', self::$origResponse, true);
        DI::getInstance()->set('router', self::$origRouter, true);
        DI::getInstance()->set('command-factory', self::$origFactory, true);
    }

    public function testVersion()
    {
        $this->assertSame(Application::VERSION, Application::version());
    }

    public function testRunBrowser()
    {
        $response = $this->getMockBuilder(Response::class)->setMethods(['send'])->getMock();
        $response->expects($this->once())->method('send');
        DI::getInstance()->set('response', $response, true);

        $router = $this->getMockBuilder(Router::class)->setMethods(['dispatch'])->getMock();
        $router->expects($this->once())
            ->method('dispatch')
            //->with(DI::getInstance()->get('request'))
            ->willReturn($response);
        DI::getInstance()->set('router', $router, true);

        $this->setPrivateProperty(Application::class, 'basePath', __DIR__ .'/_data');
        Application::run();
    }

    public function testConsole()
    {
        $command = $this->getMockBuilder(\stdClass::class)->setMethods(['run'])->getMock();
        $command->expects($this->once())
            ->method('run')
            ->willReturn(Command::EXIT_SUCCESS);

        $factory = $this->getMockBuilder(CommandFactory::class)->setMethods(['command'])->getMock();
        $factory->expects($this->once())
            ->method('command')
            ->with(['help'])
            ->willReturn($command);
        DI::getInstance()->set('command-factory', $factory, true);

        $_SERVER['argv'] = ['console', 'help'];
        $this->setPrivateProperty(Application::class, 'basePath', __DIR__ .'/_data');
        $this->assertSame(Command::EXIT_SUCCESS, Application::console());
    }
}