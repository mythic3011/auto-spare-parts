<?php

/**
 * Template Renderer
 *
 * Simple template rendering system for reusable HTML components
 */
class TemplateRenderer
{
    private string $basePath;
    /**
     * @var array<string, mixed>
     */
    private array $data = [];

    public function __construct(string $basePath = '')
    {
        $this->basePath = $basePath ?: dirname(__DIR__) . DIRECTORY_SEPARATOR . 'templates';
    }

    /**
     * Set data for template
     *
     * @param array<string, mixed> $data
     */
    public function with(array $data): self
    {
        $this->data = array_merge($this->data, $data);
        return $this;
    }

    /**
     * Render a template file
     *
     * @param string $templateName Template name without extension
     * @return string Rendered HTML
     */
    public function render(string $templateName): string
    {
        $filePath = $this->basePath . DIRECTORY_SEPARATOR . $templateName . '.php';

        if (!file_exists($filePath)) {
            throw new RuntimeException("Template not found: {$templateName}");
        }

        // Extract data to make variables available
        extract($this->data, EXTR_SKIP);

        // Capture output
        ob_start();
        include $filePath;
        return ob_get_clean() ?: '';
    }

    /**
     * Render and echo template
     */
    public function display(string $templateName): void
    {
        echo $this->render($templateName);
    }

    /**
     * Escape HTML content
     */
    public static function escape(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}
