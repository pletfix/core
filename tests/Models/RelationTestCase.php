<?php

namespace Core\Tests\Models;

use Core\Models\Model;
use Core\Services\Contracts\Database;
use Core\Services\Contracts\DatabaseFactory;
use Core\Services\DI;
use Core\Testing\TestCase;

class RelationTestCase extends TestCase
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
        self::$fixturePath = __DIR__  . '/../_data/fixtures/sqlite';
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

    protected function setUp()
    {
        $this->defineMemoryAsDefaultDatabase();
        $db = database();
        $this->execSqlFile($db, 'create_employee_relation');
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
    public function beforeInsert()
    {
        return $this->name != 'cancel';
    }

    public function beforeUpdate()
    {
        return $this->name != 'cancel';
    }

    public function beforeDelete()
    {
        return $this->name != 'cancel';
    }

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
    public function beforeInsert()
    {
        return $this->name != 'cancel';
    }

    public function beforeUpdate()
    {
        return $this->name != 'cancel';
    }

    public function beforeDelete()
    {
        return $this->name != 'cancel';
    }

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
    public function beforeInsert()
    {
        return $this->name != 'cancel';
    }

    public function beforeUpdate()
    {
        return $this->name != 'cancel';
    }

    public function beforeDelete()
    {
        return $this->name != 'cancel';
    }

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
    public function beforeInsert()
    {
        return $this->name != 'cancel';
    }

    public function beforeUpdate()
    {
        return $this->name != 'cancel';
    }

    public function beforeDelete()
    {
        return $this->name != 'cancel';
    }

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
    public function beforeInsert()
    {
        return $this->name != 'cancel';
    }

    public function beforeUpdate()
    {
        return $this->name != 'cancel';
    }

    public function beforeDelete()
    {
        return $this->name != 'cancel';
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}