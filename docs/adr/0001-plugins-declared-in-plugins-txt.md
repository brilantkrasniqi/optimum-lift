# ADR-0001: Plugins are declared in plugins.txt, not installed by hand

**Status:** Accepted (2026-09-01)

## Context

The site needs WooCommerce and, in time, other plugins. Plugin code is vendor
code: it must not be committed (`.gitignore` already excludes `/plugins/*`), but
*which* plugins the site runs is a fact about the project that has to survive a
`docker compose down -v` and reach every machine.

Three options were considered:

1. **Install through wp-admin.** Not reproducible. The next person to bring the
   stack up gets a different site, and nothing records what changed.
2. **Hardcode `wp plugin install` calls in `docker/setup.sh`.** Reproducible, but
   mixes the list of plugins with the provisioning logic and pins nothing.
3. **Declare them in a data file the provisioning script reads.**

## Decision

Plugins are declared in `plugins.txt` at the repo root, one `slug` or
`slug:version` per line. `docker/setup.sh` reads it on every `docker compose up`
and installs, version-corrects, and activates each entry. The file is mounted
read-only into the `setup` and `wpcli` containers.

`docker-compose.yml` is not the place for this: it describes services, not site
state.

## Consequences

- Adding a plugin is a one-line diff, reviewable like any other change.
- The list only covers plugins published on wordpress.org, which have a slug.
  The first premium or private plugin forces a move to Composer + wpackagist
  (with `wp-content` path config and a lockfile). That migration replaces
  `plugins.txt` with `composer.json` and changes nothing else about the setup,
  which is why the cheaper option is acceptable now.
- Versions are optional. Leaving one unpinned means the installed version drifts
  with wordpress.org; pin anything whose behaviour the theme depends on.
