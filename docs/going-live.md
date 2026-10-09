# Going live: Hetzner and Cloudflare

How the live site is built and kept up to date. The short version:

- **The server** is a Hetzner Cloud machine running Ubuntu 24.04 with Apache, PHP 8.3 and MariaDB, the same pieces as the local Docker stack.
- **The domain** stays on Cloudflare, which sits in front of the server: DNS, HTTPS and protection.
- **The database is built, never copied** (ADR-0015). `deploy/bootstrap-live.sh` installs WordPress with your own admin login and a generated password, and `wp ol-shop setup` creates every setting, page and attribute the code depends on. Products, Plans and the front page's sections you enter on the live site.
- **Code goes live from `main`.** Merging a pull request deploys the theme, the Plans plugin and the hardening mu-plugin (`.github/workflows/deploy.yml`, once switched on). Uploads, `wp-config.php` and the database are never deployed.

Prices and plan names below were checked in October 2026; Hetzner raised Cloud prices in 2026, so check the current ones when ordering.

## 1. The server (Hetzner Cloud)

In the [Hetzner Cloud Console](https://console.hetzner.cloud):

1. Create a project, then **Add Server**:
   - **Location:** Falkenstein or Nuremberg (Germany) or Helsinki. Any of them keeps customer data in the EU.
   - **Image:** Ubuntu 24.04.
   - **Type:** shared vCPU, x86, with **4 GB RAM** (2 vCPU). WooCommerce runs on 2 GB, but the PDF downloads and the Exercise import are memory hungry, and 4 GB leaves room. You can resize later.
   - **SSH key:** add your public key (on Windows: `ssh-keygen -t ed25519` in PowerShell, then paste the contents of `C:\Users\Work\.ssh\id_ed25519.pub`). Do not use a root password.
   - **Backups:** on. Hetzner keeps 7 daily snapshots of the whole server for 20% of its price.
   - **Firewall:** create one with inbound **TCP 22** (SSH) and **TCP 80, 443** (web). Once Cloudflare works (section 2), set the sources of 80 and 443 to the ranges listed at [cloudflare.com/ips](https://www.cloudflare.com/ips/), so nobody can reach the server around Cloudflare.
2. Note the server's IPv4 and IPv6 addresses.

Then, logged in as root over SSH (`ssh root@<ipv4>`):

```sh
# Updates, and security updates from now on without you
apt update && apt -y full-upgrade
apt -y install unattended-upgrades && dpkg-reconfigure -f noninteractive unattended-upgrades

# The web server, PHP 8.3 (Ubuntu 24.04's own) and the database
apt -y install apache2 mariadb-server libapache2-mod-php8.3 \
  php8.3-mysql php8.3-curl php8.3-gd php8.3-intl php8.3-mbstring php8.3-xml php8.3-zip php8.3-bcmath php8.3-imagick \
  unzip rsync
a2enmod rewrite ssl headers remoteip

# WP-CLI
curl -sSLo /usr/local/bin/wp https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
chmod +x /usr/local/bin/wp

# A user for deploys, so nothing logs in as root
adduser --disabled-password --gecos "" deploy
usermod -aG www-data deploy
install -d -m 700 -o deploy -g deploy /home/deploy/.ssh
cp ~/.ssh/authorized_keys /home/deploy/.ssh/ && chown deploy:deploy /home/deploy/.ssh/authorized_keys

# No root login and no passwords over SSH
sed -i 's/^#\?PermitRootLogin.*/PermitRootLogin no/; s/^#\?PasswordAuthentication.*/PasswordAuthentication no/' /etc/ssh/sshd_config
systemctl reload ssh
```

Before closing the root session, give `deploy` sudo (`usermod -aG sudo deploy`, then `passwd deploy` for the password sudo asks for) and check `ssh deploy@<ipv4>` works from your PC. From here on, log in as `deploy`.

### The database

```sh
sudo mariadb-secure-installation        # answer yes to everything
DB_PASS=$(tr -dc 'A-Za-z0-9' </dev/urandom | head -c 32)
sudo mariadb -e "CREATE DATABASE optimumlift CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
  CREATE USER 'optimumlift'@'localhost' IDENTIFIED BY '$DB_PASS';
  GRANT ALL ON optimumlift.* TO 'optimumlift'@'localhost';"
echo "$DB_PASS"    # goes into wp-config.php below; keep it in your password manager too
```

### WordPress files and wp-config.php

The site's files belong to `www-data`, the user Apache runs PHP as, so wp-admin can update WordPress and plugins. The `deploy` user is in the `www-data` group and writes through it. WP-CLI always runs as `www-data`.

```sh
sudo install -d -o www-data -g www-data -m 2775 /var/www/optimumlift
# The bootstrap script lives next to the site, not in it.
sudo install -d -o deploy -g www-data -m 2775 /var/www/optimum-lift-deploy
cd /var/www/optimumlift
alias wpw='sudo -u www-data env WP_CLI_CACHE_DIR=/tmp/wp-cli-cache wp'
wpw core download --locale=en_US
wpw config create --dbname=optimumlift --dbuser=optimumlift --prompt=dbpass --extra-php <<'PHP'
define('WP_ENVIRONMENT_TYPE', 'production');
define('WP_DEBUG', false);
define('DISALLOW_FILE_EDIT', true);
define('FORCE_SSL_ADMIN', true);
// WordPress's own cron runs on page views; a real one (below) is reliable.
define('DISABLE_WP_CRON', true);
PHP
sudo chmod 640 wp-config.php
sudo -u www-data mkdir -p wp-content/mu-plugins
sudo find /var/www/optimumlift -type d -exec chmod 2775 {} +
```

`--prompt=dbpass` asks for the database password instead of putting it in your shell history. Never copy `wp-config.php` from your PC: the Docker one is for local only.

WP-Cron, every five minutes (scheduled sales, WooCommerce's background jobs, emails):

```sh
echo '*/5 * * * * www-data cd /var/www/optimumlift && WP_CLI_CACHE_DIR=/tmp/wp-cli-cache wp cron event run --due-now --quiet' | sudo tee /etc/cron.d/optimumlift
```

### Apache

Save as `/etc/apache2/sites-available/optimumlift.conf`, with your domain in place of `optimumlift.com`:

```apache
<VirtualHost *:80>
    ServerName optimumlift.com
    ServerAlias www.optimumlift.com
    Redirect permanent / https://optimumlift.com/
</VirtualHost>

<VirtualHost *:443>
    ServerName optimumlift.com
    ServerAlias www.optimumlift.com
    DocumentRoot /var/www/optimumlift

    SSLEngine on
    SSLCertificateFile    /etc/ssl/cloudflare/optimumlift.pem
    SSLCertificateKeyFile /etc/ssl/cloudflare/optimumlift.key

    <Directory /var/www/optimumlift>
        AllowOverride All
        Require all granted
    </Directory>

    # Nothing in uploads is a script.
    <Directory /var/www/optimumlift/wp-content/uploads>
        <FilesMatch "\.(php|phtml|phar)$">
            Require all denied
        </FilesMatch>
    </Directory>

    ErrorLog  ${APACHE_LOG_DIR}/optimumlift-error.log
    CustomLog ${APACHE_LOG_DIR}/optimumlift-access.log combined
</VirtualHost>
```

Apache is chosen over Nginx because it reads `.htaccess` files, and the site relies on two: WooCommerce's in `uploads/woocommerce_uploads/` (the diet PDFs) and the Plans plugin's in `uploads/ol-plans/` (the Plan PDF cache). Under Nginx those folders would be open to anyone who guesses a file name.

The certificate comes from Cloudflare in section 2. Visitors' real addresses (for orders, logs and login limits) instead of Cloudflare's, trusting the header only from [Cloudflare's own ranges](https://www.cloudflare.com/ips/):

```sh
{ echo "RemoteIPHeader CF-Connecting-IP"
  for ip in $(curl -s https://www.cloudflare.com/ips-v4) $(curl -s https://www.cloudflare.com/ips-v6); do
    echo "RemoteIPTrustedProxy $ip"
  done; } | sudo tee /etc/apache2/conf-available/cloudflare-remoteip.conf
sudo a2enconf cloudflare-remoteip
```

Then:

```sh
sudo a2ensite optimumlift && sudo a2dissite 000-default
sudo sed -i 's/^upload_max_filesize.*/upload_max_filesize = 64M/; s/^post_max_size.*/post_max_size = 64M/; s/^memory_limit.*/memory_limit = 256M/' /etc/php/8.3/apache2/php.ini
sudo apache2ctl configtest && sudo systemctl reload apache2
```

## 2. The domain (Cloudflare)

In the Cloudflare dashboard, on your domain:

1. **DNS › Records**
   - `A`, name `@`, the server's IPv4, **Proxied** (orange cloud).
   - `AAAA`, name `@`, the server's IPv6, **Proxied**.
   - `CNAME`, name `www`, target your domain, **Proxied**.
   - Records for email (MX, and the SPF, DKIM and DMARC `TXT` records your sending service gives you) are always **DNS only**. TXT records cannot be proxied anyway; an MX target must never be.
2. **SSL/TLS › Overview:** mode **Full (strict)**. Cloudflare then talks HTTPS to your server and checks its certificate.
3. **SSL/TLS › Origin Server › Create Certificate:** keep the defaults (your domain and `*.yourdomain`, 15 years). Paste the certificate into `/etc/ssl/cloudflare/optimumlift.pem` and the private key into `/etc/ssl/cloudflare/optimumlift.key` on the server (`sudo install -d -m 700 /etc/ssl/cloudflare` first, and `sudo chmod 600` the key). The key is shown once.
4. **SSL/TLS › Edge Certificates:** **Always Use HTTPS** on, **Minimum TLS Version** 1.2. Leave HSTS off until the site has run on HTTPS for a few weeks.
5. **Rules › Redirect Rules:** "Redirect from WWW to root" template, so `www.` lands on the bare domain.
6. **Speed › Optimization:** **Rocket Loader off**. It rewrites how scripts load and breaks checkout and payment pages.
7. **Caching:** leave the defaults. Cloudflare caches images, CSS and JS, never pages, so the cart, checkout and My Account stay per person. Do not add a "Cache Everything" rule.
8. **Security › WAF:** the free managed rules are enough to start.

Once `https://yourdomain` shows WordPress's install screen through Cloudflare, close ports 80 and 443 to everything but Cloudflare (section 1, Firewall).

## 3. The first deploy and the bootstrap

### Uploading the code

Pick one of the two; both copy the same four things and nothing else.

**Automatically, from GitHub (recommended).** In the repository's **Settings › Secrets and variables › Actions**:

- Secrets: `DEPLOY_SSH_KEY`, a new private key made only for deploys (`ssh-keygen -t ed25519 -f deploy_key -N ""`; put `deploy_key.pub` into `/home/deploy/.ssh/authorized_keys` on the server), and `DEPLOY_KNOWN_HOSTS`, the output of `ssh-keyscan <server ipv4>`.
- Variables: `DEPLOY_HOST` (the server's IPv4), `DEPLOY_USER` = `deploy`, `DEPLOY_PATH` = `/var/www/optimumlift`, and `DEPLOY_ENABLED` = `true`.

Then **Actions › Deploy › Run workflow**. From then on every merge into `main` deploys.

**By hand, from your PC.** Build first (`npm run build`, and `npm run composer -- install` so the Plans plugin's `vendor/` exists), then copy over SFTP (WinSCP works) into `/var/www/optimumlift/wp-content/`:

| From the repo | To the server |
| --- | --- |
| `themes/optimum-lift/` | `wp-content/themes/optimum-lift/` |
| `plugins/optimum-lift-plans/` (with `vendor/`) | `wp-content/plugins/optimum-lift-plans/` |
| `mu-plugins/ol-hardening.php` | `wp-content/mu-plugins/` |
| `deploy/bootstrap-live.sh`, `plugins.txt` | `/var/www/optimum-lift-deploy/` |

Never upload `mu-plugins/ol-dynamic-host.php` or `ol-local-mail.php` (local only; the bootstrap refuses to run with them), `uploads/`, or anything from `docker/`.

**ACF Pro** goes up by hand once, either way: your licensed copy into `wp-content/plugins/advanced-custom-fields-pro/`. Enter the license key in wp-admin afterwards so it gets updates.

### Running the bootstrap

```sh
cd /var/www/optimumlift
sudo -u www-data env WP_CLI_CACHE_DIR=/tmp/wp-cli-cache \
  ADMIN_USER=<your login> ADMIN_EMAIL=<your email> \
  SITE_URL=https://optimumlift.com EMAIL_FROM=info@optimumlift.com \
  sh /var/www/optimum-lift-deploy/bootstrap-live.sh
```

- `ADMIN_USER` is your login. Not `admin`, the domain, or the shop's name: the script refuses those, because they are what login bots try first. Something like your name plus a word works. The login never shows on the site; posts and pages show "Optimum Lift" as the author.
- The script makes a 32-character random password and **prints it once**. Save the login and the password in your password manager straight away; nothing else stores it, and nothing of it goes into git.
- It installs WooCommerce from `plugins.txt`, activates ACF Pro, the Plans plugin and the theme, installs the Albanian language packs, runs `wp ol-shop setup` and imports the Exercise library (200 Exercises with images).

Run it again whenever you like: it never reinstalls WordPress or touches the admin, and fills in only what is missing. It does put the store settings back (`wp help ol-shop setup` lists them), so a tagline changed in wp-admin is reset.

### What `wp ol-shop setup` creates

The same as the local seed's site settings (both use `themes/optimum-lift/inc/site-setup.php`), minus the demo data:

- Settings: tagline, timezone Europe/Belgrade, date format, euro with `.` thousands and `,` decimals after the price, store country Kosovo, Coming soon off, registration on My Account, the Albanian privacy texts at checkout, no terms checkbox (the withdrawal waiver is the one box), the email sender name and address, comments closed, permalinks `/%postname%/`, Site language Shqip.
- **Cash on delivery, bank transfer and cheque off.** An unpaid order must never grant a Plan.
- Pages: Dyqani, Shporta and Pagesa with the classic cart and checkout, Llogaria ime, the front page "Kreu", and the three legal pages **as drafts**, so no placeholder legal text goes public.
- Product categories (Programe stërvitjeje, Plane ushqimore, Paketa), the Objektivi attribute, and the Size attributes **Gjinia** and **Pesha** with their terms in order, so diets can be sold in Sizes right away.
- Deletes "Hello world!" and the sample pages.

It creates no Products, reviews, sales counts or coupons. Those were demo data locally.

## 4. After the bootstrap

In wp-admin, logged in with your new admin:

1. **Two-factor login.** Install the free *Two Factor* plugin (by WordPress.org contributors), then *Users › Profile*: turn on an authenticator app and save the backup codes. Add `two-factor` to `plugins.txt` in a pull request so the next setup has it too.
2. **Legal pages.** Paste the reviewed texts from the legal drafts into *Pages › Kushtet e shërbimit / Politika e privatësisë / Politika e kthimit* and Publish each. The footer and the checkout's withdrawal waiver link to them.
3. **Payments.** Install and set up the card gateway (Raiffeisen's plugin, once the merchant account exists), and add it to `plugins.txt`. Until then the shop cannot take money, and with the offline methods off nobody can order.
4. **Email.** Set up the sending service and WP Mail SMTP, add its SPF, DKIM and DMARC records in Cloudflare (DNS only), and place a test order. Customers log in only through emailed links, so this has to work before launch.
5. **Customizer › Optimum Lift:** contact email, WhatsApp, Instagram, TikTok, guarantee text, offer label and end date, customer baseline, payment badges. Leave the Meta Pixel ID empty until you run ads.
6. **Content.** Import Plans (*Training › Import Plan*, from `content/plans/`), create the Products with their Plans, Sizes and sections, and fill in the front page's sections.
7. **Store address** under *WooCommerce › Settings › General*.
8. **Search engines.** *Settings › Reading › Search engine visibility* stays unticked; turn it on temporarily only if you want to hide the site while you fill it in.

## 5. Updating the site later

- **Code:** merge into `main`. The workflow builds and copies it; nothing in the database changes. Run `wp ol-shop setup` again only when a change says so (a new setting, attribute or page).
- **WordPress, WooCommerce and plugins:** update from wp-admin, after a backup. A plugin version pinned in `plugins.txt` is the one to move first in a pull request.
- **Translations:** they ship inside the theme's and the plugin's `languages/` folders, so they deploy with the code.
- **Backups:** Hetzner's daily server backups cover everything. Before a big change, also take a database dump: `wpw db export /tmp/backup-$(date +%F).sql`, then move it off the server.
- **Never** copy the local database up, or the live one down over your local site: the live one holds real customers' data.
