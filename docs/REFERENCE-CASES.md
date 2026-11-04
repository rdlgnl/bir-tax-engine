<!-- Generated from tests/Feature/Acceptance/ReferenceCasesTest.php. Change the cases there, then run: REFERENCE_CASES_WRITE=1 php artisan test --filter=ReferenceCases -->

# Reference cases

Worked returns from BuwisBuddy for a CPA or tax lawyer to check, alongside the rule review in [RULE-REVIEW.md](RULE-REVIEW.md). BuwisBuddy is open source and was released without a professional review; send what you find as an issue or pull request (see [CONTRIBUTING.md](../CONTRIBUTING.md)). Each case is a typical taxpayer: the facts that matter, then each return the app prepares for them, line by line, as its return page lists the amounts to copy into eBIRForms. Every return is prepared on its due date and paid in full that day, except in case R8.

**What we ask:** for each line, tick the CPA column if the amount is right, or write the right amount and its basis. Note under the return any line that's missing or shouldn't be there, and answer the case's question where it has one.

Once you confirm or correct the figures, the app is tested against them: until it computes a corrected figure, its tests fail.

| Case | Taxpayer | Returns |
| ---- | -------- | ------- |
| R1 | Freelance designer on the 8% option | 1701Q, 1701-MS |
| R2 | Consultant on graduated rates with the 40% optional standard deduction | 2551Q, 1701Q, 1701-MS |
| R3 | Professional on graduated rates with itemized deductions | 2551Q, 1701Q, 1701-MS |
| R4 | Employee with a side business on the 8% option | 1701Q, 1701 |
| R5 | Sales pass ₱3,000,000 on the 8% option | 2551Q, 1701Q |
| R6 | VAT-registered professional | 2550Q, 1701Q |
| R7 | A 2022 quarter, on the rules of that year | 2551Q, 1701Q |
| R8 | A quarterly return filed a month late | 1701Q |
| R9 | An employer with one employee | 1601-C, 2316 |
| R10 | A corporation on the 20% rate, compared with MCIT | 1702Q, 1702-RT |

## R1 · Freelance designer on the 8% option

- Purely self-employed, registered with the BIR in 2024; not VAT-registered.
- Elected the 8% option on the Q1 2026 return.
- Sales in 2026: ₱300,000 in Q1; ₱350,000 in Q2, of which ₱100,000 from Acme Corp., which withheld ₱5,000 (5%) and gave a 2307; ₱250,000 in Q3; ₱300,000 in Q4. ₱1,200,000 for the year.

### 1701Q, Q1 2026, filed May 15, 2026

| Line | Amount (₱) | CPA |
| ---- | ---------: | --- |
| Sales/receipts, this quarter | 300,000.00 |     |
| Sales/receipts, previous quarters | 0.00 |     |
| Less: allowable reduction from gross sales | 250,000.00 |     |
| Taxable income to date | 50,000.00 |     |
| Tax due (8%) | 4,000.00 |     |
| Less: tax paid in previous quarters | 0.00 |     |
| Less: creditable tax withheld, previous quarters | 0.00 |     |
| Less: creditable tax withheld, this quarter | 0.00 |     |
| Tax payable | 4,000.00 |     |

### 1701Q, Q2 2026, filed August 17, 2026

| Line | Amount (₱) | CPA |
| ---- | ---------: | --- |
| Sales/receipts, this quarter | 350,000.00 |     |
| Sales/receipts, previous quarters | 300,000.00 |     |
| Less: allowable reduction from gross sales | 250,000.00 |     |
| Taxable income to date | 400,000.00 |     |
| Tax due (8%) | 32,000.00 |     |
| Less: tax paid in previous quarters | 4,000.00 |     |
| Less: creditable tax withheld, previous quarters | 0.00 |     |
| Less: creditable tax withheld, this quarter | 5,000.00 |     |
| Tax payable | 23,000.00 |     |

### 1701Q, Q3 2026, filed November 16, 2026

| Line | Amount (₱) | CPA |
| ---- | ---------: | --- |
| Sales/receipts, this quarter | 250,000.00 |     |
| Sales/receipts, previous quarters | 650,000.00 |     |
| Less: allowable reduction from gross sales | 250,000.00 |     |
| Taxable income to date | 650,000.00 |     |
| Tax due (8%) | 52,000.00 |     |
| Less: tax paid in previous quarters | 27,000.00 |     |
| Less: creditable tax withheld, previous quarters | 5,000.00 |     |
| Less: creditable tax withheld, this quarter | 0.00 |     |
| Tax payable | 20,000.00 |     |

### 1701-MS, 2026 annual, filed April 15, 2027

| Line | Amount (₱) | CPA |
| ---- | ---------: | --- |
| Sales/receipts for the year | 1,200,000.00 |     |
| Less: allowable reduction from gross sales | 250,000.00 |     |
| Taxable income to date | 950,000.00 |     |
| Tax due (8%) | 76,000.00 |     |
| Less: tax paid in Q1 to Q3 returns | 47,000.00 |     |
| Less: creditable tax withheld for the year | 5,000.00 |     |
| Tax payable | 24,000.00 |     |

## R2 · Consultant on graduated rates with the 40% optional standard deduction

- Purely self-employed, registered for income tax and percentage tax; not VAT-registered.
- Graduated rates with the optional standard deduction (OSD) for 2026.
- Sales in 2026: ₱400,000 in Q1, ₱500,000 in Q2, ₱450,000 in Q3, ₱450,000 in Q4. ₱1,800,000 for the year. No tax withheld by clients.

### 2551Q, Q1 2026, filed April 27, 2026

| Line | Amount (₱) | CPA |
| ---- | ---------: | --- |
| Taxable amount (sales, this quarter) | 400,000.00 |     |
| Tax due (3%) | 12,000.00 |     |
| Less: tax credits / payments | 0.00 |     |
| Tax payable | 12,000.00 |     |

### 1701Q, Q1 2026, filed May 15, 2026

| Line | Amount (₱) | CPA |
| ---- | ---------: | --- |
| Sales/receipts, this quarter | 400,000.00 |     |
| Sales/receipts, previous quarters | 0.00 |     |
| Less: optional standard deduction (40%) | 160,000.00 |     |
| Taxable income to date | 240,000.00 |     |
| Tax due (graduated rates) | 0.00 |     |
| Less: tax paid in previous quarters | 0.00 |     |
| Less: creditable tax withheld, previous quarters | 0.00 |     |
| Less: creditable tax withheld, this quarter | 0.00 |     |
| Tax payable | 0.00 |     |

### 1701Q, Q2 2026, filed August 17, 2026

| Line | Amount (₱) | CPA |
| ---- | ---------: | --- |
| Sales/receipts, this quarter | 500,000.00 |     |
| Sales/receipts, previous quarters | 400,000.00 |     |
| Less: optional standard deduction (40%) | 360,000.00 |     |
| Taxable income to date | 540,000.00 |     |
| Tax due (graduated rates) | 50,500.00 |     |
| Less: tax paid in previous quarters | 0.00 |     |
| Less: creditable tax withheld, previous quarters | 0.00 |     |
| Less: creditable tax withheld, this quarter | 0.00 |     |
| Tax payable | 50,500.00 |     |

### 1701Q, Q3 2026, filed November 16, 2026

| Line | Amount (₱) | CPA |
| ---- | ---------: | --- |
| Sales/receipts, this quarter | 450,000.00 |     |
| Sales/receipts, previous quarters | 900,000.00 |     |
| Less: optional standard deduction (40%) | 540,000.00 |     |
| Taxable income to date | 810,000.00 |     |
| Tax due (graduated rates) | 105,000.00 |     |
| Less: tax paid in previous quarters | 50,500.00 |     |
| Less: creditable tax withheld, previous quarters | 0.00 |     |
| Less: creditable tax withheld, this quarter | 0.00 |     |
| Tax payable | 54,500.00 |     |

### 1701-MS, 2026 annual, filed April 15, 2027

| Line | Amount (₱) | CPA |
| ---- | ---------: | --- |
| Sales/receipts for the year | 1,800,000.00 |     |
| Less: optional standard deduction (40%) | 720,000.00 |     |
| Taxable income to date | 1,080,000.00 |     |
| Tax due (graduated rates) | 172,500.00 |     |
| Less: tax paid in Q1 to Q3 returns | 105,000.00 |     |
| Less: creditable tax withheld for the year | 0.00 |     |
| Tax payable | 67,500.00 |     |

## R3 · Professional on graduated rates with itemized deductions

- Purely self-employed, registered for income tax and percentage tax; not VAT-registered; books kept on the accrual method.
- Graduated rates with itemized deductions for 2026.
- Sales of ₱250,000 each quarter, ₱1,000,000 for the year. Expenses of ₱90,000 each quarter (office rent and supplies), each with an invoice in the taxpayer's name: ₱360,000 for the year.

**Please check:** The 3% percentage tax paid (₱7,500 a quarter) isn't among the itemized deductions. Should it be deducted as a tax on the business (NIRC Sec. 34(C))?

### 2551Q, Q3 2026, filed October 26, 2026

| Line | Amount (₱) | CPA |
| ---- | ---------: | --- |
| Taxable amount (sales, this quarter) | 250,000.00 |     |
| Tax due (3%) | 7,500.00 |     |
| Less: tax credits / payments | 0.00 |     |
| Tax payable | 7,500.00 |     |

### 1701Q, Q3 2026, filed November 16, 2026

| Line | Amount (₱) | CPA |
| ---- | ---------: | --- |
| Sales/receipts, this quarter | 250,000.00 |     |
| Sales/receipts, previous quarters | 500,000.00 |     |
| Less: allowable itemized deductions | 270,000.00 |     |
| Taxable income to date | 480,000.00 |     |
| Tax due (graduated rates) | 38,500.00 |     |
| Less: tax paid in previous quarters | 10,500.00 |     |
| Less: creditable tax withheld, previous quarters | 0.00 |     |
| Less: creditable tax withheld, this quarter | 0.00 |     |
| Tax payable | 28,000.00 |     |

### 1701-MS, 2026 annual, filed April 15, 2027

| Line | Amount (₱) | CPA |
| ---- | ---------: | --- |
| Sales/receipts for the year | 1,000,000.00 |     |
| Less: allowable itemized deductions | 360,000.00 |     |
| Taxable income to date | 640,000.00 |     |
| Tax due (graduated rates) | 70,500.00 |     |
| Less: tax paid in Q1 to Q3 returns | 38,500.00 |     |
| Less: creditable tax withheld for the year | 0.00 |     |
| Tax payable | 32,000.00 |     |

## R4 · Employee with a side business on the 8% option

- Employed all of 2026 at ₱50,000 a month (₱600,000 for the year, no 13th month recorded), with the employer withholding the full tax on it.
- A side business on the 8% option: ₱40,000 in Q1, ₱40,000 in Q2, ₱90,000 in Q3, ₱30,000 in Q4. ₱200,000 for the year.
- With a salary, the ₱250,000 reduction doesn't apply to the business income.

### 1701Q, Q3 2026, filed November 16, 2026

| Line | Amount (₱) | CPA |
| ---- | ---------: | --- |
| Sales/receipts, this quarter | 90,000.00 |     |
| Sales/receipts, previous quarters | 80,000.00 |     |
| Less: allowable reduction (none with a job) | 0.00 |     |
| Taxable income to date | 170,000.00 |     |
| Tax due (8%) | 13,600.00 |     |
| Less: tax paid in previous quarters | 6,400.00 |     |
| Less: creditable tax withheld, previous quarters | 0.00 |     |
| Less: creditable tax withheld, this quarter | 0.00 |     |
| Tax payable | 7,200.00 |     |

### 1701, 2026 annual, filed April 15, 2027

| Line | Amount (₱) | CPA |
| ---- | ---------: | --- |
| Sales/receipts for the year | 200,000.00 |     |
| Less: allowable reduction (none with a job) | 0.00 |     |
| Taxable business income | 200,000.00 |     |
| Tax due on business (8%) | 16,000.00 |     |
| Taxable salary (Northwind BPO Inc.) | 600,000.00 |     |
| Tax due on salary (graduated rates) | 62,500.00 |     |
| Total tax due | 78,500.00 |     |
| Less: tax paid in Q1 to Q3 returns | 13,600.00 |     |
| Less: creditable tax withheld for the year | 0.00 |     |
| Less: tax withheld by your employer (2316) | 62,500.00 |     |
| Tax payable | 2,400.00 |     |

## R5 · Sales pass ₱3,000,000 on the 8% option

- Purely self-employed on the 8% option, registered for percentage tax; not VAT-registered.
- Sales of ₱1,000,000 in February and ₱1,000,000 in May; ₱800,000 in July and ₱500,000 in August, passing ₱3,000,000 in August.
- A ₱500,000 expense in September with an invoice in the taxpayer's name.
- Q1 and Q2 were filed and paid on time at 8%.

**Please check:** The Q3 2551Q carries percentage tax on all sales from January to August, and the Q3 1701Q switches to graduated rates with itemized deductions for the year to date, crediting the 8% tax paid in Q1 and Q2 (RR 8-2018). Is that how both should be filed?

### 2551Q, Q3 2026, filed October 26, 2026

| Line | Amount (₱) | CPA |
| ---- | ---------: | --- |
| Taxable amount (sales, this quarter) | 3,300,000.00 |     |
| Tax due (3%) | 99,000.00 |     |
| Less: tax credits / payments | 0.00 |     |
| Tax payable | 99,000.00 |     |

### 1701Q, Q3 2026, filed November 16, 2026

| Line | Amount (₱) | CPA |
| ---- | ---------: | --- |
| Sales/receipts, this quarter | 1,300,000.00 |     |
| Sales/receipts, previous quarters | 2,000,000.00 |     |
| Less: allowable itemized deductions | 500,000.00 |     |
| Taxable income to date | 2,800,000.00 |     |
| Tax due (graduated rates) | 642,500.00 |     |
| Less: tax paid in previous quarters | 140,000.00 |     |
| Less: creditable tax withheld, previous quarters | 0.00 |     |
| Less: creditable tax withheld, this quarter | 0.00 |     |
| Tax payable | 502,500.00 |     |

## R6 · VAT-registered professional

- Purely self-employed, VAT-registered (so not registered for percentage tax), on graduated rates with the OSD.
- Q3 2026 sales: ₱500,000 in July and ₱300,000 in August, each plus 12% VAT (₱60,000 and ₱36,000).
- Q3 purchases: ₱200,000 plus ₱24,000 VAT in August, with a VAT invoice in the taxpayer's name. No sales or purchases in Q1 and Q2.

### 2550Q, Q3 2026, filed October 26, 2026

| Line | Amount (₱) | CPA |
| ---- | ---------: | --- |
| VATable sales | 800,000.00 |     |
| Output VAT (12%) | 96,000.00 |     |
| Domestic purchases | 200,000.00 |     |
| Less: input VAT on purchases | 24,000.00 |     |
| VAT payable | 72,000.00 |     |

### 1701Q, Q3 2026, filed November 16, 2026

| Line | Amount (₱) | CPA |
| ---- | ---------: | --- |
| Sales/receipts, this quarter | 800,000.00 |     |
| Sales/receipts, previous quarters | 0.00 |     |
| Less: optional standard deduction (40%) | 320,000.00 |     |
| Taxable income to date | 480,000.00 |     |
| Tax due (graduated rates) | 38,500.00 |     |
| Less: tax paid in previous quarters | 0.00 |     |
| Less: creditable tax withheld, previous quarters | 0.00 |     |
| Less: creditable tax withheld, this quarter | 0.00 |     |
| Tax payable | 38,500.00 |     |

## R7 · A 2022 quarter, on the rules of that year

- Purely self-employed since 2019, on graduated rates with the OSD, registered for percentage tax.
- Sales of ₱300,000 in Q1 2022 and ₱400,000 in Q2 2022.
- For 2022: the 2018–2022 graduated table, and percentage tax at 1% (CREATE, July 2020 to June 2023).

### 2551Q, Q2 2022, filed July 25, 2022

| Line | Amount (₱) | CPA |
| ---- | ---------: | --- |
| Taxable amount (sales, this quarter) | 400,000.00 |     |
| Tax due (1%) | 4,000.00 |     |
| Less: tax credits / payments | 0.00 |     |
| Tax payable | 4,000.00 |     |

### 1701Q, Q2 2022, filed August 15, 2022

| Line | Amount (₱) | CPA |
| ---- | ---------: | --- |
| Sales/receipts, this quarter | 400,000.00 |     |
| Sales/receipts, previous quarters | 300,000.00 |     |
| Less: optional standard deduction (40%) | 280,000.00 |     |
| Taxable income to date | 420,000.00 |     |
| Tax due (graduated rates) | 35,000.00 |     |
| Less: tax paid in previous quarters | 0.00 |     |
| Less: creditable tax withheld, previous quarters | 0.00 |     |
| Less: creditable tax withheld, this quarter | 0.00 |     |
| Tax payable | 35,000.00 |     |

## R8 · A quarterly return filed a month late

- Purely self-employed on the 8% option; a micro taxpayer (sales under ₱3,000,000), so the reduced EOPT penalties apply.
- Sales of ₱400,000 in Q1 2026.
- The Q1 1701Q, due May 15, 2026, was filed and paid on June 15, 2026.

**Please check:** Only the surcharge and interest are added. Should a compromise penalty be suggested too?

### 1701Q, Q1 2026, filed June 15, 2026

| Line | Amount (₱) | CPA |
| ---- | ---------: | --- |
| Sales/receipts, this quarter | 400,000.00 |     |
| Sales/receipts, previous quarters | 0.00 |     |
| Less: allowable reduction from gross sales | 250,000.00 |     |
| Taxable income to date | 150,000.00 |     |
| Tax due (8%) | 12,000.00 |     |
| Less: tax paid in previous quarters | 0.00 |     |
| Less: creditable tax withheld, previous quarters | 0.00 |     |
| Less: creditable tax withheld, this quarter | 0.00 |     |
| Tax payable | 12,000.00 |     |
| Add: surcharge (10%) | 1,200.00 |     |
| Add: interest (6% a year, 31 days) | 61.00 |     |
| Total amount payable | 13,261.00 |     |

## R9 · An employer with one employee

- The taxpayer hired one employee on October 1, 2026, at ₱30,000 a month, with no contributions or de minimis benefits entered.
- Her 13th month for October to December, ₱7,500, is paid with December's pay.
- Tax is withheld each month by the BIR withholding table, with the year-end adjustment in December.

**Please check:** The November 1601-C withholds ₱1,375.05 by the table, yet lists the same ₱30,000 as taxable compensation not withheld on (item 23), because her pay for the year, ₱90,000, is under ₱250,000. Which is right for October and November, and should the December refund be shown this way?

### 1601-C, November 2026

| Line | Amount (₱) | CPA |
| ---- | ---------: | --- |
| Total compensation | 30,000.00 |     |
| Less: minimum wage pay | 0.00 |     |
| Less: 13th month and other benefits, untaxed | 0.00 |     |
| Less: de minimis benefits | 0.00 |     |
| Less: employee's contributions | 0.00 |     |
| Taxable compensation | 30,000.00 |     |
| Taxable compensation not withheld on | 30,000.00 |     |
| Tax withheld | 1,375.05 |     |
| Year-end refund to the employee | 0.00 |     |

### 1601-C, December 2026

| Line | Amount (₱) | CPA |
| ---- | ---------: | --- |
| Total compensation | 37,500.00 |     |
| Less: minimum wage pay | 0.00 |     |
| Less: 13th month and other benefits, untaxed | 7,500.00 |     |
| Less: de minimis benefits | 0.00 |     |
| Less: employee's contributions | 0.00 |     |
| Taxable compensation | 30,000.00 |     |
| Taxable compensation not withheld on | 30,000.00 |     |
| Tax withheld | 0.00 |     |
| Year-end refund to the employee | 2,750.10 |     |

### 2316 for 2026

| Line | Amount (₱) | CPA |
| ---- | ---------: | --- |
| Total compensation, with the 13th month | 97,500.00 |     |
| 13th month and other benefits | 7,500.00 |     |
| Of which untaxed | 7,500.00 |     |
| Taxable compensation | 90,000.00 |     |
| Tax due for the year | 0.00 |     |
| Tax withheld before December | 2,750.10 |     |
| Refunded in December | 2,750.10 |     |

## R10 · A corporation on the 20% rate, compared with MCIT

- A domestic corporation registered with the BIR in 2022, so MCIT applies from 2026. Total assets of ₱30,000,000, excluding land.
- Each quarter of 2026: sales of ₱3,000,000 and cost of sales of ₱1,800,000. Deductions of ₱700,000, ₱650,000, ₱750,000 and ₱800,000; creditable tax withheld of ₱25,000, ₱25,000, ₱40,000 and ₱40,000.
- Q1 and Q2 were filed and paid on time.

**Please check:** The 1702Q applies the 20% rate to taxable income to date; the rule review's question Q6 asks whether that's how the quarterly return tests it.

### 1702Q, Q1 2026, filed June 1, 2026

| Line | Amount (₱) | CPA |
| ---- | ---------: | --- |
| Sales to date | 3,000,000.00 |     |
| Less: cost of sales | 1,800,000.00 |     |
| Gross income to date | 1,200,000.00 |     |
| Less: deductions | 700,000.00 |     |
| Taxable income to date | 500,000.00 |     |
| Regular tax (20%) | 100,000.00 |     |
| MCIT (2% of gross income to date) | 24,000.00 |     |
| Tax due (the higher) | 100,000.00 |     |
| Less: tax paid in previous quarters | 0.00 |     |
| Less: creditable tax withheld | 25,000.00 |     |
| Tax payable | 75,000.00 |     |

### 1702Q, Q2 2026, filed September 1, 2026

| Line | Amount (₱) | CPA |
| ---- | ---------: | --- |
| Sales to date | 6,000,000.00 |     |
| Less: cost of sales | 3,600,000.00 |     |
| Gross income to date | 2,400,000.00 |     |
| Less: deductions | 1,350,000.00 |     |
| Taxable income to date | 1,050,000.00 |     |
| Regular tax (20%) | 210,000.00 |     |
| MCIT (2% of gross income to date) | 48,000.00 |     |
| Tax due (the higher) | 210,000.00 |     |
| Less: tax paid in previous quarters | 75,000.00 |     |
| Less: creditable tax withheld | 50,000.00 |     |
| Tax payable | 85,000.00 |     |

### 1702Q, Q3 2026, filed December 1, 2026

| Line | Amount (₱) | CPA |
| ---- | ---------: | --- |
| Sales to date | 9,000,000.00 |     |
| Less: cost of sales | 5,400,000.00 |     |
| Gross income to date | 3,600,000.00 |     |
| Less: deductions | 2,100,000.00 |     |
| Taxable income to date | 1,500,000.00 |     |
| Regular tax (20%) | 300,000.00 |     |
| MCIT (2% of gross income to date) | 72,000.00 |     |
| Tax due (the higher) | 300,000.00 |     |
| Less: tax paid in previous quarters | 160,000.00 |     |
| Less: creditable tax withheld | 90,000.00 |     |
| Tax payable | 50,000.00 |     |

### 1702-RT for 2026

| Line | Amount (₱) | CPA |
| ---- | ---------: | --- |
| Sales | 12,000,000.00 |     |
| Less: cost of sales | 7,200,000.00 |     |
| Gross income | 4,800,000.00 |     |
| Less: deductions | 2,900,000.00 |     |
| Taxable income | 1,900,000.00 |     |
| Regular tax (20%) | 380,000.00 |     |
| MCIT (2% of gross income) | 96,000.00 |     |
| Tax due (the higher) | 380,000.00 |     |
| Less: tax paid in Q1 to Q3 | 210,000.00 |     |
| Less: creditable tax withheld for the year | 130,000.00 |     |
| Tax payable | 40,000.00 |     |

---

## Sign-off

|                       |     |
| --------------------- | --- |
| Reviewer              |     |
| CPA / PRC licence no. |     |
| Date of review        |     |
| Cases changed         |     |
| Signature             |     |
