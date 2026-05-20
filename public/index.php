<?php

require_once dirname(__DIR__) . '/app/Core/Bootstrap.php';

$router = require BASE_PATH . '/config/routes.php';
$router->dispatch(request_method(), current_uri());
