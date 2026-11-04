# BuwisBuddy

A BIR tax app for Philippine freelancers and sole proprietors: a desktop app for macOS, Windows and Linux that keeps their records on their own computer, works out their taxes and fills the BIR's own forms. Built from the approved design in `design/`.

Laravel 13 · React 19 · TypeScript · Inertia 3 · Tailwind CSS 4 · shadcn/ui · SQLite

> **Tax rules are not reviewed by a tax professional.** BuwisBuddy is open source and was released without a CPA review: the rules were encoded from the NIRC as amended and the issuances in [docs/RULE-REVIEW.md](docs/RULE-REVIEW.md), and mistakes are reported and fixed in the open. The app says so beside the rules, with a "Report a mistake" link to the issue tracker. Every rate, threshold and deadline is in the rule pack (`2026.11`): `config/tax.php` reads it as one from its parts in `resources/rule-pack/` (rates, deadlines, holidays, form editions, tax codes, file formats, citations). A part or rule counts as reviewed only when a tax professional has signed it off (see "Updating the rule pack"). [CONTRIBUTING.md](CONTRIBUTING.md) says how to help; the code is under the [MIT License](LICENSE).

In `docs/`:

- [RULE-REVIEW.md](docs/RULE-REVIEW.md): every rule as the app applies it, for a CPA or tax lawyer to check.
- [REFERENCE-CASES.md](docs/REFERENCE-CASES.md): ten typical taxpayers and the returns the app prepares for them, line by line; generated from the test that checks them (see "Checks").
- [REVIEW-TASKS.md](docs/REVIEW-TASKS.md): the November 2026 review's fix list, all closed.
- [LICENSES.md](docs/LICENSES.md): what the app and its desktop build ship with, and under what terms.
- `research/`: the notes behind the rules, with their sources.

## Run it

```bash
composer install && npm install
cp .env.example .env && php artisan key:generate
php artisan migrate
php artisan db:seed      # optional: Juan, the sample taxpayer from the design
composer run dev         # or: php artisan serve + npm run dev
```

The app is single-user and keeps no accounts: data stays in `database/database.sqlite` on this computer. Backups (`.bbk`, encrypted with a password you choose) go to `~/Documents/BuwisBuddy` unless you pick another folder under Your data; set `BACKUP_FOLDER` to change the default.

The one exception is the assistant (Ask), which is off by default. Turned on under Your data, it uses the taxpayer's own API key for Claude or an OpenAI-compatible API, and sends their question and the figures it looks up, never their name, TIN or address. Reading a BIR letter is the one case where the letter itself goes, their name and TIN on it, and only after they tick the box for that letter. The key is stored encrypted (with `APP_KEY`, or in the desktop app with its own key: see "Desktop app"), stays out of backups, and "Erase all data" removes it.

## Checks

```bash
composer ci:check        # all of the below, as CI runs them
php artisan test         # Pest
vendor/bin/phpstan analyse
vendor/bin/pint
npm run check            # lint + format
npm run types:check
```

- Feature tests render pages from the built frontend: after adding a page, run `npm run build` (CI does, in `composer setup`). A page missing from the build fails with "Not a valid Inertia response".
- If PHPStan stops at PHP's memory limit (Laravel Herd sets 128M), run it with `--memory-limit=1G`.
- `tests/Feature/Acceptance/ReferenceCasesTest.php` checks the returns in [docs/REFERENCE-CASES.md](docs/REFERENCE-CASES.md) line by line, and that the document matches. When a change moves a figure on purpose, update the case's expected figures and rewrite the document with `REFERENCE_CASES_WRITE=1 php artisan test --filter=ReferenceCases`.
- Every file name must work on Windows, where the installers are built too, or the checkout fails: no `< > : " \ | ? *` in a name, no trailing dot or space, no reserved name like `CON`. A test checks.

## Layout

Business domains sit directly under `app/`, each with ordinary Laravel parts and its own `routes.php` (registered in `bootstrap/app.php`):

| Domain             | Owns                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                           |
| ------------------ | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| `app/Profiles`     | The tax profile, the setup wizard, how the taxpayer files (eBIRForms, or eFPS and its group, from a month on), the BIR classification per year, and registration updates (filled Form 1905)                                                                                                                                                                                                                                                                                                                                    |
| `app/Tax`          | The tax engine (`TaxCalculationService`), quarterly VAT (`VatCalculator`), due dates (`FilingCalendar`, eFPS filers' withholding dates included), the "why" explanation, My tax and What if, and the rule pack in use with what a new one changed                                                                                                                                                                                                                                                                              |
| `app/Transactions` | Payments, invoices, expenses, 2307 tracking, the live tax preview, finding records (search, quick filters, export), importing payments from bank, e-wallet and payout statements (CSV), and importing marketplace payout reports (sales, fees, platform withholding)                                                                                                                                                                                                                                                           |
| `app/Compliance`   | What's due next, the quarter checklist, 2307 certificates (read from their PDF's text) and matching, kept up to date as payments change, the 2316 from an employer, the deadline calendar and its .ics export                                                                                                                                                                                                                                                                                                                  |
| `app/Returns`      | Preparing a return (values to copy, filled form, PDF), marking it filed with its figures frozen, amending a filed 1701Q, paying late (surcharge and interest), history, the SAWT and SLSP with their BIR data files, and reports (tax summary, CSV exports, encrypted accountant ZIP)                                                                                                                                                                                                                                          |
| `app/Books`        | Books of accounts: the monthly cash receipts and disbursements journals, the general journal and ledger, their PDFs, which months are copied, and registering loose-leaf books (filled Form 1900)                                                                                                                                                                                                                                                                                                                              |
| `app/Data`         | Your data: encrypted backups to a folder on this computer, restoring one (also before setup, for a new device), the monthly reminder, erasing everything, and the app lock (PIN, first-run offer, lock screen)                                                                                                                                                                                                                                                                                                                 |
| `app/Notices`      | Notifications: what needs attention now (worked out on the spot) and what happened (recorded as it happens)                                                                                                                                                                                                                                                                                                                                                                                                                    |
| `app/Loans`        | Loans the taxpayer signed: the documentary stamp tax on each, when Form 2000 is due (unless a bank files it), and the filled form                                                                                                                                                                                                                                                                                                                                                                                              |
| `app/Payroll`      | Employees and the tax withheld on their pay (with the year-end adjustment), each month's 1601-C, each employee's 2316, and the 1604-C                                                                                                                                                                                                                                                                                                                                                                                          |
| `app/Letters`      | Letters from the BIR: what each one says, what the taxpayer's records show about it, what to send or bring, and the reply date on the calendar                                                                                                                                                                                                                                                                                                                                                                                 |
| `app/Corporations` | A corporation the taxpayer runs: each quarter's figures from its books, the 1702Q at 20% or 25% against the minimum corporate income tax, the filled form, and the year's 1702-RT (estimated until Q4 is in)                                                                                                                                                                                                                                                                                                                   |
| `app/Withholding`  | The taxpayer as a withholding agent: tax kept from fees and rent they pay, the monthly 0619-E, the quarterly 1601-EQ with its list of payees (QAP data file), the 2307 each payee is given, and the annual 1604-E with its alphalist                                                                                                                                                                                                                                                                                           |
| `app/Assistant`    | Ask about your taxes: the settings (provider, model, the taxpayer's own encrypted key), read-only lookups of the engine's figures and the rule pack (`TaxLookups`), Claude through Anthropic's SDK and OpenAI-compatible APIs behind one `Provider`, the conversation kept in the session, reading a BIR letter into the letter form (`LetterReader`), and its suggestions (an email draft, a transaction to add, a profile answer to change), which change nothing until approved through the app's own forms (`Suggestions`) |

`app/Http/Controllers/HomeController` draws on all of them, so it lives outside the domains. So do the desktop app's own pieces: `app/Desktop/DeviceKey` (each install's encryption key), `app/Providers/NativeAppServiceProvider` (its window and PHP settings) and `app/Http/Controllers/ExternalLinkController` (opening links out in the computer's browser or email app).

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

## Desktop app

The app ships as a desktop app through [NativePHP for Desktop](https://nativephp.com/docs/desktop/2) (Electron with a bundled PHP 8.4), for macOS, Windows and Linux.

```bash
php artisan native:install   # once after composer install: Electron and its npm packages, in vendor/
composer native:dev          # the app in its own window, with Vite
php artisan native:build mac # or win, linux: the installer lands in nativephp/electron/dist
```

`composer update` reinstalls NativePHP's Electron packages itself (`post-update-cmd`). A build downloads Electron and the PHP binary for the target, runs `npm run build` first (`prebuild` in `config/nativephp.php`), and bundles your `.env` without the keys listed under `cleanup_env_keys`.

What the packaged app does differently (`AppServiceProvider`, `NativeAppServiceProvider`, `config/nativephp.php`):

- **Production, whatever the builder's `.env` says.** `APP_ENV`, `APP_DEBUG` and `BACKUP_FOLDER` are stripped from the bundle, so the app runs with the defaults: production, debug off, backups in `~/Documents/BuwisBuddy`. Error pages carry the taxpayer's data; the debug page would show far more.
- **Its own data folder.** NativePHP moves the database (its own SQLite file), sessions and stored files (certificates, letters) to the app's data folder and runs the migrations at each start.
- **Its own encryption key.** Each install makes a key on its first run (`App\Desktop\DeviceKey`, in the data folder) and uses it instead of the build's `APP_KEY`, so the assistant's API key and the session can't be opened with another copy of the app.
- **One window, kept to the app.** Links out (Report a mistake, email drafts) open in the computer's browser or email app through `POST /external`, which only opens the issue tracker and `mailto:` links. Only the app's own window can reach its server: NativePHP turns ordinary browser visits away, and `RejectUnknownHosts` answers `localhost` and `127.0.0.1` only.
- **PHP settings** for the PDFs and the assistant (memory, time, upload size) in `NativeAppServiceProvider::phpIni()`.

### Releases

Push a version tag and GitHub Actions builds the installers (`.github/workflows/release.yml`):

```bash
git tag v1.0.0
git push origin v1.0.0
```

It drafts a GitHub release for the tag with notes from the merged pull requests, builds on GitHub's runners for macOS (Apple silicon and Intel, `.dmg` and `.zip`), Windows (setup `.exe`) and Linux (`.AppImage` and `.deb`), with the app's version taken from the tag, and attaches each installer to the draft. Check the installers, then publish the release on GitHub. A pre-release tag like `v1.1.0-beta.1` builds the same way, a good first run for a new version. A failed build can be run again from the Actions tab ("release", Run workflow, with the tag).

Before a public release: sign the builds (unsigned ones are blocked by macOS Gatekeeper and warned about by Windows SmartScreen; NativePHP takes the Apple notarization and Azure Trusted Signing settings from `.env`), and keep the licence files of the dependencies listed in [docs/LICENSES.md](docs/LICENSES.md). Updates through GitHub releases are set up but off until `NATIVEPHP_UPDATER_ENABLED=true`. To sign in the release workflow, add those settings as repository secrets, write them into the build's `.env` in its Install step, and drop `CSC_IDENTITY_AUTO_DISCOVERY` from its Build step.

## Updating the rule pack

The rules are in `resources/rule-pack/`, one file per kind, read as one by `config/tax.php`.

1. Change the part, citing the issuance. A rule that changes on a date goes in `rates.php` → `dated` under that date, holding only what changed; earlier periods keep their rules, and filed returns keep the rule set they were filed on.
2. Bump the pack's `version` and the part's under `parts`, describe the change under `changes` (the app shows it on its "what changed" screen), and clear the part's `reviewed` sign-off: it's a new version to review.
3. Run the tests.
4. Once a tax professional has checked it, record their sign-off on the part and on each rule it cites (`'reviewed' => ['by' => …, 'on' => 'YYYY-MM-DD', 'source' => …]`). `php artisan rules:status` lists what's left; `--strict` fails until nothing is, for a release that requires a full review. Never record a sign-off that didn't happen: the app shows it to taxpayers as reviewed.

Every December, add the next year's holidays to `holidays.php` from the proclamation (regular holidays and special non-working days, nationwide; the Eid holidays when they're declared). Until a year has a list the calendar moves deadlines off weekends only, and says so.
