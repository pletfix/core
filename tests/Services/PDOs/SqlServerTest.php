<?php

namespace Core\Tests\Services;

use Core\Services\PDOs\Builder\SqlServerBuilder;
use Core\Services\PDOs\Schemas\SqlServerSchema;
use Core\Services\PDOs\SqlServer;
use Core\Testing\TestCase;
use PDO;
use PDOException;

class SqlServerTest extends TestCase
{
    /**
     * @var SqlServer
     */
    private $db;

    protected function setUp()
    {
        $this->db = new SqlServer([
            'driver'   => 'sqlsrv',
            'host'     => 'localhost',
            'database' => '~test',
            'username' => 'sa',
            'password' => 'psss',
        ]);
    }

    protected function tearDown()
    {
        $this->db->disconnect();
    }

    public function testVersion()
    {
        $this->assertFalse(is_string($this->db->version()), 'get version');
    }

    public function testQuoteName()
    {
        $this->assertSame('[foo[]]bar]', $this->db->quoteName('foo[]bar'));
    }

    public function testSchema()
    {
        $this->assertInstanceOf(SqlServerSchema::class, $this->db->schema());
    }

    public function testBuilder()
    {
        $this->assertInstanceOf(SqlServerBuilder::class, $this->db->builder());
    }

    public function testConnect()
    {
        $this->expectException(PDOException::class);
        $this->assertInstanceOf(SqlServer::class, $this->db->connect());
    }


//    public function testConnect()
//    {
//        require_once '../fakes/PDO.php.fake';
//
//        $pdo = $this->getMockBuilder(PDO::class)
//            ->disableOriginalConstructor()
//            ->getMock();
//
//        //$pdo->expects($this->once())->method('render')->willReturn('blub');
//
//        $this->assertInstanceOf(SqlServer::class, $this->db->connect());
//
//        $pdo = $this->setPrivateProperty($this->db, 'pdo', $pdo);
//
//        $this->assertSame([
//            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
//            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
//            PDO::ATTR_CASE               => PDO::CASE_NATURAL,
//            PDO::ATTR_ORACLE_NULLS       => PDO::NULL_NATURAL,
//            PDO::ATTR_STRINGIFY_FETCHES  => false,
//        ], $pdo->options);
//
//        $this->assertSame('sa', $pdo->username);
//        $this->assertSame('psss', $pdo->password);
//
//    }
}
