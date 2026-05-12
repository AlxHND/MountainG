<?php

namespace App\Helpers;

class Router
{
    private static array $routes = [];

    /**
     * Регистрация маршрутов
     *
     * @param string $method HTTP-метод (GET или POST)
     * @param string $path Путь маршрута
     * @param callable|array $handler Обработчик маршрута
     */
    public static function add(string $method, string $path, $handler)
    {
        self::$routes[] = [
            'method' => strtoupper($method),
            'path' => trim($path, '/'),
            'handler' => $handler
        ];
    }

    /**
     * Запуск маршрутизатора
     */
    public static function dispatch()
    {
        $requestUri = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
        $requestMethod = $_SERVER['REQUEST_METHOD'];
    
        foreach (self::$routes as $route) {
            // Поддержка динамических параметров в маршрутах
            $pattern = preg_replace('/{[a-zA-Z0-9_]+}/', '([a-zA-Z0-9_-]+)', $route['path']);
            $pattern = "#^" . $pattern . "$#";
    
            if ($route['method'] === $requestMethod && preg_match($pattern, $requestUri, $matches)) {
                array_shift($matches);  // Убираем первый элемент, т.к. это полное совпадение
    
                if (is_callable($route['handler'])) {
                    call_user_func_array($route['handler'], $matches);
                } elseif (is_array($route['handler'])) {
                    $controllerName = $route['handler'][0];
                    $methodName = $route['handler'][1];
    
                    $controller = new $controllerName();
                    call_user_func_array([$controller, $methodName], $matches);
                }
                return;
            }
        }
    
        // Если маршрут не найден, пропускаем

    }
    
}
