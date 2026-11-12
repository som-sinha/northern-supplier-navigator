#!/usr/bin/env bash
#
# Backs up the site: the database, plus uploaded files if there are any.
# Deletes backups older than two weeks. Made to run every night on the server
# (see docs/backup-and-restore.md), but it can also be run by hand.
#
#   scripts/backup.sh                                  # server defaults
#   BACKUP_DIR=~/nsn-backups KEEP_DAYS=7 scripts/backup.sh
#
# Settings (environment variables), with their defaults:
#   PROJECT_DIR  the project folder             /var/www/nsn
#   BACKUP_DIR   where backups are written      /var/www/nsn-backups
#   DRUSH        the Drush command to run       vendor/bin/drush --uri=https://nsn.somsinha.net
#   KEEP_DAYS    delete backups older than this 14
#
# Each run writes files named with the date and time (UTC), for example:
#   nsn-db-2026-11-12-0230.sql.gz       the database
#   nsn-files-2026-11-12-0230.tar.gz    uploaded files (only if there are some)
#
# Exits with an error if any step fails, so a failed backup is not silent.

# Stop at the first error, including errors inside a pipe.
set -euo pipefail

PROJECT_DIR="${PROJECT_DIR:-/var/www/nsn}"
BACKUP_DIR="${BACKUP_DIR:-/var/www/nsn-backups}"
DRUSH="${DRUSH:-vendor/bin/drush --uri=https://nsn.somsinha.net}"
KEEP_DAYS="${KEEP_DAYS:-14}"

# Backups hold user accounts and password hashes, so only the owner may read
# the new files.
umask 077

stamp="$(date -u +%Y-%m-%d-%H%M)"
db_file="$BACKUP_DIR/nsn-db-$stamp.sql"
files_file="$BACKUP_DIR/nsn-files-$stamp.tar.gz"

log() {
  echo "$(date -u '+%Y-%m-%d %H:%M:%S') nsn-backup: $*"
}

mkdir -p "$BACKUP_DIR"
cd "$PROJECT_DIR"

# 1. The database. Drush adds ".gz" to the file name. Cache, session and log
#    tables are saved empty ("common" list): they hold nothing worth keeping
#    and Drupal refills them.
log "dumping the database"
$DRUSH sql:dump --gzip --structure-tables-key=common --result-file="$db_file" >/dev/null
db_file="$db_file.gz"

# Check the file is a complete gzip file, not cut short.
gzip -t "$db_file"
log "database saved: $db_file ($(du -h "$db_file" | cut -f1))"

# 2. Uploaded files. The css, js, php and styles folders and .htaccess are
#    made by Drupal and can be rebuilt, so they are left out.
files_dir="web/sites/default/files"
excludes=(--exclude=./css --exclude=./js --exclude=./php --exclude=./styles --exclude=./.htaccess)
if [ -d "$files_dir" ] && [ -n "$(find "$files_dir" -type f \
    -not -path "$files_dir/css/*" -not -path "$files_dir/js/*" \
    -not -path "$files_dir/php/*" -not -path "$files_dir/styles/*" \
    -not -name .htaccess -print -quit)" ]; then
  tar -czf "$files_file" -C "$files_dir" "${excludes[@]}" .
  log "uploaded files saved: $files_file ($(du -h "$files_file" | cut -f1))"
else
  log "no uploaded files, so no files archive"
fi

# 3. Delete this script's backups older than KEEP_DAYS days. Other files in
#    the folder, such as backups taken before a release, are left alone.
find "$BACKUP_DIR" -maxdepth 1 -type f \
  \( -name 'nsn-db-*.sql.gz' -o -name 'nsn-files-*.tar.gz' \) \
  -mmin +"$((KEEP_DAYS * 24 * 60))" -print -delete | while read -r old; do
  log "deleted old backup: $old"
done

log "done"
