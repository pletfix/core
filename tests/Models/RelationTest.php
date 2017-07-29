<?php

namespace Core\Tests\Models;

use Core\Models\Model;
use Core\Models\Relation;
use Core\Services\AbstractDatabase;
use Core\Services\PDOs\Builders\Builder;
use Core\Testing\TestCase;
use PHPUnit_Framework_MockObject_MockObject;

class RelationTest extends TestCase
{
    /**
     * @var Relation|PHPUnit_Framework_MockObject_MockObject
     */
    private $relation;

    /**
     * @var Builder|PHPUnit_Framework_MockObject_MockObject
     */
    private $builder;

    protected function setUp()
    {
        $model = new Model();
        $this->setPrivateProperty($model, 'table', 'authors');

        $db = $this->getMockBuilder(AbstractDatabase::class)
            ->setConstructorArgs([['database' => '~test']])
            ->getMockForAbstractClass();

        $this->builder = $this->getMockBuilder(Builder::class)
            ->setConstructorArgs([$db])
            ->setMethods([
                'select',
                'distinct',
                'from',
                'join',
                'leftJoin',
                'rightJoin',
                'where',
                'whereIs',
                'whereSubQuery',
                'whereExists',
                'whereNotExists',
                'whereIn',
                'whereNotIn',
                'whereBetween',
                'whereNotBetween',
                'whereIsNull',
                'whereIsNotNull',
                'orderBy',
                'limit',
                'offset',
                'all',
                'cursor',
                'first',
                'count',
                'max',
                'min',
                'avg',
                'sum',
                'update',
            ])
            ->getMockForAbstractClass();

        $this->relation = $this->getMockBuilder(Relation::class)
            ->setConstructorArgs([$model, $this->builder])
            ->setMethods([])
            ->getMockForAbstractClass();
    }

    public function testNoConstraints()
    {
        $constraints = $this->getPrivateProperty(Relation::class, 'constraints');
        $this->assertTrue($constraints);

        $result = $this->relation->noConstraints(function() {
            $constraints = $this->getPrivateProperty(Relation::class, 'constraints');
            $this->assertFalse($constraints);
            return 4711;
        });

        $this->assertSame(4711, $result);
    }

    public function testModel()
    {
        $this->assertInstanceOf(Model::class, $this->relation->model());
    }

    public function testBuilder()
    {
        $this->assertSame($this->builder, $this->relation->builder());
    }

    public function testSelect()
    {
        $this->builder->expects($this->once())->method('select')->with('name, a = ?', [47])->willReturnSelf();
        $this->assertInstanceOf(Builder::class, $this->relation->select('name, a = ?', [47]));
    }

    public function testDistinct()
    {
        $this->builder->expects($this->once())->method('distinct')->with()->willReturnSelf();
        $this->assertInstanceOf(Builder::class, $this->relation->distinct());
    }

    public function testFrom()
    {
        /** @noinspection SqlDialectInspection */
        $query = 'SELECT * FROM table1 WHERE x = ?';
        $this->builder->expects($this->once())->method('from')->with($query, 't1', [47])->willReturnSelf();
        $this->assertInstanceOf(Builder::class, $this->relation->from($query, 't1', [47]));
    }

    public function testJoin()
    {
        $this->builder->expects($this->once())->method('join')->with('table1', 'authors.id = t1.author_id AND t1.x = ?', 't1', [47])->willReturnSelf();
        $this->assertInstanceOf(Builder::class, $this->relation->join('table1', 'authors.id = t1.author_id AND t1.x = ?', 't1', [47]));
    }

    public function testLeftJoin()
    {
        $this->builder->expects($this->once())->method('leftJoin')->with('table1', 'authors.id = t1.author_id AND t1.x = ?', 't1', [47])->willReturnSelf();
        $this->assertInstanceOf(Builder::class, $this->relation->leftJoin('table1', 'authors.id = t1.author_id AND t1.x = ?', 't1', [47]));
    }

    public function testRightJoin()
    {
        $this->builder->expects($this->once())->method('rightJoin')->with('table1', 'authors.id = t1.author_id AND t1.x = ?', 't1', [47])->willReturnSelf();
        $this->assertInstanceOf(Builder::class, $this->relation->rightJoin('table1', 'authors.id = t1.author_id AND t1.x = ?', 't1', [47]));
    }

    public function testWhere()
    {
        $this->builder->expects($this->once())->method('where')->with('name <> ?', ['Peter'])->willReturnSelf();
        $this->assertInstanceOf(Builder::class, $this->relation->where('name <> ?', ['Peter']));
    }

    public function testWhereIs()
    {
        $this->builder->expects($this->once())->method('whereIs')->with('name', 'Peter', '<>')->willReturnSelf();
        $this->assertInstanceOf(Builder::class, $this->relation->whereIs('name', 'Peter', '<>'));
    }

    public function testWhereSubQuery()
    {
        /** @noinspection SqlDialectInspection */
        $query = 'SELECT id FROM table1 WHERE x = ?';
        $this->builder->expects($this->once())->method('whereSubQuery')->with('id', $query, '=', [47])->willReturnSelf();
        $this->assertInstanceOf(Builder::class, $this->relation->whereSubQuery('id', $query, '=', [47]));
    }

    public function testWhereExists()
    {
        /** @noinspection SqlDialectInspection */
        $query = 'SELECT * FROM table1 WHERE x = ?';
        $this->builder->expects($this->once())->method('whereExists')->with($query, [47])->willReturnSelf();
        $this->assertInstanceOf(Builder::class, $this->relation->whereExists($query, [47]));
    }

    public function testWhereNotExists()
    {
        /** @noinspection SqlDialectInspection */
        $query = 'SELECT * FROM table1 WHERE x = ?';
        $this->builder->expects($this->once())->method('whereNotExists')->with($query, [47])->willReturnSelf();
        $this->assertInstanceOf(Builder::class, $this->relation->whereNotExists($query, [47]));
    }

    public function testWhereIn()
    {
        $this->builder->expects($this->once())->method('whereIn')->with('id', [46, 47, 48])->willReturnSelf();
        $this->assertInstanceOf(Builder::class, $this->relation->whereIn('id', [46, 47, 48]));
    }

    public function testWhereNotIn()
    {
        $this->builder->expects($this->once())->method('whereNotIn')->with('id', [46, 47, 48])->willReturnSelf();
        $this->assertInstanceOf(Builder::class, $this->relation->whereNotIn('id', [46, 47, 48]));
    }

    public function testWhereBetween()
    {
        $this->builder->expects($this->once())->method('whereBetween')->with('id', 46, 48)->willReturnSelf();
        $this->assertInstanceOf(Builder::class, $this->relation->whereBetween('id', 46, 48));
    }

    public function testWhereNotBetween()
    {
        $this->builder->expects($this->once())->method('whereNotBetween')->with('id', 46, 48)->willReturnSelf();
        $this->assertInstanceOf(Builder::class, $this->relation->whereNotBetween('id', 46, 48));
    }

    public function testWhereIsNull()
    {
        $this->builder->expects($this->once())->method('whereIsNull')->with('name')->willReturnSelf();
        $this->assertInstanceOf(Builder::class, $this->relation->whereIsNull('name'));
    }

    public function testWhereIsNotNull()
    {
        $this->builder->expects($this->once())->method('whereIsNotNull')->with('name')->willReturnSelf();
        $this->assertInstanceOf(Builder::class, $this->relation->whereIsNotNull('name'));
    }

    public function testOrderBy()
    {
        $this->builder->expects($this->once())->method('orderBy')->with('id = ?, name', [47])->willReturnSelf();
        $this->assertInstanceOf(Builder::class, $this->relation->orderBy('id = ?, name', [47]));
    }

    public function testLimit()
    {
        $this->builder->expects($this->once())->method('limit')->with(10)->willReturnSelf();
        $this->assertInstanceOf(Builder::class, $this->relation->limit(10));
    }

    public function testOffset()
    {
        $this->builder->expects($this->once())->method('offset')->with(5)->willReturnSelf();
        $this->assertInstanceOf(Builder::class, $this->relation->offset(5));
    }

    public function testAll()
    {
        $this->builder->expects($this->once())->method('all')->with()->willReturn([new Model]);
        $authors = $this->relation->all();
		$this->assertCount(1, $authors);
		$this->assertInstanceOf(Model::class, $authors[0]);
    }

    public function testCursor()
    {
        $this->builder->expects($this->once())->method('cursor')->with()->willReturn('Generator');
        $this->assertSame('Generator', $this->relation->cursor());
    }

    public function testFirst()
    {
        $this->builder->expects($this->once())->method('first')->with()->willReturn(new Model);
        $this->assertInstanceOf(Model::class, $this->relation->first());
    }

    public function testCount()
    {
        $this->builder->expects($this->once())->method('count')->with()->willReturn(2);
        $this->assertSame(2, $this->relation->count());
    }

    public function testMax()
    {
        $this->builder->expects($this->once())->method('max')->with('id')->willReturn(2);
        $this->assertSame(2, $this->relation->max('id'));
    }

    public function testMin()
    {
        $this->builder->expects($this->once())->method('min')->with('id')->willReturn(1);
        $this->assertSame(1, $this->relation->min('id'));
    }

    public function testAvg()
    {
        $this->builder->expects($this->once())->method('avg')->with('id')->willReturn(1.5);
        $this->assertSame(1.5, $this->relation->avg('id'));
    }

    public function testSum()
    {
        $this->builder->expects($this->once())->method('sum')->with('id')->willReturn(3);
        $this->assertSame(3, $this->relation->sum('id'));
    }

    public function testUpdate()
    {
        $this->builder->expects($this->once())->method('update')->with(['name' => 'dummy'])->willReturn(1);
        $this->assertSame(1, $this->relation->update(['name' => 'dummy']));
    }
}