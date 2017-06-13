<?php

namespace Core\Services;

use App\Models\User; // todo Existenz des Models sollte nicht im Core vorausgesetzt werden (weil es im Namespace App liegt)!
use Core\Services\Contracts\Auth as AuthContract;
use Exception;

class Auth implements AuthContract
{
    /**
     * Attributes of the current user.
     *
     * It's null if the attributes do not loaded from the session yet.
     *
     * @var array|null
     */
    private $attributes = null;

    /**
     * @inheritdoc
     */
    public function login(array $credentials)
    {
        $config = config('auth.model');
        $model  = $config['class'];
        $field  = $config['identity']; // usually "email" or "name"
        $value  = $credentials[$field];
        $user   = call_user_func([$model, 'whereIs'], $field, $value)->first();
        //$user = User::whereIs($field, $value)->first();
        
        $password = $credentials['password'];
        if ($user === null || !password_verify($password, $user->password)) {
            return false;
        }

        $this->setPrincipal($user->id, $user->name, $user->role);

        // If the user should be permanently "remembered" by the application we will create a "remember me" cookie.
        if (!empty($credentials['remember'])) {
            $this->createRememberMeTokenIfDoesntExist($user);
            $this->saveRememberMeCookie($user);
        }
        else {
            $this->deleteRememberMeCookie();
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
        $this->deleteRememberMeCookie();
        $this->attributes = [];
    }

    /**
     * Create a new "remember me" token for the user if one doesn't already exist.
     *
     * @param User $user
     */
    private function createRememberMeTokenIfDoesntExist($user)
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
    private function saveRememberMeCookie($user)
    {
        cookie()->setForever('remember_me', base64_encode($user->id . '|' . $user->remember_token));
    }

    /**
     * Delete the "remember me" cookie.
     */
    private function deleteRememberMeCookie()
    {
        cookie()->delete('remember_me');
    }

    /**
     * Read the "remember me" cookie if exist and store the principal into the session.
     */
    private function loadPrincipalFromRememberMeCookie()
    {
        $hash = cookie('remember_me');
        if ($hash === null) {
            return;
        }

        try {
            list($id, $token) = explode('|', base64_decode($hash));
        }
        catch (Exception $e) {
            $this->deleteRememberMeCookie();
            return;
        }

        /** @var User $user */
        $model = config('auth.model.class');
        $user  = call_user_func([$model, 'find'], $id);
        //$user = User::find($id);
        if ($user !== null && !empty($token) && $token === $user->remember_token) {
            $this->setPrincipal($user->id, $user->name, $user->role);
        }
    }

    /**
     * @inheritdoc
     */
    public function setPrincipal($id, $name, $role)
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
    private function getAbilities($role)
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
    private function attribute($key)
    {
        if ($this->attributes === null) {
            $this->attributes = session('_auth', []);
            if (empty($this->attributes)) {
                $this->loadPrincipalFromRememberMeCookie();
            }
        }

        return isset($this->attributes[$key]) ? $this->attributes[$key] : null;
    }

    /**
     * @inheritdoc
     */
    //public function isVerified() // or check? or isValid? or ...?
    public function isLoggedIn()
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
        if ($this->isLoggedIn() && $this->name() !== $name) {
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
        if ($this->isLoggedIn() && $this->role() !== $role) {
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