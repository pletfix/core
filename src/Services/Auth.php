<?php

namespace Core\Services;

use App\Models\User;
use Core\Services\Contracts\Auth as AuthContract;
use Exception;

class Auth implements AuthContract
{
//    /**
//     * Find the user by this column.
//     *
//     * @var string
//     */
//    protected $userKey = 'email';

    /**
     * Attributes of the current user.
     *
     * It's null if the attributes do not loaded from the session yet.
     *
     * @var array|null
     */
    protected $attributes = null;

//    /**
//     * Create a new Auth instance.
//     */
//    public function __construct()
//    {
//        $this->attributes = session('_auth', []);
//    }

    /**
     * @inheritdoc
     */
    public function login(array $credentials)
    {
        $email    = $credentials['email'];
        $password = $credentials['password'];

        $user = User::whereIs('email', $email)->first();

        if ($user === null || !password_verify($password, $user->password)) {
            return false;
        }

        $this->setPrincipal($user->id, $user->name, $user->role);

        // If the user should be permanently "remembered" by the application we will queue a permanent cookie that
        // contains the encrypted copy of the user identifier. We will then decrypt this later to retrieve the users.
        if (!empty($credentials['remember'])) {
            $this->createRememberTokenIfDoesntExist($user);
            $this->saveCookie($user);
        }
        else {
            $this->deleteCookie();
        }

        return true;
    }


    /**
     * @inheritdoc
     */
    public function logout()
    {
        session()
            ->delete('_auth')
            ->delete('_csrf_token')
            ->regenerate();
        $this->deleteCookie();
        $this->attributes = [];
    }

    /**
     * Create a new "remember me" token for the user if one doesn't already exist.
     *
     * @param User $user
     */
    protected function createRememberTokenIfDoesntExist($user)
    {
        if (empty($user->remember_token)) {
            $user->remember_token = random_string(60);
            $user->save();
        }
    }

    /**
     * Create a "remember me" cookie for a given ID.
     *
     * @param User $user
     */
    protected function saveCookie($user)
    {
        $path = rtrim(dirname($_SERVER['PHP_SELF']), '/.');

        // Create a cookie that lasts "forever" (five years).
        cookie()->set('remember_me', base64_encode($user->id . '|' . $user->remember_token), 2628000, $path); // todo Path anpassen
    }

    /**
     * Delete the "remember me" cookie for a given ID.
     */
    protected function deleteCookie()
    {
        //$host =strtolower(isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME']);
        $path = rtrim(dirname($_SERVER['PHP_SELF']), '/.');

        cookie()->delete('remember_me', $path); // Path anpassen
    }

    /**
     * Read the "remember me" cookie if exist and store the principal into the session.
     */
    protected function laodFromCookie()
    {
        $hash = cookie('remember_me');
        if ($hash === null) {
            return;
        }

        try {
            list($id, $token) = explode('|', base64_decode($hash));
        }
        catch (Exception $e) {
            $this->deleteCookie();
            return;
        }

        /** @var User $user */
        $user = User::find($id);
        if ($user !== null && !empty($token) && $token === $user->remember_token) {
            $this->setPrincipal($user->id, $user->name, $user->role);
        }
    }

    /**
     * Set the attributes of the principal and store it in the session.
     *
     * @param int $id
     * @param string $name
     * @param string $role
     */
    protected function setPrincipal($id, $name, $role)
    {
        $this->attributes = [
            'id'        => $id,
            'name'      => $name,
            'role'      => $role,
            'abilities' => $this->getAbilities($role),
        ];

        session()->set('_auth', $this->attributes);
    }

    /**
     * Read the abilities from the ACL for the given role.
     *
     * @param $role
     * @return array
     */
    protected function getAbilities($role)
    {
        $abilities = [];
        foreach (config('auth.acl') as $ability => $roles) {
            if (in_array($role, $roles)) {
                $abilities[] = $ability;
            }
        }

        return $abilities;
    }

    /**
     * Load the user attributes from the session and get the attribute.
     *
     * @param string $key
     * @return mixed
     */
    protected function attribute($key)
    {
        if ($this->attributes === null) {
            $this->attributes = session('_auth', []);
            if (empty($this->attributes)) {
                $this->laodFromCookie();
            }
        }

        return isset($this->attributes[$key]) ? $this->attributes[$key] : null;
    }

    /**
     * @inheritdoc
     */
    public function isVerified() // or check? or isValid? or ...?
    {
        return $this->attribute('id') !== null;
    }

    /**
     * @inheritdoc
     */
    public function id()
    {
        return $this->attribute('id');
    }

    /**
     * @inheritdoc
     */
    public function name()
    {
        return $this->attribute('name');
    }

    /**
     * @inheritdoc
     */
    public function role()
    {
        return $this->attribute('role');
    }

    /**
     * @inheritdoc
     */
    public function abilities()
    {
        return $this->attribute('abilities');
    }

    /**
     * @inheritdoc
     */
    public function is($role)
    {
        return $this->role() === $role;
    }

    /**
     * @inheritdoc
     */
    public function can($ability)
    {
        return in_array($ability, $this->abilities() ?: []);
    }

    /**
     * Change the display name of the current user.
     *
     * @param string $name
     */
    public function changeName($name)
    {
        if ($this->isVerified() && $this->name() !== $name) {
            $this->setPrincipal($this->id(), $name, $this->role());
        }
    }

    /**
     * Change the role of the current user.
     *
     * @param string $role
     */
    public function changeRole($role)
    {
        if ($this->isVerified() && $this->role() !== $role) {
            $this->setPrincipal($this->id(), $this->name(), $role);
            session()
                ->delete('_csrf_token')
                ->regenerate();
        }
    }

    /**
     * Convert the collection to its string representation.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->name();
    }
}