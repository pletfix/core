<?php

namespace Core\Tests\Services\PDOs;

use Core\Services\AbstractDatabase;
use Core\Services\PDOs\Builders\SQLiteBuilder;
use Core\Services\PDOs\Schemas\SQLiteSchema;
use Core\Services\PDOs\SQLite;
use Core\Testing\TestCase;
use InvalidArgumentException;
use PDO;
use PDOStatement;
use PHPUnit_Framework_MockObject_MockObject;

class SQLiteTest extends TestCase
{
    private $config = [
        'driver'   => 'sqlite',
        'database' => ':memory:',
    ];

    private $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_CASE               => PDO::CASE_NATURAL,
        PDO::ATTR_ORACLE_NULLS       => PDO::NULL_NATURAL,
        PDO::ATTR_STRINGIFY_FETCHES  => false,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    public static function tearDownAfterClass()
    {
        @unlink(storage_path('db/~test.db'));
    }

    public function testSchema()
    {
        $db = new SQLite($this->config);
        $this->assertInstanceOf(SQLiteSchema::class, $db->schema());
    }

    public function testBuilder()
    {
        $db = new SQLite($this->config);
        $this->assertInstanceOf(SQLiteBuilder::class, $db->builder());
    }

    public function testConnectMemory()
    {
        $pdo = $this->getMockBuilder(PDO::class)->disableOriginalConstructor()->getMock();

        /** @var AbstractDatabase|PHPUnit_Framework_MockObject_MockObject $db */
        $db = $this->getMockBuilder(SQLite::class)
            ->setMethods(['createPDO'])
            ->setConstructorArgs([$this->config])
            ->getMock();

        $db->expects($this->once())
            ->method('createPDO')
            ->with('sqlite::memory:', null, null, $this->options)
            ->willReturn($pdo);

        $this->assertInstanceOf(SQLite::class, $db->connect());
    }

    public function testConnectFile()
    {
        $file = storage_path('db/~test.db');
        if (!file_exists($file)) {
            @mkdir(dirname($file));
            @touch($file);
        }
        $this->config['database'] = $file;

        $pdo = $this->getMockBuilder(PDO::class)->disableOriginalConstructor()->getMock();

        /** @var AbstractDatabase|PHPUnit_Framework_MockObject_MockObject $db */
        $db = $this->getMockBuilder(SQLite::class)
            ->setMethods(['createPDO'])
            ->setConstructorArgs([$this->config])
            ->getMock();

        $db->expects($this->once())
            ->method('createPDO')
            ->with('sqlite:' . $this->config['database'], null, null, $this->options)
            ->willReturn($pdo);

        $this->assertInstanceOf(SQLite::class, $db->connect());
    }

    public function testConnectFileDoesNotExist()
    {
        $this->config['database'] = storage_path('db/wrong.db');
        $db = new SQLite($this->config);
        $this->expectException(InvalidArgumentException::class);
        $this->assertInstanceOf(SQLite::class, $db->connect());
    }

    public function testExec()
    {
        /** @noinspection SqlDialectInspection */
        $sql = 'INSERT INTO table1 (name) VALUES (?), (?)';
        $bindings = ['bar', 'buz'];

        $pdoStatement = $this->getMockBuilder(PDOStatement::class)->disableOriginalConstructor()->setMethods(['rowCount'])->getMock();
        $pdoStatement->expects($this->once())->method('rowCount')->willReturn(2);

        /** @var AbstractDatabase|PHPUnit_Framework_MockObject_MockObject $db */
        $db = $this->getMockBuilder(SQLite::class)
            ->setMethods(['perform'])
            ->setConstructorArgs([$this->config])
            ->getMock();

        $db->expects($this->once())
            ->method('perform')
            ->with($sql, $bindings )
            ->willReturn($pdoStatement);

        $this->assertSame(2, $db->exec($sql, $bindings));
        $this->assertSame('table1', $this->getPrivateProperty($db, 'lastInsertTo'));
    }

    public function testLastInsertId()
    {
        $db = new SQLite($this->config);

        // no "INSERT INTO" query since connecting!
        $this->assertSame(0, $db->lastInsertId());

        // Fake an "INSERT INTO table1 ..." query
        $this->setPrivateProperty($db, 'lastInsertTo', 'table1');

        $this->assertSame(0, $db->lastInsertId());
    }
}
