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
use Core\Services\AbstractDatabase;
use Core\Services\Contracts\Database;
use Core\Services\Contracts\DatabaseFactory;
use Core\Services\DI;
use Core\Services\PDOs\Builders\AbstractBuilder;
use Core\Services\PDOs\Builders\Contracts\Builder;
use Core\Testing\TestCase;
use LogicException;

class ModelTest extends TestCase
{
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
        $db = database();
        $this->execSqlFile($db, 'create_author_books_relation');
        $this->execSqlFile($db, 'create_table_for_hooktest');
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
        $model = new Author;
        $relation = $model->hasOne(Book::class);
        $this->assertInstanceOf(HasOneRelation::class, $relation);

        $self = $this->getPrivateProperty($relation, 'model');
        $this->assertSame($model, $self);

        $builder = $this->getPrivateProperty($relation, 'builder');
        $this->assertSame(Book::class, $builder->getClass());

        $foreignKey = $this->getPrivateProperty($relation, 'foreignKey');
        $this->assertSame('author_id', $foreignKey);

        $localKey = $this->getPrivateProperty($relation, 'localKey');
        $this->assertSame('id', $localKey);
    }

    // employee <- salaries
    public function testHasManyRelation()
    {
        //        $model = $this->getMockBuilder(Author::class)
//            ->setMethods(['book'])->getMock();
//        $model->expects($this->once())->method('book');

        $model = new Author;
        $relation = $model->hasMany(Book::class);
        $this->assertInstanceOf(HasManyRelation::class, $relation);

        $self = $this->getPrivateProperty($relation, 'model');
        $this->assertSame($model, $self);

        $builder = $this->getPrivateProperty($relation, 'builder');
        $this->assertSame(Book::class, $builder->getClass());

        $foreignKey = $this->getPrivateProperty($relation, 'foreignKey');
        $this->assertSame('author_id', $foreignKey);

        $localKey = $this->getPrivateProperty($relation, 'localKey');
        $this->assertSame('id', $localKey);
    }

    // salary -> employee
    public function testBelongsToRelation()
    {
        $model = new Author;
        $relation = $model->belongsTo(Book::class);
        $this->assertInstanceOf(BelongsToRelation::class, $relation);

        $self = $this->getPrivateProperty($relation, 'model');
        $this->assertSame($model, $self);

        $builder = $this->getPrivateProperty($relation, 'builder');
        $this->assertSame(Book::class, $builder->getClass());

        $foreignKey = $this->getPrivateProperty($relation, 'foreignKey');
        $this->assertSame('book_id', $foreignKey);

        $otherKey = $this->getPrivateProperty($relation, 'otherKey');
        $this->assertSame('id', $otherKey);
    }

    // department <-> employees
    public function testBelongsToManyRelation()
    {
        $model = new Author;
        $relation = $model->belongsToMany(Book::class);
        $this->assertInstanceOf(BelongsToManyRelation::class, $relation);

        $self = $this->getPrivateProperty($relation, 'model');
        $this->assertSame($model, $self);

        $builder = $this->getPrivateProperty($relation, 'builder');
        $this->assertSame(Book::class, $builder->getClass());

        $joinTable = $this->getPrivateProperty($relation, 'joinTable');
        $this->assertSame('author_book', $joinTable);

        $localForeignKey = $this->getPrivateProperty($relation, 'localForeignKey');
        $this->assertSame('author_id', $localForeignKey);

        $otherForeignKey = $this->getPrivateProperty($relation, 'otherForeignKey');
        $this->assertSame('book_id', $otherForeignKey);

        $localKey = $this->getPrivateProperty($relation, 'localKey');
        $this->assertSame('id', $localKey);

        $otherKey = $this->getPrivateProperty($relation, 'otherKey');
        $this->assertSame('id', $otherKey);
    }

    // department <- picture
    public function testMorphOneRelation()
    {
        $model = new Author;
        $relation = $model->morphOne(Book::class, 'imageable');
        $this->assertInstanceOf(MorphOneRelation::class, $relation);

        $self = $this->getPrivateProperty($relation, 'model');
        $this->assertSame($model, $self);

        $builder = $this->getPrivateProperty($relation, 'builder');
        $this->assertSame(Book::class, $builder->getClass());

        $typeAttribute = $this->getPrivateProperty($relation, 'typeAttribute');
        $this->assertSame('imageable_type', $typeAttribute);

        $foreignKey = $this->getPrivateProperty($relation, 'foreignKey');
        $this->assertSame('imageable_id', $foreignKey);
    }

    // employees <- pictures
    public function testMorphManyRelation()
    {
        $model = new Author;
        $relation = $model->morphMany(Book::class, 'imageable');
        $this->assertInstanceOf(MorphManyRelation::class, $relation);

        $self = $this->getPrivateProperty($relation, 'model');
        $this->assertSame($model, $self);

        $builder = $this->getPrivateProperty($relation, 'builder');
        $this->assertSame(Book::class, $builder->getClass());

        $typeAttribute = $this->getPrivateProperty($relation, 'typeAttribute');
        $this->assertSame('imageable_type', $typeAttribute);

        $foreignKey = $this->getPrivateProperty($relation, 'foreignKey');
        $this->assertSame('imageable_id', $foreignKey);
    }

    // picture -> employees
    public function testMorphToRelation()
    {
        $model = new Author;
        $model->setAttribute('imageable_type', Book::class);

        $relation = $model->morphTo('imageable');
        $this->assertInstanceOf(MorphToRelation::class, $relation);

        $self = $this->getPrivateProperty($relation, 'model');
        $this->assertSame($model, $self);

        $builder = $this->getPrivateProperty($relation, 'builder');
        $this->assertSame(Book::class, $builder->getClass());

        $foreignKey = $this->getPrivateProperty($relation, 'foreignKey');
        $this->assertSame('imageable_id', $foreignKey);

        $otherKey = $this->getPrivateProperty($relation, 'otherKey');
        $this->assertSame('id', $otherKey);

        $typeAttribute = $this->getPrivateProperty($relation, 'typeAttribute');
        $this->assertSame('imageable_type', $typeAttribute);

        $relation = $model->morphTo('runable');  // attribute runable_type not exist -> build relation with itself
        $this->assertInstanceOf(MorphToRelation::class, $relation);
        $builder = $this->getPrivateProperty($relation, 'builder');
        $this->assertSame(Author::class, $builder->getClass());
    }

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

    public function testToString()
    {
        $model = new Author;
        $attributes = ['abc' => 'def', 'uvw' => 'xyz'];
        $model->setAttributes($attributes);
        $this->assertSame(json_encode($attributes), (string)$model);
    }

    public function testToArray()
    {
        $model = new Author;
        $attributes = ['abc' => 'def', 'uvw' => 'xyz'];
        $model->setAttributes($attributes);
        $this->assertSame($attributes, $model->toArray());
    }

    public function testToJson()
    {
        $model = new Author;
        $attributes = ['abc' => 'def', 'uvw' => 'xyz'];
        $model->setAttributes($attributes);
        $this->assertSame(json_encode($attributes), $model->toJson());
    }
}

///////////////////////////////////////////////////////////////////////
// Relation Test Books

/**
 * @property integer $id
 * @property string $name
 */
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

/**
 * @property integer $id
 * @property integer $author_id
 * @property string $title
 */
class Book extends Model // Salary
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
