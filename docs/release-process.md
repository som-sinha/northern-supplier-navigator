# Release process

How a change gets from a laptop to a running site, and how to undo it. Every release
follows the same steps, whether it is a security update or a new feature.

## 1. Prepare

| Step | Command | Why |
|---|---|---|
| Start from a clean copy | `git status` | Nothing half-finished goes into the release |
| Back up the database | `ddev snapshot --name=before-<version>` | A Git revert cannot undo a database change |
| List known security problems | `ddev composer audit` | Record what the release fixes |

## 2. Make the change

For an update to Drupal or its add-ons:

```bash
ddev composer update "drupal/core-*" -W     # or name the add-ons to update
ddev drush updatedb -y                      # apply database updates
ddev drush cache:rebuild
ddev drush config:export -y                 # save any setting changes as files
```

For a feature, make the change in the admin screens or the code, then run
`ddev drush config:export -y`.

## 3. Check

```bash
ddev exec vendor/bin/phpunit                # automated tests
ddev exec vendor/bin/phpcs                  # Drupal coding standards
ddev drush migrate:rollback --group=nsn     # the import still works from empty...
ddev drush migrate:import --group=nsn
ddev drush migrate:import --group=nsn       # ...and a second run adds nothing
scripts/smoke-test.sh                       # pages, access rules, site state
ddev composer audit                         # no known security problems left
```

Do not release if any of these fail.

## 4. Record

1. Write the release notes in `docs/releases/<version>.md`: what changed, why, what
   was checked, and how to roll back.
2. Commit, then tag the release: `git tag -a v<version> -m "<summary>"`.
3. Push the commit and the tag.

## 5. Deploy to a server

On the server, from the project folder:

```bash
git fetch --tags && git checkout v<version>
composer install --no-dev                   # exact versions from composer.lock
vendor/bin/drush deploy                     # database updates, settings import, cache rebuild
BASE_URL=https://<site> DRUSH=vendor/bin/drush scripts/smoke-test.sh
```

`drush deploy` runs the steps in a safe order: database updates first, then the
settings in `config/sync`, then a cache rebuild.

Take a database backup on the server before running these steps:
`vendor/bin/drush sql:dump --gzip --result-file=../backups/before-<version>.sql`.

## 6. Roll back

Use this when a release breaks something that cannot be fixed quickly.

1. Check out the previous release: `git checkout v<previous version>`.
2. Reinstall its exact dependencies: `composer install --no-dev`.
3. Restore the database backup taken in step 1 or step 5:
   - laptop: `ddev snapshot restore before-<version>`
   - server: `vendor/bin/drush sql:drop -y && gunzip -c ../backups/before-<version>.sql.gz | vendor/bin/drush sql:cli`
4. Rebuild the cache and run the smoke test.

**Limits to know about:**

- **A Git revert alone is not a rollback.** If a release ran database updates or
  imported settings, the database must be restored too.
- **Restoring the database loses edits made after the backup.** Editors' changes
  between the backup and the rollback have to be redone.
- **Uploaded files are not in the database.** This site has none yet. Once it does,
  back up `web/sites/default/files` together with the database.
- **The demo data can always be rebuilt.** `drush migrate:rollback --group=nsn`
  followed by `drush migrate:import --group=nsn` recreates all guides from the CSV
  files, but it discards edits made on the site.
