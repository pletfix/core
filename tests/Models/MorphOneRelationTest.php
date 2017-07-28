<?php

namespace Core\Tests\Models;

use Core\Models\MorphOneRelation;
use Core\Models\Relation;

require_once 'RelationTestCase.php';

class MorphOneRelationTest extends RelationTestCase
{
    // department <- picture
    public function testMorphOneRelation()
    {
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

        $relations = $this->getPrivateProperty($department, 'relations');
        $this->assertArrayHasKey('picture', $relations);
        $this->assertInstanceOf(Picture::class, $relations['picture']);
        $this->assertSame('Picture Marketing', $relations['picture']->name);

        $this->assertSame('Marketing', $department->name);
        $this->assertSame('Picture Marketing', $department->picture->name);
    }
}