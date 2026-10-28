# Northern Supplier Navigator

An independent portfolio prototype: a task-oriented Drupal application that helps
Yellowknife-area businesses find the right public GNWT supplier service and prepare
their next step. **Not an official or endorsed GNWT product.** It links to official
services and collects no personal, banking, bid or invoice information.

## Status

| Area | State |
|---|---|
| Drupal 11 on DDEV (Apache, PHP 8.4, MariaDB 10.11) | Done |
| Exported configuration in `config/sync/` | Done (Standard profile baseline) |
| Content model, navigator, editorial workflow | Planned |
| Review dashboard, migration, staging release | Planned |

## Stack

- Drupal 11.4 (`drupal/recommended-project`), Drush 13
- Apache + PHP-FPM 8.4, MariaDB 10.11
- Local environment: [DDEV](https://ddev.com) on Docker

## Local setup

Requirements: Docker Desktop (or OrbStack) and DDEV.

```bash
git clone <repo-url> gnwt-application
cd gnwt-application
ddev start
ddev composer install
ddev drush site:install --existing-config -y
ddev drush user:create portfolio-editor --mail=portfolio-editor@example.com --password="$(openssl rand -base64 24)"
ddev drush user:role:add content_editor portfolio-editor
ddev drush uli    # one-time admin login link
```

User accounts are content, not configuration, so `portfolio-editor` is created by
hand on each environment. The migration maps the fixture's `review_owner` value to
this account.

The site is served at <https://gnwt-navigator.ddev.site>.

## Repository layout

| Path | Contents | In Git |
|---|---|---|
| `.ddev/` | Local environment definition | Yes |
| `composer.json`, `composer.lock` | Dependencies and pinned versions | Yes |
| `config/sync/` | Exported site configuration | Yes |
| `web/modules/custom/`, `web/themes/custom/` | Project code | Yes |
| `web/core/`, `vendor/`, `*/contrib/` | Built by Composer | No |
| `web/sites/default/files/` | Uploads and generated assets | No |

## Configuration workflow

Change the site in the admin UI, then export and commit:

```bash
ddev drush config:export -y
git add config/sync && git commit
```

Apply committed configuration to a site with `ddev drush config:import -y`.

## Secrets

`settings.php` contains no secrets. Locally, DDEV writes database credentials and
the hash salt to `settings.ddev.php` (git-ignored). On a server, provide them through
environment variables (`DRUPAL_HASH_SALT`) or a git-ignored `settings.local.php`, and
generate a fresh salt per environment:

```bash
drush php:eval 'echo \Drupal\Component\Utility\Crypt::randomBytesBase64(55) . PHP_EOL;'
```
