<?php

abstract class AppController
{
    protected function render(string $view, array $data = []): void
    {
        // Udostępnij zmienne dla widoku
        extract($data);

        $viewPath = __DIR__ . '/../../public/views/' . $view . '.php';

        if (!file_exists($viewPath)) {
            http_response_code(404);
            include __DIR__ . '/../../public/views/errors/404.php';
            exit();
        }

        include $viewPath;
    }

    protected function isPost(): bool
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    protected function isGet(): bool
    {
        return $_SERVER['REQUEST_METHOD'] === 'GET';
    }

    protected function redirect(string $path): void
    {
        $url = $this->baseUrl();
        header("Location: {$url}{$path}");
        exit();
    }

    protected function baseUrl(): string
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return "{$scheme}://{$host}";
    }

    protected function json(mixed $data, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit();
    }

    protected function jsonError(string $message, int $code = 400): void
    {
        $this->json(['error' => $message], $code);
    }

    protected function getPost(string $key, string $default = ''): string
    {
        return trim($_POST[$key] ?? $default);
    }

    protected function getQuery(string $key, string $default = ''): string
    {
        return trim($_GET[$key] ?? $default);
    }
}
