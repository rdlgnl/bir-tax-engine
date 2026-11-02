# Pre-review of the open rules

The rule pack's open `REVIEW` notes, checked against primary texts before the
CPA's review. **This is not a CPA's sign-off.** It was prepared with an AI
assistant, which is not a licensed CPA. No `reviewed` sign-off in
[config/tax.php](../../config/tax.php) has been set from it. Its purpose is to
narrow down what the CPA has to decide and to show the basis we found for
each item.

Each item gets one verdict:

- **Correct**: the app matches a primary text we read.
- **Incorrect**: the app contradicts a primary text. The code has been changed,
  and the change is listed here so the CPA can confirm it.
- **No clear basis found**: no law, regulation or circular we read settles
  it. The app keeps its current treatment and its `REVIEW` note, and the
  question goes to the CPA.

Kinds of basis: **Law** (NIRC as amended), **RR** (revenue regulation),
**RMO/RMC**, **Form** (the BIR's own form or its guidelines),
**Practice**, **Interpretation**.

Sources read in primary text for batch 1:

- RR 8-2018 (TRAIN income tax): Sec. 2(g)–(h) definitions, Sec. 8
  deductions and the OSD election, Sec. 12 and Illustration 18 on exceeding
  ₱3,000,000. Read from the BIR's scanned copy, via its text layer.
- RMO 23-2018, Secs. II.6, II.7.2, II.9 and II.15 (8% option procedures),
  from the Supreme Court e-Library.
- The BIR's own forms in [resources/forms/bir/](../../resources/forms/bir/):
  1701Q (January 2018) item 8, 1601-EQ (January 2019) ATC schedule, 2307
  (January 2018), 2551Q (January 2018) ATC table, 2550Q (April 2024).

Earlier research: [BIR rule review answers.md](BIR%20rule%20review%20answers.md)
and [notes/](notes/).

---

## Batch 1 · Individuals: income tax, percentage tax, VAT, codes

| #   | Rule                                                                 | Where                                                                           | Verdict                | Basis                          | Changed   |
| --- | -------------------------------------------------------------------- | ------------------------------------------------------------------------------- | ---------------------- | ------------------------------ | --------- |
| B1  | When a sale counts in the 8% base                                    | [TaxProfile::incomeMethod](../../app/Profiles/Models/TaxProfile.php)            | **Incorrect** → fixed  | RR 8-2018 Sec. 2(g)            | Yes       |
| B2  | OSD after an 8% year passes ₱3,000,000                               | [TaxProfile::onGraduatedRates](../../app/Profiles/Models/TaxProfile.php)        | No clear basis found   | RR 8-2018 Sec. 8 (default)     | Note only |
| B3  | The switch itself: graduated, PT to the breach month, VAT next month | [TaxCalculator::switchToGraduated](../../app/Tax/Calculation/TaxCalculator.php) | Correct                | RR 8-2018 Sec. 12, Ill. 18     | No        |
| B4  | Percentage tax on services billed before April 27, 2024              | [Transaction::salesTaxDate](../../app/Transactions/Models/Transaction.php)      | No clear basis found   | RR 3-2024 Sec. 7(a) is for VAT | No        |
| B5  | VAT on a sale recorded without VAT after the switch                  | [VatCalculator](../../app/Tax/Services/VatCalculator.php)                       | No clear basis found   | —                              | No        |
| B6  | Interest on tax unpaid across April 27, 2024                         | [rates.php `penalties`](../../resources/rule-pack/rates.php)                    | No clear basis found   | RR 6-2024 not read             | No        |
| B7  | Withholding ATCs: professional fees to a company                     | [codes.php `withholding.atcs`](../../resources/rule-pack/codes.php)             | **Incorrect** → fixed  | Form 1601-EQ; RR 11-2018       | Yes       |
| B8  | Withholding ATCs: persons, rent, contractors                         | same                                                                            | Correct                | Form 1601-EQ, 2307             | Note only |
| B9  | Income tax ATCs on the 1701Q; PT010 on the 2551Q                     | [codes.php `atc`](../../resources/rule-pack/codes.php)                          | Correct                | Forms 1701Q, 2551Q             | Note only |
| B10 | The 2550Q's ATC "VT010"                                              | same                                                                            | No clear basis found   | Form 2550Q has no ATC box      | Note only |
| B11 | Compensation withholding table, 2018–2022 (Annex "D")                | [rates.php `compensation_brackets`](../../resources/rule-pack/rates.php)        | Correct (as published) | RR 11-2018 Annex "D"           | No        |

### B1 · The 8% base counts receipts, not invoices: incorrect, fixed

**Before.** The 8% base followed the books' method, which defaults to
accrual. An unpaid invoice counted in the 8% base on its invoice date.

**Basis.** RR 8-2018 Sec. 2(g) (**RR**) defines gross receipts as the money
"actually or constructively received during the taxable period" for
services, excluding returnable security deposits and, for a VAT taxpayer,
the VAT. The 8% under NIRC Sec. 24(A)(2)(b) (**Law**) is on "gross sales or
gross receipts and other non-operating income". The EOPT Act's change of
"gross receipts" to "gross sales" (RR 3-2024) is limited to VAT and
percentage tax (Titles IV and V). We found nothing that amends Title II or
RR 8-2018's definition.

**Now.** On 8%, a sale counts when it is paid, whatever the books' method
([TaxProfile::incomeMethod](../../app/Profiles/Models/TaxProfile.php)). A
year that leaves 8% stays on receipts after the switch, so a sale counts
once. Graduated rates still follow the books' method (NIRC Sec. 43, **Law**).
Percentage tax and VAT are unchanged and still count on the invoice date.

**Effect on the demo.** The unpaid ₱40,000 invoice 0142 (October 26) no
longer counts until it is paid. 2026 gross becomes ₱655,000 instead of
₱695,000, and the annual payable ₱2,900 instead of ₱6,100. These figures
now differ from the design screens, which assumed invoice-date counting.

**For the CPA.**

1. Sellers of goods: is their 8% base gross sales "reported during the
   period" (Sec. 2(h)), that is, on the invoice? The app doesn't tell goods
   from services. It treats all sales as services, which suits the
   freelancers it is built for.
2. Is the ₱3,000,000 that ends the 8% option measured on receipts (as now)
   or on billings? Since the EOPT Act, the VAT threshold in Sec. 109(BB) is
   on gross sales, so a service provider could pass it on billings first.
   **No clear basis found.**
3. A filed 8% quarter that counted an unpaid invoice now shows a lower tax.
   The app will offer an amendment. Should it, or is the overpayment simply
   credited on the annual return?

**Confidence.** Medium-high. The definition is explicit. No ruling or
circular applying it to accrual-basis 8% filers was found, in either
direction.

### B2 · OSD after the switch: no clear basis found

RR 8-2018 Sec. 8 (**RR**) says the OSD is elected by signifying it on the
return, for the first quarter or the first quarter after a business starts,
and is irrevocable for the year. A graduated taxpayer who doesn't signify
it "shall be considered as having availed of the itemized deductions". An
8% filer's Q1 return elected 8%, not the OSD. The switch provisions
(Sec. 12, Illustration 18; RMO 23-2018 Secs. II.9 and II.15) say graduated
rates apply, but not which deduction. RR 8-2018 Illustration 12 shows the
OSD with gross receipts of ₱4,250,000, so the OSD itself is not barred
above ₱3,000,000.

The app applies itemized deductions, the literal default. **For the CPA:**
may the OSD be elected on the first 1701Q filed on graduated rates?

### B3 · The switch: correct

RR 8-2018 Sec. 12 and Illustration 18 (**RR**), with RMO 23-2018
Sec. II.9 (**RMO**), cover the switch:

- registration is updated within the month after the limit is passed;
- VAT applies from the first day of the next month;
- percentage tax is due from the start of the year to the month the limit
  was passed, without penalty if paid on time;
- graduated rates apply "from the time" the limit was exceeded, with the
  8% already paid credited against the annual tax.

The app does each of these. The annual return being the 1701 follows from
RMO 23-2018 Sec. II.9, which requires audited financial statements to be
attached.

### B4 · Percentage tax on services billed before April 27, 2024: no clear basis found

RR 3-2024 Sec. 7(a) (**RR**, read through Grant Thornton and KPMG
summaries) declares output **VAT** on pre-effectivity services when
collected. No source gives a percentage tax counterpart. The app applies
the same rule to percentage tax by analogy: the old Sec. 116 base was
receipts. **For the CPA:** confirm.

### B5 · VAT on a sale recorded without VAT: no clear basis found

After the switch, a sale invoiced as non-VAT is treated as before VAT, and
12% is added on top. The other reading treats the amount as VAT-inclusive
and takes 12/112 of it, which is lower. We did not find the primary text
on a taxpayer liable to VAT before registering. **For the CPA:** which
applies?

### B6 · Interest across April 27, 2024: no clear basis found

The reduced interest for micro and small taxpayers (RA 11976; RR 6-2024)
applies to returns due from April 27, 2024. For tax already overdue on that
date, the app applies the rate of the due date for the whole period. The
earlier change from 20% to 12% (RR 21-2018) split the period at the date
of change. **For the CPA:** should the reduced rate apply from April 27,
2024 to tax already unpaid then?

### B7 · Professional fees to a company: incorrect, fixed

**Before.** One code, WC010 at 10%, for every company payee.

**Basis.** The 1601-EQ (January 2019) ATC schedule (**Form**), under
RR 11-2018 (**RR**):

- **WC010**, 10%: gross income for the current year not over ₱720,000;
- **WC011**, 15%: gross income over ₱720,000.

The 2307 (January 2018) has the same codes.

**Now.** WC011 is added at 15%. The payment form asks whether the company
declared gross income of ₱720,000 or less, the way it asks a person for
their sworn declaration. "No, or not sure" gives 15%.

**For the CPA:** is 15% right for a company that gives no declaration?

### B8 · Other withholding codes: correct

The 1601-EQ and 2307 (**Form**) give:

| Payment                                             | ATC (person / company) | Rate |
| --------------------------------------------------- | ---------------------- | ---- |
| Professional fee, gross income not over ₱3,000,000  | WI010                  | 5%   |
| Professional fee, over ₱3,000,000 or VAT-registered | WI011                  | 10%  |
| Rent of real property                               | WI100 / WC100          | 5%   |
| Income payments to certain contractors              | WI120 / WC120          | 2%   |

The app matches. Its note on WI010 says the person has a sworn declaration
and isn't VAT-registered, which is how the forms' gross income test is
applied in practice.

### B9 · Income tax and percentage tax codes: correct

The 1701Q (January 2018) item 8 (**Form**) lists the six codes the app uses:

| Income     | Graduated | 8%    |
| ---------- | --------- | ----- |
| Business   | II012     | II015 |
| Profession | II014     | II017 |
| Mixed      | II013     | II016 |

The 2551Q's table lists PT010 for "persons exempt from VAT under
Sec. 109(BB) (Sec. 116)".

### B10 · The 2550Q code: no clear basis found

The April 2024 2550Q has no ATC box, so "VT010" is shown only inside the
app and never printed on the form. Nothing to fix. The code is unconfirmed.

### B11 · Annex "D", 2018–2022: correct as published

The monthly table matches RR 11-2018 Annex "D" as published:

| Monthly taxable pay | Tax                             |
| ------------------- | ------------------------------- |
| up to ₱20,833       | 0                               |
| ₱20,833–33,333      | 20% of the excess over ₱20,833  |
| ₱33,333–66,667      | ₱2,500 + 25% of the excess      |
| ₱66,667–166,667     | ₱10,833.33 + 30% of the excess  |
| ₱166,667–666,667    | ₱40,833.33 + 32% of the excess  |
| over ₱666,667       | ₱200,833.33 + 35% of the excess |

The printed annex itself was not compared, so the `REVIEW` note stays.

---

## Batch 2 · Corporations

Sources read in primary text for batch 2:

- RR 5-2021 (CREATE corporate income tax): the rate table and the MCIT
  clause, from the BIR's PDF.
- RMC 62-2021, the BIR's digest: total assets and when MCIT starts.
- The BIR's forms: 1702Q (January 2018) items 14–16 and its ATC table,
  1702-RT (January 2018) items 33–47 and Schedule IV, 2000 (January 2018)
  ATC table.
- Secondary: Grant Thornton on RMC 36-2024 (2023 MCIT); Grant Thornton
  and KPMG on RR 19-2025 (DST under CMEPA).

| #   | Rule                                                     | Where                                                                                | Verdict               | Basis                                | Changed   |
| --- | -------------------------------------------------------- | ------------------------------------------------------------------------------------ | --------------------- | ------------------------------------ | --------- |
| C1  | What "total assets" means for the 20% rate               | [corporation/edit.tsx](../../resources/js/pages/corporation/edit.tsx)                | **Incorrect** → fixed | RMC 62-2021                          | Yes       |
| C2  | Rates: 25%, or 20% at ≤ ₱5M taxable income and ≤ ₱100M   | [rates.php `dated`](../../resources/rule-pack/rates.php)                             | Correct               | RR 5-2021                            | Note only |
| C3  | MCIT 1% from July 1, 2020 to June 30, 2023, then 2%      | same                                                                                 | Correct               | RR 5-2021                            | Note only |
| C4  | MCIT from the 4th taxable year after BIR registration    | [CorporateTax::mcitFrom](../../app/Corporations/Services/CorporateTax.php)           | Correct               | RMC 62-2021; RR 9-98                 | Note only |
| C5  | 2023 MCIT at 1.5% (months averaged)                      | [CorporateTax::mcitAverageRate](../../app/Corporations/Services/CorporateTax.php)    | Correct               | RMC 69-2023; RMC 36-2024 (secondary) | Note only |
| C6  | The 20% test on each 1702Q, on taxable income to date    | [CorporateCalculator::tax](../../app/Corporations/Services/CorporateCalculator.php)  | No clear basis found  | —                                    | No        |
| C7  | Excess MCIT: only when regular tax is due, no MCIT floor | [CorporateCalculator::year](../../app/Corporations/Services/CorporateCalculator.php) | Correct               | Sec. 27(E)(1); 1702Q, 1702-RT        | Note only |
| C8  | No NOLCO under the OSD; its years keep running           | same                                                                                 | Correct (reading)     | Sec. 34(L), 34(D)(3); 1702-RT        | Note only |
| C9  | DST on loans: DS106; 0.75% from July 1, 2025             | [rates.php `dst`](../../resources/rule-pack/rates.php)                               | Partly                | Form 2000; RR 19-2025 (secondary)    | No        |

### C1 · Total assets: incorrect, fixed

**Before.** The corporation's settings asked for "Total assets, excluding
land". The 20% explanation said the same.

**Basis.** RMC 62-2021 (**RMC**) gives three rules:

- total assets are net of depreciation and allowance for bad debts;
- only "the land where the business entity's office, plant and equipment
  are situated" is excluded, at its cost or fair value as the financial
  statements carry it, prorated by the floor area the office uses;
- land "held primarily for sale to customers or land held for investment"
  is not excluded.

RR 5-2021 (**RR**) also requires that land to be shown in its own account
in the financial statements.

**Now.** The field says "Total assets, excluding the land your office stands
on", with a line on what to leave out. The 20% explanation and the 1702Q
note say the same. A corporation that left out investment land could have
taken the 20% rate wrongly.

### C2–C5 · Rates and MCIT: correct

RR 5-2021's rate table (**RR**):

| Domestic corporations                   | Regular rate | MCIT                                                |
| --------------------------------------- | ------------ | --------------------------------------------------- |
| In general                              | 25%          | 1% from July 1, 2020 to June 30, 2023; 2% from then |
| Taxable income ≤ ₱5M and assets ≤ ₱100M | 20%          | same                                                |

Both regular rates apply from July 1, 2020. The MCIT applies "beginning on
the fourth taxable year immediately following the year in which such
corporation commenced its business operations". RMC 62-2021 (**RMC**)
reads that year as the year of BIR registration, citing RR 9-98, and gives
the example of a business that commenced in 2017 paying MCIT from 2021.
The app does the same.

For 2023, gross income is divided by 12, with 1% for January to June and
2% after. That is 1.50% for a year ending December 31, 2023 (RMC 36-2024's
table, as Grant Thornton reports it). The app averages the months' rates,
which gives the same result. RMC 36-2024's own text wasn't read, so its
`REVIEW` note stays.

### C6 · The 20% test on the 1702Q: no clear basis found

The app gives a quarter the 20% rate when the taxable income to date is
₱5,000,000 or less and the total assets entered are ₱100,000,000 or less.
RR 5-2021 and RMC 62-2021 tie the test to the taxable income "during the
taxable year" and say nothing about quarterly returns. The 1702Q (January 2018) predates CREATE and still shows 30%.

**For the CPA:** each quarter on income to date (as now), each quarter on
the year's expected income, or the annual return only, with the quarters
trued up there?

### C7 · Excess MCIT: correct

NIRC Sec. 27(E)(1) (**Law**) carries the excess of MCIT over the normal tax
forward three years, "credited against the normal income tax". The 1702Q
item 15 (**Form**) allows it "only if the quarterly's tax due is the
regular/normal rate". On the 1702-RT, item 43 takes the higher of the
normal tax and the MCIT, and item 47 subtracts the excess MCIT applied
like any other credit, so the result can go below that year's MCIT. The
app does each of these.

The app leaves 1702Q item 15 blank, as it says on that form, so the
accountant fills it in. The credit is still taken on the annual return.

### C8 · NOLCO under the OSD: correct as a reading of the law

**Not deducted under the OSD.** NIRC Sec. 34(L) (**Law**) grants the OSD
"in lieu of" the Sec. 34 deductions, and NOLCO is one of them
(Sec. 34(D)(3)). On the 1702-RT (**Form**), NOLCO (item 36) is part of
the itemized deductions (items 34–37), which are the alternative to the
OSD (item 38).

**The years keep running.** Sec. 34(D)(3) counts "the next three
consecutive taxable years", so a year on the OSD still uses one up. This
is an **Interpretation**: no regulation we read says it.

**For the CPA:** confirm the reading, and whether a loss arising in an OSD
year carries over. The app carries it.

### C9 · DST on loans: partly confirmed

Form 2000 (January 2018) (**Form**) lists DS106, "Original issue of all
Debt Instruments", at ₱1.50 per ₱200 of the issue price, prorated by days
over 365 for terms under one year. The app matches this up to June 30, 2025.

From July 1, 2025, RR 19-2025 under RA 12214 (CMEPA) sets 0.75% of the
issue price. That comes from Grant Thornton and KPMG; the RR itself
wasn't read. No Form 2000 edition after 2018 was found, so whether the ATC
changed has **no clear basis found**. The app keeps DS106 and its `REVIEW`
note.

---

## Batch 3 · Withholding, payroll, filing, books

Sources for batch 3:

- The BIR's 2316 (September 2021), items 29–42.
- Secondary: KPMG on RR 29-2025 (de minimis); GMA News on Proclamation
  No. 1427, s. 2026 (2027 holidays); news reports and the BIR's press
  release on RMC 98-2026 (EIS). The Official Gazette and PCO pages
  refused automated reading.
- Earlier research on no-payment returns:
  [notes/q5](notes/q5_withholding_and_nil_returns.md).

| #   | Rule                                                | Where                                                                                                  | Verdict                | Basis                                      | Changed   |
| --- | --------------------------------------------------- | ------------------------------------------------------------------------------------------------------ | ---------------------- | ------------------------------------------ | --------- |
| D1  | 2027 holidays                                       | [holidays.php](../../resources/rule-pack/holidays.php)                                                 | **Incomplete** → fixed | Proclamation No. 1427, s. 2026 (secondary) | Yes       |
| D2  | 2316 item 29 for pay of ₱250,000 or less            | [Form2316Items](../../app/Returns/Forms/Items/Form2316Items.php)                                       | Correct                | Form 2316 (September 2021)                 | Note only |
| D3  | De minimis limits from January 6, 2026              | [rates.php `dated`](../../resources/rule-pack/rates.php)                                               | Correct                | RR 29-2025 (secondary)                     | Note only |
| D4  | De minimis clothing ₱7,000 during 2025              | same                                                                                                   | No clear basis found   | RR 4-2025's effective date not found       | No        |
| D5  | EIS by December 31, 2026, micro taxpayers excluded  | [rates.php `eis`](../../resources/rule-pack/rates.php)                                                 | Correct                | RMC 98-2026                                | Note only |
| D6  | Income tax paid as owner's drawings                 | [GeneralLedger](../../app/Books/Services/GeneralLedger.php)                                            | Correct                | NIRC Sec. 34(C)(1)(a)                      | Note only |
| D7  | No-payment 0619-E every month from registration     | [TaxObligations](../../app/Compliance/Services/TaxObligations.php)                                     | Correct                | RMC 12-2015, RMC 28-2017 (secondary)       | No        |
| D8  | No-payment 1601-EQ and 1604-E                       | same                                                                                                   | No clear basis found   | —                                          | No        |
| D9  | New hire with a previous employer: months averaged  | [Payroll](../../app/Payroll/Services/Payroll.php)                                                      | No clear basis found   | RR 2-98 Sec. 2.79(B)(5)(a) not read        | No        |
| D10 | When withholding registration began, for both kinds | [WithholdingAgent](../../app/Withholding/Services/WithholdingAgent.php), Payroll                       | Not a rule: app gap    | —                                          | No        |
| D11 | QAP, alphalist, SAWT and SLSP file layouts          | [codes.php](../../resources/rule-pack/codes.php), [formats.php](../../resources/rule-pack/formats.php) | No clear basis found   | BIR's alphalist/RELIEF specs not read      | No        |
| D12 | Filing channel (eBIRForms; paper 1701-MS only)      | [rates.php `filing`](../../resources/rule-pack/rates.php)                                              | Stays a yearly check   | RMC 20-2026                                | No        |

### D1 · 2027 holidays: incomplete, fixed

Proclamation No. 1427, s. 2026 (signed September 8, 2026), as GMA News
lists it, gives:

- **Regular holidays:** January 1, March 25 and 26, April 9, May 1, June 12,
  August 30, November 30, December 25 and December 30.
- **Special non-working days:** August 21, November 1, December 8 and
  December 31, with February 6 (Chinese New Year), March 27, November 2
  and December 24 added.
- **Special working day:** February 25 (EDSA). It doesn't move deadlines.

The app had every date except **February 6** and **November 2**, which are
now added. The Eid holidays will be proclaimed later. Practice
(RMC 65-2016, as the app applies it) moves a deadline that falls on any of
these days to the next working day.

### D2 · 2316 item 29: correct

The 2316 (September 2021) (**Form**) labels item 29 "Basic Salary
(including the exempt P250,000 & below) or the Statutory Minimum Wage of
the MWE". It sits under non-taxable compensation; item 39 is basic salary
under taxable compensation. The app puts basic pay in item 29 for a minimum
wage earner or for a year's pay of ₱250,000 or less, and in item 39
otherwise.

### D3–D4 · De minimis: 2026 correct; 2025 open

RR 29-2025 (dated October 27, 2025; in effect January 6, 2026, per KPMG)
sets these limits, which the app already applies from that date:

| Benefit                              | Limit                    |
| ------------------------------------ | ------------------------ |
| Rice subsidy                         | ₱2,500 a month           |
| Uniform and clothing                 | ₱8,000 a year            |
| Medical cash allowance to dependents | ₱2,000 a semester (₱333) |
| Laundry                              | ₱400 a month             |

The app lists only these four. KPMG's table also covers:

- actual medical assistance, achievement awards, and CBA or productivity
  incentives: ₱12,000 a year each;
- Christmas and anniversary gifts: ₱6,000 a year;
- unused vacation leave converted to cash: up to 12 days a year;
- overtime and night-shift meals: up to 30% of the basic minimum wage.

RR 4-2025 raised clothing to ₱7,000 earlier in 2025. We didn't find when
it took effect, so 2025 keeps ₱6,000. **For the CPA:** RR 4-2025's
effective date, and whether the app should list the other benefits.

### D5 · E-invoicing: correct

RMC 98-2026 (September 22, 2026) requires small, medium and large
taxpayers in e-commerce or internet sales, among others, to issue
electronic invoices by December 31, 2026. Micro taxpayers are not covered.
That comes from the BIR's press release and news reports; the RMC itself
wasn't read in full. The app's reminder matches. The number is now cited.

### D6 · Income tax as drawings: correct

NIRC Sec. 34(C)(1)(a) (**Law**) excludes "the income tax provided for under
this Title" from deductible taxes. A sole proprietor's income tax is
therefore not a business expense. The ledger books it as owner's drawings.
The chart of accounts as a whole and the payroll entries remain for the
CPA.

### D7–D8 · No-payment returns

RMC 12-2015 and RMC 28-2017, as summarized by KPMG and others, require
business taxpayers to file no-payment returns electronically rather than
skip them. One summary names the monthly 0619-E. The app applies the same
rule to the 1601-EQ and 1604-E, but no source we found says so. **For the
CPA:** confirm the quarterly 1601-EQ and annual 1604-E with nothing
withheld.

### D9–D10 · Payroll and registration

- **D9:** for a new hire who had another employer earlier in the year
  (RR 2-98 Sec. 2.79(B)(5)(a)), the app averages over the months from
  January. The section's text wasn't read.
- **D10:** the app takes the start of withholding from the first payment
  withheld on, or the BIR registration date. It doesn't ask when the
  withholding tax types were added to the COR. This is a missing input, not
  a rule question; a field for it would settle both notes.

### D11–D12 · File layouts and the filing channel

The `.DAT` layouts for the QAP, the 1604-E alphalist, the SAWT and the
RELIEF/SLSP follow the alphalist and RELIEF data entry modules as the app
understands them. The BIR's technical specifications weren't read, so each
file should be tested by validating it in the BIR's module before relying
on it.

The filing channel follows RMC 20-2026 and is meant to be checked each
year.

---

All three batches are done. What's left for the CPA is every
"No clear basis found" above, with its question, plus the sign-off of each
rule-pack part.
