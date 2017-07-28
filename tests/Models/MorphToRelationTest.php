<?php

namespace Core\Tests\Models;

use Core\Models\MorphToRelation;
use Core\Models\Relation;
use LogicException;

require_once 'RelationTestCase.php';

class MorphToRelationTest extends RelationTestCase
{
    // picture -> employees
    public function testMorphToEmployees()
    {
        /** @var Picture $picture */
        $picture = Picture::find(1);
        $this->assertSame('Picture Anton 1', $picture->name);
        $this->assertInstanceOf(MorphToRelation::class, $picture->imageable());

        $builder = $picture->imageable()->builder();
        /** @noinspection SqlDialectInspection */
        $this->assertSame('SELECT * FROM "employees" WHERE "id" = ?', $builder->toSql());
        $this->assertSame(['1'], $builder->bindings());

        $this->assertSame(1, $picture->imageable()->count());

        $this->assertInstanceOf(Employee::class, $picture->imageable);
        /** @noinspection PhpUndefinedFieldInspection */
        $this->assertSame('Anton', $picture->imageable->name);

        // Eager Loading

        Relation::noConstraints(function () {
            /** @var Picture[] $pictures */
            $pictures = Picture::whereIs('imageable_type', 'Core\Tests\Models\Employee')->all();
            $builder = $pictures[0]->imageable()->addEagerConstraints($pictures);
            /** @noinspection SqlDialectInspection */
            $this->assertSame('SELECT * FROM "employees" WHERE "id" IN (?, ?, ?)', $builder->toSql());
            $this->assertSame(['1', '1', '2'], $builder->bindings());
        });

        /** @var Picture[] $pictures */
        $pictures = Picture::with('imageable')->whereIs('imageable_type', 'Core\Tests\Models\Employee')->all();
        $this->assertCount(3, $pictures);
        $this->assertInstanceOf(Picture::class, $pictures[0]);

        $relations = $this->getPrivateProperty($pictures[0], 'relations');
        $this->assertArrayHasKey('imageable', $relations);
        $this->assertInstanceOf(Employee::class, $relations['imageable']);
        $this->assertSame('Anton', $relations['imageable']->name);

        $relations = $this->getPrivateProperty($pictures[2], 'relations');
        $this->assertArrayHasKey('imageable', $relations);
        $this->assertInstanceOf(Employee::class, $relations['imageable']);
        $this->assertSame('Berta', $relations['imageable']->name);

        $this->assertSame('Picture Anton 1', $pictures[0]->name);
        $this->assertSame('Picture Berta 1', $pictures[2]->name);
        /** @noinspection PhpUndefinedFieldInspection */
        $this->assertSame('Anton', $pictures[0]->imageable->name);
        /** @noinspection PhpUndefinedFieldInspection */
        $this->assertSame('Berta', $pictures[2]->imageable->name);
    }

    // picture -> department
    public function testMorphToDepartment()
    {
        /** @var Picture $picture */
        $picture = Picture::find(4);
        $this->assertSame('Picture Development', $picture->name);
        $this->assertInstanceOf(MorphToRelation::class, $picture->imageable());

        $builder = $picture->imageable()->builder();
        /** @noinspection SqlDialectInspection */
        $this->assertSame('SELECT * FROM "departments" WHERE "id" = ?', $builder->toSql());
        $this->assertSame(['1'], $builder->bindings());

        $this->assertSame(1, $picture->imageable()->count());

        $this->assertInstanceOf(Department::class, $picture->imageable);
        /** @noinspection PhpUndefinedFieldInspection */
        $this->assertSame('Development', $picture->imageable->name);

        // Eager Loading

        Relation::noConstraints(function () {
            /** @var Picture[] $pictures */
            $pictures = Picture::whereIs('imageable_type', 'Core\Tests\Models\Department')->all();
            $builder = $pictures[0]->imageable()->addEagerConstraints($pictures);
            /** @noinspection SqlDialectInspection */
            $this->assertSame('SELECT * FROM "departments" WHERE "id" IN (?, ?)', $builder->toSql());
            $this->assertSame(['1', '2'], $builder->bindings());
        });

        /** @var Picture[] $pictures */
        $pictures = Picture::with('imageable')->whereIs('imageable_type', 'Core\Tests\Models\Department')->all();
        $this->assertCount(2, $pictures);
        $this->assertInstanceOf(Picture::class, $pictures[0]);

        $relations = $this->getPrivateProperty($pictures[0], 'relations');
        $this->assertArrayHasKey('imageable', $relations);
        $this->assertInstanceOf(Department::class, $relations['imageable']);
        $this->assertSame('Development', $relations['imageable']->name);

        $relations = $this->getPrivateProperty($pictures[1], 'relations');
        $this->assertArrayHasKey('imageable', $relations);
        $this->assertInstanceOf(Department::class, $relations['imageable']);
        $this->assertSame('Marketing', $relations['imageable']->name);

        $this->assertSame('Picture Development', $pictures[0]->name);
        $this->assertSame('Picture Marketing', $pictures[1]->name);
        /** @noinspection PhpUndefinedFieldInspection */
        $this->assertSame('Development', $pictures[0]->imageable->name);
        /** @noinspection PhpUndefinedFieldInspection */
        $this->assertSame('Marketing', $pictures[1]->imageable->name);
    }

    // picture -> department + employee
    public function testMorphToEagerLoadSeveralTypes()
    {
        $this->expectException(LogicException::class);
        Picture::with('imageable')->all(); // Cannot load several types eagerly.
    }

    public function testAssociateAndDisassociateEmployee()
    {
        /** @var Picture $picture */
        $picture = Picture::find(1);

        // create a employee
        $employee  = Employee::create(['name'  => 'dummy']);

        // associate the employee with the picture
        $picture->imageable()->associate($employee);
        $this->assertCount(1, $employee->pictures);
        $this->assertSame($employee->id, $picture->imageable_id);
        $this->assertSame('Core\Tests\Models\Employee', $picture->imageable_type);

        // remove the relation about the picture
        $picture->imageable()->disassociate($employee);
        $this->assertEmpty($employee->pictures);
        $this->assertNull($picture->imageable_id);
        $this->assertNull($picture->imageable_type);

        // remove the relation about the picture without specified employee
        $picture->imageable()->associate($employee);
        $picture->imageable()->disassociate();
        $this->assertNull($picture->imageable_id);
        $this->assertNull($picture->imageable_type);
    }

    public function testAssociateAndDisassociateDepartment()
    {
        /** @var Picture $picture */
        $picture = Picture::find(1);

        // create a department
        $department  = Department::create(['name'  => 'dummy']);

        // associate the department with the picture
        $picture->imageable()->associate($department);
        $this->assertSame($department->id, $picture->imageable_id);
        $this->assertSame('Core\Tests\Models\Department', $picture->imageable_type);

        // remove the relation about the picture
        $picture->imageable()->disassociate($department);
        $this->assertNull($picture->imageable_id);
        $this->assertNull($picture->imageable_type);

        // disassociate a non-associated relationship
        $this->assertTrue($picture->imageable()->disassociate($department));
    }

    public function testCreateAndDeleteEmployee()
    {
        /** @var Picture $picture */
        $picture = Picture::find(1);
        $this->assertSame('Core\Tests\Models\Employee', $picture->imageable_type);
        $this->assertSame(3, Employee::count());
        $picture->imageable_id = null;

        /** @var Employee $employee */
        $employee = $picture->imageable()->create(['name'  => 'dummy']);
        $this->assertInstanceOf(Employee::class, $employee);
        $this->assertSame('Core\Tests\Models\Employee', $picture->imageable_type);
        $this->assertSame($employee->id, $picture->imageable_id);
        $this->assertSame(4, Employee::count());

        $picture->imageable()->delete($employee);
        $this->assertNull($employee->id);
        $this->assertNull($picture->imageable_id);
        $this->assertNull($picture->imageable_type);
        $this->assertSame(3, Employee::count());

        // cancel operation by hook
        $this->assertFalse($picture->imageable()->create(['name' => 'cancel']));
        $this->assertFalse($picture->setAttribute('name', 'cancel')->imageable()->create(['name' => 'dummy']));
        $picture->setAttribute('name', 'dummy');
        $employee = Employee::find(1);
        $employee->name = 'cancel';
        $this->assertFalse($picture->imageable()->delete($employee));

        $relation = new MorphToRelation($picture, database()->builder(), 'imageable_type', 'imageable_id', 'id'); // note, the class attribute of the builder is not set!
        $this->expectException(LogicException::class);
        $relation->create(['name' => 'dummy']);
    }

    public function testCraeteAndDeleteDepartment()
    {
        /** @var Picture $picture */
        $picture = Picture::find(4);
        $this->assertSame('Core\Tests\Models\Department', $picture->imageable_type);
        $this->assertSame(4, Department::count());
        $picture->imageable_id = null;

        /** @var Department $department */
        $department = $picture->imageable()->create(['name'  => 'dummy']);
        $this->assertInstanceOf(Department::class, $department);
        $this->assertSame('Core\Tests\Models\Department', $picture->imageable_type);
        $this->assertSame($department->id, $picture->imageable_id);
        $this->assertSame(5, Department::count());

        $picture->imageable()->delete($department);
        $this->assertNull($department->id);
        $this->assertNull($picture->imageable_id);
        $this->assertNull($picture->imageable_type);
        $this->assertSame(4, Department::count());

        // cancel operation by hook
        $this->assertFalse($picture->imageable()->create(['name' => 'cancel']));
        $department = Department::find(1);
        $department->name = 'cancel';
        $this->assertFalse($picture->imageable()->delete($department));
    }
}