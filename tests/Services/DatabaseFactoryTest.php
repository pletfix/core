<?php

namespace Core\Tests\Services;

use Core\Services\Contracts\Database;
use Core\Services\DatabaseFactory;
use Core\Services\DI;
use Core\Testing\TestCase;
use InvalidArgumentException;

class DatabaseFactoryTest extends TestCase
{
    /**
     * @var DatabaseFactory
     */
    private $factory;

    public static function setUpBeforeClass()
    {
        DI::getInstance()->get('config')->set('database', [
            'default' => 'sqlite',
            'stores' => [
                'mysql' => [
                    'driver' => 'mysql',
                ],
                'pgsql' => [
                    'driver' => 'pgsql',
                ],
                'sqlite' => [
                    'driver'   => 'sqlite',
                    'database' => ':memory:',
                ],
                'sqlsrv' => [
                    'driver' => 'sqlsrv',
                ],
                'foo1' => [
                ],
                'foo2' => [
                    'driver' => 'wrong',
                ],
            ],
        ]);
    }

    protected function setUp()
    {
        $this->factory = new DatabaseFactory;
    }

    public function testMySqlStore()
    {
        $this->assertInstanceOf(Database::class, $this->factory->store('mysql'));
    }

    public function testPostgreSQL()
    {
        $this->assertInstanceOf(Database::class, $this->factory->store('pgsql'));
    }

    public function testSQLiteStore()
    {
        $this->assertInstanceOf(Database::class, $this->factory->store());
        $this->assertInstanceOf(Database::class, $this->factory->store('sqlite'));
    }

    public function testSQLServerStore()
    {
        $this->assertInstanceOf(Database::class, $this->factory->store('sqlsrv'));
    }

    public function testStoreNotDefined()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->factory->store('foo');
    }

    public function testDriverNotSpecified()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->factory->store('foo1');
    }

    public function testDriverNotSupported()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->factory->store('foo2');
    }
}