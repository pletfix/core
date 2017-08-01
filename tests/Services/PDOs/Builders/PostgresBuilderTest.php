<?php

namespace Core\Tests\Services\PDOs\Builders;

use Core\Services\Database;
use Core\Services\PDOs\Builders\Contracts\Builder;
use Core\Services\PDOs\Builders\PostgresBuilder;
use Core\Services\PDOs\Schemas\PostgresSchema;
use Core\Testing\TestCase;
use PHPUnit_Framework_MockObject_MockObject;

class PostgresBuilderTest extends TestCase
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

        $this->builder = new PostgresBuilder($this->db);
    }

    public function testInsert()
    {
        $data = ['a' => 'A', 'b' => 'B'];

        /** @noinspection SqlDialectInspection */
        $this->db->expects($this->once())
            ->method('exec')
            ->with('INSERT INTO "table1" ("a", "b") VALUES (?, ?)', ['A', 'B'])
            ->willReturn(0);

        /** @noinspection SqlDialectInspection */
        $this->db->expects($this->once())
            ->method('lastInsertId')
            ->willReturn(88);

        $this->assertSame(88, $this->builder->from('table1')->insert($data));
    }

//    private function expectsQuery($expectedQuery, $expectedBindings, $result, $index = null)
//    {
//        $this->db->expects($index === null ? $this->once() : $this->at($index))
//            ->method('query')
//            ->willReturnCallback(function($actualQuery, $actualBindings) use ($expectedQuery, $expectedBindings, $result) {
//                $actualQuery   = trim(preg_replace('/\s+/', ' ', str_replace("\n", '', $actualQuery)), '; ');
//                $expectedQuery = trim(preg_replace('/\s+/', ' ', str_replace("\n", '', $expectedQuery)), '; ');
//                $this->assertSame($expectedQuery, $actualQuery);
//                $this->assertSame($expectedBindings, $actualBindings);
//                return $result;
//            });
//    }

    public function testInsertEmptyData()
    {
        $schema = $this->getMockBuilder(PostgresSchema::class)
            ->setConstructorArgs([$this->db])
            ->setMethods(['columns'])
            ->getMock();

        $this->setPrivateProperty($this->db, 'schema', $schema);

        $schema->expects($this->any())
            ->method('columns')
            ->willReturn([
                'id' => ['name' => 'id', 'type' => 'bigidentity', 'size' => null, 'scale' => null, 'nullable' => false, 'default' => null, 'collation' => null, 'comment' => null]
            ]);

        /** @noinspection SqlDialectInspection */
        $this->db->expects($this->once())
            ->method('exec')
            ->with('INSERT INTO "table2" ("id") VALUES (nextval(pg_get_serial_sequence(\'table2\', \'id\')))')
            ->willReturn(0);

        /** @noinspection SqlDialectInspection */
        $this->db->expects($this->once())
            ->method('lastInsertId')
            ->willReturn(88);

        $this->assertSame(88, $this->builder->from('table2')->insert([]));
    }

    public function testInsertEmptyDataWithoutIdentity()
    {
        $schema = $this->getMockBuilder(PostgresSchema::class)
            ->setConstructorArgs([$this->db])
            ->setMethods(['columns'])
            ->getMock();

        $this->setPrivateProperty($this->db, 'schema', $schema);

        $schema->expects($this->any())
            ->method('columns')
            ->willReturn([
                'string1' => ['name' => 'string1', 'type' => 'string', 'size' => 255, 'scale' => null, 'nullable' => false, 'default' => null, 'collation' => null, 'comment' => null],
                'string2' => ['name' => 'string2', 'type' => 'string', 'size' => 255, 'scale' => null, 'nullable' => false, 'default' => null, 'collation' => null, 'comment' => null],
            ]);

        /** @noinspection SqlDialectInspection */
        $this->db->expects($this->once())
            ->method('exec')
            ->with('INSERT INTO "table3" ("string1") VALUES (?)', [''])
            ->willReturn(0);

        /** @noinspection SqlDialectInspection */
        $this->db->expects($this->once())
            ->method('lastInsertId')
            ->willReturn(87);

        $this->assertSame(87, $this->builder->from('table3')->insert([]));
    }
}