# Backup and restore

How the live site at <https://nsn.somsinha.net> is backed up, how to bring a backup
back, and the restore test that showed it works.

## At a glance

| Question | Answer |
|---|---|
| What is backed up | The whole database, and uploaded files if there are any |
| When | Every night at 09:30 UTC (02:30 in Yellowknife in winter, 03:30 in summer) |
| Where | `/var/www/nsn-backups` on the server, readable only by the `deploy` account |
| How long it is kept | 14 days, then deleted automatically |
| Script | [`scripts/backup.sh`](../scripts/backup.sh), installed on the server as `/usr/local/bin/nsn-backup` |
| Schedule | `/etc/cron.d/nsn-backup` (cron is Linux's built-in task scheduler) |
| Log | `/var/www/nsn-backups/backup.log` |

## What is in a backup

| File | What it holds |
|---|---|
| `nsn-db-<date>-<time>.sql.gz` | The database: guides, their past versions, categories, user accounts and the site settings, compressed |
| `nsn-files-<date>-<time>.tar.gz` | Uploaded files, made only when there are some. The site has none yet, so this file is not made |

Some things are left out on purpose:

- **Cache, session and log tables** are saved empty. Drupal refills caches by itself,
  and sessions only keep people signed in. Leaving them out also keeps sign-in
  sessions out of the backup.
- **Folders Drupal builds by itself** (`css`, `js`, `php` and `styles` inside the files
  folder) are left out of the files archive.
- **The code** is not backed up here. It is in Git, and the server is checked out at a
  release tag.
- **`settings.local.php`** (the database password and secret key) is not backed up.
  If the server is lost, new secrets are created on the new server.

A database backup holds user accounts, email addresses and password hashes (scrambled
passwords). The files are created readable by `deploy` only, and the folder is not
served by the web server.

## Changing the settings

The script reads four settings from environment variables. The defaults suit the
server.

| Setting | Default | What it is |
|---|---|---|
| `PROJECT_DIR` | `/var/www/nsn` | The project folder |
| `BACKUP_DIR` | `/var/www/nsn-backups` | Where backups go |
| `DRUSH` | `vendor/bin/drush --uri=https://nsn.somsinha.net` | The Drush command |
| `KEEP_DAYS` | `14` | Backups older than this many days are deleted |

Only files the script made (`nsn-db-*` and `nsn-files-*`) are deleted. Other files in
the folder, such as `before-1.2.0.sql.gz` from a release, are kept until someone
deletes them.

## Installing it on the server

Done once, on 2026-11-12, as root. The code checked out in `/var/www/nsn` was not
changed: the script was copied from the laptop.

```bash
# from the laptop
scp scripts/backup.sh root@<server>:/usr/local/bin/nsn-backup
# on the server
chown root:root /usr/local/bin/nsn-backup && chmod 755 /usr/local/bin/nsn-backup
```

`/etc/cron.d/nsn-backup` (owned by root, mode 644; cron ignores files that others can
change):

```
SHELL=/bin/bash
PATH=/usr/local/bin:/usr/bin:/bin
30 9 * * * deploy /usr/local/bin/nsn-backup >> /var/www/nsn-backups/backup.log 2>&1
```

When `scripts/backup.sh` changes in a release, copy it to the server again.

To take a backup by hand, for example before a release:

```bash
sudo -u deploy /usr/local/bin/nsn-backup
```

The first run by hand, on 2026-11-12 at 00:58 UTC, took about 1 second and made
`nsn-db-2026-11-12-0058.sql.gz` (712 KB). It made no files archive, because the site
has no uploaded files.

## How to restore

On a laptop with DDEV, or any copy of the project:

```bash
# 1. Get the newest backup from the server
scp root@<server>:/var/www/nsn-backups/nsn-db-<date>-<time>.sql.gz .

# 2. Load it. This replaces everything in the database.
ddev import-db --file=nsn-db-<date>-<time>.sql.gz
ddev drush cache:rebuild

# 3. Check it
ddev drush config:status
scripts/smoke-test.sh
```

On the server itself, take a fresh backup first, then:

```bash
cd /var/www/nsn
gunzip -c /var/www/nsn-backups/nsn-db-<date>-<time>.sql.gz | sudo -u deploy vendor/bin/drush --uri=https://nsn.somsinha.net sql:cli
sudo -u deploy vendor/bin/drush --uri=https://nsn.somsinha.net cache:rebuild
```

If there is a files archive, unpack it into `web/sites/default/files`:
`tar -xzf nsn-files-<date>-<time>.tar.gz -C web/sites/default/files`.

The code must be at the same release as the backup (or newer, followed by
`drush deploy`). A backup from a newer release loaded into older code can break.

## Restore test

Run on 2026-11-12 on the laptop, in a separate DDEV project so the normal local site
was not touched.

| Step | What was done | Time |
|---|---|---|
| 1 | Copied `nsn-db-2026-11-12-0058.sql.gz` (712 KB) from the server | not timed |
| 2 | Made a copy of the project (files in Git, plus new files not yet committed) and named its DDEV project `gnwt-restore` | not timed |
| 3 | `ddev start` | 14 seconds |
| 4 | `ddev composer install` | 18 seconds |
| 5 | `ddev import-db --file=nsn-db-2026-11-12-0058.sql.gz` | 7 seconds |
| 6 | `ddev drush cache:rebuild` | 3 seconds |
| 7 | Checks (below) | 13 seconds for the smoke test |

About 1 minute in all, from an empty folder to a working copy of the live site.

Results:

| Check | Result |
|---|---|
| Published service guides | 11 |
| `drush config:status` | "No differences between DB and sync directory." The live site runs release 1.2.0, and the site settings in `config/sync` have not changed since, so none were expected |
| `BASE_URL=https://gnwt-restore.ddev.site scripts/smoke-test.sh` | 14 of 14 checks passed |

Afterwards the `gnwt-restore` project was deleted (`ddev delete -Oy`), with its folder
and the copied backup file.

The restored copy ran on PHP 8.4 (the laptop's version), not 8.3 like the server.
Uploaded files were not part of the test, because there are none yet.

## Limits

- **The backups sit on the same server as the site.** If the server is lost or
  broken into, the backups go with it. Nothing is copied off the server yet. The next
  step is to copy each night's backup to storage somewhere else, such as DigitalOcean
  Spaces or another provider.
- **Only one restore has been tested,** by hand. Nothing checks automatically that
  each night's backup can be restored. The script does check that each database file
  is a complete compressed file.
- **A failed backup is only recorded in the log.** No one is emailed or alerted.
- **Up to a day of changes can be lost,** because the backup runs once a night.
- **The restore test did not cover uploaded files**, or a restore on the server
  itself.
