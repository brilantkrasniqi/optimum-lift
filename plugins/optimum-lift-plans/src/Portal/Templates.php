<?php

/**
 * Template lookup. A theme overrides a template by copying it to
 * `optimum-lift-plans/<name>.php` inside the theme; it never stores Plan data.
 */

declare(strict_types=1);

namespace OptimumLift\Plans\Portal;

final class Templates
{
    /**
     * @param array<string, mixed> $vars Extracted into the template's scope.
     */
    public static function render(string $name, array $vars = []): void
    {
        $file = locate_template('optimum-lift-plans/' . $name . '.php');

        if ($file === '') {
            $file = \OptimumLift\Plans\DIR . '/templates/' . $name . '.php';
        }

        (static function (string $__file, array $__vars): void {
            extract($__vars, EXTR_SKIP);
            include $__file;
        })($file, $vars);
    }

    /**
     * @param array<string, mixed> $vars
     */
    public static function capture(string $name, array $vars = []): string
    {
        ob_start();
        self::render($name, $vars);

        return (string) ob_get_clean();
    }
}
