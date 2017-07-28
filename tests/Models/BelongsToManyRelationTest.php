<?php

namespace Core\Tests\Models;

use Core\Models\BelongsToManyRelation;
use Core\Models\Relation;

require_once 'RelationTestCase.php';

class BelongsToManyRelationTest extends RelationTestCase
{
    // employee <-> departments
    public function testBelongsToManyDepartment()
    {
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

        $relations = $this->getPrivateProperty($employee, 'relations');
        $this->assertArrayHasKey('departments', $relations);
        $this->assertCount(2, $relations['departments']);
        $this->assertInstanceOf(Department::class, $relations['departments'][0]);
        $this->assertSame('Marketing', $relations['departments'][0]->name);
        $this->assertSame('HR', $relations['departments'][1]->name);

        $this->assertSame('Berta', $employee->name);
        $this->assertCount(2, $employee->departments);
        $this->assertSame('Marketing', $employee->departments[0]->name);
        $this->assertSame('HR', $employee->departments[1]->name);
    }

    // department <-> employees
    public function testBelongsToManyEmployees()
    {
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

        $relations = $this->getPrivateProperty($department, 'relations');
        $this->assertArrayHasKey('employees', $relations);
        $this->assertCount(1, $relations['employees']);
        $this->assertInstanceOf(Employee::class, $relations['employees'][0]);
        $this->assertSame('Berta', $relations['employees'][0]->name);

        $this->assertSame('HR', $department->name);
        $this->assertCount(1, $department->employees);
        $this->assertSame('Berta', $department->employees[0]->name);
    }

    public function testAssociateAndDisassociate()
    {
        /** @var Employee $employee */
        $employee = Employee::find(1);

        // create a department
        $department = Department::create(['name' => 'dummy']);
        $this->assertSame(5, Department::count());

        // associate the department with the employee
        $employee->departments()->associate($department);
        $this->assertCount(1, $department->employees);
        $this->assertCount(3, $employee->departments);
        $rows = database()->table('department_employee')
            ->where('employee_id = ? AND department_id = ?', [$employee->id, $department->id])
            ->all();
        $this->assertCount(1, $rows);

        // remove the relation about the employee
        $employee->departments()->disassociate($department);
        $this->assertEmpty($department->employees);
        $this->assertCount(2, $employee->departments);

        // remove the relation about the department
        $employee->departments()->associate($department);
        $this->assertCount(1, $department->employees);
        $this->assertCount(3, $employee->departments);
        $department->employees()->disassociate();
        $this->assertEmpty($department->employees);
        $this->assertCount(2, $employee->clearRelationCache()->departments);
        $rows = database()->table('department_employee')
            ->where('employee_id = ? AND department_id = ?', [$employee->id, $department->id])
            ->all();
        $this->assertEmpty($rows);

        // remove all relations between departments and the given employee
        $employee->departments()->disassociate();
        $this->assertEmpty($employee->departments);
    }

    public function testCreateAndDelete()
    {
        /** @var Employee $employee */
        $employee = Employee::find(1);
        $this->assertCount(2, $employee->departments);

        /** @var Department $department */
        $department = $employee->departments()->create(['name' => 'dummy']);
        $this->assertInstanceOf(Department::class, $department);
        $this->assertSame('dummy', $department->name);
        $this->assertCount(1, $department->employees);
        $this->assertCount(3, $employee->departments);
        $rows = $employee->database()->table('department_employee')->where('employee_id = ? AND department_id = ?', [$employee->id, $department->id])->all();
        $this->assertCount(1, $rows);
        $this->assertSame(5, Department::count());

        $departmentId = $department->id;
        $employee->departments()->delete($department);
        $this->assertNull($department->id);
        $this->assertCount(2, $employee->departments);
        $this->assertSame(4, Department::count());
        $rows = $employee->database()->table('department_employee')->where('employee_id = ? AND department_id = ?', [$employee->id, $departmentId])->all();
        $this->assertEmpty($rows);

        // cancel operation by hook
        $this->assertFalse($employee->departments()->create(['name' => 'cancel']));
        $department = Department::find(1);
        $department->name = 'cancel';
        $this->assertFalse($employee->departments()->delete($department));
    }
}