# BuwisBuddy

A BIR tax app for Philippine freelancers and sole proprietors, built from the approved design in `design/`.

Laravel 13 · React 19 · TypeScript · Inertia 3 · Tailwind CSS 4 · shadcn/ui · SQLite

> **Tax rules are not yet reviewed.** Every rate, threshold and deadline lives in `config/tax.php` (rule pack `2026.10`). It was encoded from the NIRC as amended by TRAIN and checked against the figures on the approved screens, but the design's "blueprint doc" was not available. Have a tax professional review it, then set `reviewed` to `true`.

## Run it

```bash
composer install && npm install
cp .env.example .env && php artisan key:generate
php artisan migrate
php artisan db:seed      # optional: Juan, the sample taxpayer from the design
composer run dev         # or: php artisan serve + npm run dev
```

The app is single-user and keeps no accounts: data stays in `database/database.sqlite` on this computer.

## Checks

```bash
php artisan test         # Pest
vendor/bin/phpstan analyse
vendor/bin/pint
npm run check            # lint + format
npm run types:check
```

## Layout

Business domains sit directly under `app/`, each with ordinary Laravel parts and its own `routes.php` (registered in `bootstrap/app.php`):

| Domain             | Owns                                                                                                                                                                                  |
| ------------------ | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `app/Profiles`     | The tax profile and the setup wizard                                                                                                                                                  |
| `app/Tax`          | The tax engine (`TaxCalculationService`), due dates (`FilingCalendar`), the "why" explanation, My tax and What if                                                                     |
| `app/Transactions` | Payments, invoices, expenses, 2307 tracking, the live tax preview                                                                                                                     |
| `app/Compliance`   | What's due next, the quarter checklist, 2307 certificates and matching, the deadline calendar and its .ics export                                                                     |
| `app/Returns`      | Preparing a return (values to copy, filled form, PDF), marking it filed with its figures frozen, history, and reports (tax summary, CSV exports, SAWT data, encrypted accountant ZIP) |
| `app/Books`        | Books of accounts: the monthly cash receipts and disbursements journals, their PDF, and which months are copied                                                                       |

`app/Http/Controllers/HomeController` draws on all of them, so it lives outside the domains.

## Official BIR forms

Downloaded returns are the BIR's own PDFs (1701Q, 1701A, 1701, 2551Q, January 2018 editions), filled in. The forms have no fillable fields, so `app/Returns/Forms/OfficialFormFiller` draws each value where its box sits on the page:

- `resources/forms/bir/originals/` — the PDFs exactly as downloaded from bir.gov.ph (sources and checksums in `manifest.json`)
- `resources/forms/bir/{form}-{edition}.pdf` — the same PDFs rewritten so the PDF library can read them
- `resources/forms/bir/{form}-{edition}.php` — the field map: where each item's box is, in points from the top-left of the page
- `app/Returns/Forms/Items/` — which value goes in which item, from the return's figures
- `config/tax.php` → `forms` — the edition each form uses

When the BIR revises a form:

1. Add its URL to `scripts/bir-forms/fetch.sh` (the BIR Forms page lists the files; it needs `brew install qpdf`) and run it.
2. Copy the old field map to the new edition's name and adjust it. `php artisan forms:calibrate 1701Q --grid` fills every box with sample values over a coordinate grid, written to `storage/app/private/forms-calibration/`.
3. Point `config/tax.php` → `forms` at the new template, update the item builder if item numbers moved, and run the tests.

Print the forms at actual size on 8.5 × 13 in (long bond) paper.
