<?php

namespace Core\Tests;

use Core\Application;
use Core\Console;
use Core\Services\Command;
use Core\Services\CommandFactory;
use Core\Services\DI;
use Core\Services\Response;
use Core\Testing\TestCase;

class ConsoleTest extends TestCase
{
    private static $origFactory;

    public static function setUpBeforeClass()
    {
        self::$origFactory = DI::getInstance()->get('command-factory');
    }

    public static function tearDownAfterClass()
    {
        DI::getInstance()->set('command-factory', self::$origFactory, true);
    }

    public function testVersion()
    {
        $this->assertSame(Console::VERSION, Console::version());
        $this->assertSame(Application::version(), Console::version());
    }

    public function testRun()
    {
        $command = $this->getMockBuilder(Response::class)->setMethods(['run'])->getMock();
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
        $this->setPrivateProperty(Console::class, 'basePath', __DIR__ .'/_data');
        $this->assertSame(Command::EXIT_SUCCESS, Console::run());
    }
}