<?php

namespace Core\Tests\Models;

use Core\Models\HasOneRelation;
use Core\Models\Relation;

require_once 'RelationTestCase.php';

class HasOneRelationTest extends RelationTestCase
{
    // employee <- profile
    public function testHasOneRelation()
    {
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

        /** @var Employee[] $employees */
        $employees = Employee::with('profile')->all();
        $this->assertCount(3, $employees);
        $this->assertInstanceOf(Employee::class, $employees[0]);

        $relations = $this->getPrivateProperty($employee, 'relations');
        $this->assertArrayHasKey('profile', $relations);
        $this->assertInstanceOf(Profile::class, $relations['profile']);
        $this->assertSame('Profile Anton', $relations['profile']->name);

        $this->assertSame('Anton', $employees[0]->name);
        $this->assertSame('Profile Anton', $employees[0]->profile->name);
    }
}