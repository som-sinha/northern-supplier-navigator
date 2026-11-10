#!/usr/bin/env bash
#
# Quick checks to run after every release, on a laptop or a server.
#
#   scripts/smoke-test.sh                       # local DDEV site
#   BASE_URL=https://example.ca DRUSH="vendor/bin/drush" scripts/smoke-test.sh
#
# Exits with an error if any check fails.

BASE_URL="${BASE_URL:-https://gnwt-navigator.ddev.site}"
DRUSH="${DRUSH:-ddev drush}"
failures=0

check() {
  # check "description" "expected" "actual"
  if [ "$2" = "$3" ]; then
    echo "  ok    $1"
  else
    echo "  FAIL  $1 (expected: $2, got: $3)"
    failures=$((failures + 1))
  fi
}

status() {
  curl -sk -o /dev/null -w '%{http_code}' "$BASE_URL$1"
}

contains() {
  # Prints "yes" when the page at $1 contains the text $2.
  if curl -sk "$BASE_URL$1" | grep -q "$2"; then echo yes; else echo no; fi
}

echo "Public pages ($BASE_URL)"
check "home page loads" 200 "$(status /)"
check "home page lists the journeys" yes "$(contains / 'Become a bidder')"
check "a guide page loads" 200 "$(status /guides/register-as-gnwt-bidder)"
check "guide links to the official page" yes "$(contains /guides/register-as-gnwt-bidder 'Go to the official page')"
check "unknown page gives 404" 404 "$(status /this-page-does-not-exist)"

echo "Access rules for visitors"
check "admin area is blocked" 403 "$(status /admin)"
check "review dashboard is blocked" 403 "$(status /admin/content/guide-review)"
guide_path="$($DRUSH sql:query "SELECT path FROM path_alias WHERE alias='/guides/register-as-gnwt-bidder'" 2>/dev/null | tr -d '[:space:]')"
check "draft versions are blocked" 403 "$(status "$guide_path/latest")"
check "revision history is blocked" 403 "$(status "$guide_path/revisions")"
check "default Drupal front page is off" 404 "$(status /node)"

echo "Site state"
check "no pending database updates" yes \
  "$($DRUSH updatedb:status 2>&1 | grep -q 'No database updates required' && echo yes || echo no)"
check "settings match config/sync" yes \
  "$($DRUSH config:status 2>&1 | grep -q 'No differences' && echo yes || echo no)"
check "all 11 guides are published" 11 \
  "$($DRUSH sql:query "SELECT COUNT(*) FROM node_field_data WHERE type='service_guide' AND status=1" 2>/dev/null | tr -d '[:space:]')"
check "every import is complete" 0 \
  "$($DRUSH migrate:status --group=nsn --format=json 2>/dev/null | grep -o '"unprocessed": *[0-9]*' | grep -vc ': *0$')"

echo
if [ "$failures" -eq 0 ]; then
  echo "All checks passed."
else
  echo "$failures check(s) failed."
  exit 1
fi
