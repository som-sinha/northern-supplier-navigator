# Northern Supplier Navigator

A website that helps Yellowknife-area businesses find the right GNWT service for what
they want to do, such as registering to bid, getting procurement help or asking about
a payment, and tells them what to prepare. It links to the official government pages
and never asks visitors for personal, banking, bid or invoice information.

> **Disclaimer:** All data on this site was compiled from publicly available GNWT and
> City of Yellowknife information. It may contain errors and is for demonstration
> only. This is an independent portfolio project, not an official GNWT service.

## Progress

| Part | Status |
|---|---|
| Drupal site running locally | Done |
| Site settings saved in Git (`config/sync/`) | Done |
| Guide page structure (fields, categories, checklists) | Done |
| Demo data: 11 guides from public GNWT pages (`migrations/demo-data/`) | Done |
| Importing the demo data into the site | In progress |
| "What do you want to do?" page, editor approval steps | Planned |
| Dashboard showing guides due for review | Planned |
| Public test server, backup and restore test | Planned |

## Built with

- **Drupal 11**: the content management system the site is built on
- **Apache, PHP 8.4, MariaDB 10.11**: the web server, programming language and database (the "LAMP" stack)
- **DDEV and Docker**: run all of the above on a laptop without installing it directly
- **Composer and Drush**: Drupal's tools for installing add-ons and running commands

## Run it on your own computer

You need Docker Desktop (or OrbStack) and [DDEV](https://ddev.com).

```bash
git clone https://github.com/som-sinha/northern-supplier-navigator.git
cd northern-supplier-navigator
ddev start
ddev composer install
ddev drush site:install --existing-config -y
ddev drush user:create portfolio-editor --mail=portfolio-editor@example.com --password="$(openssl rand -base64 24)"
ddev drush user:role:add content_editor portfolio-editor
ddev drush uli    # prints a one-time admin login link
```

The site opens at <https://gnwt-navigator.ddev.site>.

User accounts are stored in the database, not in Git, so the `portfolio-editor`
account has to be created on each new copy of the site. Every guide in the demo data
names this account as the person responsible for reviewing it.

## What's in this repository

| Folder or file | What it holds | Saved in Git |
|---|---|---|
| `.ddev/` | Settings for the local environment | Yes |
| `composer.json`, `composer.lock` | The list of add-ons and their exact versions | Yes |
| `config/sync/` | The site's settings, saved as files | Yes |
| `migrations/demo-data/` | The demo data and notes on where it came from | Yes |
| `web/modules/custom/`, `web/themes/custom/` | Code written for this project | Yes |
| `web/core/`, `vendor/`, `*/contrib/` | Drupal and add-ons, downloaded by Composer | No |
| `web/sites/default/files/` | Uploaded files | No |

## Changing site settings

Make the change in the admin screens, then save it to files and commit:

```bash
ddev drush config:export -y
git add config/sync && git commit
```

To apply saved settings to another copy of the site, run `ddev drush config:import -y`.

## Passwords and secret keys

No passwords or secret keys are stored in Git. On a laptop, DDEV creates them in
`settings.ddev.php`, which Git ignores. On a server, set them in a `settings.local.php`
file (also ignored) or as environment variables, and create a new secret key
(`DRUPAL_HASH_SALT`) for each server:

```bash
drush php:eval 'echo \Drupal\Component\Utility\Crypt::randomBytesBase64(55) . PHP_EOL;'
```
