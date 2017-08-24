<?php

namespace Core\Tests\Services\PDOs\Builders;

use Core\Services\Database;
use Core\Services\PDOs\Builders\Contracts\Builder;
use Core\Services\PDOs\Builders\MSSQLBuilder;
use Core\Services\PDOs\MSSQL;
use Core\Services\PDOs\Schemas\MSSQLSchema;
use Core\Testing\TestCase;
use PHPUnit_Framework_MockObject_MockObject;

class MSSQLBuilderTest extends TestCase
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
        $this->db = $this->getMockBuilder(MSSQL::class)
            ->setConstructorArgs([['database' => '~test', 'username' => 'sa', 'password' => 'psss']])
            ->setMethods(['quote', 'version', 'schema'])
            ->getMockForAbstractClass();

        $this->db->expects($this->any())
            ->method('quote')
            ->willReturnCallback(function($value) {
                return "'$value'";
            });

        $this->builder = $this->getMockBuilder(MSSQLBuilder::class)
            ->setConstructorArgs([$this->db])
            ->setMethods(['count'])
            ->getMock();

        $this->builder->expects($this->any())
            ->method('count')
            ->willReturn(10);

        $schema = $this->getMockBuilder(MSSQLSchema::class)
            ->setConstructorArgs([$this->db])
            ->setMethods(['columns'])
            ->getMock();

        $this->db->expects($this->any())
            ->method('schema')
            ->willReturn($schema);

        $schema->expects($this->any())
            ->method('columns')
            ->willReturn([
                'name'      => ['name' => 'name',   'type' => 'string',   'size' => 255,  'scale' => null, 'nullable' => true,  'default' => null, 'collation' => 'Latin1_General_CI_AS', 'comment' => null],
                'gender'    => ['name' => 'gender', 'type' => 'string',   'size' => 1,    'scale' => null, 'nullable' => true,  'default' => null, 'collation' => 'Latin1_General_CS_AS', 'comment' => null],
                'id'        => ['name' => 'id',     'type' => 'identity', 'size' => null, 'scale' => null, 'nullable' => false, 'default' => null, 'collation' => null,                   'comment' => null],
            ]);
    }

    public function testLimit2012()
    {
        /** @var PHPUnit_Framework_MockObject_MockObject|MSSQL $db */
        $this->db->expects($this->any())->method('version')->willReturn('2012');

        $this->builder->from('employees');

        // without limit
        $result = $this->builder->copy()->toSql();
        /** @noinspection SqlDialectInspection */
        $this->assertSame('SELECT * FROM [employees]', $result);

        // with limit, without offset
        $result = $this->builder->copy()->limit(2)->toSql();
        /** @noinspection SqlDialectInspection */
        $this->assertSame('SELECT TOP 2 * FROM [employees]', $result);

        // with offset
        $result = $this->builder->copy()->limit(2)->offset(3)->toSql();
        /** @noinspection SqlDialectInspection */
        $this->assertSame('SELECT * FROM [employees] ORDER BY (SELECT 0) OFFSET 3 ROWS FETCH NEXT 2 ROWS ONLY', $result);

        // with bindings
        $builder2 = $this->builder->copy()->orderBy('CASE WHEN LEN(name) = ? THEN 0 ELSE 1 END, id', [4])->limit(2)->offset(3);
        /** @noinspection SqlDialectInspection */
        $this->assertSame('SELECT * FROM [employees] ORDER BY CASE WHEN LEN([name]) = ? THEN 0 ELSE 1 END, [id] OFFSET 3 ROWS FETCH NEXT 2 ROWS ONLY', $builder2->toSql());
        $this->assertSame([4], $builder2->bindings());
    }

    public function testLimit2008()
    {
        /** @var PHPUnit_Framework_MockObject_MockObject|MSSQL $db */
        $this->db->expects($this->any())->method('version')->willReturn('2008');

        $this->builder->from('employees');

        // without limit
        $result = $this->builder->copy()->toSql();
        /** @noinspection SqlDialectInspection */
        $this->assertSame('SELECT * FROM [employees]', $result);

        // with limit, without offset
        $result = $this->builder->copy()->limit(2)->toSql();
        /** @noinspection SqlDialectInspection */
        $this->assertSame('SELECT TOP 2 * FROM [employees]', $result);

        // with offset
        $result = $this->builder->copy()->limit(2)->offset(3)->toSql();
        /** @noinspection SqlDialectInspection */
        $this->assertSame('SELECT * FROM (SELECT *, ROW_NUMBER() OVER (ORDER BY (SELECT 0)) AS _row FROM [employees]) AS _t1 WHERE _row BETWEEN 4 AND 5', $result);

        // with bindings
        $builder2 = $this->builder->copy()->orderBy('CASE WHEN LEN(name) = ? THEN 0 ELSE 1 END, id', [4])->limit(2)->offset(3);
        /** @noinspection SqlDialectInspection */
        $this->assertSame('SELECT * FROM (SELECT *, ROW_NUMBER() OVER (ORDER BY CASE WHEN LEN([name]) = ? THEN 0 ELSE 1 END, [id]) AS _row FROM [employees]) AS _t1 WHERE _row BETWEEN 4 AND 5', $builder2->toSql());
        $this->assertSame([4], $builder2->bindings());
    }

    public function testLimit2000()
    {
        /** @var PHPUnit_Framework_MockObject_MockObject|MSSQL $db */
        $this->db->expects($this->any())->method('version')->willReturn('2000');

        $this->builder->from('employees');

        // without limit
        $result = $this->builder->copy()->toSql();
        /** @noinspection SqlDialectInspection */
        $this->assertSame('SELECT * FROM [employees]', $result);

        // with limit, without offset
        $result = $this->builder->copy()->limit(2)->toSql();
        /** @noinspection SqlDialectInspection */
        $this->assertSame('SELECT TOP 2 * FROM [employees]', $result);

        // with offset
        $result = $this->builder->copy()->limit(2)->offset(3)->toSql();
        /** @noinspection SqlDialectInspection */
        $this->assertSame('SELECT * FROM (SELECT TOP 2 * FROM (SELECT TOP 5 * FROM [employees] ORDER BY [id]) AS _t1 ORDER BY [id] DESC) AS _t2 ORDER BY [id]', $result);

        // limit+offset out of range
        $result = $this->builder->copy()->limit(2)->offset(9)->toSql();
        /** @noinspection SqlDialectInspection */
        $this->assertSame('SELECT * FROM (SELECT TOP 1 * FROM (SELECT TOP 10 * FROM [employees] ORDER BY [id]) AS _t1 ORDER BY [id] DESC) AS _t2 ORDER BY [id]', $result);

        // with order by
        $result = $this->builder->copy()->orderBy(['name DESC', 'gender ASC', 'id'])->limit(2)->offset(3)->toSql();
        /** @noinspection SqlDialectInspection */
        $this->assertSame('SELECT * FROM (SELECT TOP 2 * FROM (SELECT TOP 5 * FROM [employees] ORDER BY [name] DESC, [gender] ASC, [id]) AS _t1 ORDER BY [name], [gender] DESC, [id] DESC) AS _t2 ORDER BY [name] DESC, [gender] ASC, [id]', $result);

        // with bindings
        $builder2 = $this->builder->copy()->orderBy(['CASE WHEN LEN(name) = ? THEN 0 ELSE 1 END', 'id'], [4])->limit(2)->offset(3);
        /** @noinspection SqlDialectInspection */
        $this->assertSame('SELECT * FROM (SELECT TOP 2 * FROM (SELECT TOP 5 * FROM [employees] ORDER BY CASE WHEN LEN([name]) = ? THEN 0 ELSE 1 END, [id]) AS _t1 ORDER BY CASE WHEN LEN([name]) = ? THEN 0 ELSE 1 END DESC, [id] DESC) AS _t2 ORDER BY CASE WHEN LEN([name]) = ? THEN 0 ELSE 1 END, [id]', $builder2->toSql());
        $this->assertSame([4, 4, 4], $builder2->bindings());
    }
}