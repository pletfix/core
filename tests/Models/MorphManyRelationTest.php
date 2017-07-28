<?php

namespace Core\Tests\Models;

use Core\Models\MorphManyRelation;
use Core\Models\Relation;

require_once 'RelationTestCase.php';

class MorphManyRelationTest extends RelationTestCase
{
    // employees <- pictures
    public function testMorphManyRelation()
    {
        /** @var Employee $employee */
        $employee = Employee::find(2);
        $this->assertSame('Berta', $employee->name);
        $this->assertInstanceOf(MorphManyRelation::class, $employee->pictures());

        $builder = $employee->pictures()->builder();
        /** @noinspection SqlDialectInspection */
        $this->assertSame('SELECT * FROM "pictures" WHERE "imageable_type" = ? AND "imageable_id" = ?', $builder->toSql());
        $this->assertSame(['Core\Tests\Models\Employee', '2'], $builder->bindings());

        $this->assertSame(1, $employee->pictures()->count());

        $this->assertCount(1, $employee->pictures);
        $this->assertInstanceOf(Picture::class, $employee->pictures[0]);
        $this->assertSame('Picture Berta 1', $employee->pictures[0]->name);

        // employees -> pictures (Eager Loading)

        Relation::noConstraints(function () {
            /** @var Employee[] $employees */
            $employees = Employee::all();
            $builder = $employees[0]->pictures()->addEagerConstraints($employees);
            /** @noinspection SqlDialectInspection */
            $this->assertSame('SELECT * FROM "pictures" WHERE "imageable_type" = ? AND "imageable_id" IN (?, ?, ?)', $builder->toSql());
            $this->assertSame(['Core\Tests\Models\Employee', '1', '2', '3'], $builder->bindings());
        });

        $employee = Employee::with('pictures')->find(1);
        $this->assertSame('Anton', $employee->name);

        $relations = $this->getPrivateProperty($employee, 'relations');
        $this->assertArrayHasKey('pictures', $relations);
        $this->assertCount(2, $relations['pictures']);
        $this->assertInstanceOf(Picture::class, $relations['pictures'][0]);
        $this->assertSame('Picture Anton 1', $relations['pictures'][0]->name);

        $this->assertCount(2, $employee->pictures);
        $this->assertSame('Picture Anton 1', $employee->pictures[0]->name);
        $this->assertSame('Picture Anton 2', $employee->pictures[1]->name);
    }

    public function testAssociateAndDisassociate()
    {
        /** @var Employee $employee */
        $employee = Employee::find(1);

        // create a picture
        $picture  = Picture::create(['name'  => 'dummy']);

        // associate the picture with the employee
        $employee->pictures()->associate($picture);
        $this->assertSame($employee->id, $picture->imageable_id);
        $this->assertSame('Core\Tests\Models\Employee', $picture->imageable_type);

        // remove the relation about the employee
        $employee->pictures()->disassociate($picture);
        $this->assertNull($picture->imageable_id);
        $this->assertNull($picture->imageable_type);

        // disassociate a non-associated relationship
        $this->assertTrue($employee->pictures()->disassociate($picture));

        // remove all relations between pictures and the employee
        $this->assertCount(2, $employee->pictures);
        $employee->pictures()->disassociate();
        $this->assertEmpty($employee->pictures);
    }

    public function testCreateAndDelete()
    {
        /** @var Employee $employee */
        $employee = Employee::find(1);
        $this->assertCount(2, $employee->pictures);
        $this->assertSame(5, Picture::count());

        /** @var Picture $picture */
        $picture = $employee->pictures()->create(['name' => 'dummy']);
        $this->assertInstanceOf(Picture::class, $picture);
        $this->assertSame($employee->id, $picture->imageable_id);
        $this->assertSame('Core\Tests\Models\Employee', $picture->imageable_type);
        $this->assertSame(6, Picture::count());

        $employee->pictures()->delete($picture);
        $this->assertNull($picture->imageable_id);
        $this->assertNull($picture->imageable_type);
        $this->assertSame(5, Picture::count());

        // cancel operation by hook
        $this->assertFalse($employee->pictures()->create(['name' => 'cancel']));
        $picture = Picture::find(1);
        $picture->name = 'cancel';
        $this->assertFalse($employee->pictures()->delete($picture));
    }
}