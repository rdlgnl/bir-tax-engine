# BuwisBuddy

A BIR tax app for Philippine freelancers and sole proprietors, built from the approved design in `design/`.

Laravel 13 · React 19 · TypeScript · Inertia 3 · Tailwind CSS 4 · shadcn/ui · SQLite

> **Tax rules are not yet reviewed.** Every rate, threshold and deadline is in the rule pack (`2026.11`): `config/tax.php` reads it as one from its parts in `resources/rule-pack/` (rates, deadlines, holidays, form editions, tax codes, file formats, citations). It was encoded from the NIRC as amended and the issuances in [docs/RULE-REVIEW.md](docs/RULE-REVIEW.md). Have a tax professional review each part, then set its `reviewed` under `parts`, and `reviewed` for the pack once all are. When you change the pack, bump `version` (and the part's) and describe the change under `changes`: the app shows it on its "what changed" screen.

## Run it

```bash
composer install && npm install
cp .env.example .env && php artisan key:generate
php artisan migrate
php artisan db:seed      # optional: Juan, the sample taxpayer from the design
composer run dev         # or: php artisan serve + npm run dev
```

The app is single-user and keeps no accounts: data stays in `database/database.sqlite` on this computer. Backups (`.bbk`, encrypted with a password you choose) go to `~/Documents/BuwisBuddy` unless you pick another folder under Your data; set `BACKUP_FOLDER` to change the default.

The one exception is the assistant (Ask), which is off by default. Turned on under Your data, it uses the taxpayer's own API key for Claude or an OpenAI-compatible API, and sends their question and the figures it looks up, never their name, TIN or address. Reading a BIR letter is the one case where the letter itself goes, their name and TIN on it, and only after they tick the box for that letter. The key is stored encrypted with `APP_KEY`, stays out of backups, and "Erase all data" removes it.

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

| Domain             | Owns                                                                                                                                                                                                                                                                                                                                                      |
| ------------------ | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `app/Profiles`     | The tax profile, the setup wizard, and registration updates (filled Form 1905)                                                                                                                                                                                                                                                                            |
| `app/Tax`          | The tax engine (`TaxCalculationService`), quarterly VAT (`VatCalculator`), due dates (`FilingCalendar`), the "why" explanation, My tax and What if, and the rule pack in use with what a new one changed                                                                                                                                                  |
| `app/Transactions` | Payments, invoices, expenses, 2307 tracking, the live tax preview, finding records (search, quick filters, export), importing payments from bank, e-wallet and payout statements (CSV), and importing marketplace payout reports (sales, fees, platform withholding)                                                                                      |
| `app/Compliance`   | What's due next, the quarter checklist, 2307 certificates (read from their PDF's text) and matching, kept up to date as payments change, the 2316 from an employer, the deadline calendar and its .ics export                                                                                                                                             |
| `app/Returns`      | Preparing a return (values to copy, filled form, PDF), marking it filed with its figures frozen, amending a filed 1701Q, paying late (surcharge and interest), history, the SAWT and SLSP with their BIR data files, and reports (tax summary, CSV exports, encrypted accountant ZIP)                                                                     |
| `app/Books`        | Books of accounts: the monthly cash receipts and disbursements journals, the general journal and ledger, their PDFs, which months are copied, and registering loose-leaf books (filled Form 1900)                                                                                                                                                         |
| `app/Data`         | Your data: encrypted backups to a folder on this computer, restoring one (also before setup, for a new device), the monthly reminder, erasing everything, and the app lock (PIN, first-run offer, lock screen)                                                                                                                                            |
| `app/Notices`      | Notifications: what needs attention now (worked out on the spot) and what happened (recorded as it happens)                                                                                                                                                                                                                                               |
| `app/Loans`        | Loans the taxpayer signed: the documentary stamp tax on each, when Form 2000 is due (unless a bank files it), and the filled form                                                                                                                                                                                                                         |
| `app/Payroll`      | Employees and the tax withheld on their pay (with the year-end adjustment), each month's 1601-C, each employee's 2316, and the 1604-C                                                                                                                                                                                                                     |
| `app/Letters`      | Letters from the BIR: what each one says, what the taxpayer's records show about it, what to send or bring, and the reply date on the calendar                                                                                                                                                                                                            |
| `app/Corporations` | A corporation the taxpayer runs: each quarter's figures from its books, the 1702Q at 20% or 25% against the minimum corporate income tax, the filled form, and the year's 1702-RT (estimated until Q4 is in)                                                                                                                                              |
| `app/Withholding`  | The taxpayer as a withholding agent: tax kept from fees and rent they pay, the monthly 0619-E, the quarterly 1601-EQ with its list of payees (QAP data file), the 2307 each payee is given, and the annual 1604-E with its alphalist                                                                                                                      |
| `app/Assistant`    | Ask about your taxes: the settings (provider, model, the taxpayer's own encrypted key), read-only lookups of the engine's figures and the rule pack (`TaxLookups`), Claude through Anthropic's SDK and OpenAI-compatible APIs behind one `Provider`, the conversation kept in the session, and reading a BIR letter into the letter form (`LetterReader`) |

`app/Http/Controllers/HomeController` draws on all of them, so it lives outside the domains.

Books prints the two cash journals and the general journal and ledger. The cash journals record money as it moves; the general journal follows the books' method for each year (on accrual books an invoice is income on its date, owed as accounts receivable until paid). The ledger's balances carry over from the first records in the app, each year's income and expenses closing into owner's capital; money held before the first record is added by hand.

## Official BIR forms

Downloaded returns and registration forms are the BIR's own PDFs, filled in: 1701Q, 1701A, 1701 and 2551Q (January 2018 editions), 1701-MS (August 2024), 2550Q (April 2024), 1905 and 1900 (October 2025), for a withholding agent 0619-E, 1604-E and 2307 (January 2018) and 1601-EQ (January 2019), Form 2000 for documentary stamp tax, and for an employer 1601-C and 1604-C (January 2018) and 2316 (September 2021). The annual return is the 1701-MS for the purely self-employed (the 1701A or 1701 stays accepted), and the 1701 for someone with a job as well (the 1701-MS stays accepted). Their salary, from the employer's 2316 (or the monthly taxable salary for twelve months until it's added), goes on the annual return with the business, and what the employer withheld is credited; the quarterly returns cover the business alone. The forms have no fillable fields, so `app/Returns/Forms/OfficialFormFiller` draws each value where its box sits on the page:

- `resources/forms/bir/originals/` — the PDFs exactly as downloaded from bir.gov.ph (sources and checksums in `manifest.json`)
- `resources/forms/bir/{form}-{edition}.pdf` — the same PDFs rewritten so the PDF library can read them
- `resources/forms/bir/{form}-{edition}.php` — the field map: where each item's box is, in points from the top-left of the page
- `app/Returns/Forms/Items/` — which value goes in which item, from the return's figures (or the profile, for 1905 and 1900, and the payments withheld on, for 0619-E, 1601-EQ, 1604-E and 2307)
- `resources/rule-pack/forms.php` → `forms` — the edition each form uses

When the BIR revises a form:

1. Add its URL to `scripts/bir-forms/fetch.sh` (the BIR Forms page lists the files; it needs `brew install qpdf`) and run it.
2. Copy the old field map to the new edition's name and adjust it. `php artisan forms:calibrate 1701Q --grid` fills every box with sample values over a coordinate grid, written to `storage/app/private/forms-calibration/`.
3. Point `resources/rule-pack/forms.php` → `forms` at the new template, update the item builder if item numbers moved, and run the tests.

Print the forms at actual size on 8.5 × 13 in (long bond) paper, except the 1900 and 2550Q, which are 8.5 × 14 in (legal), and the 0619-E, which is 8.5 × 11 in (letter).

## Packaging

A packaged build (the desktop app) runs on the taxpayer's own computer, so:

- Ship it with `APP_ENV=production` and `APP_DEBUG=false`. Error pages carry the app's shared data, the taxpayer's name and figures among it; the debug page would show far more. `config/app.php` defaults `debug` to off when `APP_DEBUG` isn't set.
- Set `APP_URL` to the address the app answers on: `RejectUnknownHosts` only answers `localhost`, `127.0.0.1`, `[::1]` and that host.
- Run `php artisan config:cache`; the rule pack in `resources/rule-pack/` is read into it.
- Keep the licence files of the dependencies listed in [docs/LICENSES.md](docs/LICENSES.md).

## Updating the rule pack

The rules are in `resources/rule-pack/`, one file per kind, read as one by `config/tax.php`.

1. Change the part, citing the issuance. A rule that changes on a date goes in `rates.php` → `dated` under that date, holding only what changed; earlier periods keep their rules, and filed returns keep the rule set they were filed on.
2. Bump the pack's `version` and the part's under `parts`, describe the change under `changes` (the app shows it on its "what changed" screen), and clear the part's `reviewed` sign-off: it's a new version to review.
3. Run the tests.
4. Once a tax professional has checked it, record their sign-off on the part and on each rule it cites (`'reviewed' => ['by' => …, 'on' => 'YYYY-MM-DD', 'source' => …]`). `php artisan rules:status` lists what's left; `--strict` fails until nothing is, for a release check.

Every December, add the next year's holidays to `holidays.php` from the proclamation (regular holidays and special non-working days, nationwide; the Eid holidays when they're declared). Until a year has a list the calendar moves deadlines off weekends only, and says so.
