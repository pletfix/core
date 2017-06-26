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
     *
     * @return $this;
     */
    public function logout();

    /**
     * Set the attributes of the principal and store it in the session.
     *
     * @param int $id
     * @param string $name
     * @param string $role
     * @return $this;
     */
    public function setPrincipal($id, $name, $role);

    /**
     * Determine if the current user is authenticated.
     *
     * @return bool
     */
    public function isLoggedIn();

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
     * @param string $role
     * @return bool
     */
    public function is($role);

    /**
     * Determine if the user has the given ability.
     *
     * @param string $ability
     * @return bool
     */
    public function can($ability);

    /**
     * Change the display name of the current user.
     *
     * @param string $name
     * @return $this
     */
    public function changeName($name);

    /**
     * Change the role of the current user.
     *
     * @param string $role
     * @return $this
     */
    public function changeRole($role);
}
