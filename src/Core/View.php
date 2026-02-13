<?php
declare(strict_types=1);

namespace App\Core;

final class View
{
    /** @var string[] Keys that must never be injected into template scope */
    private const RESERVED_KEYS = [
        'this', '_SERVER', '_GET', '_POST', '_COOKIE', '_FILES', '_ENV', '_REQUEST', '_SESSION',
        'GLOBALS', 'templatePath', 'layoutPath', 'data', 'template', 'layout', 'content',
    ];

    public static function render(string $template, array $data = [], string $layout = 'admin'): void
    {
        $templatePath = __DIR__ . '/../../views/' . $template . '.php';
        $layoutPath = __DIR__ . '/../../views/layouts/' . $layout . '.php';

        if (!file_exists($templatePath) || !file_exists($layoutPath)) {
            http_response_code(500);
            echo 'Template tidak ditemukan.';
            return;
        }

        $safeData = array_diff_key($data, array_flip(self::RESERVED_KEYS));
        extract($safeData, EXTR_SKIP);

        ob_start();
        require $templatePath;
        $content = (string) ob_get_clean();

        require $layoutPath;
    }

    public static function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
