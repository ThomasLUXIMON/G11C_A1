<?php
class View {
    private string $viewsPath;
    private array $data = [];

    public function __construct() {
        $this->viewsPath = APP_PATH . '/Views';
    }

    public function render(string $view, array $data = []): void {
        $this->data = array_merge($this->data, $data);
        extract($this->data);

        $viewFile = $this->getViewPath($view);

        if (!file_exists($viewFile)) {
            throw new Exception("View file not found: {$viewFile}");
        }

        if (strpos($view, 'layouts/') !== 0 && strpos($view, 'partials/') !== 0) {
            include $this->viewsPath . '/layouts/app.php';
        } else {
            include $viewFile;
        }
    }

    public function setGlobal(string $key, $value): void {
        $this->data[$key] = $value;
    }

    public function getViewPath(string $view): string {
        $basePath = $this->viewsPath . '/' . str_replace('.', '/', $view);
        if (file_exists($basePath . '.html')) {
            return $basePath . '.html';
        }
        return $basePath . '.php';
    }
}