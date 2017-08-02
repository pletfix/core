<?php

namespace Core\Tests\Services\PDOs\Builders;

use Core\Services\Database;
use Core\Services\PDOs\Builders\Contracts\Builder;
use Core\Services\PDOs\Builders\MySQLBuilder;
use Core\Testing\TestCase;
use PHPUnit_Framework_MockObject_MockObject;

class MySQLBuilderTest extends TestCase
{
    /**
     * @var Builder
     */
    private $builder;

    /**
     * @var Database|PHPUnit_Framework_MockObject_MockObject
     */
    private $db;

    protected function setUp()
    {
        $this->db = $this->getMockBuilder(Database::class)
            ->setConstructorArgs([['database' => '~test']])
            ->setMethods(['quote', 'exec', 'lastInsertId'])
            ->getMockForAbstractClass();

        $this->db->expects($this->any())
            ->method('quote')
            ->willReturnCallback(function ($value) {
                return "'$value'";
            });

        $this->builder = new MySQLBuilder($this->db);
    }

    public function testInsertEmptyData()
    {
        /** @noinspection SqlDialectInspection */
        $this->db->expects($this->once())
            ->method('exec')
            ->with('INSERT INTO "table2" () VALUES ()')
            ->willReturn(0);

        /** @noinspection SqlDialectInspection */
        $this->db->expects($this->once())
            ->method('lastInsertId')
            ->willReturn(88);

        $this->assertSame(88, $this->builder->from('table2')->insert([]));
    }

    public function testDelete()
    {
        /** @noinspection SqlDialectInspection */
        $this->db->expects($this->once())
            ->method('exec')
            ->with('DELETE FROM "table1" WHERE "c" = ?', ['C'])
            ->willReturn(1);

        $this->assertSame(1, $this->builder
            ->from('table1')
            ->whereCondition('c = ?', ['C'])
            ->delete());
    }
}