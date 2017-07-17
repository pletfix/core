<?php

namespace Core\Tests\Services\PDOs;

use Core\Services\AbstractDatabase;
use Core\Services\PDOs\Builder\PostgresBuilder;
use Core\Services\PDOs\Postgres;
use Core\Services\PDOs\Schemas\PostgresSchema;
use Core\Testing\TestCase;
use PDO;
use PDOStatement;
use PHPUnit_Framework_MockObject_MockObject;

class PostgresTest extends TestCase
{
    private $config = [
        'driver'   => 'pgsql',
        'host'     => 'localhost',
        'database' => '~test',
        'username' => 'pguser',
        'password' => 'psss',
    ];

    private $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_CASE               => PDO::CASE_NATURAL,
        PDO::ATTR_ORACLE_NULLS       => PDO::NULL_NATURAL,
        PDO::ATTR_STRINGIFY_FETCHES  => false,
    ];

    public function testSchema()
    {
        $db = new Postgres($this->config);
        $this->assertInstanceOf(PostgresSchema::class, $db->schema());
    }

    public function testBuilder()
    {
        $db = new Postgres($this->config);
        $this->assertInstanceOf(PostgresBuilder::class, $db->builder());
    }

    public function testConnectWithDefaultConfig()
    {
        $pdo = $this->getMockBuilder(PDO::class)->disableOriginalConstructor()->setMethods(['exec'])->getMock();
        $pdo->expects($this->any())->method('exec')->willReturn(0);

        /** @var AbstractDatabase|PHPUnit_Framework_MockObject_MockObject $db */
        $db = $this->getMockBuilder(Postgres::class)
            ->setMethods(['createPDO'])
            ->setConstructorArgs([$this->config])
            ->getMock();

        $db->expects($this->once())
            ->method('createPDO')
            ->with('pgsql:host=localhost;dbname=~test;port=5432;sslmode=prefer', 'pguser', 'psss', $this->options)
            ->willReturn($pdo);

        $this->assertInstanceOf(Postgres::class, $db->connect());
    }

    public function testConnectWithOptionalParams()
    {
        $this->config['schema']           = ['myschema'];
        $this->config['timezone']         = 'Europe/Berlin';
        $this->config['application_name'] = 'myapp';
        $this->config['sslcert']          = '/path/to/client-cert.pem';
        $this->config['sslkey']           = '/path/to/client-key.pem';
        $this->config['sslrootcert']      = '/path/to/server-ca.pem';

        $pdo = $this->getMockBuilder(PDO::class)->disableOriginalConstructor()->setMethods(['exec'])->getMock();
        $pdo->expects($this->any())->method('exec')->willReturn(0);
//        $pdo->expects($this->at(0))->method('exec')->with('SET search_path to "public"')->willReturn($pdoStatement);
//        $pdo->expects($this->at(1))->method('exec')->with("SET names 'utf8'")->willReturn($pdoStatement);
//        $pdo->expects($this->at(2))->method('exec')->with("SET time_zone='Europe/Berlin'")->willReturn($pdoStatement);
//        $pdo->expects($this->at(3))->method('exec')->with("SET application_name to 'myapp'")->willReturn($pdoStatement);


        /** @var AbstractDatabase|PHPUnit_Framework_MockObject_MockObject $db */
        $db = $this->getMockBuilder(Postgres::class)
            ->setMethods(['createPDO'])
            ->setConstructorArgs([$this->config])
            ->getMock();

        $db->expects($this->once())
            ->method('createPDO')
            ->with('pgsql:host=localhost;dbname=~test;port=5432;sslmode=prefer;sslcert=/path/to/client-cert.pem;sslkey=/path/to/client-key.pem;sslrootcert=/path/to/server-ca.pem', 'pguser', 'psss', $this->options)
            ->willReturn($pdo);

        $this->assertInstanceOf(Postgres::class, $db->connect());
    }

    public function testExec()
    {
        /** @noinspection SqlDialectInspection */
        $sql = 'INSERT INTO table1 (name) VALUES (?), (?)';
        $bindings = ['bar', 'buz'];

        $pdoStatement = $this->getMockBuilder(PDOStatement::class)->disableOriginalConstructor()->setMethods(['rowCount'])->getMock();
        $pdoStatement->expects($this->once())->method('rowCount')->willReturn(2);

        /** @var AbstractDatabase|PHPUnit_Framework_MockObject_MockObject $db */
        $db = $this->getMockBuilder(Postgres::class)
            ->setMethods(['perform'])
            ->setConstructorArgs([$this->config])
            ->getMock();

        $db->expects($this->once())
            ->method('perform')
            ->with($sql, $bindings)
            ->willReturn($pdoStatement);

        $this->assertSame(2, $db->exec($sql, $bindings));
        $this->assertSame('table1', $this->getPrivateProperty($db, 'lastInsertTo'));
    }

    public function testLastInsertId()
    {
        /** @var AbstractDatabase|PHPUnit_Framework_MockObject_MockObject $db */
        $db = $this->getMockBuilder(Postgres::class)
            ->setMethods(['scalar'])
            ->setConstructorArgs([$this->config])
            ->getMock();

        /** @noinspection SqlDialectInspection */
        $db->expects($this->once())
            ->method('scalar')
            ->with("
            SELECT column_default
            FROM information_schema.columns
            WHERE table_schema = ?
            AND table_name = ?
            AND column_default LIKE 'nextval(%'
        ", ['public', 'table1'])
            ->willReturn("nextval('table1_id_seq'::regclass)"); // todo fixture verwenden

        $pdo = $this->getMockBuilder(PDO::class)->disableOriginalConstructor()->setMethods(['lastInsertId'])->getMock();
        $pdo->expects($this->once())->method('lastInsertId')->willReturn(4711);
        $this->setPrivateProperty($db, 'pdo', $pdo);

        // no "INSERT INTO" query since connecting!
        $this->assertSame(0, $db->lastInsertId());

        // Fake an "INSERT INTO table1 ..." query
        $this->setPrivateProperty($db, 'lastInsertTo', 'table1');

        $this->assertSame(4711, $db->lastInsertId());
    }
}
