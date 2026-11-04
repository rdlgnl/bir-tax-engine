# Review tasks — November 2026 system review

Fix list from the deep technical and BIR-compliance review of BuwisBuddy.
Tick an item in the same PR that fixes it, and link the PR.

**How to read this**

- **ID**: `C` critical, `H` high, `M` medium, `L` low.
- **Basis**: _Confirmed_ = settled law; _Needs verification_ = check with a CPA or the BIR issuance before relying on it. No rule here should be coded from this file alone.
- **Done when**: the acceptance check for the item.

Status: 97 of 97 done.

---

## Launch gate

Do not let anyone rely on the app for real filings until these are ticked:

- [x] C1 Tax rules follow the date they apply to
- [x] C2 Elections are kept per tax year
- [x] C3 Rule pack reviewed by a CPA: decided 2026-11-04 to release as open source without one (see C3)
- [x] H1 A sale can't be counted twice
- [x] H3 Filing channel settled and the copy says it plainly
- [x] H5 Host header check (DNS rebinding)

---

## Phase 1 — Critical fixes

### C1 · Tax rules follow the date they apply to

- [x] **Stop-gap:** refuse periods before the earliest encoded rules: transactions, imports, letters and returns before 2018, corporations before 2021 (2020 straddles the CREATE change and isn't encoded).
- [x] Effective-dated rules: the rule pack's `dated` section (`2018-01-01`, `2020-07-01`, `2021-01-01`, `2023-01-01`, `2023-07-01`), each holding only what changed. In [resources/rule-pack/rates.php](../resources/rule-pack/rates.php) since A4.
- [x] `TaxRules::on($date)` / `forYear()` / `forQuarter()` resolver returning a `RuleSet`; the engines, payroll, 2316, the 2551Q and 1702 forms and the explainer use it. No `config()` read of a dated rate remains.
- [x] Percentage tax and MCIT take each quarter's rate (the changes fall on quarter starts); each sale's tax effect uses its own quarter.
- [x] Snapshots store the rule set itself: its date and every dated rule in force for the period (`rule_set`), so a filed return keeps the rules it was computed on even after the pack changes (groundwork for A3).

**Why:** one set of rates (2023 graduated table, 3% percentage tax, 2% MCIT) is used for every year. `citations.*.effective` is only displayed ([TaxExplainer.php](../app/Tax/Services/TaxExplainer.php)).
**Basis (Confirmed):**

- RA 10963 (TRAIN): separate graduated tables for 2018–2022 and 2023 onwards.
- RA 11534 (CREATE): percentage tax (NIRC Sec. 116) and MCIT at 1% from July 1, 2020 to June 30, 2023.

**Tests:** 2022 graduated return; 2023 Q2 vs Q3 percentage tax; MCIT 2022; June 30 / July 1, 2023 boundary.
**Done when:** the same inputs for 2022 and 2026 use their own tables, and a period with no rules is refused. ✔ [DatedRulesTest.php](../tests/Feature/Tax/DatedRulesTest.php)

### C2 · Elections are kept per tax year

- [x] `tax_years` table: `year`, `regime`, `deduction_method`, `vat_registered`, `registered_percentage_tax` (a blank keeps the profile's value). No `elected_on`/`locked_at`: the Q1 filing is the lock, as before ([UpdateSetupStepRequest.php](../app/Profiles/Requests/UpdateSetupStepRequest.php)).
- [x] `TaxProfile::forYear($year)`, a read-only copy with the year's values, used by the engine, return snapshots (so amendments, late payment and annual returns), deadlines and checklist, history, letter checks, SLSP and sworn declarations.
- [x] Rows are written when a Q1 1701Q is filed, and when the method or a registration changes: every earlier year with records keeps the values it had. The current year follows the profile until its Q1 is filed.
- [x] Migration records the method of every Q1 1701Q already filed. Years without one follow the profile until the next change freezes them.

**Why:** [TaxCalculationService.php](../app/Tax/Services/TaxCalculationService.php) reads `$profile->regime` for any year. Switching from 8% to graduated rewrites past years and raises false amendment notices.
**Basis (Confirmed):** the 8% election is made on the first quarterly return and holds for the year (NIRC Sec. 24(A)(2)(b); RR 8-2018).
**Tests:** switch 8% → graduated across two years; amend the earlier year after switching; VAT registration starting mid-year.
**Done when:** changing this year's method leaves last year's figures unchanged. ✔ [TaxYearsTest.php](../tests/Feature/Profiles/TaxYearsTest.php)

### C3 · Rule pack reviewed by a CPA

- [x] List every rule the app applies, `REVIEW` comments included, for a CPA: [RULE-REVIEW.md](RULE-REVIEW.md) (each rule as applied, its value, basis and periods, a decision column, seven questions and a sign-off).
- [x] CPA reviews each one; record reviewer, date and source per rule. Closed, not done: on 2026-11-04 the owner decided to release BuwisBuddy as open source (MIT) without a CPA review, and fix mistakes in the open. No sign-off is recorded, so the app keeps saying "not yet reviewed by a tax professional", now with a "Report a mistake" link to the issue tracker. A tax professional who reviews a part or rule later is recorded with the existing sign-off (who, when, against what), and the app shows it as reviewed.
- [x] Set `reviewed` per rule and per part (not one flag for the whole pack), as a sign-off record (who, when, against what); the app says "not yet reviewed" for any rule without one, and the pack counts as reviewed when every part is. `php artisan rules:status` lists what's left.

**Includes:**

- graduated table, 8% and OSD
- percentage tax
- VAT
- penalties (EOPT reduced rates for micro and small)
- deadlines
- ATC codes and rates
- SAWT, QAP and 1604-E `.DAT` layouts and file names
- corporate rates, the quarterly 20% test and MCIT
- payroll withholding
- loan DST
- the books' chart of accounts and income tax as owner's drawings
- books retention, the loose-leaf rules and the computerized-books limit
- letter kinds

**Done when:** no rule is used for filing figures without a recorded sign-off.

---

## Phase 2 — Compliance corrections

### H1 · A sale can't be counted twice

- [x] A payment whose invoice number matches a recorded unpaid invoice of the same amount settles that invoice (marks it paid, keeps the withholding) instead of adding income. A different amount, an invoice already paid, or an edited payment is turned back with what to do instead.
- [x] Unique invoice numbers for invoices: validation message plus a partial unique index (`WHERE type = 'invoice'`). Payments may still quote the invoice they pay.
- [x] Statement import: a credit from a client matching an unpaid invoice (full amount, or less up to 15% withheld, issued in the six months before) shows "Pays invoice 0142" and marks it paid. Marketplace orders aren't invoiced; their existing duplicate check stays.

**Why:** invoices count as income on their date and payments count too ([Transaction::isIncome](../app/Transactions/Models/Transaction.php)); nothing links them.
**Tests:** invoice then payment with the same number; duplicate invoice number; import of an already-invoiced payment.
**Done when:** each sale counts once however it's entered. ✔ [InvoicePaymentsTest.php](../tests/Feature/Transactions/InvoicePaymentsTest.php)

### H2 · One income basis for returns and books

- [x] Per tax type, from the research (Q1): percentage tax and VAT count a sale on its invoice date from April 27, 2024 (RR 3-2024), and when paid before; income tax follows the books' method (NIRC Secs. 43–45), chosen per year on the books page (when invoiced or when paid). Invoices record the day they're paid.
- [x] The books put a paid invoice in the receipts journal on the day it was paid. On cash books, a quarter's income on the return equals the books' receipts.
- [x] On accrual books, an invoice is income on its date in the general journal, owed as accounts receivable until paid, then collected; the books page shows what clients owed at month end. An invoice follows the method of the year it's dated, so it counts once when the method changes.
- [x] The return checklist says how sales are counted, by the books' method.

**Basis:** RA 11976 Secs. 17, 24; RR 3-2024 Sec. 7(a) for invoices before April 27, 2024 (VAT; percentage tax by analogy, **needs verification**); NIRC Secs. 43–45.
**Done when:** a quarter's gross on the return equals the books' income for that quarter. ✔ on cash books, [IncomeTimingTest.php](../tests/Feature/Tax/IncomeTimingTest.php)

### H3 · Filing channel settled and the copy says it plainly

- [x] Which taxpayers must e-file and whether a printed filled PDF is accepted: RMC 20-2026, taxpayers not on eFPS file through eBIRForms; paper only for the 1701-MS of micro and small taxpayers, or when the BIR declares its systems down. In the rules as `tax.filing.paper_forms`.
- [x] Per form, say exactly how to file it: the return pages already list the values to copy into eBIRForms; the annual page's steps and the form preview now say e-file, except for the 1701-MS.
- [x] "Download filled form" is "See your filled form" where it's a worksheet.

**Done when:** no screen implies a printed PDF is a filing where it isn't. ✔ [FilingGuidanceTest.php](../tests/Feature/Returns/FilingGuidanceTest.php)

### H4 · History of changes to records

- [x] Append-only `record_changes` table: subject, record id, event, label, before and after (JSON), cause (the screen it came from), at. The model refuses edits and deletes ([RecordChange.php](../app/Data/Models/RecordChange.php)).
- [x] Model events (the [RecordsChanges](../app/Data/Concerns/RecordsChanges.php) trait) on transactions, the profile and the years' choices, certificates, loans, employees and their 2316s, corporations and their quarters, letters, filings and issued 2307s. Bulk updates through the query builder aren't recorded.
- [x] A filed return lists every change since to the records it was computed from (its period's sales and expenses, its year's 2307s and tax choices, the profile), under "Changes since filed" on the history page, beside the existing amendment link.
- [x] Included in backups; a restore brings back the backup's history and adds a "restored" entry; only "Erase all data" clears it.

**Why:** records are edited in place; only filed snapshots and a few notices remain.
**Tests:** an edit after filing appears in the history; a delete leaves a record.
**Done when:** for any filed return you can list every record change made since. ✔ [RecordHistoryTest.php](../tests/Feature/Data/RecordHistoryTest.php)

### H5 · Host header check (DNS rebinding)

- [x] [RejectUnknownHosts](../app/Http/Middleware/RejectUnknownHosts.php), first in the global stack: only `localhost`, `127.0.0.1`, `[::1]` and the `APP_URL` host are answered; anything else gets a bare 400 with no data. Laravel's `trustHosts()` was not enough: it stands down in the local environment. Add the desktop app's origin when it's packaged.

**Why:** no login by design and any Host header accepted. A malicious site can rebind its domain to 127.0.0.1 and read the app's pages and downloads.
**Tests:** a request with `Host: evil.example` is rejected; localhost still works.
**Done when:** that test passes. ✔ [HostTest.php](../tests/Feature/Security/HostTest.php)
**Found on the way:** error pages are rendered with the app's shared data (the taxpayer's name and figures), so a rejected host must not reach them; it doesn't.

### H6 · Financial statements on the annual return

- [x] Graduated filers on itemized deductions: say financial statements (or an Account Information Form) are attached; on OSD or 8%, none (RMC 17-2019).
- [x] The CPA audit threshold: gross sales above ₱3,000,000 (NIRC Sec. 232(A) as TRAIN amended it), in the rules as `tax.financial_statements.audit_above`.
- [x] Show it on the annual return page ([returns/annual.tsx](../resources/js/pages/returns/annual.tsx)).
- [x] And on the compliance checklist (the annual quarter's "Financial statements" item).

**Done when:** a graduated filer above the threshold is told to attach audited statements. ✔ [FilingGuidanceTest.php](../tests/Feature/Returns/FilingGuidanceTest.php)

### M3 · Payroll exemptions

- [x] Model the ₱90,000 13th-month and other benefits exemption (NIRC Sec. 32(B)(7)(e), _Confirmed_): the 13th month is paid with the last month and untaxed up to ₱90,000.
- [x] De minimis benefits, and contributions entered rather than "pay after contributions"; minimum wage earners.
- [x] Our annualized method against the BIR withholding table: the table each month, annualized only at year end (see R).

**Done when:** the 1601-C and 2316 show the untaxed parts on their own lines. ✔ [WithholdingTest.php](../tests/Feature/Payroll/WithholdingTest.php)

**Location:** [Payroll.php](../app/Payroll/Services/Payroll.php).

### M4 · Corporations: scope and gaps

- [x] Decide: launch, label as beta, or hide. Labelled **beta**: a pill on each corporation page and the home card, and a note on the overview, setup and filled returns to check them with an accountant.
- [x] NOLCO and the excess MCIT carry-forward, on the 1702-RT. ✔ [CarryForwardTest.php](../tests/Feature/Corporations/CarryForwardTest.php)
- [x] The 1% MCIT period, via C1, and 2023 at the months' average (1.5%).
- [x] The quarterly 20% test uses taxable income to date, labelled provisional on the corporation's home, the quarter and the filled 1702Q; the 1702-RT tests the year. **Needs verification** by the CPA: no BIR text says how the 1702Q applies it (pre-review C6).

### M10 · Excess input VAT stops at year end

- [x] Carry the Q4 excess into the next year's Q1 ([VatCalculator.php](../app/Tax/Services/VatCalculator.php) works out the years before, back to the first record).

**Basis (Confirmed):** NIRC Sec. 110(B), excess input tax carries over to the succeeding quarter or quarters. Row 2.7 of the rule review.
**Done when:** a Q4 excess shows as carried over in the next year's Q1. ✔ [VatTest.php](../tests/Feature/Tax/VatTest.php)

### R · Corrections from the rule research

From [BIR rule review answers.md](research/BIR%20rule%20review%20answers.md). Each item is open to review with the rest of the pack (C3).

Done:

- [x] Loan DST: ₱1.50 per ₱200 up to June 30, 2025; 0.75% of the loan from July 1, 2025 (RA 12214, RR 19-2025). Loans under a year are prorated by days over 365, not by months. The rate shown on Form 2000 follows the loan's signing date.
- [x] December 1601-C is due January 15; the 2316 copies go to the BIR by February 28 and show as a reminder (RR 11-2018 Sec. 2.83).
- [x] Reduced EOPT penalties only for returns due from April 27, 2024 (RR 6-2024), and only for micro and small taxpayers. The BIR-assigned classification can be set per year on the profile; without it the app estimates from sales (RR 8-2024 thresholds: ₱3M, ₱20M, ₱1B).
- [x] The ₱3M computerized-books rule is removed: computerized books are required only of large taxpayers (RR 9-2009).
- [x] E-marketplace withholding note: WI760 / WC760, RR 16-2023 with the RMC 8-2024 start date.
- [x] Payroll: the BIR's monthly withholding table (RR 11-2018 Annex "D" and "E"), the cumulative average method for a new hire with an earlier employer this year, and the annualized computation only at year end. Contributions, de minimis benefits (RR 29-2025 limits listed from January 6, 2026), the 13th month up to ₱90,000 and a minimum wage are kept out of taxable pay, on the 1601-C (items 15–20, 23) and the 2316 (items 10, 11, 22, 25B, 29, 34–36, 48). Replaces M3.
- [x] 8% to graduated: once an 8% year's sales exceed ₱3M, graduated rates (itemized) apply from that quarter with the 8% paid credited; that quarter's 2551Q carries percentage tax from January to the month passed; the annual return is the 1701; a calendar reminder to register for VAT by the end of the next month (RR 8-2018). The app no longer stops at ₱3M.
- [x] No-payment returns: a withholding agent's 0619-E, 1601-EQ and 1604-E are listed every period from when withholding started, at ₱0 with "No payment"; the withholding page marks earlier periods and links to removing withholding from the registration (RMC 12-2015). The 1701Q and 2551Q were already listed at ₱0.
- [x] Financial statements on the annual return by deduction method, audited above ₱3M (H6); filing channel: e-file by default, paper only for the 1701-MS (H3); the 1701-MS only for micro and small taxpayers, medium ones on the 1701A (8% or OSD) or 1701, by the class the BIR assigned or the estimate from sales.
- [x] Notices: the RR 4-2026 abatement for micro taxpayers with returns due by the end of 2025 still unfiled, on the late return and the calendar until December 31, 2026; e-invoicing (EIS) by December 31, 2026 for online sellers that aren't micro; the 2550M is optional (RMC 52-2023); the 5% professional fee rate only for a payee who isn't VAT-registered (RR 14-2018).
- [x] Corporations: MCIT at the months' average (1.5% for 2023, RMC 69-2023); MCIT from the fourth year after BIR registration; NOLCO (three years, five for 2020–2021) and excess MCIT (three years) carried on the 1702-RT.
- [x] Income timing: percentage tax and VAT on the invoice date from April 27, 2024 (when paid before); income tax on the books' method per year, accrual by default; invoices record when they're paid; the books' receipts on that day (H2).
- [x] Holidays 2018 to 2026 from each year's proclamations (nationwide regular and special non-working days, Eid included), so past deadlines and penalties move as they did; the annual tax over ₱2,000 in two halves, the second by October 15 (NIRC Sec. 56(A)(2)), on the annual page and the calendar.
- [x] VAT for part of a year: after the switch, VAT from the first day of the next month, on a 2550Q and SLSP from that quarter; a sale recorded without VAT has 12% added (REVIEW: whether such a price is taken as including VAT).

Still to build:

- [x] No-payment 1702Q: every quarter is listed from the year the corporation registered, "No payment" when nothing's due; a 1601-C for months with no one on payroll after the year's first hire, "No payment", the month page pointing to the 1905 to stop (REVIEW: the app doesn't record the compensation-withholding registration).
- [x] eFPS filers' dates: the profile and the corporation say how they file, eBIRForms or eFPS and its group (the profile from a month on, so earlier returns keep their dates). An eFPS filer's 0619-E is due the 15th, its 1601-C on the group's day, the 11th to the 15th, December's included (RR 26-2002; PwC's 2026 calendar). The app shows the e-filing date; payment by the 15th (December's January 20) isn't shown apart (REVIEW). ✔ [EfpsTest.php](../tests/Feature/Tax/EfpsTest.php)

### M1 · Holidays beyond 2027

- [x] Warn for years without a holiday list: the calendar says deadlines move off weekends only ([FilingCalendar.php](../app/Tax/Services/FilingCalendar.php) `knowsHolidays`).
- [x] Yearly holiday update as part of the rule-pack process: in the README's "Updating the rule pack".

---

## Phase 3 — Architecture

- [x] **A1 Pure calculator.** [TaxInputs](../app/Tax/Calculation/TaxInputs.php) holds a year's sales (their dates already on the right method), expenses, filings, the year's taxpayer, rules and due dates; [TaxCalculator](../app/Tax/Calculation/TaxCalculator.php) computes the year from them alone, no queries and no `config()`, tested without the app ([TaxCalculatorTest](../tests/Unit/TaxCalculatorTest.php)). `TaxCalculationService` gathers the inputs; its public methods are unchanged.
- [x] **A1b** The same shape for corporations: [CorporateInputs](../app/Corporations/Services/CorporateInputs.php) and [CorporateCalculator](../app/Corporations/Services/CorporateCalculator.php), tested without the app; `CorporateTax` gathers. The planning comparison and per-transaction effects take the gathered rule values, so the rule pack is read only when gathering.
- [x] **A2 Trace.** Each quarter of the calculator's result carries its `trace`: every step from gross to tax due and the percentage tax, with the rule it applies, its rate and the graduated band used (amounts round to the centavo, half up). The explanation and a return's rules used are read from it, and a filed return's snapshot keeps it with the figures.
- [x] **A3 Amendments compare like with like.** A filed quarter is recomputed on the rule set kept with it (`TaxRules::pinned`), so only changed records call for an amendment, and the amended return uses the same rules. Returns filed before rule sets were kept use today's.
- [x] **A4 Rule files split by kind.** The pack's parts are in [resources/rule-pack/](../resources/rule-pack/): rates, deadlines, holidays, form editions, tax codes, file formats and citations, each with its own version and sign-off under `parts`, listed on the BIR rules page. `config('tax.*')` reads them as one, unchanged.
- [x] **M8 Side effect on saving a transaction.** Documented in `Transaction::booted`: saving or deleting a sale relinks the client's 2307s.

---

## Phase 4 — Testing

Add these tests (table-driven where possible):

- [x] Gross exactly ₱250,000 on 8% → ₱0 income tax
- [x] Gross exactly ₱3,000,000 on 8% → still allowed
- [x] Graduated taxable at ₱250,000 / ₱400,000 / ₱800,000 / ₱2,000,000 / ₱8,000,000 → each band's base tax
- [x] Zero income, expenses only → ₱0, never negative
- [x] Centavo rounding: amounts at x.005 → consistent with the forms' whole pesos
- [x] 2022 return → 2018–2022 table
- [x] 2023 Q2 vs Q3 percentage tax → 1% then 3%
- [x] Regime switch between years → each year uses its own election
- [x] Invoice then its payment → counted once
- [x] Unpaid earlier quarter → shown as owed, not assumed paid (M7)
- [x] Engine total vs printed form total → within ₱1 (M2) ✔ [BoundaryTest.php](../tests/Feature/Tax/BoundaryTest.php)
- [x] Host header `evil.example` → rejected
- [x] CPA reference cases → a small set of worked returns as the acceptance suite. Drafted: ten cases in [REFERENCE-CASES.md](REFERENCE-CASES.md) with the app's figures and a question where the app may be wrong, generated from and tested by [ReferenceCasesTest.php](../tests/Feature/Acceptance/ReferenceCasesTest.php). Closed with C3: the cases test the app's own figures; anyone who checks them and finds one wrong corrects the case, and the app is fixed until it passes.

---

## Phase 5 — UX

- [x] **M2 Peso vs centavo.** On whole-peso forms (1701Q, 1701-MS, 1701A, 1701), the return pages show the form's amount to pay, penalties included, and their lines in whole pesos. The calendar and home still show the exact figure, within ₱1.
- [x] **M5 Honest PIN wording.** The lock card and first run say the PIN locks the screens, not the files, and point to the computer's login and disk encryption; backups have their own password.
- [x] **M6 Past ₱3M on 8%.** Graduated from the quarter the limit was passed, percentage tax to that month, VAT from the next, a VAT registration reminder; `cannot_compute` is gone.
- [x] **M7 Unpaid quarters.** Later quarters credit an earlier one with its recorded payment, or with its tax only while it can still be paid on time; an overdue one, or one filed with nothing paid, stays owed in later returns, and My tax says how much of the amount it is.
- [x] **M9 Silent cascade deletes.** Removing an employee or the corporation asks first, and their 2316s or quarters are removed one by one, each in the history (H4).

---

## Phase 6 — Future BIR readiness

- [x] EOPT taxpayer classification (micro, small, medium, large) stored per year, set on the profile; RR 8-2024 bands (₱3M, ₱20M, ₱1B).
- [x] E-invoicing (EIS) readiness, if the app ever issues invoices. Closed, not planned (2026-11-04): issuing invoices through the EIS needs the BIR to accredit the software, too heavy for a free app on the taxpayer's own computer. The app records invoices issued elsewhere and keeps its reminder of the EIS deadline for online sellers.
- [x] Rule updates shipped as dated rule files with a sign-off record (C1 + C3): the pack's parts, each versioned with its sign-off, and the update steps in the README.
- [x] Filing and payment through an accredited provider: decision first (not in the design). Closed, not planned (2026-11-04): the same accreditation applies. The app prepares each return and the values to copy into eBIRForms, and the taxpayer files and pays there.

---

## Low priority

- [x] **L1** Packaged builds ship with `APP_DEBUG=false`: a Packaging section in the README (debug off, the app URL, config cache, licences); `debug` already defaults to off. No packaged build exists yet.
- [x] **L2** The general ledger builds each month's journal once per request, so opening balances no longer rebuild the months before for every account ([GeneralLedger.php](../app/Books/Services/GeneralLedger.php)).
- [x] **L3** Split the largest pages when next touched: `certificates/create`, `letters/create`, `corporation/*`. Done for the two largest (2026-11-04): `transactions/import` (737 lines to 159) and `certificates/create` (651 to 153), their parts in `components/transactions/import/` and `components/compliance/certificate-form/`, unchanged in what they show. `letters/create` (about 460) and `corporation/*` (300 to 360 each) are left as they are.
- [x] **L4** The server keeps the VAT sent from the form to at most 12% of the amount, a peso allowed for rounding.
- [x] **L5** Move holiday names and other copy out of the rule pack. Closed, not done: what's left is the law's own wording for its data (holiday names from the proclamations, ATC and de minimis descriptions, rule titles), reviewed with it. Moving it would split each date or rate from its name and make every yearly update two edits, and holidays can be renamed in the app.
- [x] **L6** Licences list: [docs/LICENSES.md](LICENSES.md), with the LGPL libraries (dompdf, smalot/pdfparser) and what they ask of a packaged build.

---

## Already in good shape (keep it that way)

- Money stored as DECIMAL(15,2); the engine computes in whole centavos.
- Filed returns are frozen as snapshots with the regime and rule-pack version.
- Every rate lives in the rule pack, not in code.
- CSV exports escape formula cells; backup restore only writes plain names inside its folder; backups are AES-256 encrypted.
- PIN hashed and guessing rate-limited; the server listens on 127.0.0.1 only.
- Form Requests on every write; PHPStan level 7; 366 passing tests.
