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

Batches still to come:

- **Batch 2, corporations:** the quarterly 20% test, 2023 MCIT, excess MCIT,
  NOLCO under the OSD, the 1% MCIT period, the DST code.
- **Batch 3, withholding and filing:** nil 1601-EQ and 1604-E, 2316 item 29,
  payroll periods, registration dates, file layouts, 2027 holidays, the EIS
  circular, chart of accounts, clothing ₱7,000.
