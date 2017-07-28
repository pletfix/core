<?php

namespace Core\Tests\Models;

use Core\Models\HasManyRelation;
use Core\Models\Relation;

require_once 'RelationTestCase.php';

class HasManyRelationTest extends RelationTestCase
{
    // employee <- salaries
    public function testHasManyRelation()
    {
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

        $relations = $this->getPrivateProperty($employee, 'relations');
        $this->assertArrayHasKey('salaries', $relations);
        $this->assertCount(2, $relations['salaries']);
        $this->assertInstanceOf(Salary::class, $relations['salaries'][0]);
        $this->assertSame('Salary Berta 1', $relations['salaries'][0]->name);

        $this->assertSame('Berta', $employee->name);
        $this->assertCount(2, $employee->salaries);
        $this->assertSame('Salary Berta 1', $employee->salaries[0]->name);
        $this->assertSame('Salary Berta 2', $employee->salaries[1]->name);
    }

    public function testAssociateAndDisassociate()
    {
        /** @var Employee $employee */
        $employee = Employee::find(1);

        // create a salary
        $salary = Salary::create(['name' => 'dummy']);

        // associate the salary with the employee
        $employee->salaries()->associate($salary);
        $this->assertSame($employee->id, $salary->employee_id);

        // remove the relation
        $employee->salaries()->disassociate($salary);
        $this->assertNull($salary->employee_id);

        // disassociate a non-associated relationship
        $this->assertTrue($employee->salaries()->disassociate($salary));

        // remove all relations between salaries and the employee
        $this->assertCount(2, $employee->salaries);
        $employee->salaries()->disassociate();
        $this->assertEmpty($employee->salaries);
    }

    public function testCreateAndDelete()
    {
        /** @var Employee $employee */
        $employee = Employee::find(1);
        $this->assertCount(2, $employee->salaries);
        $this->assertSame(4, Salary::count());

        /** @var Salary $salary */
        $salary = $employee->salaries()->create(['name' => 'dummy']);
        $this->assertInstanceOf(Salary::class, $salary);
        $this->assertSame($employee->id, $salary->employee_id);
        $this->assertCount(3, $employee->salaries);
        $this->assertSame(5, Salary::count());

        $employee->salaries()->delete($salary);
        $this->assertNull($salary->id);
        $this->assertCount(2, $employee->salaries);
        $this->assertSame(4, Salary::count());

        // cancel operation by hook
        $this->assertFalse($employee->salaries()->create(['name' => 'cancel']));
        $salary = Salary::find(1);
        $salary->name = 'cancel';
        $this->assertFalse($employee->salaries()->delete($salary));
    }
}