# Plugin scaffold and tooling

Type: task
Status: resolved

## What to build

`plugins/optimum-lift-plans/`: the bootstrap file, a PSR-4 autoloader for `OptimumLift\Plans\`, a dependency check (WooCommerce + ACF Pro, admin notice if either is missing), and a schema-versioned `dbDelta` installer. Add a `composer.json` for runtime dependencies (Dompdf).

Wire it into the repo:
- `.gitignore`: track the plugin, ignore its `vendor/`.
- PHPCS and PHPStan: add the plugin to their paths; add `php-stubs/acf-pro-stubs`.
- `docker/setup.sh`: install the plugin's Composer dependencies and activate it.
- `docker/php.ini`: raise `max_input_vars` to 10000 (ADR-0003).
- `CLAUDE.md`: add the plugin commands.

## Acceptance criteria

- [ ] The plugin activates on `docker compose up` and creates the three tables.
- [ ] With ACF deactivated, the site still boots and shows the notice.
- [ ] `npm run lint:php` and `npm run analyse:php` pass and cover the plugin.
