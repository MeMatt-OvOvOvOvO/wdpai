<?php

class SessionService
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            // D3: cookie ma być Secure tylko na połączeniu HTTPS – inaczej
            // przeglądarka w ogóle by go nie przesłała przy lokalnym dev po HTTP
            $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

            // C3, D3, E3: flagi bezpieczeństwa ciasteczka sesji
            session_set_cookie_params([
                'lifetime' => 3600,
                'path'     => '/',
                'domain'   => '',
                'secure'   => $isHttps, // D3: wysyłane tylko przez HTTPS, gdy dostępne
                'httponly' => true,     // C3: JS nie ma dostępu do cookie
                'samesite' => 'Lax',    // E3: ochrona przed CSRF
            ]);
            session_start();
        }
    }

    public static function set(string $key, mixed $value): void
    {
        self::start();
        $_SESSION[$key] = $value;
    }

    public static function get(string $key): mixed
    {
        self::start();
        return $_SESSION[$key] ?? null;
    }

    public static function has(string $key): bool
    {
        self::start();
        return isset($_SESSION[$key]);
    }

    public static function remove(string $key): void
    {
        self::start();
        unset($_SESSION[$key]);
    }

    public static function destroy(): void
    {
        self::start();
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        session_destroy();
    }

    public static function isLoggedIn(): bool
    {
        return self::has('user_id') && self::get('is_logged_in') === true;
    }

    public static function isCoordinator(): bool
    {
        return self::isLoggedIn() && self::get('user_role') === 'coordinator';
    }

    public static function isRescuer(): bool
    {
        return self::isLoggedIn() && self::get('user_role') === 'rescuer';
    }

    public static function requireLogin(): void
    {
        if (!self::isLoggedIn()) {
            header('Location: /login');
            exit();
        }
    }

    public static function requireCoordinator(): void
    {
        self::requireLogin();
        if (!self::isCoordinator()) {
            http_response_code(403);
            include __DIR__ . '/../../public/views/errors/403.php';
            exit();
        }
    }

    public static function flash(string $key, string $message): void
    {
        self::set('flash_' . $key, $message);
    }

    public static function getFlash(string $key): ?string
    {
        $msg = self::get('flash_' . $key);
        self::remove('flash_' . $key);
        return $msg;
    }

    // -------------------------------------------------------
    // B2/C2: CSRF token
    // -------------------------------------------------------

    public static function generateCsrfToken(): string
    {
        if (!self::has('csrf_token')) {
            self::set('csrf_token', bin2hex(random_bytes(32)));
        }
        return self::get('csrf_token');
    }

    public static function validateCsrfToken(string $token): bool
    {
        $stored = self::get('csrf_token');
        if (!$stored) return false;
        return hash_equals($stored, $token);
    }

    public static function rotateCsrfToken(): void
    {
        self::set('csrf_token', bin2hex(random_bytes(32)));
    }

    // -------------------------------------------------------
    // A4/E5: limit prób logowania / blokada czasowa
    // -------------------------------------------------------

    private const MAX_LOGIN_ATTEMPTS = 5;
    private const LOCKOUT_SECONDS    = 300; // 5 minut

    /**
     * Zwraca liczbę sekund pozostałych do końca blokady, albo 0 jeśli konto
     * (a właściwie ten adres email z tej przeglądarki) nie jest zablokowane.
     */
    public static function getLoginLockoutRemaining(string $email): int
    {
        self::start();
        $key = 'login_lock_' . sha1(strtolower($email));
        $lockedUntil = $_SESSION[$key]['locked_until'] ?? null;

        if ($lockedUntil !== null && $lockedUntil > time()) {
            return $lockedUntil - time();
        }

        // Blokada wygasła – wyczyść licznik, żeby zacząć od nowa
        if ($lockedUntil !== null && $lockedUntil <= time()) {
            unset($_SESSION[$key]);
        }

        return 0;
    }

    /**
     * Rejestruje nieudaną próbę logowania. Po przekroczeniu progu
     * MAX_LOGIN_ATTEMPTS uruchamia czasową blokadę formularza (A4).
     */
    public static function registerFailedLogin(string $email): void
    {
        self::start();
        $key = 'login_lock_' . sha1(strtolower($email));

        $attempts = ($_SESSION[$key]['attempts'] ?? 0) + 1;
        $_SESSION[$key]['attempts'] = $attempts;

        if ($attempts >= self::MAX_LOGIN_ATTEMPTS) {
            $_SESSION[$key]['locked_until'] = time() + self::LOCKOUT_SECONDS;
        }
    }

    /**
     * Czyści licznik nieudanych prób po poprawnym logowaniu.
     */
    public static function clearLoginAttempts(string $email): void
    {
        self::start();
        $key = 'login_lock_' . sha1(strtolower($email));
        unset($_SESSION[$key]);
    }
}
