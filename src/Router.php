<?php

/**
 * Router minimalis tanpa framework.
 *
 * Pola rute mendukung placeholder, dengan batasan opsional:
 *   $router->get('/laporan', $handler);
 *   $router->any('/laporan/{action:kirim|terima|hapus}/{id:\d+}', $handler);
 *
 * Handler menerima satu argumen: array parameter hasil tangkapan, mis. ['id' => '12'].
 * Handler bertanggung jawab sendiri soal login/role (lihat requireLogin/requireRole).
 */
class Router
{
    public const NOT_FOUND = 0;
    public const FOUND = 1;
    public const METHOD_NOT_ALLOWED = 2;

    /** @var array<int, array{method:string, regex:string, names:string[], handler:callable}> */
    private array $routes = [];

    public function get(string $pattern, callable $handler): void
    {
        $this->add('GET', $pattern, $handler);
    }

    public function post(string $pattern, callable $handler): void
    {
        $this->add('POST', $pattern, $handler);
    }

    /** Cocok untuk semua method (handler yang memutuskan). */
    public function any(string $pattern, callable $handler): void
    {
        $this->add('ANY', $pattern, $handler);
    }

    private function add(string $method, string $pattern, callable $handler): void
    {
        $names = [];
        $regex = preg_replace_callback(
            '#\{([a-zA-Z_][a-zA-Z0-9_]*)(?::([^}]+))?\}#',
            static function (array $m) use (&$names) {
                $names[] = $m[1];
                return '(' . ($m[2] ?? '[^/]+') . ')';
            },
            $pattern
        );

        $this->routes[] = [
            'method'  => $method,
            'regex'   => '#^' . $regex . '$#',
            'names'   => $names,
            'handler' => $handler,
        ];
    }

    /**
     * Samakan bentuk path: selalu diawali "/" dan tanpa trailing slash.
     * Root tetap "/".
     */
    private function normalizePath(string $path): string
    {
        $path = '/' . ltrim($path, '/');
        if ($path !== '/') {
            $path = rtrim($path, '/');
        }

        return $path === '' ? '/' : $path;
    }

    /**
     * Jalankan rute yang cocok.
     *
     * @return int Salah satu dari Router::FOUND, Router::NOT_FOUND,
     *             atau Router::METHOD_NOT_ALLOWED.
     */
    public function dispatch(string $method, string $path): int
    {
        $method = strtoupper($method);
        if ($method === 'HEAD') {
            $method = 'GET';
        }

        $path = $this->normalizePath($path);
        $allowed = [];

        foreach ($this->routes as $route) {
            if (!preg_match($route['regex'], $path, $matches)) {
                continue;
            }

            if ($route['method'] === 'ANY' || $route['method'] === $method) {
                array_shift($matches);
                $params = [];
                foreach ($route['names'] as $i => $name) {
                    $params[$name] = $matches[$i] ?? null;
                }

                ($route['handler'])($params);

                return self::FOUND;
            }

            $allowed[$route['method']] = true;
        }

        if ($allowed) {
            $methods = array_keys($allowed);
            sort($methods);
            header('Allow: ' . implode(', ', $methods));

            return self::METHOD_NOT_ALLOWED;
        }

        return self::NOT_FOUND;
    }
}
