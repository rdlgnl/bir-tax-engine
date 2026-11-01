# Review tasks — November 2026 system review

Fix list from the deep technical and BIR-compliance review of BuwisBuddy.
Tick an item in the same PR that fixes it, and link the PR.

**How to read this**

- **ID**: `C` critical, `H` high, `M` medium, `L` low.
- **Basis**: *Confirmed* = settled law; *Needs verification* = check with a CPA or the BIR issuance before relying on it. No rule here should be coded from this file alone.
- **Done when**: the acceptance check for the item.

Status: 0 of 75 done.

---

## Launch gate

Do not let anyone rely on the app for real filings until these are ticked:

- [ ] C1 Tax rules follow the date they apply to
- [ ] C2 Elections are kept per tax year
- [ ] C3 Rule pack reviewed by a CPA
- [ ] H1 A sale can't be counted twice
- [ ] H3 Filing channel settled and the copy says it plainly
- [ ] H5 Host header check (DNS rebinding)

---

## Phase 1 — Critical fixes

### C1 · Tax rules follow the date they apply to
- [ ] **Stop-gap:** refuse periods before the earliest encoded rules (transactions, returns, corporations). Today `occurred_on` has no lower bound ([SaveTransactionRequest.php](../app/Transactions/Requests/SaveTransactionRequest.php)).
- [ ] Effective-dated rule files: `resources/rules/2018-01-01.php`, `2020-07-01.php`, `2023-01-01.php`, `2023-07-01.php`, each holding only what changed.
- [ ] `RuleSet::for($date)` resolver; the engines take a `RuleSet` instead of calling `config('tax.*')`.
- [ ] Percentage tax splits by date when a rate change falls inside a quarter.
- [ ] Snapshots store the rule-set id (they already store `rule_pack`).

**Why:** one set of rates (2023 graduated table, 3% percentage tax, 2% MCIT) is used for every year. `citations.*.effective` is only displayed ([TaxExplainer.php](../app/Tax/Services/TaxExplainer.php)).
**Basis (Confirmed):**
- RA 10963 (TRAIN): separate graduated tables for 2018–2022 and 2023 onwards.
- RA 11534 (CREATE): percentage tax (NIRC Sec. 116) and MCIT at 1% from July 1, 2020 to June 30, 2023.

**Tests:** 2022 graduated return; 2023 Q2 vs Q3 percentage tax; MCIT 2022; June 30 / July 1, 2023 boundary.
**Done when:** the same inputs for 2022 and 2026 use their own tables, and a period with no rules is refused.

### C2 · Elections are kept per tax year
- [ ] `tax_years` table: `year`, `regime`, `deduction_method`, `vat_registered`, `registered_percentage_tax`, `elected_on`, `locked_at`.
- [ ] Engine, amendments, late payment, annual return and letter checks read the year's row, not `TaxProfile`.
- [ ] Setup and the "tax method locks" reminder write the year's row.
- [ ] Migrate the current profile values into a row for each year with records.

**Why:** [TaxCalculationService.php](../app/Tax/Services/TaxCalculationService.php) reads `$profile->regime` for any year. Switching from 8% to graduated rewrites past years and raises false amendment notices.
**Basis (Confirmed):** the 8% election is made on the first quarterly return and holds for the year (NIRC Sec. 24(A)(2)(b); RR 8-2018).
**Tests:** switch 8% → graduated across two years; amend the earlier year after switching; VAT registration starting mid-year.
**Done when:** changing this year's method leaves last year's figures unchanged.

### C3 · Rule pack reviewed by a CPA
- [ ] List every `REVIEW` comment in [config/tax.php](../config/tax.php) and the code (`grep -rn REVIEW app config`).
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
- [ ] A payment whose invoice number matches a recorded invoice settles that invoice (marks it paid) instead of adding income; or the form warns and offers to.
- [ ] Unique invoice numbers for invoices (index plus validation message).
- [ ] Statement and marketplace imports check for an existing invoice or payment.

**Why:** invoices count as income on their date and payments count too ([Transaction::isIncome](../app/Transactions/Models/Transaction.php)); nothing links them.
**Tests:** invoice then payment with the same number; duplicate invoice number; import of an already-invoiced payment.
**Done when:** each sale counts once however it's entered.

### H2 · One income basis for returns and books
- [ ] With a CPA, decide per tax type: invoice date (accrual) or cash received.
- [ ] Make the engine and the books ([Journals.php](../app/Books/Services/Journals.php), [GeneralLedger.php](../app/Books/Services/GeneralLedger.php)) agree; if invoice date stays, add accounts receivable to the books.
- [ ] Remove or correct the "Sales are counted by invoice date (EOPT)" copy to match.

**Why:** an invoice unpaid at quarter end is on the 1701Q and 2551Q but not in the books.
**Basis:**
- Confirmed: VAT on services moved to gross sales under RA 11976 (EOPT).
- **Needs verification:** percentage tax under EOPT, and income tax for individuals on the cash method.

**Done when:** a quarter's gross on the return equals the books' income for that quarter.

### H3 · Filing channel settled and the copy says it plainly
- [ ] **Needs verification:** which taxpayers must e-file (eBIRForms or eFPS) and whether a printed filled PDF is accepted for each form we produce (RR 9-2013 and later; EOPT "file anywhere").
- [ ] Per form, say exactly how to file it. Where e-filing is required, add a "copy these into eBIRForms" checklist with item numbers.
- [ ] Rename "Download filled form" where it's a reference copy, not the filing.

**Done when:** no screen implies a printed PDF is a filing where it isn't.

### H4 · History of changes to records
- [ ] Append-only `record_changes` table: model, record id, event, before and after (JSON), at, cause.
- [ ] Model events on transactions, the profile, certificates, loans, employees, corporations and their quarters, letters and filings.
- [ ] Editing or deleting in a filed quarter is shown as a change after filing, linking to the amendment.
- [ ] Include it in backups; never erased except by "Erase all data".

**Why:** records are edited in place; only filed snapshots and a few notices remain.
**Tests:** an edit after filing appears in the history; a delete leaves a record.
**Done when:** for any filed return you can list every record change made since.

### H5 · Host header check (DNS rebinding)
- [ ] `$middleware->trustHosts(at: ['localhost', '127.0.0.1'])` in [bootstrap/app.php](../bootstrap/app.php) (plus the desktop app's origin later).

**Why:** no login by design and any Host header accepted. A malicious site can rebind its domain to 127.0.0.1 and read the app's pages and downloads.
**Tests:** a request with `Host: evil.example` is rejected; localhost still works.
**Done when:** that test passes.

### H6 · Financial statements on the annual return
- [ ] Graduated filers: say financial statements are attached.
- [ ] **Needs verification:** the CPA audit threshold (NIRC Sec. 232(A), likely gross annual sales above ₱3,000,000 after TRAIN). Put it in the rules.
- [ ] Show it on the annual return page ([returns/annual.tsx](../resources/js/pages/returns/annual.tsx)) and the checklist.

**Done when:** a graduated filer above the threshold is told to attach audited statements.

### M3 · Payroll exemptions
- [ ] Model the ₱90,000 13th-month and other benefits exemption (NIRC Sec. 32(B)(7)(e), *Confirmed*).
- [ ] De minimis benefits, and contributions entered or computed rather than "pay after contributions".
- [ ] **Needs verification:** our annualized method against the BIR withholding table.

**Location:** [Payroll.php](../app/Payroll/Services/Payroll.php).

### M4 · Corporations: scope and gaps
- [ ] Decide: launch, label as beta, or hide.
- [ ] If kept:
  - NOLCO and the excess MCIT carry-forward
  - the 1% MCIT period, via C1
  - **Needs verification:** the quarterly 20% test, which today uses taxable income to date

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
- [ ] 2022 return → 2018–2022 table (or refused until C1)
- [ ] 2023 Q2 vs Q3 percentage tax → 1% then 3%
- [ ] Regime switch between years → each year uses its own election
- [ ] Invoice then its payment → counted once
- [ ] Unpaid earlier quarter → shown as owed, not assumed paid (M7)
- [ ] Engine total vs printed form total → within ₱1 (M2)
- [ ] Host header `evil.example` → rejected
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
