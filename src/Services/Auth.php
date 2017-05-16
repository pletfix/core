<?php

namespace Core\Services;

use App\Models\User;
use Core\Services\Contracts\Auth as AuthContract;

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

        return true;
    }

    /**
     * @inheritdoc
     */
    public function logout()
    {
        session()->delete('_auth')->regenerate();
        $this->attributes = [];
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
            session()->regenerate();
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