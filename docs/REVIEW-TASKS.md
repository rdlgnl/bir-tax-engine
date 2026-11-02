# Review tasks — November 2026 system review

Fix list from the deep technical and BIR-compliance review of BuwisBuddy.
Tick an item in the same PR that fixes it, and link the PR.

**How to read this**

- **ID**: `C` critical, `H` high, `M` medium, `L` low.
- **Basis**: _Confirmed_ = settled law; _Needs verification_ = check with a CPA or the BIR issuance before relying on it. No rule here should be coded from this file alone.
- **Done when**: the acceptance check for the item.

Status: 34 of 94 done.

---

## Launch gate

Do not let anyone rely on the app for real filings until these are ticked:

- [x] C1 Tax rules follow the date they apply to
- [x] C2 Elections are kept per tax year
- [ ] C3 Rule pack reviewed by a CPA
- [x] H1 A sale can't be counted twice
- [ ] H3 Filing channel settled and the copy says it plainly
- [x] H5 Host header check (DNS rebinding)

---

## Phase 1 — Critical fixes

### C1 · Tax rules follow the date they apply to

- [x] **Stop-gap:** refuse periods before the earliest encoded rules: transactions, imports, letters and returns before 2018, corporations before 2021 (2020 straddles the CREATE change and isn't encoded).
- [x] Effective-dated rules: the rule pack's `dated` section (`2018-01-01`, `2020-07-01`, `2021-01-01`, `2023-01-01`, `2023-07-01`), each holding only what changed. Kept in [config/tax.php](../config/tax.php) rather than separate files; A4 can split them.
- [x] `TaxRules::on($date)` / `forYear()` / `forQuarter()` resolver returning a `RuleSet`; the engines, payroll, 2316, the 2551Q and 1702 forms and the explainer use it. No `config()` read of a dated rate remains.
- [x] Percentage tax and MCIT take each quarter's rate (the changes fall on quarter starts); each sale's tax effect uses its own quarter.
- [ ] Snapshots store the rule-set id. For now `rule_pack` plus the period identify the rules, but older packs aren't kept in code (see A3).

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
- [ ] CPA reviews each one; record reviewer, date and source per rule.
- [ ] Set `reviewed` per rule (not one flag for the whole pack); the app keeps saying "not yet reviewed" for any rule without sign-off.

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

- [ ] With a CPA, decide per tax type: invoice date (accrual) or cash received.
- [ ] Make the engine and the books ([Journals.php](../app/Books/Services/Journals.php), [GeneralLedger.php](../app/Books/Services/GeneralLedger.php)) agree; if invoice date stays, add accounts receivable to the books.
- [ ] Remove or correct the "Sales are counted by invoice date (EOPT)" copy to match.

**Why:** an invoice unpaid at quarter end is on the 1701Q and 2551Q but not in the books.
**Basis:**

- Confirmed: VAT on services moved to gross sales under RA 11976 (EOPT).
- **Needs verification:** percentage tax under EOPT, and income tax for individuals on the cash method.

**Done when:** a quarter's gross on the return equals the books' income for that quarter.
**Waiting on:** Question Q1 in [RULE-REVIEW.md](RULE-REVIEW.md).

### H3 · Filing channel settled and the copy says it plainly

- [ ] **Needs verification:** which taxpayers must e-file (eBIRForms or eFPS) and whether a printed filled PDF is accepted for each form we produce (RR 9-2013 and later; EOPT "file anywhere").
- [ ] Per form, say exactly how to file it. Where e-filing is required, add a "copy these into eBIRForms" checklist with item numbers.
- [ ] Rename "Download filled form" where it's a reference copy, not the filing.

**Done when:** no screen implies a printed PDF is a filing where it isn't.
**Waiting on:** Question Q2 in [RULE-REVIEW.md](RULE-REVIEW.md).

### H4 · History of changes to records

- [ ] Append-only `record_changes` table: model, record id, event, before and after (JSON), at, cause.
- [ ] Model events on transactions, the profile, certificates, loans, employees, corporations and their quarters, letters and filings.
- [ ] Editing or deleting in a filed quarter is shown as a change after filing, linking to the amendment.
- [ ] Include it in backups; never erased except by "Erase all data".

**Why:** records are edited in place; only filed snapshots and a few notices remain.
**Tests:** an edit after filing appears in the history; a delete leaves a record.
**Done when:** for any filed return you can list every record change made since.

### H5 · Host header check (DNS rebinding)

- [x] [RejectUnknownHosts](../app/Http/Middleware/RejectUnknownHosts.php), first in the global stack: only `localhost`, `127.0.0.1`, `[::1]` and the `APP_URL` host are answered; anything else gets a bare 400 with no data. Laravel's `trustHosts()` was not enough: it stands down in the local environment. Add the desktop app's origin when it's packaged.

**Why:** no login by design and any Host header accepted. A malicious site can rebind its domain to 127.0.0.1 and read the app's pages and downloads.
**Tests:** a request with `Host: evil.example` is rejected; localhost still works.
**Done when:** that test passes. ✔ [HostTest.php](../tests/Feature/Security/HostTest.php)
**Found on the way:** error pages are rendered with the app's shared data (the taxpayer's name and figures), so a rejected host must not reach them; it doesn't.

### H6 · Financial statements on the annual return

- [ ] Graduated filers: say financial statements are attached.
- [ ] **Needs verification:** the CPA audit threshold (NIRC Sec. 232(A), likely gross annual sales above ₱3,000,000 after TRAIN). Put it in the rules.
- [ ] Show it on the annual return page ([returns/annual.tsx](../resources/js/pages/returns/annual.tsx)) and the checklist.

**Done when:** a graduated filer above the threshold is told to attach audited statements.
**Waiting on:** Question Q3 in [RULE-REVIEW.md](RULE-REVIEW.md).

### M3 · Payroll exemptions

- [x] Model the ₱90,000 13th-month and other benefits exemption (NIRC Sec. 32(B)(7)(e), _Confirmed_): the 13th month is paid with the last month and untaxed up to ₱90,000.
- [x] De minimis benefits, and contributions entered rather than "pay after contributions"; minimum wage earners.
- [x] Our annualized method against the BIR withholding table: the table each month, annualized only at year end (see R).

**Done when:** the 1601-C and 2316 show the untaxed parts on their own lines. ✔ [WithholdingTest.php](../tests/Feature/Payroll/WithholdingTest.php)

**Location:** [Payroll.php](../app/Payroll/Services/Payroll.php).

### M4 · Corporations: scope and gaps

- [ ] Decide: launch, label as beta, or hide.
- [ ] If kept:
    - NOLCO and the excess MCIT carry-forward
    - the 1% MCIT period, via C1
    - **Needs verification:** the quarterly 20% test, which today uses taxable income to date

### M10 · Excess input VAT stops at year end

- [x] Carry the Q4 excess into the next year's Q1 ([VatCalculator.php](../app/Tax/Services/VatCalculator.php) works out the years before, back to the first record).

**Basis (Confirmed):** NIRC Sec. 110(B), excess input tax carries over to the succeeding quarter or quarters. Row 2.7 of the rule review.
**Done when:** a Q4 excess shows as carried over in the next year's Q1. ✔ [VatTest.php](../tests/Feature/Tax/VatTest.php)

### R · Corrections from the rule research

From [BIR rule review answers.md](research/BIR%20rule%20review%20answers.md). Each item still goes to the CPA with the rest of the pack (C3).

Done:

- [x] Loan DST: ₱1.50 per ₱200 up to June 30, 2025; 0.75% of the loan from July 1, 2025 (RA 12214, RR 19-2025). Loans under a year are prorated by days over 365, not by months. The rate shown on Form 2000 follows the loan's signing date.
- [x] December 1601-C is due January 15; the 2316 copies go to the BIR by February 28 and show as a reminder (RR 11-2018 Sec. 2.83).
- [x] Reduced EOPT penalties only for returns due from April 27, 2024 (RR 6-2024), and only for micro and small taxpayers. The BIR-assigned classification can be set per year on the profile; without it the app estimates from sales (RR 8-2024 thresholds: ₱3M, ₱20M, ₱1B).
- [x] The ₱3M computerized-books rule is removed: computerized books are required only of large taxpayers (RR 9-2009).
- [x] E-marketplace withholding note: WI760 / WC760, RR 16-2023 with the RMC 8-2024 start date.
- [x] Payroll: the BIR's monthly withholding table (RR 11-2018 Annex "D" and "E"), the cumulative average method for a new hire with an earlier employer this year, and the annualized computation only at year end. Contributions, de minimis benefits (RR 29-2025 limits listed from January 6, 2026), the 13th month up to ₱90,000 and a minimum wage are kept out of taxable pay, on the 1601-C (items 15–20, 23) and the 2316 (items 10, 11, 22, 25B, 29, 34–36, 48). Replaces M3.
- [x] 8% to graduated: once an 8% year's sales exceed ₱3M, graduated rates (itemized) apply from that quarter with the 8% paid credited; that quarter's 2551Q carries percentage tax from January to the month passed; the annual return is the 1701; a calendar reminder to register for VAT by the end of the next month (RR 8-2018). The app no longer stops at ₱3M.
- [x] No-payment returns: a withholding agent's 0619-E, 1601-EQ and 1604-E are listed every period from when withholding started, at ₱0 with "No payment"; the withholding page marks earlier periods and links to removing withholding from the registration (RMC 12-2015). The 1701Q and 2551Q were already listed at ₱0.
- [ ] VAT for part of a year: after the switch, sales from the next month are VAT-able. The app says so but doesn't compute that VAT; registration is per year.

Still to build:

- [ ] No-payment 1702Q for a corporation with no income, and a 1601-C for months with no employees while registered for withholding on compensation (the app doesn't record that registration).
- [ ] 5% professional fee rate (WI010) only when the payee is non-VAT and declared under ₱3M (RR 14-2018).
- [ ] Financial statements: graduated with itemized attaches them, audited above ₱3M of gross sales (NIRC Sec. 232(A)); OSD and 8% need none. Replaces H6.
- [ ] Filing channel: e-filing is the default; the filled PDFs are worksheets to copy, except 1701-MS. Replaces H3.
- [ ] 1701-MS only for micro and small taxpayers.
- [ ] Notices: the RR 4-2026 abatement (until December 31, 2026); 2550M is optional; EIS by December 31, 2026 for those covered.
- [ ] Corporations: MCIT at the monthly average for 2023 (1.5%), MCIT from the fourth year after BIR registration, NOLCO and excess-MCIT ledgers. Feeds M4.
- [ ] Income timing: percentage tax and VAT on the invoice date only from April 27, 2024; income tax follows the books' method for the year. Settles H2.
- [ ] Holidays back to 2018; the annual ITR's second installment; 0619-E on eFPS by the 15th.

### M1 · Holidays beyond 2027

- [ ] Warn or refuse for years without a holiday list ([FilingCalendar.php](../app/Tax/Services/FilingCalendar.php)).
- [ ] Yearly holiday update as part of the rule-pack process.

---

## Phase 3 — Architecture

- [ ] **A1 Pure calculator.** Gather inputs into a `TaxInputs` value. `TaxCalculator` takes inputs and a `RuleSet` and returns a `TaxResult`, with no queries and no `config()`. Corporations follow the same shape.
- [ ] **A2 Trace.** `TaxResult` carries each step: input, rule id, rate, rounding. `TaxExplainer` reads it, and the snapshot stores it.
- [ ] **A3 Amendments compare like with like.** Recompute the filed period under the snapshot's rule set, not today's.
- [ ] **A4 Rule files split by kind.** Rates, deadlines, holidays, form editions, ATC codes and file formats are reviewed and versioned separately.
- [ ] **M8 Side effect on saving a transaction.** Document the 2307 relinking in `Transaction::booted`, or have imports call it explicitly.

---

## Phase 4 — Testing

Add these tests (table-driven where possible):

- [ ] Gross exactly ₱250,000 on 8% → ₱0 income tax
- [ ] Gross exactly ₱3,000,000 on 8% → still allowed
- [ ] Graduated taxable at ₱250,000 / ₱400,000 / ₱800,000 / ₱2,000,000 / ₱8,000,000 → each band's base tax
- [ ] Zero income, expenses only → ₱0, never negative
- [ ] Centavo rounding: amounts at x.005 → consistent with the forms' whole pesos
- [x] 2022 return → 2018–2022 table
- [x] 2023 Q2 vs Q3 percentage tax → 1% then 3%
- [x] Regime switch between years → each year uses its own election
- [x] Invoice then its payment → counted once
- [ ] Unpaid earlier quarter → shown as owed, not assumed paid (M7)
- [ ] Engine total vs printed form total → within ₱1 (M2)
- [x] Host header `evil.example` → rejected
- [ ] CPA reference cases → a small set of worked returns as the acceptance suite

---

## Phase 5 — UX

- [ ] **M2 Peso vs centavo.** On whole-peso forms, show the whole-peso amount as the amount to pay.
- [ ] **M5 Honest PIN wording.** "Protects the screen, not the files", or encrypt sensitive data with a key from the PIN.
- [ ] **M6 Past ₱3M on 8%.** A guided path (graduated for the year, VAT registration, penalties) instead of `cannot_compute`.
- [ ] **M7 Unpaid quarters.** Count only recorded payments in later quarters; show an unpaid earlier quarter as owed (`priorPaid += $quarterPaid ?? $due` today).
- [ ] **M9 Silent cascade deletes.** Removing employees or a corporation is recorded (via H4) and confirmed.

---

## Phase 6 — Future BIR readiness

- [ ] EOPT taxpayer classification (micro, small, medium, large) stored per year. **Needs verification:** RR 8-2024 bands.
- [ ] E-invoicing (EIS) readiness, if the app ever issues invoices.
- [ ] Rule updates shipped as dated rule files with a sign-off record (C1 + C3).
- [ ] Filing and payment through an accredited provider: decision first (not in the design).

---

## Low priority

- [ ] **L1** Packaged builds ship with `APP_DEBUG=false`.
- [ ] **L2** Cache year-end ledger balances if the general ledger slows down ([GeneralLedger.php](../app/Books/Services/GeneralLedger.php)).
- [ ] **L3** Split the largest pages when next touched: `certificates/create`, `letters/create`, `corporation/*`.
- [ ] **L4** Server recomputes or bounds the VAT amount sent from the form.
- [ ] **L5** Move holiday names and other copy out of the rule pack.
- [ ] **L6** Licences list (`smalot/pdfparser` is LGPL-3.0).

---

## Already in good shape (keep it that way)

- Money stored as DECIMAL(15,2); the engine computes in whole centavos.
- Filed returns are frozen as snapshots with the regime and rule-pack version.
- Every rate lives in the rule pack, not in code.
- CSV exports escape formula cells; backup restore only writes plain names inside its folder; backups are AES-256 encrypted.
- PIN hashed and guessing rate-limited; the server listens on 127.0.0.1 only.
- Form Requests on every write; PHPStan level 7; 366 passing tests.
