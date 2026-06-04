<?php

require_once 'src/controllers/SecurityController.php';
require_once 'src/controllers/DashboardController.php';
require_once 'src/controllers/ReservationController.php';
require_once 'src/controllers/TicketsController.php';
require_once 'src/controllers/ContactController.php';
require_once 'src/controllers/AdminController.php';

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
        "movies" => [
            "controller" => "DashboardController",
            "action" => "index"
        ],
        "tickets" => [
            "controller" => "TicketsController",
            "action" => "index"
        ],
        "reservation" => [
            "controller" => "ReservationController",
            "action" => "index"
        ],
        "contact" => [
            "controller" => "ContactController",
            "action" => "submit"
        ],
        "" => [
            "controller" => "SecurityController",
            "action" => "login"
        ],
    ];

    private static $adminActions = [
        'messages', 'reservations', 'movies', 'users', 'reports',
    ];

    public static function run(string $path) {
        $parts = explode("/", trim($path, '/'));
        $actionKey = $parts[0] ?? '';

        if ($actionKey === 'admin') {
            self::runAdmin($parts[1] ?? 'index');
            return;
        }

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
        $controllerObj->$action($parts[1] ?? null);
    }

    private static function runAdmin(string $action): void {
        $controllerName = 'AdminController';

        if (!isset(self::$instances[$controllerName])) {
            self::$instances[$controllerName] = new AdminController();
        }

        $controller = self::$instances[$controllerName];

        if ($action === '' || $action === 'index') {
            $controller->index();
            return;
        }

        if (!in_array($action, self::$adminActions, true) || !method_exists($controller, $action)) {
            include 'public/views/404.html';
            return;
        }

        $controller->$action();
    }
}
