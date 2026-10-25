# Source inventory and migration fixture

**Portfolio fixture, not a GNWT extract.** These files were hand-curated on
2026-10-25 from public GNWT Finance, GNWT ITI and City of Yellowknife web pages.
The legacy-shaped format (free-text categories, pipe-separated lists, string IDs)
gives the Drupal Migrate API something realistic to transform. No tenders,
transactions, supplier accounts or personal data are included.

## Files and import order

Import in this order so references resolve:

| # | File | Rows | Becomes in Drupal | Depends on |
|---|---|---|---|---|
| 1 | `journeys.csv` | 4 | Taxonomy term: Journey | — |
| 2 | `organizations.csv` | 6 | Taxonomy term: Organization (hierarchical) | itself (`parent_id`) |
| 3 | `guides.csv` | 11 | Node: Service guide | journeys, organizations |
| 4 | `checklist_items.csv` | 18 | Paragraph or entity: Checklist item | guides |
| 5 | `resources.csv` | 5 | Entity: Resource (link + metadata) | guides |

Every row has a stable `*_id` that never changes or gets reused. The Migrate API
uses it as the source ID, which is what makes reruns update rather than duplicate.

## Journeys covered

| Journey | Guides |
|---|---|
| Become a bidder | GDE-001 register, GDE-002 sign-in problems, GDE-003 how to bid |
| Find procurement support | GDE-004 BIP, GDE-005 training, GDE-006 general questions, GDE-007 vendor complaints |
| Ask about a payment | GDE-008 submit an invoice, GDE-009 payment enquiry, GDE-010 direct deposit |
| Other governments | GDE-011 City of Yellowknife bids |

## Field map: `guides.csv` → Service guide

| Source column | Drupal field | Process |
|---|---|---|
| `source_id` | migrate map (source ID) | — |
| `title` | `title` | trim |
| `category` | `field_journey` (term ref) | **normalize**: look up legacy label in `journeys.csv:legacy_labels` (`Procurement Help` and `Training` both → `find-support`) |
| `audience` | `field_audience` (term ref) | create-or-match term |
| `summary` | `field_summary` (plain text) | trim |
| `steps` | `field_steps` (multi-value text, ordered) | **explode** on `\|` |
| `official_url` | `field_official_link` (link) | validate absolute https URL |
| `org_id` | `field_organization` (term ref) | `migration_lookup` → organizations |
| `contact_email` | `field_contact_email` (email) | skip if empty |
| `contact_phone` | `field_contact_phone` (telephone) | keep as published text |
| `language` | `langcode` | — |
| `source_url` | `field_source_url` (link) | — |
| `source_checked` | `field_last_reviewed` (date) | ISO date |
| `review_owner` | `field_review_owner` (user ref) | map to editor account |
| `next_review` | `field_next_review` (date) | ISO date |
| `related_ids` | `field_related_guides` (node ref) | explode on `\|`, `migration_lookup` → guides (second pass or stubs) |
| `notes` | `field_editor_notes` (internal only) | not shown to the public |

`checklist_items.csv` maps to `field_checklist` on the guide by `guide_id`, ordered by
`sequence`. `kind` is `prepare` or `eligibility`. `required` is `yes` only where the
source page states it. `unstated` means editors must confirm against the official
guide before changing it.

## Verification status

| Check | State |
|---|---|
| Every fact traced to `source_url` (or noted in `notes`) | Done, 2026-10-25 |
| Contacts copied exactly as published | Done |
| Staff names and personal phone numbers excluded | Done (GDE-004) |
| Resource file sizes | **Not verified.** Requests timed out; `size_bytes` left blank |
| Checklist `required` flags for GDE-001 | **Unstated** in source; confirm against the bidder registration PDF |
| GDE-007 owning unit | Inferred from the shared PSS phone line; confirm |
| French content | Only RES-005's official bilingual title. No translations written |

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
