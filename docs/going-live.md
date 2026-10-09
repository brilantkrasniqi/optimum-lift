# Going live: Hetzner and Cloudflare

How the live site is built and kept up to date. The short version:

- **The server** is a Hetzner Cloud server created from Hetzner's [WordPress app](https://docs.hetzner.com/cloud/apps/list/wordpress/): Ubuntu 24.04 with Apache, MySQL, PHP and WordPress ready to switch on. Hetzner installs it, but it is not managed hosting: the server is yours to keep updated (security updates run automatically, step 3 below).
- **The domain** stays on Cloudflare, which sits in front of the server: DNS, HTTPS and protection.
- **The database is built, never copied** (ADR-0015). WordPress's first-login setup creates your admin, and `wp ol-shop setup` creates every setting, page and attribute the code depends on. Products, Plans and the front page's sections you enter on the live site.
- **Code goes live from `main`.** Merging a pull request deploys the theme, the Plans plugin and the hardening mu-plugin (`.github/workflows/deploy.yml`, once switched on). Uploads, `wp-config.php` and the database are never deployed.

## 1. The domain (Cloudflare), part one

Do this before creating the server's certificate, so Let's Encrypt can reach the server directly.

In the Cloudflare dashboard, on your domain, **DNS › Records** (you get the IP addresses in section 2, step 1):

- `A`, name `@`, the server's IPv4, **DNS only** (grey cloud) for now.
- `AAAA`, name `@`, the server's IPv6, **DNS only** for now.
- `CNAME`, name `www`, target your domain, **DNS only** for now.

## 2. The server (Hetzner Cloud, WordPress app)

1. In the [Hetzner Cloud Console](https://console.hetzner.cloud), **Add Server**:
   - **Location:** Falkenstein, Nuremberg or Helsinki. All keep customer data in the EU.
   - **Image:** *Apps › WordPress*.
   - **Type:** at least **2 GB RAM**, 4 GB if the price is right. 1 GB is too little: MySQL, WooCommerce and the Plan PDF downloads each want a good share of it, and an out-of-memory server drops orders. Disk is no concern; the whole site with the Exercise library is under 1 GB.
   - **SSH key:** add your public key (on Windows: `ssh-keygen -t ed25519` in PowerShell, then paste the contents of `C:\Users\Work\.ssh\id_ed25519.pub`). Without one, Hetzner emails a root password instead.
   - **Backups:** on. Hetzner keeps 7 daily copies of the whole server for 20% of its price.
   - **Firewall:** create one with inbound **TCP 22, 80 and 443**. It is narrowed to Cloudflare in section 3.
   Put its IPv4 and IPv6 into the Cloudflare records from section 1.
2. **First login:** `ssh root@<ipv4>`. The app's setup starts and walks you through switching WordPress on:
   - **Domain:** your domain, without `www`.
   - **Admin username:** not `admin`, the domain or the shop's name: those are what login bots try first. Something like your name plus a word. The login never shows on the site (`mu-plugins/ol-hardening.php` hides it).
   - **Admin password:** let your password manager make a long one and save it there.
   - **Let's Encrypt:** yes. This is why the Cloudflare records are still grey.
   Generated passwords, such as the database's, are in `/root/.hcloud_password`.
3. **Keep it updated:** `apt update && apt -y full-upgrade`, then `apt -y install unattended-upgrades && dpkg-reconfigure -f noninteractive unattended-upgrades` so security updates install themselves.
4. **WP-CLI**, which runs `wp ol-shop setup`:
   ```sh
   curl -sSLo /usr/local/bin/wp https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
   chmod +x /usr/local/bin/wp
   ```
   Run it as the web server's user, from the WordPress folder (`/var/www/html` on the app; `ls` it to check): `sudo -u www-data wp ...`.
5. **wp-config.php**, so the site runs as production:
   ```sh
   cd /var/www/html
   sudo -u www-data wp config set WP_ENVIRONMENT_TYPE production
   sudo -u www-data wp config set WP_DEBUG false --raw
   sudo -u www-data wp config set DISALLOW_FILE_EDIT true --raw
   ```
   Never copy `wp-config.php` from your PC: the Docker one is for local only.
6. **A deploy user**, so GitHub never logs in as root (skip if you upload by hand):
   ```sh
   adduser --disabled-password --gecos "" deploy
   usermod -aG www-data deploy
   install -d -m 700 -o deploy -g deploy /home/deploy/.ssh
   chmod -R g+w /var/www/html/wp-content
   ```

## 3. The domain (Cloudflare), part two

Once `https://yourdomain` shows the site with a valid certificate:

1. **DNS › Records:** switch the three records to **Proxied** (orange cloud). Records for email (MX, and the SPF, DKIM and DMARC `TXT` records your sending service gives you) stay **DNS only**.
2. **SSL/TLS › Overview:** mode **Full (strict)**. Cloudflare then talks HTTPS to your server and checks its Let's Encrypt certificate. Certbot keeps renewing it through Cloudflare on its own.
3. **SSL/TLS › Edge Certificates:** **Always Use HTTPS** on, **Minimum TLS Version** 1.2. Leave HSTS off until the site has run on HTTPS for a few weeks.
4. **Rules › Redirect Rules:** "Redirect from WWW to root" template, so `www.` lands on the bare domain.
5. **Speed › Optimization:** **Rocket Loader off**. It rewrites how scripts load and breaks checkout and payment pages.
6. **Caching:** leave the defaults. Cloudflare caches images, CSS and JS, never pages, so the cart, checkout and My Account stay per person. Do not add a "Cache Everything" rule.
7. **Security › WAF:** the free managed rules are enough to start.
8. **Hetzner Firewall:** set the sources of ports 80 and 443 to the ranges listed at [cloudflare.com/ips](https://www.cloudflare.com/ips/), so nobody reaches the server around Cloudflare.

Then, on the server, so WordPress sees visitors' real addresses (for orders, logs and login limits) instead of Cloudflare's, trusting the header only from Cloudflare's own ranges:

```sh
a2enmod remoteip
{ echo "RemoteIPHeader CF-Connecting-IP"
  for ip in $(curl -s https://www.cloudflare.com/ips-v4) $(curl -s https://www.cloudflare.com/ips-v6); do
    echo "RemoteIPTrustedProxy $ip"
  done; } > /etc/apache2/conf-available/cloudflare-remoteip.conf
a2enconf cloudflare-remoteip && systemctl reload apache2
```

## 4. The code, then `wp ol-shop setup`

### Uploading the code

Pick one of the two; both copy the same three things and nothing else.

**Automatically, from GitHub (recommended).** Make a key just for deploys on your PC (`ssh-keygen -t ed25519 -f deploy_key -N ""`) and put `deploy_key.pub` into `/home/deploy/.ssh/authorized_keys` on the server. Then, in the repository's **Settings › Secrets and variables › Actions**:

- Secrets: `DEPLOY_SSH_KEY` (the contents of `deploy_key`) and `DEPLOY_KNOWN_HOSTS` (the output of `ssh-keyscan <server ipv4>`).
- Variables: `DEPLOY_HOST` (the server's IPv4), `DEPLOY_USER` = `deploy`, `DEPLOY_PATH` = `/var/www/html`, and `DEPLOY_ENABLED` = `true`.

Then **Actions › Deploy › Run workflow**. From then on every merge into `main` deploys.

**By hand, from your PC.** Build first (`npm run build`, and `npm run composer -- install` so the Plans plugin's `vendor/` exists), then copy over SFTP (WinSCP works):

| From the repo | To the server |
| --- | --- |
| `themes/optimum-lift/` | `wp-content/themes/optimum-lift/` |
| `plugins/optimum-lift-plans/` (with `vendor/`) | `wp-content/plugins/optimum-lift-plans/` |
| `mu-plugins/ol-hardening.php` | `wp-content/mu-plugins/` |

Never upload `mu-plugins/ol-dynamic-host.php` or `ol-local-mail.php` (local only), `uploads/`, or anything from `docker/`.

**ACF Pro** goes up by hand once, either way: your licensed copy into `wp-content/plugins/advanced-custom-fields-pro/`. Enter the license key in wp-admin afterwards so it gets updates.

### Plugins, theme and setup

In wp-admin: install and activate **WooCommerce** (*Plugins › Add New*; `plugins.txt` lists what the site runs), activate **ACF Pro** and **Optimum Lift Plans**, and activate the **Optimum Lift** theme. Then on the server:

```sh
cd /var/www/html
sudo -u www-data wp language core install sq
sudo -u www-data wp language plugin install woocommerce sq
sudo -u www-data wp ol-shop setup --email-from=info@yourdomain
sudo -u www-data wp ol-plans import-exercises
```

The last one imports the Exercise library (200 Exercises with images); *Training › Import* in wp-admin does the same.

`wp ol-shop setup` can run again whenever you like: pages and attributes are only created when missing, and published pages are never touched. It does put the store settings below back, so a tagline changed in wp-admin is reset.

### What `wp ol-shop setup` does

The same as the local seed's site settings (both use `themes/optimum-lift/inc/site-setup.php`), minus the demo data:

- Settings: tagline, timezone Europe/Belgrade, date format, euro with `.` thousands and `,` decimals after the price, store country Kosovo, Coming soon off, registration on My Account, the Albanian privacy texts at checkout, no terms checkbox (the withdrawal waiver is the one box), the email sender name and address, comments closed, permalinks `/%postname%/`, Site language Shqip.
- **Cash on delivery, bank transfer and cheque off.** An unpaid order must never grant a Plan.
- Pages: Dyqani, Shporta and Pagesa with the classic cart and checkout, Llogaria ime, the front page "Kreu", and the three legal pages **as drafts**, so no placeholder legal text goes public.
- Product categories (Programe stërvitjeje, Plane ushqimore, Paketa), the Objektivi attribute, and the Size attributes **Gjinia** and **Pesha** with their terms in order, so diets can be sold in Sizes right away.
- Deletes "Hello world!" and the sample pages.

It creates no Products, reviews, sales counts or coupons. Those were demo data locally.

## 5. After the setup

In wp-admin, logged in as your admin:

1. **Two-factor login.** Install the free *Two Factor* plugin (by WordPress.org contributors), then *Users › Profile*: turn on an authenticator app and save the backup codes. Add `two-factor` to `plugins.txt` in a pull request so the next setup has it too.
2. **Legal pages.** Paste the reviewed texts from the legal drafts into *Pages › Kushtet e shërbimit / Politika e privatësisë / Politika e kthimit* and Publish each. The footer and the checkout's withdrawal waiver link to them.
3. **Payments.** Install and set up the card gateway (Raiffeisen's plugin, once the merchant account exists), and add it to `plugins.txt`. Until then the shop cannot take money, and with the offline methods off nobody can order.
4. **Email.** Set up the sending service and WP Mail SMTP, add its SPF, DKIM and DMARC records in Cloudflare (DNS only), and place a test order. Customers log in only through emailed links, so this has to work before launch.
5. **Customizer › Optimum Lift:** contact email, WhatsApp, Instagram, TikTok, guarantee text, offer label and end date, customer baseline, payment badges. Leave the Meta Pixel ID empty until you run ads.
6. **Content.** Import Plans (*Training › Import Plan*, from `content/plans/`), create the Products with their Plans, Sizes and sections, and fill in the front page's sections.
7. **Store address** under *WooCommerce › Settings › General*.
8. **Search engines.** *Settings › Reading › Search engine visibility* stays unticked; turn it on temporarily only if you want to hide the site while you fill it in.

## 6. Updating the site later

- **Code:** merge into `main`. The workflow builds and copies it; nothing in the database changes. Run `wp ol-shop setup` again only when a change says so (a new setting, attribute or page).
- **WordPress, WooCommerce and plugins:** update from wp-admin, after a backup. A plugin version pinned in `plugins.txt` is the one to move first in a pull request.
- **Translations:** they ship inside the theme's and the plugin's `languages/` folders, so they deploy with the code.
- **Backups:** Hetzner's daily server backups cover everything. Before a big change, also take a database dump: `sudo -u www-data wp db export /tmp/backup-$(date +%F).sql`, then move it off the server.
- **Never** copy the local database up, or the live one down over your local site: the live one holds real customers' data.
