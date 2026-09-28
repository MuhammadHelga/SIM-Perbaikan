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
     * Jalankan rute yang cocok. Mengembalikan true bila ada yang cocok.
     */
    public function dispatch(string $method, string $path): bool
    {
        $method = strtoupper($method);

        foreach ($this->routes as $route) {
            if ($route['method'] !== 'ANY' && $route['method'] !== $method) {
                continue;
            }

            if (!preg_match($route['regex'], $path, $matches)) {
                continue;
            }

            array_shift($matches);
            $params = [];
            foreach ($route['names'] as $i => $name) {
                $params[$name] = $matches[$i] ?? null;
            }

            ($route['handler'])($params);

            return true;
        }

        return false;
    }
}
