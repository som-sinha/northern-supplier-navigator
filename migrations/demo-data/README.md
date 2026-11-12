# Demo data

These spreadsheet files (CSV) hold the guides shown on the site. They were built on
2026-10-25 from the public pages listed under [Sources](#sources), and they contain
no tenders, payments, supplier accounts or personal information.

> **AI use:** An AI assistant (Claude, by Anthropic) collected the information from
> the source pages, compiled it into these files and organized it into categories.
> The checks below were also done with the assistant. Treat the official pages as
> the final word, and check any fact against its source before relying on it.

The site loads them through Drupal's import tool (the "Migrate API"), the same way a
real project would move content from an old website into a new one. To make that
realistic, the files are laid out like an export from an older system: categories
are plain text, and lists are packed into one cell separated by `|`.

## The files, in the order they are imported

Some files point to others, so they load in this order:

| # | File | Rows | What it becomes on the site | Needs first |
|---|---|---|---|---|
| 1 | `journeys.csv` | 4 | The four goals a visitor can pick, like "Become a bidder" | — |
| 2 | `organizations.csv` | 6 | The government offices responsible for each guide | — |
| 3 | `guides.csv` | 11 | The guide pages themselves | journeys, organizations |
| 4 | `checklist_items.csv` | 18 | "What to prepare" lists inside guides | guides |
| 5 | `resources.csv` | 5 | Official forms and PDFs linked from guides | guides |

Every row has a permanent ID, like `GDE-001`, that is never changed or reused. The
import tool remembers which page each ID became, so running the import again
updates existing pages instead of creating duplicates.

## Guides by goal

| Goal | Guides |
|---|---|
| Become a bidder | GDE-001 register, GDE-002 sign-in problems, GDE-003 how to bid |
| Find procurement support | GDE-004 Business Incentive Policy, GDE-005 training, GDE-006 general questions, GDE-007 complaints |
| Ask about a payment | GDE-008 submit an invoice, GDE-009 check on a payment, GDE-010 direct deposit |
| Other governments | GDE-011 City of Yellowknife bids |

## Where each column of `guides.csv` goes

| Column | Field on the guide page | What happens on import |
|---|---|---|
| `source_id` | (kept by the import tool) | Used to match the row to its page on later imports |
| `title` | Title | Copied |
| `category` | Goal (`field_journey`) | Old labels are translated: "Procurement Help" and "Training" both become "Find procurement support" |
| `audience` | (not imported) | Left out on purpose. See [Columns that are not imported](#columns-that-are-not-imported) |
| `summary` | Summary (`field_summary`) | Copied |
| `steps` | Steps (`field_steps`) | Split at each `\|` into separate steps |
| `official_url` | Official link (`field_official_link`) | Copied; must start with `https://` |
| `org_id` | Organization (`field_organization`) | Linked to the matching office from `organizations.csv` |
| `contact_email` | Contact email (`field_contact_email`) | Copied; skipped if blank |
| `contact_phone` | Contact phone (`field_contact_phone`) | Copied exactly as published |
| `language` | Language | Copied |
| `source_url` | Source page (`field_source_url`) | Copied; editors only |
| `source_checked` | Last reviewed (`field_last_reviewed`) | Copied as a date |
| `review_owner` | Review owner (`field_review_owner`) | Linked to the `portfolio-editor` account |
| `next_review` | Next review (`field_next_review`) | Copied as a date; editors only |
| `related_ids` | Related guides (`field_related_guides`) | Split at each `\|` and linked to those guides |
| `notes` | Editor notes (`field_editor_notes`) | Copied; never shown to the public |

### Columns that are not imported

`audience` stays in `guides.csv` because it is part of the old export, but it is not
brought into the new site. Its six labels ("Businesses", "NWT businesses", "Vendors",
"Suppliers" and others) overlap, and they mostly repeat the goal: every "Suppliers"
guide is already under "Ask about a payment". A second filter that gives the same
results would only confuse visitors. The one rule that matters, that Business
Incentive Policy support is only for NWT businesses, is already an eligibility item
in GDE-004's checklist.

### Checklist items

In `checklist_items.csv`, each row belongs to the guide named in `guide_id`, in the
order given by `sequence`. `kind` says whether the item is something to **prepare**
or an **eligibility** rule. `required` is `yes` only when the source page says so;
`unstated` means the page doesn't say, and an editor should check the official
guide before changing it.

## What has been checked

| Check | Status |
|---|---|
| Every fact traced to its source page (or explained in `notes`) | Done, 2026-10-25 |
| Contact details copied exactly as published | Done |
| Staff names and personal phone numbers left out | Done (GDE-004) |
| File sizes of the official forms | **Not checked.** The government site timed out, so sizes are blank |
| Which bidder registration items are required (GDE-001) | **Not stated** on the source page; check against the official PDF guide |
| Which office handles complaints (GDE-007) | Assumed from a shared phone number; needs confirming |
| French content | Only the official bilingual title of one form (RES-005). No translations were written |

## Test: a changed row updates its page

Checks that when a row in the old system's export changes, importing again updates
the existing guide page instead of making a second one. Run on the local site on
2026-11-12.

**1. Before.** Guide `GDE-005`:

| | Value |
|---|---|
| Node ID (the page's number in Drupal) | 82 |
| Title | Attend GNWT procurement training |
| Summary | Procurement Shared Services runs online webinars on contracting with the GNWT, using the Contract Event Opportunities website and completing Business Incentive Policy forms. |
| Guides on the site | 11 |

**2. Change the row.** The summary of `GDE-005` in `guides.csv` was changed to
"TEST CHANGE: Procurement Shared Services runs free online webinars on contracting
with the GNWT." Then:

```
$ ddev drush migrate:import nsn_guides --update
 [notice] Processed 11 items (0 created, 11 updated, 0 failed, 0 ignored) - done with 'nsn_guides'
```

`--update` tells the import tool to load every row again, not only new ones.

**3. After.**

| Check | Result |
|---|---|
| Summary of node 82 | The new "TEST CHANGE" text, in the database and on the page itself |
| Node ID of "Attend GNWT procurement training" | Still 82 |
| Guides on the site | Still 11, all published. No duplicate |
| Related guides, checklist items and forms | Unchanged (18 checklist items, 5 forms) |

**4. Put it back.** `guides.csv` was restored from Git, and
`git diff --stat migrations/demo-data/guides.csv` printed nothing (no change). The same
import was run again (11 updated, 0 created), and node 82 had its original summary
again.

### Limits

- **`--update` overwrites edits made on the site.** If an editor changes a guide in the
  admin screens, the next `--update` puts back what the CSV file says, without a
  warning. Run it only while the CSV files are still the source of truth, before
  editors start working on the site. After that, edit guides on the site only.
- **No new version is saved.** The import changes the guide's current version in
  place (node 82 kept its single version, number 87), so the guide's history does not
  show the change, and it skips the approval steps editors go through.
- **A row deleted from the CSV file is not deleted from the site.** The import only
  adds and updates.
- Only the summary of one guide was changed in this test.

## Sources

- [Current Opportunities and Awarded Contracts](https://www.fin.gov.nt.ca/en/services/6-current-opportunities-and-awarded-contracts)
- [Procurement Shared Services](https://www.fin.gov.nt.ca/en/services/1-procurement-shared-services)
- [Frequently asked questions](https://www.fin.gov.nt.ca/en/services/3-help-and-resources/frequently-asked-questions)
- [Training sessions](https://www.fin.gov.nt.ca/en/procurementtraining)
- [Submitting invoices to the GNWT](https://www.fin.gov.nt.ca/en/services/4-help-and-resources/submitting-invoices-gnwt)
- [Direct deposit](https://www.fin.gov.nt.ca/en/services/direct-deposit)
- [Vendor complaint process](https://www.fin.gov.nt.ca/en/services/3-aide/vendor-complaint-process)
- [Financial and Employee Shared Services](https://www.fin.gov.nt.ca/en/financial-and-employee-shared-services)
- [Business Incentive Policy (ITI)](https://www.iti.gov.nt.ca/en/services/business-incentive-policy)
- [City of Yellowknife bids and tenders](https://www.yellowknife.ca/bidding-opportunities/bids-and-tenders)
