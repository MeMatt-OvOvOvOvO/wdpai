<?php

class Routing
{
    private static array $routes = [];

    public static function get(string $path, string $controller, string $method): void
    {
        self::$routes['GET'][$path] = [$controller, $method];
    }

    public static function post(string $path, string $controller, string $method): void
    {
        self::$routes['POST'][$path] = [$controller, $method];
    }

    public static function run(string $path): void
    {
        $httpMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        // Normalizacja ścieżki
        $path = '/' . trim($path, '/');
        if ($path === '/') $path = '/dashboard';

        // Dokładne dopasowanie
        if (isset(self::$routes[$httpMethod][$path])) {
            [$controllerClass, $method] = self::$routes[$httpMethod][$path];
            $controller = new $controllerClass();
            $controller->$method();
            return;
        }

        // Dopasowanie z parametrami (np. /missions/42)
        foreach (self::$routes[$httpMethod] ?? [] as $route => $handler) {
            $pattern = preg_replace('/\{(\w+)\}/', '(?P<\1>[^/]+)', $route);
            $pattern = '#^' . $pattern . '$#';

            if (preg_match($pattern, $path, $matches)) {
                // Wrzuć parametry do $_GET
                foreach ($matches as $k => $v) {
                    if (!is_int($k)) $_GET[$k] = $v;
                }
                [$controllerClass, $method] = $handler;
                $controller = new $controllerClass();
                $controller->$method();
                return;
            }
        }

        // Brak trasy → 404
        http_response_code(404);
        include __DIR__ . '/public/views/errors/404.php';
    }
}

// ============================================================
// DEFINICJA TRAS
// ============================================================

// Auth
Routing::get( '/login',           'SecurityController', 'login');
Routing::post('/login',           'SecurityController', 'login');
Routing::get( '/register',        'SecurityController', 'register');
Routing::post('/register',        'SecurityController', 'register');
Routing::get( '/logout',          'SecurityController', 'logout');

// Dashboard
Routing::get( '/dashboard',       'DashboardController', 'index');

// Missions
Routing::get( '/missions',                    'MissionController', 'index');
Routing::get( '/missions/new',                'MissionController', 'create');
Routing::post('/missions/new',                'MissionController', 'store');
Routing::get( '/missions/{id}',               'MissionController', 'show');
Routing::get( '/missions/{id}/edit',          'MissionController', 'edit');
Routing::post('/missions/{id}/edit',          'MissionController', 'update');
Routing::post('/missions/{id}/delete',        'MissionController', 'delete');
Routing::post('/missions/{id}/rescuers',      'MissionController', 'addRescuer');
Routing::post('/missions/{id}/rescuers/remove', 'MissionController', 'removeRescuer');

// Equipment
Routing::get( '/equipment',                'EquipmentController', 'index');
Routing::get( '/equipment/new',            'EquipmentController', 'create');
Routing::post('/equipment/new',            'EquipmentController', 'store');
Routing::get( '/equipment/{id}',           'EquipmentController', 'show');
Routing::get( '/equipment/{id}/edit',      'EquipmentController', 'edit');
Routing::post('/equipment/{id}/edit',      'EquipmentController', 'update');
Routing::post('/equipment/{id}/delete',    'EquipmentController', 'delete');

// API endpoints (Fetch API / AJAX)
Routing::get( '/api/missions',             'MissionController',   'apiList');
Routing::get( '/api/equipment',            'EquipmentController', 'apiList');
Routing::get( '/api/users',                'UserController',      'apiList');
Routing::get( '/api/stats',                'DashboardController', 'apiStats');
Routing::post('/api/missions/{id}/equipment', 'EquipmentController', 'apiLoanEquipment');

// Users (tylko coordinator)
Routing::get( '/users',             'UserController', 'index');
Routing::get( '/users/{id}/edit',   'UserController', 'edit');
Routing::post('/users/{id}/edit',   'UserController', 'update');
Routing::post('/users/{id}/delete', 'UserController', 'delete');

// Profile
Routing::get( '/profile',    'UserController', 'profile');
Routing::post('/profile',    'UserController', 'updateProfile');
