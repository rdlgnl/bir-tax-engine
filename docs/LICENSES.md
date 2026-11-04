# Licences

BuwisBuddy itself is under the [MIT License](../LICENSE). This is what it ships with, and under what terms. Generated from `composer licenses --no-dev` and each runtime npm package's `package.json`; development tools aren't shipped and aren't listed.

## What needs attention

- **LGPL** (`dompdf/dompdf`, `dompdf/php-font-lib`, `dompdf/php-svg-lib`, `smalot/pdfparser`): used as libraries, unmodified. The app ships their PHP source as is, so anyone can replace them, which is what the LGPL asks of a combined work. Keep their licence files in the vendor folder of a packaged build, and note any change made to them.
- **Dual-licensed** (`nette/schema`, `nette/utils`: BSD-3-Clause or GPL-2.0/3.0): used under BSD-3-Clause.
- **BIR forms**: the PDF templates in `resources/forms/bir/` are the BIR's own forms, downloaded from bir.gov.ph and filled in place; they're government forms meant for the public to fill.
- **Fonts and icons**: Lucide icons (ISC). Any font bundled with a packaged build needs its licence alongside.
- **The desktop app** (NativePHP) bundles Electron (MIT) with Chromium (BSD-3-Clause and others; electron-builder ships its `LICENSES.chromium.html`) and a static PHP binary (PHP License 3.01, with the licences of the libraries compiled into it). Keep those files in the installer.

Everything else is MIT, BSD-3-Clause, Apache-2.0 or ISC: keep the copyright notices with a packaged build.

## PHP (Composer, runtime)

| Package                               | Version  | Licence                                  |
| ------------------------------------- | -------- | ---------------------------------------- |
| `anthropic-ai/sdk`                    | v1.4.0   | Apache-2.0                               |
| `barryvdh/laravel-dompdf`             | v3.1.2   | MIT                                      |
| `brick/math`                          | 1.0.0    | MIT                                      |
| `carbonphp/carbon-doctrine-types`     | 3.2.1    | MIT                                      |
| `dflydev/dot-access-data`             | v3.0.3   | MIT                                      |
| `doctrine/inflector`                  | 2.1.1    | MIT                                      |
| `doctrine/lexer`                      | 3.0.3    | MIT                                      |
| `dompdf/dompdf`                       | v3.1.6   | LGPL-2.1                                 |
| `dompdf/php-font-lib`                 | 1.0.2    | LGPL-2.1-or-later                        |
| `dompdf/php-svg-lib`                  | 1.0.2    | LGPL-3.0-or-later                        |
| `dragonmantank/cron-expression`       | v3.6.1   | MIT                                      |
| `egulias/email-validator`             | 4.0.4    | MIT                                      |
| `fruitcake/php-cors`                  | v1.4.0   | MIT                                      |
| `graham-campbell/result-type`         | v1.2.0   | MIT                                      |
| `guzzlehttp/guzzle`                   | 8.3.1    | MIT                                      |
| `guzzlehttp/promises`                 | 3.1.0    | MIT                                      |
| `guzzlehttp/psr7`                     | 3.2.0    | MIT                                      |
| `guzzlehttp/uri-template`             | v2.0.1   | MIT                                      |
| `inertiajs/inertia-laravel`           | v3.5.2   | MIT                                      |
| `laravel/framework`                   | v13.39.0 | MIT                                      |
| `laravel/prompts`                     | v0.3.26  | MIT                                      |
| `laravel/serializable-closure`        | v2.1.0   | MIT                                      |
| `laravel/tinker`                      | v3.0.2   | MIT                                      |
| `laravel/wayfinder`                   | v0.1.23  | MIT                                      |
| `league/commonmark`                   | 2.10.4   | BSD-3-Clause                             |
| `league/config`                       | v1.2.0   | BSD-3-Clause                             |
| `league/flysystem`                    | 3.36.0   | MIT                                      |
| `league/flysystem-local`              | 3.35.3   | MIT                                      |
| `league/mime-type-detection`          | 1.17.0   | MIT                                      |
| `league/uri`                          | 7.9.1    | MIT                                      |
| `league/uri-interfaces`               | 7.9.1    | MIT                                      |
| `masterminds/html5`                   | 2.12.0   | MIT                                      |
| `monolog/monolog`                     | 3.12.1   | MIT                                      |
| `nativephp/desktop`                   | 2.4.0    | MIT                                      |
| `nesbot/carbon`                       | 3.15.1   | MIT                                      |
| `nette/schema`                        | v1.4.0   | BSD-3-Clause, GPL-2.0-only, GPL-3.0-only |
| `nette/utils`                         | v4.1.6   | BSD-3-Clause, GPL-2.0-only, GPL-3.0-only |
| `nikic/php-parser`                    | v5.9.0   | BSD-3-Clause                             |
| `nunomaduro/termwind`                 | v2.4.0   | MIT                                      |
| `open-telemetry/api`                  | 1.10.1   | Apache-2.0                               |
| `open-telemetry/context`              | 1.5.0    | Apache-2.0                               |
| `php-http/discovery`                  | 1.20.0   | MIT                                      |
| `phpoption/phpoption`                 | 1.10.0   | Apache-2.0                               |
| `phpstan/phpdoc-parser`               | 2.4.0    | MIT                                      |
| `psr/clock`                           | 1.0.0    | MIT                                      |
| `psr/container`                       | 2.0.2    | MIT                                      |
| `psr/event-dispatcher`                | 1.0.0    | MIT                                      |
| `psr/http-client`                     | 1.0.3    | MIT                                      |
| `psr/http-factory`                    | 1.1.0    | MIT                                      |
| `psr/http-message`                    | 2.0      | MIT                                      |
| `psr/log`                             | 3.0.2    | MIT                                      |
| `psr/simple-cache`                    | 3.0.0    | MIT                                      |
| `psy/psysh`                           | v0.12.24 | MIT                                      |
| `ramsey/collection`                   | 2.1.1    | MIT                                      |
| `ramsey/uuid`                         | 4.9.4    | MIT                                      |
| `sabberworm/php-css-parser`           | v9.5.0   | MIT                                      |
| `setasign/fpdf`                       | 1.9.0    | MIT                                      |
| `setasign/fpdi`                       | v2.6.9   | MIT                                      |
| `smalot/pdfparser`                    | v2.13.1  | LGPL-3.0                                 |
| `spatie/laravel-package-tools`        | 1.93.3   | MIT                                      |
| `standard-webhooks/standard-webhooks` | v1.0.2   | MIT                                      |
| `symfony/clock`                       | v8.1.9   | MIT                                      |
| `symfony/console`                     | v8.1.10  | MIT                                      |
| `symfony/css-selector`                | v8.1.10  | MIT                                      |
| `symfony/deprecation-contracts`       | v3.7.1   | MIT                                      |
| `symfony/error-handler`               | v8.1.10  | MIT                                      |
| `symfony/event-dispatcher`            | v8.1.5   | MIT                                      |
| `symfony/event-dispatcher-contracts`  | v3.7.1   | MIT                                      |
| `symfony/filesystem`                  | v8.1.11  | MIT                                      |
| `symfony/finder`                      | v8.1.10  | MIT                                      |
| `symfony/http-foundation`             | v8.1.10  | MIT                                      |
| `symfony/http-kernel`                 | v8.1.10  | MIT                                      |
| `symfony/mailer`                      | v8.1.10  | MIT                                      |
| `symfony/mime`                        | v8.1.10  | MIT                                      |
| `symfony/polyfill-ctype`              | v1.37.0  | MIT                                      |
| `symfony/polyfill-intl-grapheme`      | v1.43.3  | MIT                                      |
| `symfony/polyfill-intl-idn`           | v1.43.0  | MIT                                      |
| `symfony/polyfill-intl-normalizer`    | v1.43.0  | MIT                                      |
| `symfony/polyfill-mbstring`           | v1.43.3  | MIT                                      |
| `symfony/polyfill-php80`              | v1.43.0  | MIT                                      |
| `symfony/polyfill-php82`              | v1.43.0  | MIT                                      |
| `symfony/polyfill-php84`              | v1.43.0  | MIT                                      |
| `symfony/polyfill-php85`              | v1.43.0  | MIT                                      |
| `symfony/polyfill-php86`              | v1.43.0  | MIT                                      |
| `symfony/polyfill-time`               | v1.42.0  | MIT                                      |
| `symfony/polyfill-uuid`               | v1.43.0  | MIT                                      |
| `symfony/process`                     | v8.1.10  | MIT                                      |
| `symfony/routing`                     | v8.1.10  | MIT                                      |
| `symfony/service-contracts`           | v3.7.3   | MIT                                      |
| `symfony/string`                      | v8.1.10  | MIT                                      |
| `symfony/translation`                 | v8.1.10  | MIT                                      |
| `symfony/translation-contracts`       | v3.7.1   | MIT                                      |
| `symfony/uid`                         | v8.1.9   | MIT                                      |
| `symfony/var-dumper`                  | v8.1.10  | MIT                                      |
| `tijsverkoyen/css-to-inline-styles`   | v2.4.0   | BSD-3-Clause                             |
| `vlucas/phpdotenv`                    | v5.8.0   | BSD-3-Clause                             |
| `voku/portable-ascii`                 | 2.1.1    | MIT                                      |

## JavaScript (npm, bundled into the app)

| Package                    | Version | Licence    |
| -------------------------- | ------- | ---------- |
| `@inertiajs/react`         | 3.9.1   | MIT        |
| `@inertiajs/vite`          | 3.9.1   | MIT        |
| `@tailwindcss/vite`        | 4.3.3   | MIT        |
| `@types/react`             | 19.3.0  | MIT        |
| `@types/react-dom`         | 19.3.0  | MIT        |
| `@vitejs/plugin-react`     | 6.1.2   | MIT        |
| `class-variance-authority` | 0.7.1   | Apache-2.0 |
| `clsx`                     | 2.1.1   | MIT        |
| `concurrently`             | 10.0.6  | MIT        |
| `laravel-vite-plugin`      | 3.2.0   | MIT        |
| `lucide-react`             | 1.64.0  | ISC        |
| `radix-ui`                 | 1.8.1   | MIT        |
| `react`                    | 19.3.1  | MIT        |
| `react-dom`                | 19.3.1  | MIT        |
| `tailwind-merge`           | 3.7.0   | MIT        |
| `tailwindcss`              | 4.3.3   | MIT        |
| `tw-animate-css`           | 1.4.0   | MIT        |
| `typescript`               | 5.9.3   | Apache-2.0 |
| `vite`                     | 1.4.0   | MIT        |
