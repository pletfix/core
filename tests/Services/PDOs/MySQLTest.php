<?php

namespace Core\Tests\Services\PDOs;

use Core\Services\Database;
use Core\Services\PDOs\Builders\MySQLBuilder;
use Core\Services\PDOs\MySQL;
use Core\Services\PDOs\Schemas\MySQLSchema;
use Core\Testing\TestCase;
use PDO;
use PHPUnit_Framework_MockObject_MockObject;

class MySQLTest extends TestCase
{
    private $config = [
        'driver'   => 'MySQL',
        'host'     => 'localhost',
        'database' => '~test',
        'username' => 'myuser',
        'password' => 'psss',
    ];

    private $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_CASE               => PDO::CASE_NATURAL,
        PDO::ATTR_ORACLE_NULLS       => PDO::NULL_NATURAL,
        PDO::ATTR_STRINGIFY_FETCHES  => false,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    public function testQuoteName()
    {
        $db = new MySQL($this->config);
        $this->assertSame('`foo``bar`', $db->quoteName('foo`bar'));
    }
    
    public function testSchema()
    {
        $db = new MySQL($this->config);
        $this->assertInstanceOf(MySQLSchema::class, $db->schema());
    }

    public function testBuilder()
    {
        $db = new MySQL($this->config);
        $this->assertInstanceOf(MySQLBuilder::class, $db->builder());
    }

    public function testConnectWithDefaultConfig()
    {
        $pdo = $this->getMockBuilder(PDO::class)->disableOriginalConstructor()->setMethods(['exec'])->getMock();
        $pdo->expects($this->any())->method('exec')->willReturn(1);

        /** @var Database|PHPUnit_Framework_MockObject_MockObject $db */
        $db = $this->getMockBuilder(MySQL::class)
            ->setMethods(['createPDO'])
            ->setConstructorArgs([$this->config])
            ->getMock();

        $db->expects($this->once())
            ->method('createPDO')
            ->with('mysql:host=localhost;port=3306;dbname=~test', 'myuser', 'psss', $this->options)
            ->willReturn($pdo);

        $this->assertInstanceOf(MySQL::class, $db->connect());
    }

    public function testConnectWithOptionalParams()
    {
        $this->config['timezone'] = 'Europe/Berlin';
        $this->config['strict']   = false;

        $pdo = $this->getMockBuilder(PDO::class)->disableOriginalConstructor()->setMethods(['exec'])->getMock();
        $pdo->expects($this->any())->method('exec')->willReturn(1);

        /** @var Database|PHPUnit_Framework_MockObject_MockObject $db */
        $db = $this->getMockBuilder(MySQL::class)
            ->setMethods(['createPDO'])
            ->setConstructorArgs([$this->config])
            ->getMock();

        $db->expects($this->once())
            ->method('createPDO')
            ->with('mysql:host=localhost;port=3306;dbname=~test', 'myuser', 'psss', $this->options)
            ->willReturn($pdo);

        $this->assertInstanceOf(MySQL::class, $db->connect());
    }
}
