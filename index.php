<?php

require_once 'Routing.php';

echo "<h1>Hi There!</h1>";
$path = trim($_SERVER['REQUEST_URI'], '/');
$path = parse_url($path, PHP_URL_PATH);

Routing::run($path);