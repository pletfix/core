<?php

$di = \Core\Services\DI::getInstance();

///////////////////////////////////////////////////////////////////////////////
// frohlfing/hello

/*
 * Singleton Instance (shared)
 */

$di->set('hello', \FRohlfing\Hello\HelloService::class, true);

/*
 * Multiple Instance (not shared)
 */
