<?php

require_once 'src/controllers/SecurityController.php';
require_once 'src/controllers/DashboardController.php';

class Routing {
    private static $instances = [];

    public static $routes = [
        "login" => [
            "controller" => "SecurityController",
            "action" => "login"
        ],
        "register" => [
            "controller" => "SecurityController",
            "action" => "register"
        ],
        "logout" => [
            "controller" => "SecurityController",
            "action" => "logout"
        ],
        "dashboard" => [
            "controller" => "DashboardController",
            "action" => "index"
        ],
        "" => [
            "controller" => "SecurityController",
            "action" => "login"
        ],
    ];

    public static function run(string $path) {
 
        $parts = explode("/", $path);
        $actionKey = $parts[0]; 
        $id = $parts[1] ?? null; 

        if (!array_key_exists($actionKey, self::$routes)) {
            include 'public/views/404.html';
            return;
        }

        $controllerName = self::$routes[$actionKey]["controller"];
        $action = self::$routes[$actionKey]["action"];

        if (!isset(self::$instances[$controllerName])) {
            self::$instances[$controllerName] = new $controllerName();
        }
        
        $controllerObj = self::$instances[$controllerName];

        $controllerObj->$action($id);
    }
}