<?php
class Router {
    private array $routes = [];
    private string $basePath;

    public function __construct(string $basePath = '') {
        $this->basePath = rtrim($basePath, '/');
    }

    public function get(string $path, string $controller, string $method): void {
        $this->addRoute('GET', $path, $controller, $method);
    }

    public function post(string $path, string $controller, string $method): void {
        $this->addRoute('POST', $path, $controller, $method);
    }

    public function put(string $path, string $controller, string $method): void {
        $this->addRoute('PUT', $path, $controller, $method);
    }

    public function delete(string $path, string $controller, string $method): void {
        $this->addRoute('DELETE', $path, $controller, $method);
    }

    private function addRoute(string $httpMethod, string $path, string $controller, string $method): void {
        $this->routes[] = [
            'method' => $httpMethod,
            'path' => $this->basePath . $path,
            'controller' => $controller,
            'action' => $method
        ];
    }

    public function dispatch(): void {
        $requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $requestMethod = $_SERVER['REQUEST_METHOD'];

        // Support pour PUT et DELETE via _method
        if ($requestMethod === 'POST' && isset($_POST['_method'])) {
            $requestMethod = strtoupper($_POST['_method']);
        }

        foreach ($this->routes as $route) {
            if ($this->matchRoute($route, $requestUri, $requestMethod)) {
                $this->executeRoute($route, $requestUri);
                return;
            }
        }

        $this->handleNotFound();
    }

    private function matchRoute(array $route, string $requestUri, string $requestMethod): bool {
        return $route['method'] === $requestMethod && 
               $this->pathMatches($route['path'], $requestUri);
    }

    private function pathMatches(string $routePath, string $requestUri): bool {
        $pattern = preg_replace('/\{[^}]+\}/', '([^/]+)', $routePath);
        $pattern = str_replace('/', '\/', $pattern);
        return preg_match('/^' . $pattern . '$/', $requestUri);
    }

    private function executeRoute(array $route, string $requestUri): void {
        $controllerClass = $route['controller'];
        
        if (!class_exists($controllerClass)) {
            throw new Exception("Controller {$controllerClass} not found");
        }

        $controller = new $controllerClass();
        $method = $route['action'];

        if (!method_exists($controller, $method)) {
            throw new Exception("Method {$method} not found in {$controllerClass}");
        }

        $params = $this->extractParams($route['path'], $requestUri);
        call_user_func_array([$controller, $method], $params);
    }

    private function extractParams(string $routePath, string $requestUri): array {
        preg_match_all('/\{([^}]+)\}/', $routePath, $paramNames);
        $pattern = preg_replace('/\{[^}]+\}/', '([^/]+)', $routePath);
        $pattern = str_replace('/', '\/', $pattern);
        
        preg_match('/^' . $pattern . '$/', $requestUri, $matches);
        array_shift($matches);
        
        return $matches;
    }

    private function handleNotFound(): void {
        http_response_code(404);
        include APP_PATH . '/Views/errors/404.php';
    }
}
