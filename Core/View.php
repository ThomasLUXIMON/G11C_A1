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

        // Support pour les vues HTML directes (pour login/register)
        $htmlFile = $this->viewsPath . '/' . $view . '.html';
        $phpFile = $this->viewsPath . '/' . str_replace('.', '/', $view) . '.php';

        if (file_exists($htmlFile)) {
            include $htmlFile;
            return;
        }

        if (!file_exists($phpFile)) {
            throw new Exception("View file not found: {$phpFile}");
        }

        // Inclure le layout si ce n'est pas une vue partielle
        if (strpos($view, 'layouts/') !== 0 && strpos($view, 'partials/') !== 0) {
            include $this->viewsPath . '/layouts/app.php';
        } else {
            include $phpFile;
        }
    }

    public function setGlobal(string $key, $value): void {
        $this->data[$key] = $value;
    }

    public function getViewPath(string $view): string {
        return $this->viewsPath . '/' . str_replace('.', '/', $view) . '.php';
    }
}