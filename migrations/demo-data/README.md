# Demo data

These spreadsheet files (CSV) hold the guides shown on the site. They were compiled
by hand on 2026-10-25 from the public pages listed under [Sources](#sources), and
they contain no tenders, payments, supplier accounts or personal information.

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
| `audience` | Audience (`field_audience`) | Matched to an existing label, or a new one is created |
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
