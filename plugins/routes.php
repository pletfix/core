<?php

$route = \Core\Application::route();

///////////////////////////////////////////////////////////////////////////////
// frohlfing/hello

$route->get('hello', function() {
    return di('hello')->sayHello();
//  return view('test2', $data = collect(['name' => 'Anton']));
});
