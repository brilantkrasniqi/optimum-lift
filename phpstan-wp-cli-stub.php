<?php
/**
 * The WP_CLI methods the Plans plugin and the theme's seed command call.
 * php-stubs/wp-cli-stubs does not yet allow wordpress-stubs 7.x, so this
 * stands in for it.
 */

declare(strict_types=1);

class WP_CLI
{
    public static function add_command(string $name, callable|object|string $callable): bool
    {
        return true;
    }

    public static function success(string $message): void
    {
    }

    public static function error(string $message): never
    {
        exit(1);
    }
}
