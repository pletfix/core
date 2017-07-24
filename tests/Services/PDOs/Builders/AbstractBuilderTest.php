<?php

namespace Core\Tests\Services\PDOs\Builders;

use Core\Models\Model;
use Core\Services\AbstractDatabase;
use Core\Services\Contracts\Database;
use Core\Services\PDOs\Builders\AbstractBuilder;
use Core\Services\PDOs\Builders\Contracts\Builder;
use Core\Testing\TestCase;
use Generator;
use InvalidArgumentException;
use LogicException;
use PHPUnit_Framework_MockObject_MockObject;
use stdClass;

class AbstractBuilderTest extends TestCase
{
    /**
     * @var Builder
     */
    private $builder;

    /**
     * @var Database|PHPUnit_Framework_MockObject_MockObject
     */
    private $db;

    /**
     * @var string
     */
    protected static $fixturePath;

    public static function setUpBeforeClass()
    {
        self::$fixturePath = __DIR__  . '/../../fixtures/sqlite';
    }

    private function execSqlFile(Database $db, $name)
    {
        $statements = explode(';', file_get_contents(self::$fixturePath . '/' . $name . '.sql'));
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (!empty($statement)) {
                $db->exec($statement);
            }
        }
    }

    protected function setUp()
    {
        $this->db = $this->getMockBuilder(AbstractDatabase::class)
            ->setConstructorArgs([['database' => '~test']])
            ->setMethods(['quote', 'exec', 'query', 'single', 'scalar', 'cursor', 'lastInsertId'])
            ->getMockForAbstractClass();

        $this->db->expects($this->any())
            ->method('quote')
            ->willReturnCallback(function($value) {
                return "'$value'";
            });

//        $this->builder = $this->getMockBuilder(AbstractBuilder::class)
//            ->setConstructorArgs([$this->db])
//            //->setMethods(['tables', 'columns', 'indexes', 'createTable'])
//            ->getMockForAbstractClass();

        $this->builder = new AbstractBuilder($this->db); // todo Rename AbstractBuilder, it is not really abstract!
    }

    public function testReset()
    {
        // todo erst alles setzen, anschließend auf null prüfen
        $this->assertInstanceOf(AbstractBuilder::class, $this->builder->reset());
        $this->assertSame('SELECT *', $this->builder->toSql());
    }

    public function testCopy()
    {
        $this->builder->select('column1, t1.column2 as c2')->distinct();
        $clone = $this->builder->copy();
        $this->assertEquals($this->builder, $clone);
        $this->builder->where('a=2');
        $this->assertEquals('SELECT DISTINCT "column1", "t1"."column2" AS "c2" WHERE "a"=2', $this->builder->toSql());
        $this->assertEquals('SELECT DISTINCT "column1", "t1"."column2" AS "c2"', $clone->toSql());
    }

    public function testToString()
    {
        $this->assertSame('SELECT *', (string)$this->builder);
    }

    public function testSetAndGetClass()
    {
        $this->assertNull($this->builder->getClass());
        $this->assertInstanceOf(AbstractBuilder::class, $this->builder->asClass(stdClass::class));
        $this->assertSame(stdClass::class, $this->builder->getClass());
    }

    public function testEnableAndDisableHooks()
    {
        $this->assertInstanceOf(AbstractBuilder::class, $this->builder->enableHooks());
        $this->assertTrue($this->getPrivateProperty($this->builder, 'enableHooks'));
        $this->assertInstanceOf(AbstractBuilder::class, $this->builder->disableHooks());
        $this->assertFalse($this->getPrivateProperty($this->builder, 'enableHooks'));
    }

    public function testWith()
    {
        $this->assertInstanceOf(AbstractBuilder::class, $this->builder->with('a'));
        $this->assertSame(['a'], $this->getPrivateProperty($this->builder, 'with'));

        $this->assertInstanceOf(AbstractBuilder::class, $this->builder->with('b'));
        $this->assertSame(['a', 'b'], $this->getPrivateProperty($this->builder, 'with'));

        $this->assertInstanceOf(AbstractBuilder::class, $this->builder->with(['c', 'd']));
        $this->assertSame(['a', 'b', 'c', 'd'], $this->getPrivateProperty($this->builder, 'with'));
    }

    public function testSelect()
    {
        $sql = $this->builder->reset()->select('column1, t1.column2 as c2')->distinct()->toSql();
        $this->assertSame('SELECT DISTINCT "column1", "t1"."column2" AS "c2"', $sql);

        $sql = $this->builder->reset()->select(['column1', 't1.column2 as c2', 'c3' => 't1.column3'])->toSql();
        $this->assertSame('SELECT "column1", "t1"."column2" AS "c2", "t1"."column3" AS "c3"', $sql);

        $sql = $this->builder->reset()->select('count(*) as c1')->toSql();
        $this->assertSame('SELECT COUNT(*) AS "c1"', $sql);

        $sql = $this->builder->reset()->select('ROUND(i,2) as c1')->toSql();
        $this->assertSame('SELECT ROUND("i", 2) AS "c1"', $sql);

        $sql = $this->builder->reset()->select('c2 between 44 and max(c4) as c1')->toSql();
        $this->assertSame('SELECT "c2" BETWEEN 44 AND MAX("c4") AS "c1"', $sql);

        $sql = $this->builder->reset()->select(['c1' => '2 + round(count(*)/ c3,   2)'])->toSql();
        $this->assertSame('SELECT 2 + ROUND(COUNT(*)/ "c3", 2) AS "c1"', $sql);

        /** @noinspection SqlDialectInspection */
        $sql = $this->builder->reset()->select(['c1' => 'SELECT MAX(i) FROM table2'])->toSql();
        /** @noinspection SqlDialectInspection */
        $this->assertSame('SELECT (SELECT MAX(i) FROM table2) AS "c1"', $sql);

        /** @noinspection SqlDialectInspection */
        $sql = $this->builder->reset()->select(['c1' => '(SELECT MAX(i) FROM table2)'])->toSql();
        /** @noinspection SqlDialectInspection */
        $this->assertSame('SELECT (SELECT MAX(i) FROM table2) AS "c1"', $sql);

        $sql = $this->builder->reset()->select(['c1' => $this->builder->copy()->table('table2')->select('MAX(i)')])->toSql();
        /** @noinspection SqlDialectInspection */
        $this->assertSame('SELECT (SELECT MAX("i") FROM "table2") AS "c1"', $sql);

        $sql = $this->builder->reset()->select(['c1' => function(Builder $builder) { return $builder->from('table2')->select('MAX(i)'); }])->toSql();
        /** @noinspection SqlDialectInspection */
        $this->assertSame('SELECT (SELECT MAX("i") FROM "table2") AS "c1"', $sql);

        $sql = $this->builder->reset()->select('ROUND("i,2) ",4) as c1')->toSql();
        $this->assertSame('SELECT ROUND("i,2) ", 4) AS "c1"', $sql);

        $sql = $this->builder->reset()->select(['c1' => '"(select max(i)) as x"'])->toSql();
        $this->assertSame('SELECT "(select max(i)) as x" AS "c1"', $sql);

        $sql = $this->builder->reset()->select('"like" like "%d" as c1')->toSql();
        $this->assertSame('SELECT "like" LIKE "%d" AS "c1"', $sql);

        $sql = $this->builder->reset()->select("c1 * -- max(d)\ncount(-- d)\n*)")->toSql();
        $this->assertSame("SELECT \"c1\" * -- max(d)\nCOUNT(-- d)\n*)", $sql);

        $sql = $this->builder->reset()->select(['c1' => "(select max(-- i)\n'dd)') from table2)"])->toSql();
        /** @noinspection SqlDialectInspection */
        $this->assertSame("SELECT (select max(-- i)\n'dd)') from table2) AS \"c1\"", $sql);
    }

    public function testFrom()
    {
        $sql = $this->builder->reset()->from('employees')->toSql();
        /** @noinspection SqlDialectInspection */
        $this->assertSame('SELECT * FROM "employees"', $sql);

        $sql = $this->builder->reset()->from('employees as e')->toSql();
        /** @noinspection SqlDialectInspection */
        $this->assertSame('SELECT * FROM "employees" AS "e"', $sql);

        $sql = $this->builder->reset()->from('employees', 'e')->toSql();
        /** @noinspection SqlDialectInspection */
        $this->assertSame('SELECT * FROM "employees" AS "e"', $sql);

        $sql = $this->builder->reset()->from('select * from "table1"', 't1')->toSql();
        /** @noinspection SqlDialectInspection */
        $this->assertSame('SELECT * FROM (select * from "table1") AS "t1"', $sql);

        $sql = $this->builder->reset()->from($this->builder->copy()->table('table1'), 't1')->toSql();
        /** @noinspection SqlDialectInspection */
        $this->assertSame('SELECT * FROM (SELECT * FROM "table1") AS "t1"', $sql);

        $sql = $this->builder->reset()->from(function(Builder $builder) { return $builder->from('table1'); }, 't1')->toSql();
        /** @noinspection SqlDialectInspection */
        $this->assertSame('SELECT * FROM (SELECT * FROM "table1") AS "t1"', $sql);
    }

    public function testSetAndGetTable()
    {
        $this->assertNull($this->builder->getTable());
        $this->assertInstanceOf(AbstractBuilder::class, $this->builder->table('table1'));
        $this->assertSame('"table1"', $this->builder->getTable());
        $this->assertSame('SELECT * FROM "table1"', $this->builder->toSql());
    }

    public function testJoin()
    {
        $sql = $this->builder->reset()->join('employees', 'table1.id = table2.table1_id')->toSql();
        $this->assertSame('SELECT * INNER JOIN "employees" ON "table1"."id" = "table2"."table1_id"', $sql);

        $sql = $this->builder->reset()->leftJoin('employees', 't1.id = t2.table1_id', 't2')->toSql();
        $this->assertSame('SELECT * LEFT JOIN "employees" AS "t2" ON "t1"."id" = "t2"."table1_id"', $sql);

        $sql = $this->builder->reset()->rightJoin('employees', 't1.id = t2.table1_id or t1.c1 is not null', 't2')->toSql();
        $this->assertSame('SELECT * RIGHT JOIN "employees" AS "t2" ON "t1"."id" = "t2"."table1_id" OR "t1"."c1" IS NOT NULL', $sql);

        $sql = $this->builder->reset()->join('select * from "table1"', 't1.id = round(max(t2.table1_id),2)', 't2')->toSql();
        /** @noinspection SqlDialectInspection */
        $this->assertSame('SELECT * INNER JOIN (select * from "table1") AS "t2" ON "t1"."id" = ROUND(MAX("t2"."table1_id"), 2)', $sql);

        $sql = $this->builder->reset()->join($this->builder->copy()->table('table1'), 't1.id = t2.table1_id', 't2')->toSql();
        /** @noinspection SqlDialectInspection */
        $this->assertSame('SELECT * INNER JOIN (SELECT * FROM "table1") AS "t2" ON "t1"."id" = "t2"."table1_id"', $sql);

        $sql = $this->builder->reset()->join(function(Builder $builder) { return $builder->from('table2', 'x'); }, 't1.id = t2.table1_id', 't2')->toSql();
        /** @noinspection SqlDialectInspection */
        $this->assertSame('SELECT * INNER JOIN (SELECT * FROM "table2" AS "x") AS "t2" ON "t1"."id" = "t2"."table1_id"', $sql);

        $sql = $this->builder->reset()
            ->join('table2', 'table1.id = table2.table1_id')
            ->leftJoin('table3', 'table1.id = table3.table1_id')
            ->toSql();
        $this->assertSame('SELECT * ' .
            'INNER JOIN "table2" ON "table1"."id" = "table2"."table1_id" ' .
            'LEFT JOIN "table3" ON "table1"."id" = "table3"."table1_id"'
            , $sql
        );
    }

    public function testWhere()
    {
        $sql = $this->builder->reset()->where('column1 = ? or t1.column2 like "%?%"')->toSql();
        $this->assertSame('SELECT * WHERE "column1" = ? OR "t1"."column2" LIKE "%?%"', $sql);

        $sql = $this->builder->reset()->where('column1 = :c1 or t1.column2 like "%:c2%"')->toSql();
        $this->assertSame('SELECT * WHERE "column1" = :c1 OR "t1"."column2" LIKE "%:c2%"', $sql);

        $sql = $this->builder->reset()->where('column1 = (select max(i) from table2 where c1 = ?)')->toSql();
        /** @noinspection SqlDialectInspection */
        $this->assertSame('SELECT * WHERE "column1" = (select max(i) from table2 where c1 = ?)', $sql);

        $sql = $this->builder->reset()->where(function(Builder $builder) { return $builder->where('c1 = ?')->orWhere('c2 = ?'); })->toSql();
        $this->assertSame('SELECT * WHERE ("c1" = ? OR "c2" = ?)', $sql);

        $sql = $this->builder->reset()
            ->where('x is null')
            ->where(function(Builder $builder) { return $builder->where('c1 = ?')->orWhere('c2 = ?'); })
            ->toSql();
        $this->assertSame('SELECT * WHERE "x" IS NULL AND ("c1" = ? OR "c2" = ?)', $sql);
    }

    public function testWhereIs()
    {
        $sql = $this->builder->reset()
            ->whereIs('column1', 11)
            ->whereIs('t1.column2', 22, '>')
            ->orWhereIs('t2.column3', 33)
            ->orWhereIs('column4', 44, '<')
            ->toSql();
        $this->assertSame('SELECT * WHERE "column1" = ? AND "t1"."column2" > ? OR "t2"."column3" = ? OR "column4" < ?', $sql);
        $this->assertSame([11, 22, 33, 44], $this->builder->bindings());
    }

    public function testWhereSubQuery()
    {
        $builder2 = $this->builder->copy();
        /** @noinspection SqlDialectInspection */
        $sql = $this->builder
            ->whereSubQuery('column1', $builder2->copy()->select('min(id)')->from('table1')->where('id > ?'), '=', [48])
            ->whereSubQuery('column2', function(Builder $builder) { return $builder->copy()->select('min(id)')->from('table1')->where('id > ?', [49]); })
            ->orWhereSubQuery('column3', 'select count(*) from table2')
            ->toSql();
        /** @noinspection SqlDialectInspection */
        $this->assertSame('SELECT * ' .
            'WHERE "column1" = (SELECT MIN("id") FROM "table1" WHERE "id" > ?) ' .
            'AND "column2" = (SELECT MIN("id") FROM "table1" WHERE "id" > ?) ' .
            'OR "column3" = (select count(*) from table2)',
            $sql
        );
        $this->assertSame([48, 49], $this->builder->bindings());
    }

    public function testWhereExists()
    {
        $builder2 = $this->builder->copy();

        /** @noinspection SqlDialectInspection */
        $sql = $this->builder
            ->whereExists($builder2->copy()->from('table1')->where('id > t.column1'))
            ->whereExists(function(Builder $builder) { return $builder->copy()->from('table1')->where('id > t.column2'); })
            ->orWhereExists('select * from table2 where id = t.column3')
            ->toSql();

        /** @noinspection SqlDialectInspection */
        $this->assertSame('SELECT * ' .
            'WHERE EXISTS (SELECT * FROM "table1" WHERE "id" > "t"."column1") ' .
            'AND EXISTS (SELECT * FROM "table1" WHERE "id" > "t"."column2") ' .
            'OR EXISTS (select * from table2 where id = t.column3)',
            $sql
        );
    }

    public function testWhereNotExists() // todo gegen DB testen
    {
        $builder2 = $this->builder->copy();

        /** @noinspection SqlDialectInspection */
        $sql = $this->builder
            ->whereNotExists($builder2->copy()->from('table1')->where('id > t.column1'))
            ->whereNotExists(function(Builder $builder) { return $builder->copy()->from('table1')->where('id > t.column2'); })
            ->orWhereNotExists('select * from table2 where id = t.column3')
            ->toSql();

        /** @noinspection SqlDialectInspection */
        $this->assertSame('SELECT * ' .
            'WHERE NOT EXISTS (SELECT * FROM "table1" WHERE "id" > "t"."column1") ' .
            'AND NOT EXISTS (SELECT * FROM "table1" WHERE "id" > "t"."column2") ' .
            'OR NOT EXISTS (select * from table2 where id = t.column3)',
            $sql
        );
    }

    public function testWhereIn()
    {
        $sql = $this->builder
            ->whereIn('column1', [11, 22])
            ->whereIn('column2', [33, 44])
            ->orWhereIn('column3', [55, 66])
            ->orWhereIn('column4', [77, 88])
            ->toSql();

        /** @noinspection SqlDialectInspection */
        $this->assertSame('SELECT * ' .
            'WHERE "column1" IN (?, ?) ' .
            'AND "column2" IN (?, ?) ' .
            'OR "column3" IN (?, ?) ' .
            'OR "column4" IN (?, ?)',
            $sql
        );

        $this->assertSame([11, 22, 33, 44, 55, 66, 77, 88], $this->builder->bindings());
    }

    public function testWhereNotIn()
    {
        $sql = $this->builder
            ->whereNotIn('column1', [11, 22])
            ->whereNotIn('column2', [33, 44])
            ->orWhereNotIn('column3', [55, 66])
            ->orWhereNotIn('column4', [77, 88])
            ->toSql();

        /** @noinspection SqlDialectInspection */
        $this->assertSame('SELECT * ' .
            'WHERE "column1" NOT IN (?, ?) ' .
            'AND "column2" NOT IN (?, ?) ' .
            'OR "column3" NOT IN (?, ?) ' .
            'OR "column4" NOT IN (?, ?)',
            $sql
        );

        $this->assertSame([11, 22, 33, 44, 55, 66, 77, 88], $this->builder->bindings());
    }

    public function testWhereBetween()
    {
        // todo testen, ob Abfrage ausführbar ist, ansonst Klammern hinzufügen
        $sql = $this->builder->reset()
            ->whereBetween('column1', 11, 22)
            ->whereBetween('column2', 33, 44)
            ->orWhereBetween('column3', 55, 66)
            ->orWhereBetween('column4', 77, 88)
            ->toSql();

        $this->assertSame('SELECT * ' .
            'WHERE "column1" BETWEEN ? AND ? ' .
            'AND "column2" BETWEEN ? AND ? ' .
            'OR "column3" BETWEEN ? AND ? ' .
            'OR "column4" BETWEEN ? AND ?',
            $sql
        );

        $this->assertSame([11, 22, 33, 44, 55, 66, 77, 88], $this->builder->bindings());
    }

    public function testWhereNotBetween()
    {
        // todo testen, ob Abfrage ausführbar ist, ansonst Klammern hinzufügen
        $sql = $this->builder->reset()
            ->whereNotBetween('column1', 11, 22)
            ->whereNotBetween('column2', 33, 44)
            ->orWhereNotBetween('column3', 55, 66)
            ->orWhereNotBetween('column4', 77, 88)
            ->toSql();

        $this->assertSame('SELECT * ' .
            'WHERE "column1" NOT BETWEEN ? AND ? ' .
            'AND "column2" NOT BETWEEN ? AND ? ' .
            'OR "column3" NOT BETWEEN ? AND ? ' .
            'OR "column4" NOT BETWEEN ? AND ?',
            $sql
        );

        $this->assertSame([11, 22, 33, 44, 55, 66, 77, 88], $this->builder->bindings());
    }

    public function testWhereIsNull()
    {
        $sql = $this->builder->reset()
            ->whereIsNull('column1')
            ->whereIsNull('column2')
            ->orWhereIsNull('column3')
            ->orWhereIsNull('column4')
            ->toSql();

        $this->assertSame('SELECT * ' .
            'WHERE "column1" IS NULL ' .
            'AND "column2" IS NULL ' .
            'OR "column3" IS NULL ' .
            'OR "column4" IS NULL',
            $sql
        );
    }

    public function testWhereIsNotNull()
    {
        $sql = $this->builder->reset()
            ->whereIsNotNull('column1')
            ->whereIsNotNull('column2')
            ->orWhereIsNotNull('column3')
            ->orWhereIsNotNull('column4')
            ->toSql();

        $this->assertSame('SELECT * ' .
            'WHERE "column1" IS NOT NULL ' .
            'AND "column2" IS NOT NULL ' .
            'OR "column3" IS NOT NULL ' .
            'OR "column4" IS NOT NULL',
            $sql
        );
    }

    public function testGroupBy()
    {
        $sql = $this->builder->reset()->groupBy('column1, t2.column2')->toSql();
        /** @noinspection SqlDialectInspection */
        $this->assertSame('SELECT * GROUP BY "column1", "t2"."column2"', $sql);

        $sql = $this->builder->reset()->groupBy(['column1', 't2.column2'])->toSql();
        /** @noinspection SqlDialectInspection */
        $this->assertSame('SELECT * GROUP BY "column1", "t2"."column2"', $sql);
    }

    public function testHaving()
    {
        $sql = $this->builder->reset()->having('column1 = ? or t1.column2 like "%?%"')->toSql();
        /** @noinspection SqlDialectInspection */
        $this->assertSame('SELECT * HAVING "column1" = ? OR "t1"."column2" LIKE "%?%"', $sql);

        $sql = $this->builder->reset()->having('column1 = (select max(i) from table2 having c1 = ?)')->toSql();
        /** @noinspection SqlDialectInspection */
        $this->assertSame('SELECT * HAVING "column1" = (select max(i) from table2 having c1 = ?)', $sql);

        $sql = $this->builder->reset()->having(function(Builder $builder) { return $builder->having('c1 = ?')->orHaving('c2 = ?'); })->toSql();
        /** @noinspection SqlDialectInspection */
        $this->assertSame('SELECT * HAVING ("c1" = ? OR "c2" = ?)', $sql);

        $sql = $this->builder->reset()->having('x is null')->having(function(Builder $builder) { return $builder->having('c1 = ?')->orHaving('c2 = ?'); })->toSql();
        /** @noinspection SqlDialectInspection */
        $this->assertSame('SELECT * HAVING "x" IS NULL AND ("c1" = ? OR "c2" = ?)', $sql);
    }

    public function testOrderBy()
    {
        $sql = $this->builder->reset()->orderBy('column1, column2 ASC, t1.column3 DESC')->toSql();
        /** @noinspection SqlDialectInspection */
        $this->assertSame('SELECT * ORDER BY "column1", "column2" ASC, "t1"."column3" DESC', $sql);

        $sql = $this->builder->reset()->orderBy(['column1', 'column2 ASC', 't1.column3 DESC'])->toSql();
        /** @noinspection SqlDialectInspection */
        $this->assertSame('SELECT * ORDER BY "column1", "column2" ASC, "t1"."column3" DESC', $sql);

        $sql = $this->builder->reset()->orderBy('column1')->orderBy(['column2 ASC', 't1.column3 DESC'])->toSql();
        /** @noinspection SqlDialectInspection */
        $this->assertSame('SELECT * ORDER BY "column1", "column2" ASC, "t1"."column3" DESC', $sql);
    }

    public function testLimit()
    {
        $sql = $this->builder->reset()->limit(10)->toSql();
        /** @noinspection SqlDialectInspection */
        $this->assertSame('SELECT * LIMIT 10', $sql);

        $sql = $this->builder->reset()->offset(20)->toSql();
        /** @noinspection SqlDialectInspection */
        $this->assertSame('SELECT * LIMIT 1.844674407371E+19 OFFSET 20', $sql);

        $sql = $this->builder->reset()->limit(10)->offset(20)->toSql();
        /** @noinspection SqlDialectInspection */
        $this->assertSame('SELECT * LIMIT 10 OFFSET 20', $sql);
    }

    public function testBinding()
    {
        $builder2 = $this->builder->copy();

        $sql = $this->builder
            ->select('firstname, lastname, no - ? as no2', [40])
            ->select(['x' => $builder2->copy()->select('min(id)')->from('departments')->where('id > ?', [41])])
            ->from($builder2->copy()->from('employees')->whereIn('firstname', [42, 43])->where('a=?')->limit(10), 'sub1', [44])
            ->join($builder2->copy()->from('departments')->where('id < ?', [45])->limit(10), 'sub1.id=sub2.id + ?', 'sub2', [46])
            ->where('lastname like ?', [47])
            ->whereSubQuery('last', $builder2->copy()->select('min(id)')->from('departments')->where('id > ?', [48]))
            ->orWhereBetween('x', 49, 50)
            ->groupBy('x - ?', [51])
            ->having('x <> ? and x = ?', [52, 53])
            ->having('sub2.id != ?', [54])
            ->orderBy('sub1.firstname / ?', [55])
            ->limit(10)->dump(true);

        $bindings = $this->builder->bindings();
        $this->assertCount(16, $bindings);
        foreach ($bindings as $i => $v) {
            $this->assertSame($i, $v - 40);
        }
        
        /** @noinspection SqlDialectInspection */
        $this->assertSame(
            'SELECT "firstname", "lastname", "no" - 40 AS "no2", ' .
            '(SELECT MIN("id") FROM "departments" WHERE "id" > 41) AS "x" ' .
            'FROM (SELECT * FROM "employees" WHERE "firstname" IN (42, 43) AND "a"=44 LIMIT 10) AS "sub1" ' .
            'INNER JOIN (SELECT * FROM "departments" WHERE "id" < 45 LIMIT 10) AS "sub2" ON "sub1"."id"="sub2"."id" + 46 ' .
            'WHERE "lastname" LIKE 47 ' .
            'AND "last" = (SELECT MIN("id") FROM "departments" WHERE "id" > 48) '.
            'OR "x" BETWEEN 49 AND 50 ' .
            'GROUP BY "x" - 51 ' .
            'HAVING "x" <> 52 AND "x" = 53 ' .
            'AND "sub2"."id" != 54 ' .
            'ORDER BY "sub1"."firstname" / 55 ' .
            'LIMIT 10',
            $sql
        );
    }

    public function testFind()
    {
        /** @noinspection SqlDialectInspection */
        $this->db->expects($this->once())
            ->method('single')
            ->with('SELECT * FROM "table1" WHERE "id" = ?')
            ->willReturn([10, 'a', 'b']);

        $this->assertSame([10, 'a', 'b'], $this->builder->from('table1')->find(10));
    }

    public function testFindWithCustomKeyField()
    {
        /** @noinspection SqlDialectInspection */
        $this->db->expects($this->once())
            ->method('single')
            ->with('SELECT * FROM "table1" WHERE "keyfield1" = ?', [10])
            ->willReturn([10, 'a', 'b']);

        $this->assertSame([10, 'a', 'b'], $this->builder->from('table1')->find(10, 'keyfield1'));
    }

    public function testFindAndEagerLaod()
    {
        $this->defineMemoryAsDefaultDatabase();
        $db = database();
        $this->execSqlFile($db, 'create_author_books_relation');

        $author = $db
            ->table('authors')
            ->asClass(Author::class)
            ->with('books')
            ->find(10); // not exists

        $this->assertNull($author);

        $author = $db
            ->table('authors')
            ->asClass(Author::class)
            ->with('books')
            ->find(2);

        $this->assertInstanceOf(Author::class, $author);
        $relations = $this->getPrivateProperty($author, 'relations');
        $this->assertArrayHasKey('books', $relations);
        $this->assertCount(3, $relations['books']);
    }

    public function testAll()
    {
        $data = [(object)[10, 'a', 'b'], (object)[20, 'c', 'd']];
        /** @noinspection SqlDialectInspection */
        $this->db->expects($this->once())
            ->method('query')
            ->with('SELECT * FROM "table1" WHERE "column1" = ?', [47], stdClass::class)
            ->willReturn($data);

        $result = $this->builder
            ->from('table1')
            ->asClass(stdClass::class)
            ->whereIs('column1', 47)
            ->all();

        $this->assertSame($data, $result);
    }

    public function testAllAndEagerLaod()
    {
        $this->defineMemoryAsDefaultDatabase();
        $db = database();
        $this->execSqlFile($db, 'create_author_books_relation');

        $books = $db
            ->table('books')
            ->asClass(Book::class)
            ->with('author')
            ->where('title = ?', ['foo']) // not exists
            ->all();

        $this->assertEmpty($books);

        $books = $db
            ->table('books')
            ->asClass(Book::class)
            ->with('author')
            ->all();

        $this->assertCount(5, $books);
        $book = $books[2];
        $this->assertInstanceOf(Book::class, $book);
        $relations = $this->getPrivateProperty($book, 'relations');
        $this->assertArrayHasKey('author', $relations);
        $this->assertInstanceOf(Author::class, $relations['author']);
    }

    public function testCursor()
    {
        /** @noinspection SqlDialectInspection */
        $this->db->expects($this->once())
            ->method('cursor')
            ->with('SELECT * FROM "table1" WHERE "column1" = ?', [47], stdClass::class)
            ->willReturn(Generator::class);

        $cursor = $this->builder
            ->from('table1')
            ->asClass(stdClass::class)
            ->whereIs('column1', 47)
            ->cursor();

        $this->assertSame(Generator::class, $cursor);
    }

    public function testFirst()
    {
        /** @noinspection SqlDialectInspection */
        $this->db->expects($this->once())
            ->method('single')
            ->with('SELECT * FROM "table1" WHERE "column1" = ?', [47], stdClass::class)
            ->willReturn([10, 'a', 'b']);

        $entity = $this->builder
            ->from('table1')
            ->asClass(stdClass::class)
            ->whereIs('column1', 47)
            ->first();

        $this->assertSame([10, 'a', 'b'], $entity);
    }

    public function testFirstAndEagerLaod()
    {
        $this->defineMemoryAsDefaultDatabase();
        $db = database();
        $this->execSqlFile($db, 'create_author_books_relation');

        $author = $db
            ->table('authors')
            ->asClass(Author::class)
            ->with('books')
            ->whereIs('id', 10)  // not exists
            ->first();

        $this->assertNull($author);

        $author = $db
            ->table('authors')
            ->asClass(Author::class)
            ->with('books')
            ->whereIs('id', 2)
            ->first();

        $this->assertInstanceOf(Author::class, $author);
        $relations = $this->getPrivateProperty($author, 'relations');
        $this->assertArrayHasKey('books', $relations);
        $this->assertCount(3, $relations['books']);
    }

    public function testEagerLaodWithoutRelationClass()
    {
        $this->defineMemoryAsDefaultDatabase();
        $db = database();
        $this->execSqlFile($db, 'create_author_books_relation');

        $this->expectException(LogicException::class);
        $db->table('authors')->with('books')->find(1);
    }

    public function testEagerLaodWithNonExistentRelationMethod()
    {
        $this->defineMemoryAsDefaultDatabase();
        $db = database();
        $this->execSqlFile($db, 'create_author_books_relation');

        $this->expectException(LogicException::class);
        $db->table('authors')
            ->asClass(Author::class)
            ->with('bar') // the bar method does not exist
            ->find(1);
    }

    public function testEagerLaodWithInvalidRelationMethod()
    {
        $this->defineMemoryAsDefaultDatabase();
        $db = database();
        $this->execSqlFile($db, 'create_author_books_relation');

        $this->expectException(LogicException::class);
        $db->table('authors')
            ->asClass(Author::class)
            ->with('foo') // the foo method does not return a Relation instance
            ->find(1);
    }

    public function testValue()
    {
        /** @noinspection SqlDialectInspection */
        $this->db->expects($this->once())
            ->method('scalar')
            ->with('SELECT * FROM "table1" WHERE "column1" = ?', [47])
            ->willReturn(10);

        $value = $this->builder
            ->from('table1')
            ->whereIs('column1', 47)
            ->value();

        $this->assertSame(10, $value);
    }

    public function testCount()
    {
        /** @noinspection SqlDialectInspection */
        $this->db->expects($this->once())
            ->method('scalar')
            ->with('SELECT COUNT(*) FROM "table1"')
            ->willReturn(4711);

        $this->assertSame(4711, $this->builder->from('table1')->count());
    }

    public function testMax()
    {
        /** @noinspection SqlDialectInspection */
        $this->db->expects($this->once())
            ->method('scalar')
            ->with('SELECT MAX("column1") FROM "table1"')
            ->willReturn(4711);

        $this->assertSame(4711, $this->builder->from('table1')->max('column1'));
    }

    public function testMin()
    {
        /** @noinspection SqlDialectInspection */
        $this->db->expects($this->once())
            ->method('scalar')
            ->with('SELECT MIN("column1") FROM "table1"')
            ->willReturn(4711);

        $this->assertSame(4711, $this->builder->from('table1')->min('column1'));
    }

    public function testAvg()
    {
        /** @noinspection SqlDialectInspection */
        $this->db->expects($this->once())
            ->method('scalar')
            ->with('SELECT AVG("column1") FROM "table1"')
            ->willReturn(4711);

        $this->assertSame(4711, $this->builder->from('table1')->avg('column1'));
    }

    public function testSum()
    {
        /** @noinspection SqlDialectInspection */
        $this->db->expects($this->once())
            ->method('scalar')
            ->with('SELECT SUM("column1") FROM "table1"')
            ->willReturn(4711);

        $this->assertSame(4711, $this->builder->from('table1')->sum('column1'));
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

    public function testUpdate()
    {
        $data = ['a' => 'A', 'b' => 'B'];

        /** @noinspection SqlDialectInspection */
        $this->db->expects($this->once())
            ->method('exec')
            ->with('UPDATE "table1" SET "a" = ?, "b" = ? WHERE "c" = ?', ['A', 'B', 'C'])
            ->willReturn(1);

        $this->assertSame(1, $this->builder
            ->from('table1')
            ->where('c = ?', ['C'])
            ->update($data));
    }

    public function testUpdateWithNamedPlaceholders()
    {
        $data = ['a' => 'A', 'b' => 'B'];

        /** @noinspection SqlDialectInspection */
        $this->db->expects($this->once())
            ->method('exec')
            ->with('UPDATE "table1" SET "a" = :a, "b" = :b_1 WHERE "b" = :b', ['a' => 'A', 'b' => 'BOld', 'b_1' => 'B'])
            ->willReturn(1);

        $this->assertSame(1, $this->builder
            ->from('table1')
            ->where('b = :b', ['b' => 'BOld'])
            ->update($data));
    }

    public function testUpdateEmptyData()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->builder->from('table1')->update([]);
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
            ->where('c = ?', ['C'])
            ->delete());
    }

    public function testModifyTableWithoutHooks()
    {
        self::defineMemoryAsDefaultDatabase();
        $db = database();
        
        $this->execSqlFile($db, 'create_table_for_hooktest');
        $builder = $db->table('table1')->asClass(ModelWithoutHooks::class);

        // insert
        $id = $builder->insert(['str' => 'a']);
        $row = $db->table('table1')->first();
        $this->assertSame('1', $row['id']);
        $this->assertSame('a', $row['str']);

        // insert bulk
        $builder->insert([['str' => 'a2'], ['str' => 'a3']]);
        $this->assertSame(3, $db->table('table1')->count());

        // update
        $builder->whereIs('id', $id)->update(['str' => 'b']);
        $row = $db->table('table1')->whereIs('id', $id)->first();
        $this->assertSame('b', $row['str']);

        // delete
        $builder->whereIs('id', $id)->delete();
        $row = $db->table('table1')->first();
        $this->assertSame('a2', $row['str']);
        $this->assertSame(2, $db->table('table1')->count());
    }
    
    public function testHooksCancelBefore()
    {
        self::defineMemoryAsDefaultDatabase();
        $db = database();
        $this->execSqlFile($db, 'create_table_for_hooktest');
        $db->table('table1')->insert(['str' => 'a']);
        $builder = $db->table('table1')->asClass(ModelHooksCancelBefore::class);

        // insert
        $builder->insert(['str' => 'a1']);
        $builder->insert([['str' => 'a2'], ['str' => 'a3']]);
        $this->assertSame(1, $db->table('table1')->count());
        
        // update
        $builder->update(['str' => 'b']);
        $row = $db->table('table1')->first();
        $this->assertSame('a', $row['str']);
        
        // delete
        $builder->delete();
        $this->assertSame(1, $db->table('table1')->count());
    }

    public function testHooksCancelAfter()
    {
        self::defineMemoryAsDefaultDatabase();
        $db = database();
        $this->execSqlFile($db, 'create_table_for_hooktest');
        $db->table('table1')->insert(['str' => 'a']);
        $builder = $db->table('table1')->asClass(ModelHooksCancelAfter::class);

        // insert
        $builder->insert(['str' => 'a1']);
        $builder->insert([['str' => 'a2'], ['str' => 'a3']]);
        $this->assertSame(1, $db->table('table1')->count());
        $row = $db->table('table1')->first();
        $this->assertSame('a', $row['str']);

        // update
        $builder->update(['str' => 'b']);
        $this->assertSame(1, $db->table('table1')->count());
        $row = $db->table('table1')->first();
        $this->assertSame('a', $row['str']);

        // delete
        $builder->delete();
        $this->assertSame(1, $db->table('table1')->count());
        $row = $db->table('table1')->first();
        $this->assertSame('a', $row['str']);
    }

    public function testModifyTableWithHooksBefore()
    {
        self::defineMemoryAsDefaultDatabase();
        $db = database();
        $this->execSqlFile($db, 'create_table_for_hooktest');
        $builder = $db->table('table1')->asClass(ModelWithHooksBefore::class);

        // insert
        $id = $builder->insert(['str' => 'a']);
        $row = $db->table('table1')->first();
        $this->assertSame('1', $row['x']);

        // insert bulk
        $builder->insert([['str' => 'a2'], ['str' => 'a3']]);
        $this->assertSame(3, $db->table('table1')->count());

        // update
        $builder->whereIs('id', $id)->update(['str' => 'b']);
        //$row = $db->table('table1')->all();
        $row = $db->table('table1')->first();
        $this->assertSame('3', $row['x']);

        // delete
        $builder->whereIs('id', $id)->delete();
        $row = $db->table('table1')->first();
        $this->assertSame('5', $row['x']);
    }

    public function testModifyTableWithHooksBeforeAndAfter()
    {
        self::defineMemoryAsDefaultDatabase();
        $db = database();
        $this->execSqlFile($db, 'create_table_for_hooktest');
        $builder = $db->table('table1')->asClass(ModelWithHooks::class);

        // insert
        $id = $builder->insert(['str' => 'a']);
        $row = $db->table('table1')->first();
        $this->assertSame('2', $row['x']);

        // insert bulk
        $builder->insert([['str' => 'a2'], ['str' => 'a3']]);
        $this->assertSame(3, $db->table('table1')->count());

        // update
        $builder->whereIs('id', $id)->update(['str' => 'b']);
        $row = $db->table('table1')->first();
        $this->assertSame('4', $row['x']);

        // delete
        $builder->whereIs('id', $id)->delete();
        $row = $db->table('table1')->first();
        $this->assertSame('6', $row['x']);
    }
}

///////////////////////////////////////////////////////////////////////
// Relation Test

class Author extends Model
{
    public function books()
    {
        return $this->hasMany(Book::class);
    }

    public function foo()
    {
        return 'foo';
    }
}

class Book extends Model
{
    public function author()
    {
        return $this->belongsTo(Author::class);
    }
}

///////////////////////////////////////////////////////////////////////
// Hook Test

/**
 * @property integer $id
 * @property integer $x
 * @property string $str
 */
class ModelWithoutHooks extends Model
{
    protected $table = 'table1';
}

/**
 * @property integer $id
 * @property integer $x
 * @property string $str
 */
class ModelWithHooksBefore extends Model
{
    protected $table = 'table1';

    public function beforeInsert()
    {
        if (empty($this->original) && $this->id === null && $this->str !== null) {
            $this->x = 1;
        }
    }

    public function beforeUpdate()
    {
        $dirty = $this->getDirty();
        if ($this->original['str'] === 'a' && $dirty['str'] === 'b') {
            $this->x = 3;
        }
    }

    public function beforeDelete()
    {
        if ($this->id !== null && $this->original['id'] !== null) {
            database()->table('table1')->update(['x' => 5]);
            $this->x = 5;
        }
    }
}

/**
 * @property integer $id
 * @property integer $x
 * @property string $str
 */
class ModelWithHooks extends ModelWithHooksBefore
{
    public function afterInsert()
    {
        if ($this->id !== null && $this->str !== null && $this->x === 1) {
            database()->table('table1')->whereIs('id', $this->id)->update(['x' => 2]);
            $this->x = 2;
        }
    }

    public function afterUpdate()
    {
        if ($this->str === 'b' && $this->x === 3) {
            database()->table('table1')->whereIs('id', $this->id)->update(['x' => 4]);
            $this->x = 4;
        }
    }

    public function afterDelete()
    {
        if ($this->id === null && $this->original['id'] !== null && $this->x === 5) {
            database()->table('table1')->update(['x' => 6]);
        }
    }
}

/**
 * @property integer $id
 * @property integer $x
 * @property string $str
 */
class ModelHooksCancelBefore extends Model
{
    protected $table = 'table1';

    public function beforeInsert()
    {
        return false;
    }

    public function beforeUpdate()
    {
        return false;
    }

    public function beforeDelete()
    {
        return false;
    }
}

/**
 * @property integer $id
 * @property integer $x
 * @property string $str
 */
class ModelHooksCancelAfter extends Model
{
    protected $table = 'table1';

    public function afterInsert()
    {
        return false;
    }

    public function afterUpdate()
    {
        return false;
    }

    public function afterDelete()
    {
        return false;
    }
}