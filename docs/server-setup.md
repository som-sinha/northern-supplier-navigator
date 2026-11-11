# Server setup

How the public site at <https://somsinha.net> is hosted. It records what was
installed and why, so the server can be rebuilt from this page.

## At a glance

| Part | Choice | Why |
|---|---|---|
| Host | DigitalOcean droplet, Toronto, 1 CPU, 2 GB memory | Small, always on, and in Canada |
| System | Ubuntu 24.04 LTS | Long-term security updates |
| Web server | Apache 2.4 with PHP-FPM | The same arrangement as the local DDEV setup |
| PHP | 8.3, from Ubuntu's packages | Patched by Ubuntu; Drupal 11 needs 8.3 or newer |
| Database | MariaDB 10.11, from Ubuntu's packages | Same version as local |
| In front | Cloudflare (DNS, HTTPS certificate, proxy) | Hides the server's address and serves the certificate |

The laptop runs PHP 8.4 and the server 8.3. Nothing in the project needs 8.4.

## Security

- **Firewall (ufw):** only SSH (22) and web traffic (80, 443) are allowed in. The
  database listens on the server itself only.
- **SSH:** keys only. Password sign-in is off, and fail2ban blocks repeated failures.
- **Updates:** Ubuntu installs security updates automatically (unattended-upgrades).
- **Accounts:** the site's code belongs to an unprivileged `deploy` account. The web
  server can write only to `web/sites/default/files`.
- **Secrets:** the database password and Drupal's secret key were generated on the
  server and exist only in `web/sites/default/settings.local.php` there (readable by
  `deploy` and the web server, not in Git).
- **Allowed addresses:** Drupal answers only to `somsinha.net` and `www.somsinha.net`
  (`trusted_host_patterns`).
- **Blocked scripts:** Apache refuses `/core/install.php` and `/core/rebuild.php`.
- **Headers:** HTTPS is enforced for a year (`Strict-Transport-Security`), and Apache
  does not show its version.

## HTTPS

Visitors connect to Cloudflare, which holds the public certificate. Cloudflare
connects to the server over HTTPS as well, using a certificate created on the server
(Cloudflare's "Full" mode). Plain HTTP requests and the `www` address are redirected
to `https://somsinha.net`.

**Limit:** the server's certificate is self-signed, so Cloudflare encrypts that
connection but does not verify who it is talking to. The stricter setup is a
Cloudflare Origin Certificate on the server with the mode set to "Full (strict)".

## Where things are

| Path | What |
|---|---|
| `/var/www/nsn` | The project, checked out at a release tag |
| `/var/www/nsn/web` | The folder Apache serves |
| `/var/www/nsn/web/sites/default/settings.local.php` | Server-only settings |
| `/var/www/nsn-backups` | Database backups (not served) |
| `/etc/apache2/sites-available/nsn.conf` | Apache site settings |
| `/etc/cron.d/nsn-drupal` | Runs Drupal's scheduled tasks every 30 minutes |
| `/var/log/apache2/nsn-*.log` | Access and error logs |

## First install

Done once, on 2026-11-11, with release 1.1.0:

```bash
# as root: packages
apt-get install apache2 mariadb-server php8.3-fpm php8.3-cli php8.3-mysql php8.3-gd \
  php8.3-xml php8.3-mbstring php8.3-curl php8.3-zip php8.3-opcache php8.3-intl \
  php8.3-apcu composer git unzip ufw fail2ban unattended-upgrades
a2enmod rewrite headers expires proxy_fcgi setenvif http2 ssl mpm_event
a2enconf php8.3-fpm

# as deploy: code and site
git clone https://github.com/som-sinha/northern-supplier-navigator.git /var/www/nsn
cd /var/www/nsn && git checkout v1.1.0
composer install --no-dev
# write settings.local.php (database, secret key, allowed addresses), then:
vendor/bin/drush site:install --existing-config -y
# create the two demo accounts as in the README, then:
vendor/bin/drush migrate:import --group=nsn
BASE_URL=https://somsinha.net DRUSH="vendor/bin/drush --uri=https://somsinha.net" scripts/smoke-test.sh
```

The smoke test passed 14 of 14 checks on the server.

## Later releases

Follow [release-process.md](release-process.md), section 5: back up the database,
check out the new tag, `composer install --no-dev`, `drush deploy`, smoke test.

## Not done yet

- A scheduled database backup, and a restore of it tested on a separate machine.
- "Full (strict)" HTTPS between Cloudflare and the server.
- Limiting web traffic to Cloudflare's addresses, so the server cannot be reached
  directly by its IP address.
