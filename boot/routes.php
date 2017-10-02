<?php

$router = Core\Application::router();

$router->get('', function() {
    return 'The Pletfix Core is running!';
});
