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

| Domain             | Owns                                                                                                              |
| ------------------ | ----------------------------------------------------------------------------------------------------------------- |
| `app/Profiles`     | The tax profile and the setup wizard                                                                              |
| `app/Tax`          | The tax engine (`TaxCalculationService`), due dates (`FilingCalendar`), the "why" explanation, My tax and What if |
| `app/Transactions` | Payments, invoices, expenses, 2307 tracking, the live tax preview                                                 |
| `app/Compliance`   | Filed returns (which lock a quarter), what's due next, the quarter checklist                                      |

`app/Http/Controllers/HomeController` draws on all of them, so it lives outside the domains.
