#!/bin/sh
#
# Builds the live site's database from nothing: installs WordPress with a
# named admin and a generated password, activates the plugins and the theme,
# installs the Albanian language packs, runs `wp ol-shop setup` and imports
# the Exercise library. The local database is never copied (docs/going-live.md).
#
# Run it on the server, in the WordPress folder, as the user PHP runs as:
#
#   ADMIN_USER=brilant-ol ADMIN_EMAIL=you@example.com \
#   SITE_URL=https://optimumlift.com EMAIL_FROM=info@optimumlift.com \
#   sh bootstrap-live.sh
#
# Before running it: wp-config.php exists with the database settings, and the
# theme, wp-content/plugins/optimum-lift-plans, ACF Pro and
# wp-content/mu-plugins/ol-hardening.php are uploaded.
#
# Safe to run again. WordPress is installed only once, so the admin and the
# password are created only on the first run. A later run fills in what is
# missing and re-applies the store settings `wp ol-shop setup` lists (tagline,
# timezone, price format...); pages, Products and Plans are never touched.
#
# The password is printed once, to this terminal only. Put it in your password
# manager; it is not saved anywhere else, and none of this goes into git.

set -eu

WP="${WP:-wp}"
here=$(cd "$(dirname "$0")" && pwd)

fail() {
  echo "Error: $*" >&2
  exit 1
}

: "${ADMIN_USER:?Set ADMIN_USER, the admin login. Not admin: pick something nobody would guess.}"
: "${ADMIN_EMAIL:?Set ADMIN_EMAIL, the admin's own email address.}"
: "${SITE_URL:?Set SITE_URL, e.g. https://optimumlift.com}"
EMAIL_FROM="${EMAIL_FROM:-}"

# Login names attackers try first. The login is also never shown on the site
# (mu-plugins/ol-hardening.php hides it from the REST API and author links).
user_lower=$(printf '%s' "$ADMIN_USER" | tr '[:upper:]' '[:lower:]')
host=$(printf '%s' "$SITE_URL" | sed -e 's#^[a-z]*://##' -e 's#[/:].*##' -e 's#^www\.##')
case "$user_lower" in
  admin | administrator | root | user | test | wordpress | wp | optimum | optimumlift | optimum-lift | shop | info)
    fail "ADMIN_USER \"$ADMIN_USER\" is one of the first names attackers try. Pick another." ;;
esac
[ "$user_lower" = "${host%%.*}" ] && fail "ADMIN_USER is the domain name; attackers try that too. Pick another."
[ "${#ADMIN_USER}" -ge 6 ] || fail "ADMIN_USER should be at least 6 characters."

case "$SITE_URL" in
  https://*) ;;
  *) fail "SITE_URL must start with https://" ;;
esac

command -v "${WP%% *}" >/dev/null 2>&1 || fail "WP-CLI (wp) is not installed. See https://wp-cli.org/#installing"
[ -f wp-config.php ] || [ -f ../wp-config.php ] || fail "No wp-config.php here. Run this in the WordPress folder, after creating it (docs/going-live.md)."

env_type=$($WP config get WP_ENVIRONMENT_TYPE --type=constant 2>/dev/null || echo "not set")
[ "$env_type" = "production" ] || fail "WP_ENVIRONMENT_TYPE is \"$env_type\". Set it to production in wp-config.php first."

$WP --skip-plugins --skip-themes db check >/dev/null || fail "WordPress cannot reach the database. Check DB_NAME, DB_USER, DB_PASSWORD and DB_HOST in wp-config.php."

for path in wp-content/themes/optimum-lift/style.css \
            wp-content/plugins/optimum-lift-plans/optimum-lift-plans.php \
            wp-content/plugins/optimum-lift-plans/vendor/autoload.php \
            wp-content/themes/optimum-lift/assets/dist/main.css \
            wp-content/mu-plugins/ol-hardening.php; do
  [ -f "$path" ] || fail "$path is missing. Upload the code first (docs/going-live.md, \"Uploading the code\")."
done
[ -f wp-content/mu-plugins/ol-dynamic-host.php ] && fail "wp-content/mu-plugins/ol-dynamic-host.php is for local only. Delete it from the server."
[ -f wp-content/mu-plugins/ol-local-mail.php ] && fail "wp-content/mu-plugins/ol-local-mail.php sends every email to Mailpit. Delete it from the server."

# --- WordPress and the admin --------------------------------------------------

if $WP --skip-plugins --skip-themes core is-installed >/dev/null 2>&1; then
  echo "WordPress is already installed; the admin and the password stay as they are."
else
  echo "Installing WordPress..."
  # 32 characters from the kernel's random source, letters and digits only so
  # nothing gets mangled when it is pasted.
  password=$(LC_ALL=C tr -dc 'A-Za-z0-9' </dev/urandom | head -c 32)
  [ "${#password}" -eq 32 ] || fail "Could not generate a password."

  # The password goes in on stdin, so it never shows in the process list.
  printf '%s\n' "$password" | $WP --skip-plugins --skip-themes core install \
    --url="$SITE_URL" \
    --title="Optimum Lift" \
    --admin_user="$ADMIN_USER" \
    --admin_email="$ADMIN_EMAIL" \
    --prompt=admin_password \
    --skip-email >/dev/null

  # The public name on anything the admin writes, instead of the login.
  $WP --skip-plugins --skip-themes user update "$ADMIN_USER" --display_name="Optimum Lift" --nickname="Optimum Lift" >/dev/null

  echo
  echo "  Admin login:    $ADMIN_USER"
  echo "  Admin password: $password"
  echo
  echo "  Shown this once. Save both in your password manager now."
  echo "  Log in at $SITE_URL/wp-admin and turn on two-factor login (docs/going-live.md)."
  echo
  unset password
fi

# Nobody registers as a WordPress user; customers get accounts from
# WooCommerce, as customers.
$WP --skip-plugins --skip-themes option update users_can_register 0 >/dev/null
$WP --skip-plugins --skip-themes option update default_role customer >/dev/null 2>&1 || true

# WordPress's sample plugins.
$WP --skip-plugins --skip-themes plugin delete hello akismet >/dev/null 2>&1 || true

# --- Plugins and the theme ----------------------------------------------------

# plugins.txt is the list of plugins the site runs (ADR-0001), the same file
# docker/setup.sh reads locally.
plugins_txt="$here/../plugins.txt"
[ -f "$plugins_txt" ] || plugins_txt="$here/plugins.txt"
[ -f "$plugins_txt" ] || fail "plugins.txt not found next to this script or in the repo root."

sed -e 's/#.*//' -e 's/[[:space:]]//g' "$plugins_txt" | grep . | while read -r entry; do
  slug="${entry%%:*}"
  version="${entry#*:}"
  [ "$version" = "$entry" ] && version=""

  if ! $WP --skip-plugins --skip-themes plugin is-installed "$slug" >/dev/null 2>&1; then
    echo "Installing $slug${version:+ $version}..."
    # shellcheck disable=SC2086
    $WP --skip-plugins --skip-themes plugin install "$slug" ${version:+--version="$version"} >/dev/null
  fi
  $WP --skip-plugins --skip-themes plugin is-active "$slug" >/dev/null 2>&1 \
    || $WP --skip-plugins --skip-themes plugin activate "$slug" >/dev/null
done

$WP --skip-plugins --skip-themes plugin is-installed advanced-custom-fields-pro >/dev/null 2>&1 \
  || fail "ACF Pro is not uploaded. Put your licensed copy in wp-content/plugins/advanced-custom-fields-pro and run this again."

for slug in advanced-custom-fields-pro optimum-lift-plans; do
  $WP plugin is-active "$slug" >/dev/null 2>&1 || $WP plugin activate "$slug" >/dev/null
done
$WP theme is-active optimum-lift >/dev/null 2>&1 || $WP theme activate optimum-lift >/dev/null

# The default themes stay: WordPress falls back to one if the theme breaks.

# --- Language -----------------------------------------------------------------

echo "Installing the Albanian language packs..."
$WP language core install sq >/dev/null 2>&1 || echo "  The Albanian WordPress pack did not install; the site stays English until it does."
$WP language plugin install woocommerce sq >/dev/null 2>&1 || echo "  The Albanian WooCommerce pack did not install."

# --- The site's own settings, pages and attributes ----------------------------

echo "Setting up the shop..."
if [ -n "$EMAIL_FROM" ]; then
  $WP ol-shop setup --email-from="$EMAIL_FROM"
else
  $WP ol-shop setup
fi

# The Plans plugin stays inactive while ACF Pro or WooCommerce is missing.
$WP help ol-plans >/dev/null 2>&1 \
  || fail "The Plans plugin did not load. Check that ACF Pro (not the free ACF) and WooCommerce are active: $WP plugin list"

echo "Importing the Exercise library..."
$WP ol-plans import-exercises

echo
echo "Done. What is left is in docs/going-live.md, \"After the bootstrap\"."
