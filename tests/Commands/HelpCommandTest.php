<?php

namespace Core\Tests\Commands;

use Core\Application;
use Core\Commands\HelpCommand;
use Core\Services\CommandFactory;
use Core\Services\Contracts\Command;
use Core\Services\DI;
use Core\Services\Stdio;
use Core\Testing\TestCase;

class HelpCommandTest extends TestCase
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

    protected function setUp()
    {
        $factory = $this->getMockBuilder(CommandFactory::class)->setMethods(['commandList'])->getMock();
        $factory->expects($this->any())->method('commandList')->willReturn([
            'help'    => ['class' => HelpCommand::class, 'name' => 'help',    'description' => 'Display help for a command.'],
            'foo:bar' => ['class' => Command::class,     'name' => 'foo:bar', 'description' => 'Dummy 1.'],
            'foo:baz' => ['class' => Command::class,     'name' => 'foo:baz', 'description' => 'Dummy 2.'],
        ]);
        DI::getInstance()->set('command-factory', $factory, true);
    }

    public function testPrintHelp()
    {
        $stdio = $this->getMockBuilder(Stdio::class)->setMethods(['line', 'write', 'notice'])->getMock();
        $stdio->expects($this->at(1))->method('line')->with('  Command tool for Pletfix.')->willReturnSelf();
        $stdio->expects($this->any())->method('line')->willReturnSelf();
        $stdio->expects($this->any())->method('write')->willReturnSelf();
        $stdio->expects($this->any())->method('notice')->willReturnSelf();

        $command = new HelpCommand([], $stdio);
        $exitCode = $command->run();
        $this->assertEquals(Command::EXIT_SUCCESS, $exitCode);
    }

    public function testPrintHelpAboutSpecifyCommand()
    {
        $stdio = $this->getMockBuilder(Stdio::class)->setMethods(['line', 'write', 'notice'])->getMock();
        $stdio->expects($this->at(1))->method('line')->with('  Display help for a command.')->willReturnSelf();
        $stdio->expects($this->any())->method('line')->willReturnSelf();
        $stdio->expects($this->any())->method('write')->willReturnSelf();
        $stdio->expects($this->any())->method('notice')->willReturnSelf();

        $command = new HelpCommand(['help'], $stdio);
        $exitCode = $command->run();
        $this->assertEquals(Command::EXIT_SUCCESS, $exitCode);
    }

    public function testPrintHelpAboutUndefinedCommand()
    {
        $stdio = $this->getMockBuilder(Stdio::class)->setMethods(['error'])->getMock();
        $stdio->expects($this->once())->method('error')->with("Command \"x\" is not defined.")->willReturnSelf();

        $command = new HelpCommand(['x'], $stdio);
        $exitCode = $command->run();
        $this->assertEquals(Command::EXIT_FAILURE, $exitCode);
    }

    public function testPrintHelpWithTypo()
    {
        $stdio = $this->getMockBuilder(Stdio::class)->setMethods(['error'])->getMock();
        $stdio->expects($this->once())->method('error')->with("Command \"helb\" is not defined.\nDid you mean this?\n  - help")->willReturnSelf();

        $command = new HelpCommand(['helb'], $stdio);
        $exitCode = $command->run();
        $this->assertEquals(Command::EXIT_FAILURE, $exitCode);
    }

    public function testPrintHelpWithTypoInSubName()
    {
        $stdio = $this->getMockBuilder(Stdio::class)->setMethods(['error'])->getMock();
        $stdio->expects($this->once())->method('error')->with("Command \"foo:r\" is not defined.\nDid you mean one of these?\n  - foo:bar\n  - foo:baz")->willReturnSelf();

        $command = new HelpCommand(['foo:r'], $stdio);
        $exitCode = $command->run();
        $this->assertEquals(Command::EXIT_FAILURE, $exitCode);
    }

    public function testPrintHelpWithSeveralSuggestions()
    {
        $stdio = $this->getMockBuilder(Stdio::class)->setMethods(['error'])->getMock();
        $stdio->expects($this->once())->method('error')->with("Command \"foo\" is not defined.\nDid you mean one of these?\n  - foo:baz\n  - foo:bar")->willReturnSelf();

        $command = new HelpCommand(['foo'], $stdio);
        $exitCode = $command->run();
        $this->assertEquals(Command::EXIT_FAILURE, $exitCode);
    }

    public function testPrintHelpWithMixedNameAndSubName()
    {
        $stdio = $this->getMockBuilder(Stdio::class)->setMethods(['error'])->getMock();
        $stdio->expects($this->once())->method('error')->with("Command \"help:bar\" is not defined.\nDid you mean one of these?\n  - foo:bar\n  - foo:baz\n  - help")->willReturnSelf();

        $command = new HelpCommand(['help:bar'], $stdio);
        $exitCode = $command->run();
        $this->assertEquals(Command::EXIT_FAILURE, $exitCode);
    }

    public function testPrintVersion()
    {
        $stdio = $this->getMockBuilder(Stdio::class)->setMethods(['line'])->getMock();
        $stdio->expects($this->once())->method('line')->with('Pletfix ' . Application::version())->willReturnSelf();

        $command = new HelpCommand(['--version'], $stdio);
        $exitCode = $command->run();
        $this->assertEquals(Command::EXIT_SUCCESS, $exitCode);
    }
}