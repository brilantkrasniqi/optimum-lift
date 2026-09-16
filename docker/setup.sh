#!/bin/sh
set -eu

# A plugin whose files are incomplete fatals as soon as WordPress loads it, and
# WP-CLI loads active plugins too -- so every `wp` call below would die with the
# same fatal the browser shows. Repair work therefore runs through $WP, which
# skips plugins and themes and always boots. Only the health check and the
# WooCommerce configuration that follows it run with plugins loaded.
WP="wp --skip-plugins --skip-themes"

echo "Waiting for WordPress files..."
while [ ! -f /var/www/html/wp-includes/version.php ]; do
  sleep 2
done

echo "Waiting for database..."
i=0
while ! $WP db check >/dev/null 2>&1; do
  i=$((i + 1))
  if [ "$i" -gt 60 ]; then
    echo "Database never became ready."
    exit 1
  fi
  sleep 2
done

if ! $WP core is-installed >/dev/null 2>&1; then
  echo "Installing WordPress..."
  $WP core install \
    --url="http://localhost:8080" \
    --title="Optimum Lift" \
    --admin_user="admin" \
    --admin_password="admin" \
    --admin_email="dev@localhost.local" \
    --skip-email
else
  echo "WordPress already installed."
fi

# Install and activate everything first, then flush rewrites once at the end --
# WooCommerce registers its own rewrite rules and needs to be loaded for the
# flush to pick them up.
if [ -f /plugins.txt ]; then
  echo "Syncing plugins from plugins.txt..."
  # Strip comments and whitespace; each remaining line is "slug" or "slug:version".
  sed -e 's/#.*//' -e 's/[[:space:]]//g' /plugins.txt | grep . > /tmp/plugins.list || true

  while read -r entry; do
    slug="${entry%%:*}"
    version="${entry#*:}"
    [ "$version" = "$entry" ] && version=""

    if $WP plugin is-installed "$slug" >/dev/null 2>&1; then
      if [ -n "$version" ] && [ "$($WP plugin get "$slug" --field=version)" != "$version" ]; then
        echo "  $slug -> $version"
        $WP plugin update "$slug" --version="$version"
      fi
    else
      echo "  installing $slug${version:+ $version}"
      # shellcheck disable=SC2086
      $WP plugin install "$slug" ${version:+--version="$version"}
    fi

    $WP plugin activate "$slug" >/dev/null 2>&1 || true
  done < /tmp/plugins.list
fi

# First-party plugins live in this repo, not on wordpress.org, so plugins.txt
# cannot list them. Their Composer dependencies come from
# `npm run composer -- install`; without them the plugin still boots.
for slug in optimum-lift-plans; do
  $WP plugin activate "$slug" >/dev/null 2>&1 || true
done

if $WP theme is-installed optimum-lift >/dev/null 2>&1; then
  $WP theme activate optimum-lift >/dev/null 2>&1 || true
fi

# Health check. `plugin is-installed` only proves a plugin's header file is
# readable, so a half-extracted plugin reports its version quite happily and is
# skipped by the branch above; `plugin verify-checksums` does not help either,
# because it only checksums the files that are present and stays silent about
# the ones that are missing. Booting WordPress with plugins and the theme loaded
# is what actually proves the site works, and it catches any fatal, not just a
# truncated download.
echo "Checking the site boots..."
if wp eval 'echo "BOOT_OK";' >/tmp/boot.out 2>&1 && grep -q BOOT_OK /tmp/boot.out; then
  echo "  ok"
else
  echo "  WordPress could not boot with the active plugins:"
  sed 's/^/    /' /tmp/boot.out
  echo "  Reinstalling plugins from plugins.txt over the top..."

  while read -r entry; do
    slug="${entry%%:*}"
    version="${entry#*:}"
    [ "$version" = "$entry" ] && version=""
    # shellcheck disable=SC2086
    $WP plugin install "$slug" --force ${version:+--version="$version"}
  done < /tmp/plugins.list

  if wp eval 'echo "BOOT_OK";' >/tmp/boot.out 2>&1 && grep -q BOOT_OK /tmp/boot.out; then
    echo "  Repaired."
  else
    echo
    echo "STILL BROKEN. WordPress cannot load the active plugins:"
    sed 's/^/  /' /tmp/boot.out
    echo
    echo "The stack is up, but the site will show a critical error."
    exit 1
  fi
fi

wp rewrite structure "/%postname%/" --hard
wp option update permalink_structure "/%postname%/"
wp rewrite flush --hard

# Skip the WooCommerce setup wizard; create shop/cart/checkout/account pages.
wp option update woocommerce_onboarding_profile '{"skipped":true}' --format=json >/dev/null 2>&1 || true
wp wc tool run install_pages --user=admin >/dev/null 2>&1 || true

wp option update woocommerce_currency "EUR"
wp option update woocommerce_email_from_address "shop@localhost.local"
wp option update woocommerce_email_from_name "Optimum Lift"

echo
echo "Ready:"
echo "  Site    http://localhost:8080"
echo "  Admin   http://localhost:8080/wp-admin  (admin / admin)"
echo "  Mailpit http://localhost:8025"
echo
echo "WP-CLI: docker compose --profile cli run --rm wpcli plugin list"
