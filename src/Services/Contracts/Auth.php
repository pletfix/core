<?php

namespace Core\Services\Contracts;

interface Auth
{
    /**
     * Authenticate and log a user into the application.
     *
     * @param array $credentials
     * @return bool
     */
    public function login(array $credentials);

    /**
     * Log the user out of the application.
     */
    public function logout();

    /**
     * Determine if the current user is authenticated.
     *
     * @return bool
     */
    public function isVerified(); // or check? or isValid? or ...?

    /**
     * Get the id of the current user.
     *
     * @return int|null
     */
    public function id();

    /**
     * Get the display name of the current user.
     *
     * @return string|null
     */
    public function name();

    /**
     * Get the role of the current user.
     *
     * @return string|null
     */
    public function role();

    /**
     * Get the abilities of the current user.
     *
     * @return array|null
     */
    public function abilities();

    /**
     * Determine if the current user is the given role.
     *
     * @param string|array $role
     * @return bool
     */
    public function is($role);

    /**
     * Determine if the user has the given ability.
     *
     * @param $ability
     * @return bool
     */
    public function can($ability);

    /**
     * Change the display name of the current user.
     *
     * @param string $name
     */
    public function changeName($name);

    /**
     * Change the role of the current user.
     *
     * @param string $role
     */
    public function changeRole($role);
}
