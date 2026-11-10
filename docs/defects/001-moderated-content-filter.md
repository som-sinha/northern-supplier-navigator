# Defect 001: guides in review were missing from the "Moderated content" list

| | |
|---|---|
| Found | 2026-11-08, while building the review dashboard |
| Introduced in | commit `6a6418a` (guide review workflow) |
| Fixed in | commit `1d2f459` |
| Affected | Editors and publishers. Visitors were not affected |
| Regression test | `tests/src/ModerationStateConfigTest.php` |

## What went wrong

Drupal has a built-in list at Content → Moderated content that shows every page
with a change waiting for review. After the guide review workflow was added, a guide
in **Draft** or **In review** did not appear in that list. A publisher relying on it
would not have known anything was waiting.

Nothing showed an error. The list simply came back empty.

## Cause

Turning on Drupal's Content Moderation module creates a sample workflow called
"Editorial" and sets the Moderated content list to filter on its states
(`editorial-draft`, `editorial-archived`).

This project uses its own workflow, "Guide review", so the unused Editorial workflow
was deleted. The list kept its old filter. It was now looking for states that no
longer existed, so no guide could match.

Drupal did not warn about this because the list's settings do not record that they
depend on the workflow. Deleting the workflow therefore did not flag the list.

## Fix

The list's filters now use the Guide review states: `guide_review-draft`,
`guide_review-review` and `guide_review-archived`
(`config/sync/views.view.moderated_content.yml`).

## How it is kept from coming back

`tests/src/ModerationStateConfigTest.php` reads the exported settings and checks that
every list filtering on a review state names a state that exists in an exported
workflow. It runs with the other automated tests:

```bash
ddev exec vendor/bin/phpunit
```

Run against the settings from commit `6a6418a`, the test fails with:

```
views.view.moderated_content.yml filters on "editorial-draft", which is not a
state of any exported workflow.
```

## Lesson

Deleting or renaming something that other settings refer to by name needs a search
for those names first: `grep -r "editorial" config/sync` would have found this in
seconds.
