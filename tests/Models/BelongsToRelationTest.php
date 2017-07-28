<?php

namespace Core\Tests\Models;

use Core\Models\BelongsToRelation;
use Core\Models\Relation;

require_once 'RelationTestCase.php';

class BelongsToRelationTest extends RelationTestCase
{
    // salary -> employee
    public function testBelongsToRelation()
    {
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

        /** @var Salary[] $salaries */
        $salaries = Salary::with('employee')->all();
        $this->assertCount(4, $salaries);
        $this->assertInstanceOf(Salary::class, $salaries[0]);

        $relations = $this->getPrivateProperty($salaries[0], 'relations');
        $this->assertArrayHasKey('employee', $relations);
        $this->assertInstanceOf(Employee::class, $relations['employee']);
        $this->assertSame('Anton', $relations['employee']->name);

        $relations = $this->getPrivateProperty($salaries[3], 'relations');
        $this->assertArrayHasKey('employee', $relations);
        $this->assertInstanceOf(Employee::class, $relations['employee']);
        $this->assertSame('Berta', $relations['employee']->name);

        $this->assertSame('Salary Anton 1', $salaries[0]->name);
        $this->assertSame('Salary Berta 2', $salaries[3]->name);
        $this->assertSame('Anton', $salaries[0]->employee->name);
        $this->assertSame('Berta', $salaries[3]->employee->name);
    }

    public function testAssociateAndDisassociate()
    {
        /** @var Salary $salary */
        $salary = Salary::find(1);

        // create a employee
        $employee = Employee::create(['name' => 'dummy']);

        // associate the employee with the salary
        $salary->employee()->associate($employee);
        $this->assertSame($employee->id, $salary->employee_id);

        // remove the relation about the employee
        $salary->employee()->disassociate($employee);
        $this->assertNull($salary->employee_id);

        // remove the relation about the salary
        $salary->employee()->associate($employee);
        $this->assertSame($employee->id, $salary->employee_id);
        $salary->employee()->disassociate();
        $this->assertNull($salary->employee_id);

        // disassociate a non-associated relationship
        $this->assertTrue($salary->employee()->disassociate($employee));
    }

    public function testCreateAndDelete()
    {
        /** @var Salary $salary */
        $salary = Salary::find(1);
        $this->assertSame(3, Employee::count());

        /** @var Employee $employee */
        $employee  = $salary->employee()->create(['name'  => 'dummy']);
        $this->assertInstanceOf(Employee::class, $employee);
        $this->assertSame($salary->employee_id, $employee->id);
        $this->assertSame(4, Employee::count());

        $salary->employee()->delete($employee);
        $this->assertSame(3, Employee::count());
        $this->assertNull($salary->employee_id);
        $this->assertNull($employee->id);

        // cancel operation by hook
        $this->assertFalse($salary->employee()->create(['name' => 'cancel']));
        $this->assertFalse($salary->setAttribute('name', 'cancel')->employee()->create(['name' => 'dummy']));
        $salary->setAttribute('name', 'dummy');
        $employee = Employee::find(1);
        $employee->name = 'cancel';
        $this->assertFalse($salary->employee()->delete($employee));
    }
}