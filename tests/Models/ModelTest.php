<?php

namespace Core\Tests\Models;

use Core\Exceptions\MassAssignmentException;
use Core\Models\BelongsToManyRelation;
use Core\Models\BelongsToRelation;
use Core\Models\HasManyRelation;
use Core\Models\HasOneRelation;
use Core\Models\Model;
use Core\Models\MorphManyRelation;
use Core\Models\MorphOneRelation;
use Core\Models\MorphToRelation;
use Core\Models\Relation;
use Core\Services\AbstractDatabase;
use Core\Services\Contracts\Database;
use Core\Services\Contracts\DatabaseFactory;
use Core\Services\DI;
use Core\Services\PDOs\Builders\AbstractBuilder;
use Core\Services\PDOs\Builders\Contracts\Builder;
use Core\Testing\TestCase;
use LogicException;
use PHPUnit_Framework_MockObject_MockObject;

class ModelTest extends TestCase
{
    /**
     * @var Database|PHPUnit_Framework_MockObject_MockObject
     */
    private $db;

    /**
     * @var string
     */
    private static $fixturePath;

    /**
     * @var DatabaseFactory
     */
    private static $origFactory;

    public static function setUpBeforeClass()
    {
        self::$fixturePath = __DIR__  . '/../Services/fixtures/sqlite';
        self::$origFactory = DI::getInstance()->get('database-factory');
    }

    public static function tearDownAfterClass()
    {
        DI::getInstance()->set('database-factory', self::$origFactory, true);
    }

//    protected function setUp()
//    {
//    }

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

    private function initTestDatabase()
    {
        $this->defineMemoryAsDefaultDatabase();
        $this->db = database();
        $this->execSqlFile($this->db, 'create_employee_relation');
        $this->execSqlFile($this->db, 'create_author_books_relation'); // todo Testfälle für empploee umschreiben
        $this->execSqlFile($this->db, 'create_table_for_hooktest');
    }

    private function getMockForBuilder($table)
    {
        $factory = $this->getMockBuilder(DatabaseFactory::class)
            ->setMethods(['store'])
            ->getMockForAbstractClass();

        $db = $this->getMockBuilder(AbstractDatabase::class)
            ->setConstructorArgs([['database' => '~test']])
            ->setMethods(['createBuilder'])
            ->getMockForAbstractClass();

        $builder = $this->getMockBuilder(AbstractBuilder::class)
            ->setConstructorArgs([$db])
            ->setMethods([
                'from',
                'with',
                'select',
                'distinct',
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
                'find',
                'all',
                'cursor',
                'first',
                'count',
                'max',
                'min',
                'avg',
                'sum',
            ])
            ->getMockForAbstractClass();

        $factory->expects($this->any())->method('store')->with(null)->willReturn($db);
        $db->expects($this->any())->method('createBuilder')->willReturn($builder);
        $builder->expects($this->at(0))->method('from')->with($table)->willReturnSelf();

        DI::getInstance()->set('database-factory', $factory, true);

        return $builder;
    }

    public function testSetAndGetAttributes()
    {
        $model = new Author;
        $attributes = ['abc' => 'def', 'uvw' => 'xyz'];
        $this->assertInstanceOf(Author::class, $model->setAttributes($attributes));
        $this->assertSame($attributes, $model->getAttributes());
    }

    public function testSetAndGetAttribute()
    {
        $this->initTestDatabase();
        $model = Author::find(1);

        // ordinary attribute
        $this->assertNull($model->getAttribute('abc'));
        $this->assertInstanceOf(Author::class, $model->setAttribute('abc', 'def'));
        $this->assertSame('def', $model->getAttribute('abc'));

        // prevent the magic invocation of methods of the base class
        $this->assertNull($model->getAttribute('sync'));

        // attribute is a relationship
        $books = $model->getAttribute('books');
        $this->assertCount(2, $books);
        $this->assertInstanceOf(Book::class, $books[0]);

        // relationship has already been loaded
        $books = $model->getAttribute('books');
        $this->assertCount(2, $books);
        $this->assertInstanceOf(Book::class, $books[0]);

        // method is not instanceof of Relation
        $this->expectException(LogicException::class);
        $this->assertSame('def', $model->getAttribute('foo'));
    }

    public function testArrayAccess()
    {
        $model = new Author;
        $this->assertFalse(isset($model['abc']));
        $model['abc'] = 'def';
        $this->assertTrue(isset($model['abc']));
        $this->assertSame('def', $model['abc']);
        $this->assertSame('def', $model->getAttribute('abc'));
        unset($model['abc']);
        $this->assertFalse(isset($model['abc']));
        $this->assertNull($model->getAttribute('abc'));
    }

    public function testOriginalAndDirtyAndSync()
    {
        $model = new Author;
        $attributes = ['abc' => 'def', 'uvw' => 'xyz'];
        $this->assertFalse($model->isDirty());
        $model->setAttributes($attributes);
        $this->assertSame([], $model->getOriginal());
        $this->assertTrue($model->isDirty());
        $this->assertSame($attributes, $model->getDirty());

        $this->assertInstanceOf(Author::class, $model->sync());
        $this->assertSame($attributes, $model->getOriginal());
        $this->assertFalse($model->isDirty());
        $this->assertSame([], $model->getDirty());

        $model->setAttribute('abc', 'ghi');
        $this->assertSame($attributes, $model->getOriginal());
        $this->assertSame('def', $model->getOriginal('abc'));
        $this->assertTrue($model->isDirty());
        $this->assertTrue($model->isDirty('abc'));
        $this->assertTrue($model->isDirty(['abc', 'uvw']));
        $this->assertFalse($model->isDirty('uvw'));
        $this->assertSame(['abc' => 'ghi'], $model->getDirty());

        $model->sync();
        $this->assertSame(['abc' => 'ghi', 'uvw' => 'xyz'], $model->getOriginal());
        $this->assertFalse($model->isDirty());
        $this->assertSame([], $model->getDirty());
    }

    public function testReload()
    {
        $this->initTestDatabase();
        $model = Author::find(1);
        $model->setAttribute('name', 'Author A1');
        $this->assertSame('Author A1', $model->getAttribute('name'));
        $this->assertSame('Author 1', $model->getOriginal('name'));
        $this->assertInstanceOf(Author::class, $model->reload());
        $this->assertSame('Author 1', $model->getAttribute('name'));
        $this->assertSame('Author 1', $model->getOriginal('name'));
    }

    public function testPrimaryKey()
    {
        $model = new Author;
        $model->setAttribute('id', 4711)->sync();
        $this->assertSame('id', $model->getPrimaryKey());
        $this->assertSame(4711, $model->getId());
    }

    public function testBuilder()
    {
        $builder = $this->getMockForBuilder('authors');
        $this->assertSame($builder, Author::builder());
    }

    public function testWith()
    {
        $this->getMockForBuilder('authors')->expects($this->once())->method('with')->with('books')->willReturnSelf();
        $this->assertInstanceOf(Builder::class, Author::with('books'));
    }

    public function testSelect()
    {
        $this->getMockForBuilder('authors')->expects($this->once())->method('select')->with('name, a = ?', [47])->willReturnSelf();
        $this->assertInstanceOf(Builder::class, Author::select('name, a = ?', [47]));
    }

    public function testDistinct()
    {
        $this->getMockForBuilder('authors')->expects($this->once())->method('distinct')->with()->willReturnSelf();
        $this->assertInstanceOf(Builder::class, Author::distinct());
    }

    public function testFrom()
    {
        /** @noinspection SqlDialectInspection */
        $query = 'SELECT * FROM table1 WHERE x = ?';
        $this->getMockForBuilder('authors')->expects($this->at(1))->method('from')->with($query, 't1', [47])->willReturnSelf();
        $this->assertInstanceOf(Builder::class, Author::from($query, 't1', [47]));
    }

    public function testJoin()
    {
        $this->getMockForBuilder('authors')->expects($this->once())->method('join')->with('table1', 'authors.id = t1.author_id AND t1.x = ?', 't1', [47])->willReturnSelf();
        $this->assertInstanceOf(Builder::class, Author::join('table1', 'authors.id = t1.author_id AND t1.x = ?', 't1', [47]));
    }

    public function testLeftJoin()
    {
        $this->getMockForBuilder('authors')->expects($this->once())->method('leftJoin')->with('table1', 'authors.id = t1.author_id AND t1.x = ?', 't1', [47])->willReturnSelf();
        $this->assertInstanceOf(Builder::class, Author::leftJoin('table1', 'authors.id = t1.author_id AND t1.x = ?', 't1', [47]));
    }

    public function testRightJoin()
    {
        $this->getMockForBuilder('authors')->expects($this->once())->method('rightJoin')->with('table1', 'authors.id = t1.author_id AND t1.x = ?', 't1', [47])->willReturnSelf();
        $this->assertInstanceOf(Builder::class, Author::rightJoin('table1', 'authors.id = t1.author_id AND t1.x = ?', 't1', [47]));
    }

    public function testWhere()
    {
        $this->getMockForBuilder('authors')->expects($this->once())->method('where')->with('name <> ?', ['Peter'])->willReturnSelf();
        $this->assertInstanceOf(Builder::class, Author::where('name <> ?', ['Peter']));
    }

    public function testWhereIs()
    {
        $this->getMockForBuilder('authors')->expects($this->once())->method('whereIs')->with('name', 'Peter', '<>')->willReturnSelf();
        $this->assertInstanceOf(Builder::class, Author::whereIs('name', 'Peter', '<>'));
    }

    public function testWhereSubQuery()
    {
        /** @noinspection SqlDialectInspection */
        $query = 'SELECT id FROM table1 WHERE x = ?';
        $this->getMockForBuilder('authors')->expects($this->once())->method('whereSubQuery')->with('id', $query, '=', [47])->willReturnSelf();
        $this->assertInstanceOf(Builder::class, Author::whereSubQuery('id', $query, '=', [47]));
    }

    public function testWhereExists()
    {
        /** @noinspection SqlDialectInspection */
        $query = 'SELECT * FROM table1 WHERE x = ?';

        $this->getMockForBuilder('authors')->expects($this->once())->method('whereExists')->with($query, [47])->willReturnSelf();
        $this->assertInstanceOf(Builder::class, Author::whereExists($query, [47]));

        $this->getMockForBuilder('authors')->expects($this->once())->method('whereNotExists')->with($query, [47])->willReturnSelf();
        $this->assertInstanceOf(Builder::class, Author::whereNotExists($query, [47]));
    }

    public function testWhereIn()
    {
        $this->getMockForBuilder('authors')->expects($this->once())->method('whereIn')->with('id', [46, 47, 48])->willReturnSelf();
        $this->assertInstanceOf(Builder::class, Author::whereIn('id', [46, 47, 48]));

        $this->getMockForBuilder('authors')->expects($this->once())->method('whereNotIn')->with('id', [46, 47, 48])->willReturnSelf();
        $this->assertInstanceOf(Builder::class, Author::whereNotIn('id', [46, 47, 48]));
    }

    public function testWhereBetween()
    {
        $this->getMockForBuilder('authors')->expects($this->once())->method('whereBetween')->with('id', 46, 48)->willReturnSelf();
        $this->assertInstanceOf(Builder::class, Author::whereBetween('id', 46, 48));

        $this->getMockForBuilder('authors')->expects($this->once())->method('whereNotBetween')->with('id', 46, 48)->willReturnSelf();
        $this->assertInstanceOf(Builder::class, Author::whereNotBetween('id', 46, 48));
    }

    public function testWhereIsNull()
    {
        $this->getMockForBuilder('authors')->expects($this->once())->method('whereIsNull')->with('name')->willReturnSelf();
        $this->assertInstanceOf(Builder::class, Author::whereIsNull('name'));

        $this->getMockForBuilder('authors')->expects($this->once())->method('whereIsNotNull')->with('name')->willReturnSelf();
        $this->assertInstanceOf(Builder::class, Author::whereIsNotNull('name'));
    }

    public function testOrderBy()
    {
        $this->getMockForBuilder('authors')->expects($this->once())->method('orderBy')->with('id = ?, name', [47])->willReturnSelf();
        $this->assertInstanceOf(Builder::class, Author::orderBy('id = ?, name', [47]));
    }

    public function testLimit()
    {
        $this->getMockForBuilder('authors')->expects($this->once())->method('limit')->with(10)->willReturnSelf();
        $this->assertInstanceOf(Builder::class, Author::limit(10));
    }

    public function testOffset()
    {
        $this->getMockForBuilder('authors')->expects($this->once())->method('offset')->with(5)->willReturnSelf();
        $this->assertInstanceOf(Builder::class, Author::offset(5));
    }

    public function testFind()
    {
        $this->getMockForBuilder('authors')->expects($this->once())->method('find')->with(5, 'id')->willReturn(new Author);
        $this->assertInstanceOf(Author::class, Author::find(5, 'id'));
    }

    public function testAll()
    {
        $this->getMockForBuilder('authors')->expects($this->once())->method('all')->with()->willReturn([new Author]);
        $authors = Author::all();
		$this->assertCount(1, $authors);
		$this->assertInstanceOf(Author::class, $authors[0]);
    }

    public function testCursor()
    {
        $this->getMockForBuilder('authors')->expects($this->once())->method('cursor')->with()->willReturn('Generator'); //todo
        $this->assertSame('Generator', Author::cursor());
    }

    public function testFirst()
    {
        $this->getMockForBuilder('authors')->expects($this->once())->method('first')->with()->willReturn(new Author);
        $this->assertInstanceOf(Author::class, Author::first());
    }

    public function testCount()
    {
        $this->getMockForBuilder('authors')->expects($this->once())->method('count')->with()->willReturn(2);
        $this->assertSame(2, Author::count());
    }

    public function testMax()
    {
        $this->getMockForBuilder('authors')->expects($this->once())->method('max')->with('id')->willReturn(2);
        $this->assertSame(2, Author::max('id'));
    }

    public function testMin()
    {
        $this->getMockForBuilder('authors')->expects($this->once())->method('min')->with('id')->willReturn(1);
        $this->assertSame(1, Author::min('id'));
    }

    public function testAvg()
    {
        $this->getMockForBuilder('authors')->expects($this->once())->method('avg')->with('id')->willReturn(1.5);
        $this->assertSame(1.5, Author::avg('id'));
    }

    public function testSum()
    {
        $this->getMockForBuilder('authors')->expects($this->once())->method('sum')->with('id')->willReturn(3);
        $this->assertSame(3, Author::sum('id'));
    }

    public function testModifyModelWithoutHooks()
    {
        $this->initTestDatabase();

        // create
        $model = ModelWithoutHooks::create(['str' => 'a']);
        $this->assertInstanceOf(Model::class, $model);
        $this->assertSame(1, $model->id);
        $this->assertSame('a', $model->str);
        $row = database()->table('table1')->first();
        $this->assertSame('a', $row['str']);

        // update
        $this->assertTrue($model->update(['str' => 'b']));
        $this->assertSame('b', $model->str);
        $row = database()->table('table1')->first();
        $this->assertSame('b', $row['str']);

        // update nothing
        $this->assertTrue($model->update([]));

        // delete
        $this->assertTrue($model->delete());
        $this->assertNull($model->id);
        $this->assertSame(0, database()->table('table1')->count());
    }

    public function testHooksCancelBefore()
    {
        $this->initTestDatabase();
        database()->table('table1')->insert(['str' => 'a']);

        // create
        /** @var ModelHooksCancelBefore|false $model */
        $model = ModelHooksCancelBefore::create(['str' => 'b']);
        $this->assertFalse($model);
        $this->assertSame(1, database()->table('table1')->count());

        // update
        $model = ModelHooksCancelBefore::first();
        $this->assertFalse($model->update(['str' => 'b']));
        $row = database()->table('table1')->first();
        $this->assertSame('a', $row['str']);

        // delete
        $model = ModelHooksCancelBefore::first();
        $this->assertFalse($model->delete());
        $this->assertSame(1, database()->table('table1')->count());
    }

    public function testHooksCancelAfter()
    {
        $this->initTestDatabase();
        database()->table('table1')->insert(['str' => 'a']);

        // create
        /** @var ModelHooksCancelBefore|false $model */
        $model = ModelHooksCancelAfter::create(['str' => 'b']);
        $this->assertFalse($model);
        $this->assertSame(1, database()->table('table1')->count());

        // update
        $model = ModelHooksCancelAfter::first();
        $this->assertFalse($model->update(['str' => 'b']));
        $row = database()->table('table1')->first();
        $this->assertSame('a', $row['str']);

        // delete
        $model = ModelHooksCancelAfter::first();
        $this->assertFalse($model->delete());
        $this->assertSame(1, database()->table('table1')->count());
    }

    public function testModifyModelWithHooksBefore()
    {
        $this->initTestDatabase();

        // create
        $model = ModelWithHooksBefore::create(['str' => 'a']);
        $this->assertInstanceOf(Model::class, $model);
        $this->assertSame(1, $model->id);
        $this->assertSame('a', $model->str);
        $this->assertSame(1, $model->x);
        $row = database()->table('table1')->first();
        $this->assertSame('a', $row['str']);
        $this->assertSame('1', $row['x']);

        // update
        $this->assertTrue($model->update(['str' => 'b']));
        $this->assertSame('b', $model->str);
        $this->assertSame(3, $model->x);
        $row = database()->table('table1')->first();
        $this->assertSame('b', $row['str']);
        $this->assertSame('3', $row['x']);

        // update nothing (the before-hook will be reset the attribute to 'b')
        $this->assertTrue($model->update(['str' => 'reset to b']));

        // delete
        database()->table('table1')->insert(['str' => 'z']);
        $this->assertTrue($model->delete());
        $this->assertNull($model->id);
        $this->assertSame(1, database()->table('table1')->count());
        $row = database()->table('table1')->first();
        $this->assertSame('5', $row['x']);
    }

    public function testModifyModelWithHooksBeforeAndAfter()
    {
        $this->initTestDatabase();

        // create
        $model = ModelWithHooks::create(['str' => 'a']);
        $this->assertInstanceOf(Model::class, $model);
        $this->assertSame(1, $model->id);
        $this->assertSame('a', $model->str);
        $this->assertSame(2, $model->x);
        $row = database()->table('table1')->first();
        $this->assertSame('a', $row['str']);
        $this->assertSame('2', $row['x']);

        // update
        $this->assertTrue($model->update(['str' => 'b']));
        $this->assertSame('b', $model->str);
        $this->assertSame(4, $model->x);
        $row = database()->table('table1')->first();
        $this->assertSame('b', $row['str']);
        $this->assertSame('4', $row['x']);

        // delete
        database()->table('table1')->insert(['str' => 'z']);
        $this->assertTrue($model->delete());
        $this->assertNull($model->id);
        $this->assertSame(1, database()->table('table1')->count());
        $row = database()->table('table1')->first();
        $this->assertSame('6', $row['x']);
    }

    public function testReplicate()
    {
        $model = new Book;
        $model->id = 1;
        $model->author_id = 2;
        $model->title = 'Delux';

        $clone = $model->replicate(['author_id']);

        $this->assertInstanceOf(Model::class, $clone);
        $this->assertNull($clone->id);
        $this->assertNull($clone->author_id);
        $this->assertSame('Delux', $clone->title);
    }

    public function testGetGuarded()
    {
        $model = new Book;
        $this->assertSame(['id', 'created_by', 'created_at', 'updated_by', 'updated_at'], $model->getGuarded());
    }

    public function testCheckMassAssignment()
    {
        $this->assertNull(Book::checkMassAssignment(['title' => 'Delux', 'author_id' => 4]));
        $this->assertNull((new Book)->checkMassAssignment(['title' => 'Delux', 'author_id' => 4]));
        $this->expectException(MassAssignmentException::class);
        Book::checkMassAssignment(['id' => 3, 'title' => 'Delux']);
    }

    public function testClearRelationCache()
    {
        $model = new Book;
        $this->setPrivateProperty($model, 'relations', ['author' => new Author]);
        $this->assertInstanceOf(Model::class, $model->clearRelationCache());
        $this->assertEmpty($this->getPrivateProperty($model, 'relations'));
    }

    // employee <- profile
    public function testHasOneRelation()
    {
        $this->initTestDatabase();

        /** @var Employee $employee */
        $employee = Employee::find(1);
        $this->assertSame('Anton', $employee->name);
        $this->assertInstanceOf(HasOneRelation::class, $employee->profile());

        $builder = $employee->profile()->builder();
        /** @noinspection SqlDialectInspection */
        $this->assertSame('SELECT * FROM "profiles" WHERE "employee_id" = ?', $builder->toSql());
        $this->assertSame(['1'], $builder->bindings());

        $this->assertSame(1, $employee->profile()->count());

        $this->assertInstanceOf(Profile::class, $employee->profile);
        $this->assertSame('Profile Anton', $employee->profile->name);

        // Eager Loading

        Relation::noConstraints(function () {
            /** @var Employee[] $employees */
            $employees = Employee::all();
            $builder = $employees[0]->profile()->addEagerConstraints($employees);
            /** @noinspection SqlDialectInspection */
            $this->assertSame('SELECT * FROM "profiles" WHERE "employee_id" IN (?, ?, ?)', $builder->toSql());
            $this->assertSame(['1', '2', '3'], $builder->bindings());
        });

        $employee = Employee::with('profile')->find(2);
        $this->assertSame('Berta', $employee->name);
        $this->assertSame('Profile Berta', $employee->profile->name);
    }

    // employee <- salaries
    public function testHasManyRelation()
    {
        $this->initTestDatabase();

        /** @var Employee $employee */
        $employee = Employee::find(1);
        $this->assertSame('Anton', $employee->name);
        $this->assertInstanceOf(HasManyRelation::class, $employee->salaries());

        $builder = $employee->salaries()->builder();
        /** @noinspection SqlDialectInspection */
        $this->assertSame('SELECT * FROM "salaries" WHERE "employee_id" = ?', $builder->toSql());
        $this->assertSame(['1'], $builder->bindings());

        $this->assertSame(2, $employee->salaries()->count());

        $this->assertCount(2, $employee->salaries);
        $this->assertInstanceOf(Salary::class, $employee->salaries[0]);
        $this->assertSame('Salary Anton 1', $employee->salaries[0]->name);
        $this->assertSame('Salary Anton 2', $employee->salaries[1]->name);

        // Eager Loading

        Relation::noConstraints(function () {
            /** @var Employee[] $employees */
            $employees = Employee::all();
            $builder = $employees[0]->salaries()->addEagerConstraints($employees);
            /** @noinspection SqlDialectInspection */
            $this->assertSame('SELECT * FROM "salaries" WHERE "employee_id" IN (?, ?, ?)', $builder->toSql());
            $this->assertSame(['1', '2', '3'], $builder->bindings());
        });

        $employee = Employee::with('salaries')->find(2);
        $this->assertSame('Berta', $employee->name);
        $this->assertCount(2, $employee->salaries);
        $this->assertSame('Salary Berta 1', $employee->salaries[0]->name);
        $this->assertSame('Salary Berta 2', $employee->salaries[1]->name);
    }

    // salary -> employee
    public function testBelongsToRelation()
    {
        $this->initTestDatabase();

        /** @var Salary $salary */
        $salary = Salary::find(3);
        $this->assertSame('Salary Berta 1', $salary->name);
        $this->assertInstanceOf(BelongsToRelation::class, $salary->employee());

        $builder = $salary->employee()->builder();
        /** @noinspection SqlDialectInspection */
        $this->assertSame('SELECT * FROM "employees" WHERE "id" = ?', $builder->toSql());
        $this->assertSame(['2'], $builder->bindings());

        $this->assertInstanceOf(Employee::class, $salary->employee);
        $this->assertSame('Berta', $salary->employee->name);

        // Eager Loading

        Relation::noConstraints(function () {
            /** @var Salary[] $salaries */
            $salaries = Salary::all();
            $builder = $salaries[0]->employee()->addEagerConstraints($salaries);
            /** @noinspection SqlDialectInspection */
            $this->assertSame('SELECT * FROM "employees" WHERE "id" IN (?, ?, ?, ?)', $builder->toSql());
            $this->assertSame(['1', '1', '2', '2'], $builder->bindings());
        });

        $salary = Salary::with('employee')->find(1);
        $this->assertSame('Salary Anton 1', $salary->name);
        $this->assertSame('Anton', $salary->employee->name);
    }

    // employee <-> departments
    public function testBelongsToManyRelation()
    {
        $this->initTestDatabase();

        /** @var Employee $employee */
        $employee = Employee::find(1);
        $this->assertSame('Anton', $employee->name);
        $this->assertInstanceOf(BelongsToManyRelation::class, $employee->departments());

        $builder = $employee->departments()->builder();
        /** @noinspection SqlDialectInspection */
        $this->assertSame('SELECT "departments".* FROM "departments" INNER JOIN "department_employee" ON "departments"."id" = "department_employee"."department_id" WHERE "department_employee"."employee_id" = ?', $builder->toSql());
        $this->assertSame(['1'], $builder->bindings());

        $this->assertSame(2, $employee->departments()->count());

        $this->assertInstanceOf(Department::class, $employee->departments[0]);
        $this->assertSame('Development', $employee->departments[0]->name);
        $this->assertSame('Marketing', $employee->departments[1]->name);

        // Eager Loading

        Relation::noConstraints(function () {
            /** @var Employee[] $employees */
            $employees = Employee::all();
            $builder = $employees[0]->departments()->addEagerConstraints($employees);
            /** @noinspection SqlDialectInspection */
            $this->assertSame('SELECT "departments".*, "department_employee"."employee_id" AS "___id" FROM "departments" INNER JOIN "department_employee" ON "departments"."id" = "department_employee"."department_id" WHERE "department_employee"."employee_id" IN (?, ?, ?)', $builder->toSql());
            $this->assertSame(['1', '2', '3'], $builder->bindings());
        });

        $employee = Employee::with('departments')->find(2);
        $this->assertSame('Berta', $employee->name);
        $this->assertCount(2, $employee->departments);
        $this->assertSame('Marketing', $employee->departments[0]->name);
        $this->assertSame('HR', $employee->departments[1]->name);
    }

    // department <-> employees
    public function testBelongsToManyRelatio2()
    {
        $this->initTestDatabase();

        /** @var Department $department */
        $department = Department::find(2);
        $this->assertSame('Marketing', $department->name);
        $this->assertInstanceOf(BelongsToManyRelation::class, $department->employees());

        $builder = $department->employees()->builder();
        /** @noinspection SqlDialectInspection */
        $this->assertSame('SELECT "employees".* FROM "employees" INNER JOIN "department_employee" ON "employees"."id" = "department_employee"."employee_id" WHERE "department_employee"."department_id" = ?', $builder->toSql());
        $this->assertSame(['2'], $builder->bindings());

        $this->assertSame(2, $department->employees()->count());

        $this->assertInstanceOf(Employee::class, $department->employees[0]);
        $this->assertSame('Anton', $department->employees[0]->name);
        $this->assertSame('Berta', $department->employees[1]->name);

        // Eager Loading

        Relation::noConstraints(function () {
            /** @var Department[] $departments */
            $departments = Department::all();
            $builder = $departments[0]->employees()->addEagerConstraints($departments);
            /** @noinspection SqlDialectInspection */
            $this->assertSame('SELECT "employees".*, "department_employee"."department_id" AS "___id" FROM "employees" INNER JOIN "department_employee" ON "employees"."id" = "department_employee"."employee_id" WHERE "department_employee"."department_id" IN (?, ?, ?, ?)', $builder->toSql());
            $this->assertSame(['1', '2', '3', '4'], $builder->bindings());
        });

        $department = Department::with('employees')->find(3);
        $this->assertSame('HR', $department->name);
        $this->assertCount(1, $department->employees);
        $this->assertSame('Berta', $department->employees[0]->name);
    }

    // department <- picture
    public function testMorphOneRelation()
    {
        $this->initTestDatabase();
        
        /** @var Department $department */
        $department = Department::find(1);
        $this->assertSame('Development', $department->name);
        $this->assertInstanceOf(MorphOneRelation::class, $department->picture());

        $builder = $department->picture()->builder();
        /** @noinspection SqlDialectInspection */
        $this->assertSame('SELECT * FROM "pictures" WHERE "imageable_type" = ? AND "imageable_id" = ?', $builder->toSql());
        $this->assertSame(['Core\Tests\Models\Department', '1'], $builder->bindings());

        $this->assertSame(1, $department->picture()->count());

        $this->assertInstanceOf(Picture::class, $department->picture);
        $this->assertSame('Picture Development', $department->picture->name);

        // Eager Loading

        Relation::noConstraints(function () {
            /** @var Department[] $departments */
            $departments = Department::all();
            $builder = $departments[0]->picture()->addEagerConstraints($departments);
            /** @noinspection SqlDialectInspection */
            $this->assertSame('SELECT * FROM "pictures" WHERE "imageable_type" = ? AND "imageable_id" IN (?, ?, ?, ?)', $builder->toSql());
            $this->assertSame(['Core\Tests\Models\Department', '1', '2', '3', '4'], $builder->bindings());
        });

        $department = Department::with('picture')->find(2);
        $this->assertSame('Marketing', $department->name);
        $this->assertSame('Picture Marketing', $department->picture->name);
    }

    // employees <- pictures
    public function testMorphManyRelation()
    {
        $this->initTestDatabase();

        /** @var Employee $employee */
        $employee = Employee::find(1);
        $this->assertSame('Anton', $employee->name);
        $this->assertInstanceOf(MorphManyRelation::class, $employee->pictures());

        $builder = $employee->pictures()->builder();
        /** @noinspection SqlDialectInspection */
        $this->assertSame('SELECT * FROM "pictures" WHERE "imageable_type" = ? AND "imageable_id" = ?', $builder->toSql());
        $this->assertSame(['Core\Tests\Models\Employee', '1'], $builder->bindings());

        $this->assertSame(2, $employee->pictures()->count());

        $this->assertCount(2, $employee->pictures);
        $this->assertInstanceOf(Picture::class, $employee->pictures[0]);
        $this->assertSame('Picture Anton 1', $employee->pictures[0]->name);
        $this->assertSame('Picture Anton 2', $employee->pictures[1]->name);

        // employees -> pictures (Eager Loading)

        Relation::noConstraints(function () {
            /** @var Employee[] $employees */
            $employees = Employee::all();
            $builder = $employees[0]->pictures()->addEagerConstraints($employees);
            /** @noinspection SqlDialectInspection */
            $this->assertSame('SELECT * FROM "pictures" WHERE "imageable_type" = ? AND "imageable_id" IN (?, ?, ?)', $builder->toSql());
            $this->assertSame(['Core\Tests\Models\Employee', '1', '2', '3'], $builder->bindings());
        });

        $employee = Employee::with('salaries')->find(2);
        $this->assertSame('Berta', $employee->name);
        $this->assertCount(2, $employee->pictures);
        $this->assertSame('Picture Berta 1', $employee->pictures[0]->name);
        $this->assertSame('Picture Berta 2', $employee->pictures[1]->name);
    }

    // picture -> employees
    public function testMorphToRelation()
    {
        $this->initTestDatabase();

        /** @var Picture $picture */
        $picture = Picture::find(2);
        $this->assertSame('Picture Anton 2', $picture->name);
        $this->assertInstanceOf(MorphToRelation::class, $picture->imageable());

        $builder = $picture->imageable()->builder();
        /** @noinspection SqlDialectInspection */
        $this->assertSame('SELECT * FROM "employees" WHERE "id" = ?', $builder->toSql());
        $this->assertSame(['1'], $builder->bindings());

        $this->assertSame(2, $picture->imageable()->count());

        $this->assertInstanceOf(Employee::class, $picture->imageable[0]);
        $this->assertSame('Berta', $picture->imageable[0]->name);
        $this->assertSame('Berta', $picture->imageable[1]->name);

        // picture -> employee (Eager Loading)

        Relation::noConstraints(function () {
            /** @var Picture[] $pictures */
            $pictures = Picture::whereIn('id', [2, 3])->all();
            $builder = $pictures[0]->imageable()->addEagerConstraints($pictures);
            /** @noinspection SqlDialectInspection */
            $this->assertSame('SELECT * FROM "employees" WHERE "id" IN (?, ?, ?)', $builder->toSql());
            $this->assertSame(['1', '2', '3'], $builder->bindings());
        });

        $picture = Picture::with('imageable')->find(3);
        $this->assertSame('Picture Berta 1', $picture->name);
        $this->assertSame('Berta', $picture->imageable->name);
    }

    // picture -> department
    public function testMorphToRelation2()
    {
        $this->initTestDatabase();

        /** @var Picture $picture */
        $picture = Picture::find(3);
        $this->assertSame('Picture Berta 1', $picture->name);
        $this->assertInstanceOf(MorphToRelation::class, $picture->imageable());

        $builder = $picture->imageable()->builder();
        /** @noinspection SqlDialectInspection */
        $this->assertSame('SELECT * FROM "departments" WHERE "id" = ?', $builder->toSql());
        $this->assertSame(['2'], $builder->bindings());

        $this->assertSame(1, $picture->imageable()->count());

        $this->assertInstanceOf(Department::class, $picture->imageable);
        $this->assertSame('Berta', $picture->imageable->name);

        // picture -> department (Eager Loading)

        Relation::noConstraints(function () {
            /** @var Picture[] $pictures */
            $pictures = Picture::whereIn('id', [2, 3])->all();
            $builder = $pictures[0]->imageable()->addEagerConstraints($pictures);
            /** @noinspection SqlDialectInspection */
            $this->assertSame('SELECT * FROM "department" WHERE "id" IN (?, ?, ?)', $builder->toSql());
            $this->assertSame(['1', '2', '3'], $builder->bindings());
        });

        $picture = Picture::with('imageable')->find(2);
        $this->assertSame('Picture Anton 2', $picture->name);
        $this->assertSame('Anton', $picture->imageable->name);
    }


//    public function testAssociateRelation()
//    {
//        echo "<p><b>Associate Relations</b></p>";
//
//        /** @var Employee $employee */
//        $employee = Employee::find(1);
//        $i = count($employee->departments);
//
//        $department = Department::create(['name' => 'dummy']);
//        $employee->departments()->associate($department);
//        if (count($department->employees) !== 1) {
//            dd("Test a failed!");
//        }
//        if (count($employee->departments) !== $i + 1) {
//            dd("Test b failed!");
//        }
//        $rows = $employee->database()->table('department_employee')->where('employee_id = ? AND department_id = ?', [$employee->id, $department->id])->all();
//        if (count($rows) !== 1) {
//            dd("Test c failed!");
//        }
//        $departmentId = $department->id;
//        $employee->departments()->disassociate($department);
//        if (!empty($department->employees)) {
//            dd("Test d failed!");
//        }
//        $employee->departments()->associate($department);
//        $department->employees()->disassociate();
//        if (!empty($department->employees)) {
//            dd("Test e failed!");
//        }
//        if (count($employee->departments) !== $i) {
//            dd("Test f failed!");
//        }
//        $department->delete();
//        if (Department::count() !== 9) {
//            dd("Test g failed!");
//        }
//        $rows = $employee->database()->table('department_employee')->where('employee_id = ? AND department_id = ?', [$employee->id, $departmentId])->all();
//        if (!empty($rows)) {
//            dd("Test h failed!");
//        }
//
//        $salary = Salary::create(['salary' => 4711]);
//        $employee->salaries()->associate($salary);
//        if ($salary->employee_id !== $employee->id) {
//            dd("Test i failed!");
//        }
//        $employee->salaries()->disassociate($salary);
//        if ($salary->employee_id !== null) {
//            dd("Test j failed!");
//        }
//        $salary->delete();
//
//        $picture  = Picture::create(['name'  => 'dummy']);
//        $employee->pictures()->associate($picture);
//        if ($picture->imageable_id !== $employee->id || $picture->imageable_type != 'App\Models\Employee') {
//            dd("Test l failed!");
//        }
//        $employee->pictures()->disassociate($picture);
//        if ($picture->imageable_type !== null || $picture->imageable_id !== null) {
//            dd("Test m failed!");
//        }
//        $picture->delete();
//
//        /** @var Salary $salary */
//        $salary = Salary::find(1);
//        if ($salary->employee_id !== 9) {
//            $salary->employee_id = 9;
//            $salary->save();
//        }
//        $employee  = Employee::create(['lastname'  => 'dummy']);
//        $salary->employee()->associate($employee);
//        if ($salary->employee_id !== $employee->id) {
//            dd("Test o failed!");
//        }
//        $salary->employee()->disassociate($employee);
//        if ($salary->employee_id !== null) {
//            dd("Test p failed!");
//        }
//        $salary->employee()->associate($employee);
//        $salary->employee()->disassociate();
//        if ($salary->employee_id !== null) {
//            dd("Test s failed!");
//        }
//        $salary->employee_id = 9;
//        $salary->save();
//        $employee->delete();
//
//
//        /** @var Picture $picture */
//        $picture = Picture::find(3);
//        // if ($picture->imageable_type !== 'App\Models\Employee' || $picture->imageable_id !== 10) {
//        $picture->imageable_type = null;//'App\Models\Employee';
//        $picture->imageable_id = 10;
//        $picture->save();
//        //}
//        $employee  = Employee::create(['lastname'  => 'dummy']);
//        $picture->imageable()->associate($employee);
//        if ($picture->imageable_id !== $employee->id || $picture->imageable_type !== 'App\Models\Employee') {
//            dd("Test u failed!");
//        }
//        $picture->imageable()->disassociate($employee);
//        if ($picture->imageable_type !== null || $picture->imageable_id !== null) {
//            dd("Test v failed!");
//        }
//        $picture->imageable()->associate($employee);
//        $picture->imageable()->disassociate();
//        if ($picture->imageable_type !== null || $picture->imageable_id !== null) {
//            dd("Test x failed!");
//        }
//        $employee->delete();
//        $picture->imageable_type = 'App\Models\Employee';
//        $picture->imageable_id = 10;
//        $picture->save();
//
//        $department  = Department::create(['name'  => 'dummy']);
//        $picture->imageable()->associate($department);
//        if ($picture->imageable_id !== $department->id || $picture->imageable_type !== 'App\Models\Department') {
//            dd("Test q failed!");
//        }
//        $picture->imageable()->disassociate($department);
//        $department->delete();
//        $picture->imageable_type = 'App\Models\Employee';
//        $picture->imageable_id = 10;
//        $picture->save();
//    }

//    public function testInsertRelation()
//    {
//        echo "<p><b>Insert a Record through Relation</b></p>";
//
//        /** @var Employee $employee */
//        $employee = Employee::find(1);
//        $i = count($employee->departments);
//
//        /** @var Department $department */
//        $department = $employee->departments()->create(['name' => 'dummy']);
//        if (!($department instanceof Department)) {
//            dd("Test a1 failed!");
//        }
//        if ($department->name != 'dummy') {
//            dd("Test a2 failed!");
//        }
//        if (count($department->employees) !== 1) {
//            dd("Test a3 failed!");
//        }
//        if (count($employee->departments) !== $i + 1) {
//            dd("Test b failed!");
//        }
//        $rows = $employee->database()->table('department_employee')->where('employee_id = ? AND department_id = ?', [$employee->id, $department->id])->all();
//        if (count($rows) !== 1) {
//            dd("Test c failed!");
//        }
//        if (Department::count() !== 10) {
//            dd("Test c2 failed!");
//        }
//        $departmentId = $department->id;
//        $employee->departments()->delete($department);
//        if ($department->id !== null) {
//            dd("Test d failed!");
//        }
//        if (count($employee->departments) !== $i) {
//            dd("Test f failed!");
//        }
//        if (Department::count() !== 9) {
//            dd("Test g failed!");
//        }
//        $rows = $employee->database()->table('department_employee')->where('employee_id = ? AND department_id = ?', [$employee->id, $departmentId])->all();
//        if (!empty($rows)) {
//            dd("Test h failed!");
//        }
//
//        $n = Salary::count();
//        /** @var Salary $salary */
//        $salary = $employee->salaries()->create(['salary'  => 4711]);
//        if ($salary->employee_id !== $employee->id) {
//            dd("Test i failed!");
//        }
//        if (Salary::count() !== $n + 1) {
//            dd("Test i2 failed!");
//        }
//        $employee->salaries()->delete($salary);
//        if ($salary->employee_id !== null) {
//            dd("Test j failed!");
//        }
//        if ($salary->id !== null) {
//            dd("Test k failed!");
//        }
//        if (Salary::count() !== $n) {
//            dd("Test i2 failed!");
//        }
//
//        /** @var Picture $picture */
//        $n = Picture::count();
//        $picture = $employee->pictures()->create(['name'  => 'dummy']);
//        if ($picture->imageable_id !== $employee->id || $picture->imageable_type != 'App\Models\Employee') {
//            dd("Test l failed!");
//        }
//        if (Picture::count() !== $n + 1) {
//            dd("Test l2 failed!");
//        }
//        $employee->pictures()->delete($picture);
//        if (Picture::count() !== $n) {
//            dd("Test l3 failed!");
//        }
//        if ($picture->imageable_type !== null || $picture->imageable_id !== null) {
//            dd("Test m failed!");
//        }
//        if ($picture->id !== null) {
//            dd("Test n failed!");
//        }
//
//        /** @var Salary $salary */
//        $n = Employee::count();
//        $salary = Salary::find(1);
//        if ($salary->employee_id !== 9) {
//            $salary->employee_id = 9;
//            $salary->save();
//        }
//        $employee  = $salary->employee()->create(['lastname'  => 'dummy']);
//        if (Employee::count() !== $n + 1) {
//            dd("Test n2 failed!");
//        }
//        if ($salary->employee_id !== $employee->id) {
//            dd("Test o failed!");
//        }
//        $salary->employee()->delete($employee);
//        if (Employee::count() !== $n) {
//            dd("Test o2 failed!");
//        }
//        if ($salary->employee_id !== null) {
//            dd("Test p failed!");
//        }
//        if ($employee->id !== null) {
//            dd("Test p2 failed!");
//        }
//        $salary->employee_id = 9;
//        $salary->save();
//
//
//        /** @var Picture $picture */
//        $picture = Picture::find(3);
//        if ($picture->imageable_type !== 'App\Models\Employee' || $picture->imageable_id !== 10) {
//            $picture->imageable_type = 'App\Models\Employee';
//            $picture->imageable_id = 10;
//            $picture->save();
//        }
//        $employee = $picture->imageable()->create(['lastname'  => 'dummy']);
//        if (!($employee instanceof Employee)) {
//            dd("Test p3 failed!");
//        }
//        if (Employee::count() !== $n + 1) {
//            dd("Test p4 failed!");
//        }
//        if ($picture->imageable_id !== $employee->id || $picture->imageable_type !== 'App\Models\Employee') {
//            dd("Test q failed!");
//        }
//        $picture->imageable()->delete($employee);
//        if (Employee::count() !== $n) {
//            dd("Test u2 failed!");
//        }
//        if ($picture->imageable_id !== null) {
//            dd("Test x failed!");
//        }
//        if ($employee->id !== null) {
//            dd("Test x2 failed!");
//        }
//        $picture->imageable_type = 'App\Models\Employee';
//        $picture->imageable_id = 10;
//        $picture->save();
//
//        $picture = Picture::find(4);
//        if ($picture->imageable_type !== 'App\Models\Department' || $picture->imageable_id !== 5) {
//            $picture->imageable_type = 'App\Models\Department';
//            $picture->imageable_id = 5;
//            $picture->save();
//        }
//        $department = $picture->imageable()->create(['name'  => 'dummy']);
//        if (!($department instanceof Department)) {
//            dd("Test q1 failed!");
//        }
//        if ($picture->imageable_id !== $department->id || $picture->imageable_type !== 'App\Models\Department') {
//            dd("Test q failed!");
//        }
//        $picture->imageable()->delete($department);
//        $picture->imageable_type = 'App\Models\Department';
//        $picture->imageable_id = 5;
//        $picture->save();
//    }

//    public function testFind()
//    {
//        echo "<p><b>Execute Query</b></p>";
//
//        /** @var Employee $employee */
//        $employee = Employee::find(1);
//        if (!($employee instanceof Employee)) {
//            dd("Test a failed!");
//        }
//        if ($employee->id !== 1) {
//            dd("Test b failed!");
//        }
//    }

//    public function testAggregate()
//    {
//        echo "<p><b>Aggregate Functions</b></p>";
//
//        $count = Salary::count();
//        if ($count != 2844047) {
//            dd("Test a failed!");
//        }
//
//        $count = Salary::select('salary')->count();
//        if ($count != 2844047) {
//            dd("Test b failed!");
//        }
//
//        $count = Salary::select('salary')->distinct()->count();
//        if ($count != 85814) {
//            dd("Test c failed!");
//        }
//
//        $max = Salary::max('salary');
//        if ($max != 158220) {
//            dd("Test d failed!");
//        }
//
//        $max = Salary::select('salary')->max();
//        if ($max != 158220) {
//            dd("Test e failed!");
//        }
//
//
//        $min = Salary::min('salary');
//        if ($min != 38623) {
//            dd("Test f failed!");
//        }
//
//        $min = Salary::select('salary')->min();
//        if ($min != 38623) {
//            dd("Test g failed!");
//        }
//
//
//        $avg = Salary::avg('salary');
//        if ($avg != 63810.7448) {
//            dd("Test h failed!");
//        }
//
//        $avg = Salary::select('salary')->avg();
//        if ($avg != 63810.7448) {
//            dd("Test i failed!");
//        }
//
//
//        $sum = Salary::sum('salary');
//        if ($sum != 181480757419) {
//            dd("Test j failed!");
//        }
//
//        $sum = Salary::select('salary')->sum();
//        if ($sum != 181480757419) {
//            dd("Test k failed!");
//        }
//
//    }
}

///////////////////////////////////////////////////////////////////////
// Relation Test Books

/**
 * @property integer $id
 * @property string $name
 */
class Author extends Model // Department
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

/**
 * @property integer $id
 * @property integer $author_id
 * @property string $title
 */
class Book extends Model // Employee
{
    public function author()
    {
        return $this->belongsTo(Author::class);
    }
}

///////////////////////////////////////////////////////////////////////
// Relation Test Employees

/**
 * @property integer $id
 * @property string $name
 * @property-read Profile $profile
 * @property-read Department[] $departments
 * @property-read Salary[] $salaries
 * @property-read Picture[] $pictures
 */
class Employee extends Model
{
    public function profile()
    {
        return $this->hasOne(Profile::class);
    }

    public function departments()
    {
        return $this->belongsToMany(Department::class);
    }

    public function salaries()
    {
        return $this->hasMany(Salary::class);
    }

    public function pictures()
    {
        return $this->morphMany(Picture::class, 'imageable');
    }

    public function getFirstnameAttribute()
    {
        return ucfirst($this->attributes['firstname']);
    }

    public function setFirstnameAttribute($value)
    {
        $this->attributes['firstname'] = strtolower($value);
    }
}

/**
 * @property integer $id
 * @property string $name
 * @property-read Employee[] $employees
 * @property-read Picture $picture
 */
class Department extends Model
{
    public function employees()
    {
        return $this->belongsToMany(Employee::class);
    }

    public function picture()
    {
        return $this->morphOne(Picture::class, 'imageable');
    }
}

/**
 * @property integer $id
 * @property string $imageable_type
 * @property int $imageable_id
 * @property string $name
 * @property-read Model $imageable
 */
class Picture extends Model
{
    public function imageable()
    {
        return $this->morphTo('imageable');
    }
}

/**
 * @property integer $id
 * @property string $name
 * @property-read Employee $employee
 */
class Profile extends Model
{
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}

/**
 * @property integer $id
 * @property int $employee_id
 * @property string $name
 * @property-read Employee $employee
 */
class Salary extends Model
{
    public function employee()
    {
        return $this->belongsTo(Employee::class);
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
        else if ($dirty['str'] === 'reset to b') {
            $this->attributes['str'] = 'b';
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
