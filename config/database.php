<?php

return [

    /**
     * ----------------------------------------------------------------
     * Default Database Store Name
     * ----------------------------------------------------------------
     *
     * Here you may specify which of the database stores below you wish
     * to use as your default store.
     */

    'default' => env('DB_STORE', 'sqlite'),

    /**
     * ----------------------------------------------------------------
     * Database Stores
     * ----------------------------------------------------------------
     *
     * Here are each of the database setup for your application.
     *
     *  Supported Driver:
     * - MSSQL
     * - MySQL
     * - PostgreSQL
     * - SQLite
     */

    'stores' => [

        'mssql' => [
            'driver'     => 'MSSQL',
            'host'       => env('DB_MSSQL_HOST', 'localhost'),
            'port'       => env('DB_MSSQL_PORT', 1433),
            'database'   => env('DB_MSSQL_DATABASE'),
            'username'   => env('DB_MSSQL_USERNAME'),
            'password'   => env('DB_MSSQL_PASSWORD', ''),
        ],

        'mysql' => [
            'driver'     => 'MySQL',
            'host'       => env('DB_MYSQL_HOST', 'localhost'),
            'port'       => env('DB_MYSQL_PORT', 3306),
            'database'   => env('DB_MYSQL_DATABASE'),
            'username'   => env('DB_MYSQL_USERNAME', 'forge'),
            'password'   => env('DB_MYSQL_PASSWORD', ''),
        ],

        'pgsql' => [
            'driver'     => 'PostgreSQL',
            'host'       => env('DB_PGSQL_HOST', 'localhost'),
            'port'       => env('DB_PGSQL_PORT', 5432),
            'database'   => env('DB_PGSQL_DATABASE'),
            'username'   => env('DB_PGSQL_USERNAME'),
            'password'   => env('DB_PGSQL_PASSWORD', ''),
            'schema'     => 'test', //'public',
        ],

        'sqlite' => [
            'driver'     => 'SQLite',
            'database'   => storage_path(env('DB_SQLITE_DATABASE', 'db/sqlite.db')),
        ],

    ],
];