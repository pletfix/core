<?php

namespace Core\Tests\Commands;

use Core\Commands\MigrateCommand;
use Core\Services\Contracts\Command;
use Core\Services\DI;
use Core\Services\Migrator;
use Core\Services\Stdio;
use Core\Testing\TestCase;

class MigrateCommandTest extends TestCase
{
    private static $origMigrator;

    public static function setUpBeforeClass()
    {
        self::$origMigrator = DI::getInstance()->get('migrator');
    }

    public static function tearDownAfterClass()
    {
        DI::getInstance()->set('migrator', self::$origMigrator, true);
    }

    public function testRun()
    {
        $stdio = $this->getMockBuilder(Stdio::class)->setMethods(['line'])->getMock();
        $stdio->expects($this->once())->method('line')->with('Database successfully migrated.')->willReturnSelf();

        // mock migrator()->run();
        $migrator = $this->getMockBuilder(Migrator::class)->setMethods(['run', 'rollback', 'reset'])->getMock();
        $migrator->expects($this->once())->method('run')->with()->willReturnSelf();
        $migrator->expects($this->never())->method('rollback')->willReturnSelf();
        $migrator->expects($this->never())->method('reset')->willReturnSelf();
        DI::getInstance()->set('migrator', $migrator, true);

        $command = new MigrateCommand([], $stdio);
        $exitCode = $command->run();
        $this->assertEquals(Command::EXIT_SUCCESS, $exitCode);
    }

    public function testRollback()
    {
        $stdio = $this->getMockBuilder(Stdio::class)->setMethods(['line'])->getMock();
        $stdio->expects($this->once())->method('line')->with('Last database migration successfully rollback.')->willReturnSelf();

        // mock migrator()->rollback();
        $migrator = $this->getMockBuilder(Migrator::class)->setMethods(['run', 'rollback', 'reset'])->getMock();
        $migrator->expects($this->never())->method('run')->willReturnSelf();
        $migrator->expects($this->once())->method('rollback')->with()->willReturnSelf();
        $migrator->expects($this->never())->method('reset')->willReturnSelf();
        DI::getInstance()->set('migrator', $migrator, true);

        $command = new MigrateCommand(['--rollback'], $stdio);
        $exitCode = $command->run();
        $this->assertEquals(Command::EXIT_SUCCESS, $exitCode);
    }

    public function testReset()
    {
        $stdio = $this->getMockBuilder(Stdio::class)->setMethods(['line'])->getMock();
        $stdio->expects($this->once())->method('line')->with('Database successfully reset.')->willReturnSelf();

        // mock migrator()->reset();
        $migrator = $this->getMockBuilder(Migrator::class)->setMethods(['run', 'rollback', 'reset'])->getMock();
        $migrator->expects($this->never())->method('run')->willReturnSelf();
        $migrator->expects($this->never())->method('rollback')->willReturnSelf();
        $migrator->expects($this->once())->method('reset')->with()->willReturnSelf();
        DI::getInstance()->set('migrator', $migrator, true);

        $command = new MigrateCommand(['--reset'], $stdio);
        $exitCode = $command->run();
        $this->assertEquals(Command::EXIT_SUCCESS, $exitCode);
    }

}