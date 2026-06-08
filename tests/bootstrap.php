<?php

/**
 * Bootstrap dla testów PHPUnit.
 *
 * Rejestruje ten sam (uproszczony) autoloader co aplikacja, dzięki czemu
 * testy mogą korzystać z klas z katalogu src/ bez ładowania całego frameworka
 * routingu / połączenia z bazą danych. Testy jednostkowe celowo NIE łączą się
 * z PostgreSQL — sprawdzają wyłącznie logikę, którą da się odizolować
 * (SessionService, encje domenowe). Repozytoria (wymagające bazy) są pokryte
 * przez testy integracyjne (tests/Integration/run.sh) uruchamiane na żywej
 * instancji aplikacji w Dockerze.
 */

declare(strict_types=1);

spl_autoload_register(function (string $className): void {
    $directories = [
        __DIR__ . '/../src/Controllers/',
        __DIR__ . '/../src/Repository/',
        __DIR__ . '/../src/Entity/',
        __DIR__ . '/../src/Services/',
    ];

    foreach ($directories as $dir) {
        $file = $dir . $className . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// Testy operujące na sesji (SessionService) potrzebują działającego $_SESSION.
// W SAPI php-cli sesje działają bez realnych nagłówków/cookies — wystarczy,
// że superglobalna tablica istnieje przed pierwszym użyciem.
if (session_status() === PHP_SESSION_NONE) {
    $_SESSION = [];
}
