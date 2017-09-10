<?php

namespace Core\Tests\Services;

use Core\Models\Model;
use Core\Services\Auth;
use Core\Services\DI;
use Core\Testing\TestCase;

class AuthTest extends TestCase
{
    public static function setUpBeforeClass()
    {
        self::defineMemoryAsDefaultDatabase();

        /** @noinspection SqlDialectInspection, SqlNoDataSourceInspection */
        database()->exec('
            CREATE TABLE users (
                id INTEGER PRIMARY KEY NOT NULL,
                name VARCHAR(255) NOT NULL,
                email VARCHAR(255),
                password VARCHAR(255),
                role VARCHAR(50),
                principal VARCHAR(255),
                confirmation_token VARCHAR(60),
                remember_token VARCHAR(100)
            );
        ');

        /** @noinspection SqlDialectInspection, SqlNoDataSourceInspection */
        database()->exec('
            CREATE UNIQUE INDEX users_email_unique ON users (email);
        ');

        /** @noinspection SqlDialectInspection, SqlNoDataSourceInspection */
        database()->exec('
            CREATE UNIQUE INDEX users_principal_unique ON users (principal);
        ');

        /** @noinspection SqlDialectInspection, SqlNoDataSourceInspection */
        database()->exec('
            INSERT INTO users (name, email, password, role) VALUES 
              (\'Tiger\', \'test@example.com\', \'$2y$10$xM5BdnPXR8cZ.66zANjx1OAnY9kd9Lp6KyYqRvpfYLhF3Xq7JY11O\', \'admin\');
        ');

        DI::getInstance()->get('config')->set('auth', [
            'roles' => [
                'user'   => 'User',
                'admin'  => 'Administrator',
                'boss'   => 'Boss',
            ],
            'acl' => [
                'add'    => ['admin', 'user'],
                'manage' => ['admin'],
                'fire'   => ['boss'],
            ],
            'model' => [
                'class'    => 'App\Models\User',
                'identity' => 'email',
            ]
        ]);
    }

    public static function tearDownAfterClass()
    {
        database()->disconnect();
    }

    public function testBase()
    {
        DI::getInstance()->get('config')->set('auth.model.class', 'Core\Tests\Services\User');

        $auth = new Auth();

        // login failed
        $this->assertFalse($auth->login(['email' => 'test@example.com', 'password' => 'wrong']));
        $this->assertFalse($auth->isLoggedIn());

        // login successful
        $this->assertTrue($auth->login(['email' => 'test@example.com', 'password' => 'psss..']));
        $this->assertTrue($auth->isLoggedIn());
        $this->assertArrayNotHasKey('remember_me', $_COOKIE);

        // login and remember me
        $this->assertTrue($auth->login(['email' => 'test@example.com', 'password' => 'psss..', 'remember' => 'true']));
        $this->assertTrue($auth->isLoggedIn());
        $this->assertArrayHasKey('remember_me', $_COOKIE);
        $rememberMe = $_COOKIE['remember_me'];

        // read attributes
        $this->assertEquals(1, $auth->id());
        $this->assertSame('Tiger', $auth->name());
        $this->assertSame('admin', $auth->role());
        $this->assertSame(['add', 'manage'], $auth->abilities());
        $this->assertFalse($auth->is('user'));
        $this->assertTrue($auth->is('admin'));
        $this->assertFalse($auth->can('fire'));
        $this->assertTrue($auth->can('manage'));
        $this->assertSame('Tiger', (string)$auth); // __toString()

        // change attributes
        $this->assertInstanceOf(\Core\Services\Contracts\Auth::class, $auth->changeName('Tiger Tom'));
        $this->assertSame('Tiger Tom', $auth->name());
        $this->assertInstanceOf(\Core\Services\Contracts\Auth::class, $auth->changeRole('user'));
        $this->assertSame('user', $auth->role());

        // logout
        $_SESSION['_csrf_token'] = '123';
        $this->assertArrayHasKey('_csrf_token', $_SESSION);
        $this->assertInstanceOf(\Core\Services\Contracts\Auth::class, $auth->logout());
        $this->assertFalse($auth->isLoggedIn());
        $this->assertNull($auth->role());
        $this->assertNull($auth->abilities());
        $this->assertArrayNotHasKey('remember_me', $_COOKIE);
        $this->assertArrayNotHasKey('_csrf_token', $_SESSION);

        // set principal
        $this->assertInstanceOf(\Core\Services\Contracts\Auth::class, $auth->setPrincipal(2, 'Lion', 'boss'));
        $this->assertTrue($auth->isLoggedIn());
        $this->assertEquals(2, $auth->id());
        $this->assertSame('Lion', $auth->name());
        $this->assertSame('boss', $auth->role());
        $this->assertSame(['fire'], $auth->abilities());
        $auth->logout();

        // remember me...
        $auth = new Auth();
        $this->assertFalse($auth->isLoggedIn());
        $auth->logout();

        $_COOKIE['remember_me'] = 'try to hack';
        $auth = new Auth();
        $this->assertFalse($auth->isLoggedIn());
        $auth->logout();

        $_COOKIE['remember_me'] = $rememberMe;
        $auth = new Auth();
        $this->assertTrue($auth->isLoggedIn());
        $this->assertSame('Tiger', $auth->name());
        $this->assertSame('admin', $auth->role());
        $this->assertSame('admin', $auth->role());
    }
}

class User extends Model
{
}
