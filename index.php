<?php

declare(strict_types=1);

// ============================================================
// Bootstrap - ładowanie klas i konfiguracja
// ============================================================

// Sesja startuje przez SessionService (z flagami HttpOnly, SameSite)

// Autoloader
spl_autoload_register(function (string $className): void {
    $directories = [
        __DIR__ . '/src/Controllers/',
        __DIR__ . '/src/Repository/',
        __DIR__ . '/src/Entity/',
        __DIR__ . '/src/Services/',
    ];

    foreach ($directories as $dir) {
        $file = $dir . $className . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// Zmienne środowiskowe z .env (jeśli plik istnieje)
if (file_exists(__DIR__ . '/.env')) {
    $lines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#')) continue;
        if (str_contains($line, '=')) {
            [$key, $value] = explode('=', $line, 2);
            putenv(trim($key) . '=' . trim($value));
        }
    }
}

// Globalny handler błędów
set_exception_handler(function (Throwable $e): void {
    error_log($e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    http_response_code(500);
    if (file_exists(__DIR__ . '/public/views/errors/500.php')) {
        include __DIR__ . '/public/views/errors/500.php';
    } else {
        echo '<h1>500 - Internal Server Error</h1>';
    }
    exit();
});

// ============================================================
// Routing
// ============================================================

require_once __DIR__ . '/Routing.php';

$path = trim($_SERVER['REQUEST_URI'], '/');
$path = parse_url($path, PHP_URL_PATH);
$path = $path ?: '';

Routing::run($path);
