<?php

namespace Core\Tests\Services;

use Core\Services\DatabaseFactory;
use Core\Services\DI;
use Core\Services\PDOs\MySQL;
use Core\Services\PDOs\PostgreSQL;
use Core\Services\PDOs\SQLite;
use Core\Services\PDOs\MSSQL;
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
                'mssql' => [
                    'driver' => 'MSSQL',
                ],
                'mysql' => [
                    'driver' => 'MySQL',
                ],
                'pgsql' => [
                    'driver' => 'PostgreSQL',
                ],
                'sqlite' => [
                    'driver'   => 'SQLite',
                    'database' => ':memory:',
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

    public function testMySQLStore()
    {
        $this->assertInstanceOf(MySQL::class, $this->factory->store('mysql'));
    }

    public function testPostgreSQL()
    {
        $this->assertInstanceOf(PostgreSQL::class, $this->factory->store('pgsql'));
    }

    public function testSQLiteStore()
    {
        $this->assertInstanceOf(SQLite::class, $this->factory->store());
        $this->assertInstanceOf(SQLite::class, $this->factory->store('sqlite'));
    }

    public function testSQLServerStore()
    {
        $this->assertInstanceOf(MSSQL::class, $this->factory->store('mssql'));
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