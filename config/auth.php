<?php

return [

    /**
     * ----------------------------------------------------------------
     * User Roles
     * ----------------------------------------------------------------
     *
     * Here you may specify the available user roles.
     */

    'roles' => [
//      'guest'  => 'Gast',
        'user'   => 'Benutzer',
        'editor' => 'Redakteur',
        'admin'  => 'Administrator',
    ],

    /**
     * ----------------------------------------------------------------
     * Access Control List
     * ----------------------------------------------------------------
     *
     * To control the access you may define the user abilities.
     */

    'acl' => [
//      'login'              => ['guest'],
        'save-comment'       => ['admin', 'editor', 'user'],
        'delete-own-comment' => ['admin', 'editor', 'user'],
        'manage'             => ['admin', 'editor'],
        'manage-blog'        => ['admin', 'editor'],
        'manage-user'        => ['admin'],
    ],

    /**
     * ----------------------------------------------------------------
     * Model
     * ----------------------------------------------------------------
     *
     * Here you may specify the model which stores the user accounts.
     *
     * If you omit this option, you need additional services such as LDAP, Twitter or Facebook, to authorize the user.
     */

    'model' => [
        'class'    => 'App\Models\User',
        'identity' => 'email', // Find the user by this column, usually "email" or "name".
    ]
];