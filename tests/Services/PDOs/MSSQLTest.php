<?php

namespace Core\Tests\Services\PDOs;

use Core\Services\Database;
use Core\Services\PDOs\Builders\MSSQLBuilder;
use Core\Services\PDOs\Schemas\MSSQLSchema;
use Core\Services\PDOs\MSSQL;
use Core\Testing\TestCase;
use PDO;
use PHPUnit_Framework_MockObject_MockObject;

class MSSQLTest extends TestCase
{
    private $config = [
        'driver'   => 'MSSQL',
        'host'     => 'localhost',
        'database' => '~test',
        'username' => 'sa',
        'password' => 'psss',
    ];

    private $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_CASE               => PDO::CASE_NATURAL,
        PDO::ATTR_ORACLE_NULLS       => PDO::NULL_NATURAL,
        PDO::ATTR_STRINGIFY_FETCHES  => false,
    ];

    public function testVersion()
    {
        /** @var Database|PHPUnit_Framework_MockObject_MockObject $db */
        $db = $this->getMockBuilder(MSSQL::class)
            ->setMethods(['scalar'])
            ->setConstructorArgs([$this->config])
            ->getMock();

        $db->expects($this->once())
            ->method('scalar')
            ->with('SELECT @@VERSION')
            ->willReturn('Microsoft SQL Server 2014 - 12.0.2000.8 (Intel X86)');

        $this->assertSame('2014', $db->version());
    }

    public function testQuoteName()
    {
        $db = new MSSQL($this->config);
        $this->assertSame('[foo[]]bar]', $db->quoteName('foo[]bar'));
    }

    public function testSchema()
    {
        $db = new MSSQL($this->config);
        $this->assertInstanceOf(MSSQLSchema::class, $db->schema());
    }

    public function testBuilder()
    {
        $db = new MSSQL($this->config);
        $this->assertInstanceOf(MSSQLBuilder::class, $db->builder());
    }

    public function testConnectWithRealAvailabledDriver()
    {
        $pdo = $this->getMockBuilder(PDO::class)->disableOriginalConstructor()->getMock();

        /** @var Database|PHPUnit_Framework_MockObject_MockObject $db */
        $db = $this->getMockBuilder(MSSQL::class)
            ->setMethods(['createPDO'])
            ->setConstructorArgs([$this->config])
            ->getMock();

        $db->expects($this->once())
            ->method('createPDO')
            ->willReturn($pdo);

        $this->assertInstanceOf(MSSQL::class, $db->connect());
    }

    public function testConnectWithDblibDriver()
    {
        $pdo = $this->getMockBuilder(PDO::class)->disableOriginalConstructor()->getMock();

        /** @var Database|PHPUnit_Framework_MockObject_MockObject $db */
        $db = $this->getMockBuilder(MSSQL::class)
            ->setMethods(['createPDO', 'getAvailableDrivers'])
            ->setConstructorArgs([$this->config])
            ->getMock();

        $db->expects($this->once())
            ->method('createPDO')
            ->with('dblib:host=localhost:1433;dbname=~test', 'sa', 'psss', $this->options)
            ->willReturn($pdo);

        $db->expects($this->once())
            ->method('getAvailableDrivers')
            ->willReturn(['dblib']);

        $this->assertInstanceOf(MSSQL::class, $db->connect());
    }

    public function testConnectWithDblibDriverAndOptionalParams()
    {
        $this->config['appname'] = 'PHP Generic DB-lib';
        $this->config['charset'] = 'UTF-8';
        $this->config['port']     = 4711;

        $pdo = $this->getMockBuilder(PDO::class)->disableOriginalConstructor()->getMock();

        /** @var Database|PHPUnit_Framework_MockObject_MockObject $db */
        $db = $this->getMockBuilder(MSSQL::class)
            ->setMethods(['createPDO', 'getAvailableDrivers'])
            ->setConstructorArgs([$this->config])
            ->getMock();

        $db->expects($this->once())
            ->method('createPDO')
            ->with('dblib:host=localhost:4711;dbname=~test;appname=PHP Generic DB-lib;charset=UTF-8', 'sa', 'psss', $this->options)
            ->willReturn($pdo);

        $db->expects($this->once())
            ->method('getAvailableDrivers')
            ->willReturn(['dblib']);

        $this->assertInstanceOf(MSSQL::class, $db->connect());
    }

    public function testConnectWithOdbcDriver()
    {
        $this->config['odbc'] = 'DRIVER={Easysoft ODBC-SQL Server};SERVER=localhost\sqlexpress;UID=sa\myuser;PWD=psss';

        $pdo = $this->getMockBuilder(PDO::class)->disableOriginalConstructor()->getMock();

        /** @var Database|PHPUnit_Framework_MockObject_MockObject $db */
        $db = $this->getMockBuilder(MSSQL::class)
            ->setMethods(['createPDO', 'getAvailableDrivers'])
            ->setConstructorArgs([$this->config])
            ->getMock();

        $db->expects($this->once())
            ->method('createPDO')
            ->with('odbc:DRIVER={Easysoft ODBC-SQL Server};SERVER=localhost\sqlexpress;UID=sa\myuser;PWD=psss', 'sa', 'psss', $this->options)
            ->willReturn($pdo);

        $db->expects($this->once())
            ->method('getAvailableDrivers')
            ->willReturn(['odbc']);

        $this->assertInstanceOf(MSSQL::class, $db->connect());
    }

    public function testConnectWithSqlSrvDriver()
    {
        $this->config['odbc'] = 'DRIVER={Easysoft ODBC-SQL Server};SERVER=localhost\sqlexpress;UID=sa\myuser;PWD=psss';

        $pdo = $this->getMockBuilder(PDO::class)->disableOriginalConstructor()->getMock();

        /** @var Database|PHPUnit_Framework_MockObject_MockObject $db */
        $db = $this->getMockBuilder(MSSQL::class)
            ->setMethods(['createPDO', 'getAvailableDrivers'])
            ->setConstructorArgs([$this->config])
            ->getMock();

        $db->expects($this->once())
            ->method('createPDO')
            ->with('sqlsrv:Server=localhost,1433;Database=~test', 'sa', 'psss', $this->options)
            ->willReturn($pdo);

        $db->expects($this->once())
            ->method('getAvailableDrivers')
            ->willReturn([]);

        $this->assertInstanceOf(MSSQL::class, $db->connect());
    }

    public function testConnectWithSqlSrvDriverAndOptionalParams()
    {
        $this->config['odbc'] = 'DRIVER={Easysoft ODBC-SQL Server};SERVER=localhost\sqlexpress;UID=sa\myuser;PWD=psss';
        $this->config['appname']  = 'Apache HTTP Server';
        $this->config['readonly'] = true;
        $this->config['pooling']  = false;
        $this->config['port']     = 4711;

        $pdo = $this->getMockBuilder(PDO::class)->disableOriginalConstructor()->getMock();

        /** @var Database|PHPUnit_Framework_MockObject_MockObject $db */
        $db = $this->getMockBuilder(MSSQL::class)
            ->setMethods(['createPDO', 'getAvailableDrivers'])
            ->setConstructorArgs([$this->config])
            ->getMock();

        $db->expects($this->once())
            ->method('createPDO')
            ->with('sqlsrv:Server=localhost,4711;Database=~test;APP=Apache HTTP Server;ApplicationIntent=ReadOnly;ConnectionPooling=0', 'sa', 'psss', $this->options)
            ->willReturn($pdo);

        $db->expects($this->once())
            ->method('getAvailableDrivers')
            ->willReturn([]);

        $this->assertInstanceOf(MSSQL::class, $db->connect());
    }
}
