<?php

class SessionService
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            // C3, D3, E3: flagi bezpieczeństwa ciasteczka sesji
            session_set_cookie_params([
                'lifetime' => 3600,
                'path'     => '/',
                'domain'   => '',
                'secure'   => false, // zmień na true gdy HTTPS
                'httponly' => true,  // C3: JS nie ma dostępu do cookie
                'samesite' => 'Lax', // E3: ochrona przed CSRF
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
}
