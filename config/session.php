<?php

return [

    /**
     * ----------------------------------------------------------------
     * Session Name
     * ----------------------------------------------------------------
     *
     * Here you may change the name of the PHP session.
     */

    'name' => 'pletfix_session',

    /**
     * ----------------------------------------------------------------
     * Session Cookie Lifetime
     * ----------------------------------------------------------------
     *
     * The lifetime of the session cookie is defined in minutes.
     *
     * The value 0 means "until the browser is closed."
     *
     * The upper limit, which is still useful, depends on the setting of
     * session.gc_maxlifetime in php.ini.
     */

    'lifetime' => 120, // minutes

    /**
     * ----------------------------------------------------------------
     * Session Save Path
     * ----------------------------------------------------------------
     *
     * If specified, the path to which the session data is saved will
     * be changed.
     *
     * The default setting is specified in `php.ini` under the
     * `session.save_path` key.
     */

    'save_path' => storage_path('sessions'),

];