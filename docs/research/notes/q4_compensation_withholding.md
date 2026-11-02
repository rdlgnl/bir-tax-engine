# Philippine Withholding Tax on Compensation for Small Employers (as of November 2026)

Scope: a self-employed individual who has a few employees and acts as employer and withholding agent. Status labels used throughout:

- **Confirmed**: read in the primary BIR text (issuance and section or URL given).
- **Interpretation**: my reading of confirmed text, or a practice point.
- **Needs verification**: secondary source only, conflicting sources, or not found in primary text.

Primary documents I downloaded and read (all from the BIR CDN unless noted):

- RR 11-2018 (amends RR 2-98 Secs. 2.78–2.83): https://bir-cdn.bir.gov.ph/local/pdf/RR%20No.%2011-2018.pdf
- Annex "E", the withholding tax table effective January 1, 2023: https://bir-cdn.bir.gov.ph/local/pdf/Annex%20E%20RR%2011-2018.pdf
- RR 8-2018: https://bir-cdn.bir.gov.ph/local/pdf/RR%20No.%208-2018.pdf
- RR 4-2025 (scanned; I read it by OCR): https://bir-cdn.bir.gov.ph/BIR/pdf/RR%20No.%204-2025.pdf
- RR 29-2025 (scanned; I read it by OCR): https://bir-cdn.bir.gov.ph/BIR/pdf/RR%20No.%2029-2025.pdf
- RR 2-2015 (Deloitte-hosted copy of the BIR issuance): https://www.deloitte.com/content/dam/assets-zone1/southeast-asia/en/docs/services/tax/2024/ph-revenue-regulations-2-2015.pdf
- RR 16-2021: https://bir-cdn.bir.gov.ph/local/pdf/RR%20No.%2016-2021.pdf
- RMC 29-2024 (scanned; I read it by OCR): https://bir-cdn.bir.gov.ph/BIR/pdf/RMC%20No.%2029-2024.pdf
- BIR Form 2316, September 2021 (ENCS): https://bir-cdn.bir.gov.ph/local/pdf/2316%20Sep%202021%20ENCS_Final_corrected.pdf
- The BIR Forms page (https://www.bir.gov.ph/bir-forms), which loads its entries from the BIR CMS API. I read its 1601-C, 1604-C and 2316 filing-date entries.

## Q1. Withholding method: is the RR 11-2018 Annex E table mandatory, or can an employer annualize? How is each month's withholding computed?

### Takeaway

The table is the default rule. Every employer withholds each payroll period using Annex "E" (effective January 1, 2023 onward). Two exceptional methods are prescribed rather than optional. The cumulative average method applies in three listed situations. The annualized method applies at year-end and when employment ends before December. I found no provision that lets an employer annualize every payroll period instead of using the table.

### Cited Findings

- **Confirmed.** Sec. 2.79(B)(1) of RR 2-98, as amended by RR 11-2018: "every employer making payment of compensation shall deduct and withhold from such compensation a tax determined in accordance with the prescribed withholding tax table, Annex 'D' for compensation paid from January 1, 2018 until December 31, 2022 … and Annex 'E' for compensation paid starting January 1, 2023." — [RR 11-2018, Sec. 2.79(B)(1)](https://bir-cdn.bir.gov.ph/local/pdf/RR%20No.%2011-2018.pdf)
- **Confirmed.** Pay periods other than daily, weekly, semi-monthly or monthly:
    - Annual pay uses the annualized computation in Sec. 2.79(B)(5)(b).
    - Quarterly or semi-annual pay is divided by 3 or 6. The monthly table is applied to that average, and the result is multiplied back by 3 or 6.
    - — [RR 11-2018, Sec. 2.79(B)(1)](https://bir-cdn.bir.gov.ph/local/pdf/RR%20No.%2011-2018.pdf)
- **Confirmed.** Steps per payroll period, from Sec. 2.79(B)(3):
    - Step 1: Total the monetary and non-monetary compensation for the period, "excluding non-taxable benefits and mandatory contributions." Classify it as **regular** or **supplementary**:
        - Regular: basic salary and fixed allowances such as representation and transportation.
        - Supplementary: commission, overtime pay, taxable retirement pay, taxable bonus and other taxable benefits.
    - Step 2: Pick the Annex "E" table for the payroll period.
    - Step 3: Find the compensation range "by taking into account only the total amount of taxable regular compensation income."
    - Step 4: Tax = the prescribed tax for the bracket + the rate × (compensation − the bracket minimum).
    - — [RR 11-2018, Sec. 2.79(B)(3)](https://bir-cdn.bir.gov.ph/local/pdf/RR%20No.%2011-2018.pdf)
- **Confirmed.** In Illustration 9 of the regulation, the bracket is chosen from regular pay, and the supplementary pay (₱5,000) is then added to the excess over the bracket minimum. The worked line reads "(₱165,000.00 − ₱66,667.00 + Supplemental compensation of ₱5,000.00) x 30%". — [RR 11-2018, Illustration 9](https://bir-cdn.bir.gov.ph/local/pdf/RR%20No.%2011-2018.pdf)
- **Confirmed. Annex "E" MONTHLY table, effective January 1, 2023 and onwards:**

    | Bracket | Monthly compensation | Prescribed withholding          |
    | ------- | -------------------- | ------------------------------- |
    | 1       | ₱20,833 and below    | 0.00                            |
    | 2       | ₱20,833 – ₱33,332    | 0.00 + 15% over ₱20,833         |
    | 3       | ₱33,333 – ₱66,666    | ₱1,875.00 + 20% over ₱33,333    |
    | 4       | ₱66,667 – ₱166,666   | ₱8,541.80 + 25% over ₱66,667    |
    | 5       | ₱166,667 – ₱666,666  | ₱33,541.80 + 30% over ₱166,667  |
    | 6       | ₱666,667 and above   | ₱183,541.80 + 35% over ₱666,667 |

    — [BIR Annex "E", RR 11-2018](https://bir-cdn.bir.gov.ph/local/pdf/Annex%20E%20RR%2011-2018.pdf)

- **Confirmed. Annex "E" SEMI-MONTHLY:**
    - ≤ ₱10,417: 0
    - ₱10,417–16,666: 0 + 15% over ₱10,417
    - ₱16,667–33,332: ₱937.50 + 20% over ₱16,667
    - ₱33,333–83,332: ₱4,270.70 + 25% over ₱33,333
    - ₱83,333–333,332: ₱16,770.70 + 30% over ₱83,333
    - ≥ ₱333,333: ₱91,770.70 + 35% over ₱333,333
    - — [BIR Annex "E"](https://bir-cdn.bir.gov.ph/local/pdf/Annex%20E%20RR%2011-2018.pdf)
- **Confirmed. Annex "E" WEEKLY:**
    - ≤ ₱4,808: 0
    - ₱4,808–7,691: 0 + 15% over ₱4,808
    - ₱7,692–15,384: ₱432.60 + 20% over ₱7,692
    - ₱15,385–38,461: ₱1,971.20 + 25% over ₱15,385
    - ₱38,462–153,845: ₱7,740.45 + 30% over ₱38,462
    - ≥ ₱153,846: ₱42,355.65 + 35% over ₱153,846
    - — [BIR Annex "E"](https://bir-cdn.bir.gov.ph/local/pdf/Annex%20E%20RR%2011-2018.pdf)
- **Confirmed. Annex "E" DAILY:**
    - ≤ ₱685: 0
    - ₱685–1,095: 0 + 15% over ₱685
    - ₱1,096–2,191: ₱61.65 + 20% over ₱1,096
    - ₱2,192–5,478: ₱280.85 + 25% over ₱2,192
    - ₱5,479–21,917: ₱1,102.60 + 30% over ₱5,479
    - ≥ ₱21,918: printed as "P 6,034.00.30" + 35% over ₱21,918. That is a typo in the BIR PDF; see Gaps.
    - — [BIR Annex "E"](https://bir-cdn.bir.gov.ph/local/pdf/Annex%20E%20RR%2011-2018.pdf)
- **Confirmed. Annual graduated rates for 2023 onward**, used for the annualized computation and year-end adjustment:

    | Annual taxable compensation | Tax                              |
    | --------------------------- | -------------------------------- |
    | Up to ₱250,000              | 0                                |
    | ₱250,000–400,000            | 15% over ₱250,000                |
    | ₱400,000–800,000            | ₱22,500 + 20% over ₱400,000      |
    | ₱800,000–2,000,000          | ₱102,500 + 25% over ₱800,000     |
    | ₱2,000,000–8,000,000        | ₱402,500 + 30% over ₱2,000,000   |
    | Over ₱8,000,000             | ₱2,202,500 + 35% over ₱8,000,000 |

    — [RR 11-2018, Sec. 2.79(B)(5)(b), Step 3(b)](https://bir-cdn.bir.gov.ph/local/pdf/RR%20No.%2011-2018.pdf)

- **Confirmed. Cumulative average method**, Sec. 2.79(B)(5)(a). It is required when any of these applies:
    1. Regular pay is below the taxable level, but supplementary pay is paid during the year.
    2. Supplementary pay is equal to or more than regular pay.
    3. The employee is a new hire who had a previous employer in the same calendar year.

    The steps:
    1. Add the year-to-date taxable regular and supplementary pay, including pay from previous employers.
    2. Divide by the number of payroll periods.
    3. Apply the table to that average.
    4. Multiply the result by the number of periods.
    5. Withhold the excess over the tax already withheld, including tax withheld by previous employers.

    "The cumulative average method, once applicable to a particular employee at any time during the calendar year, shall be the same method to be consistently used for the remaining payroll period/s of the same calendar year." — [RR 11-2018, Sec. 2.79(B)(5)(a)](https://bir-cdn.bir.gov.ph/local/pdf/RR%20No.%2011-2018.pdf)

- **Confirmed.** An employee who fails or refuses to file BIR Form 1902 is withheld "under the revised withholding tax table (Annex 'D' or 'E', whichever is applicable)" (Sec. 2.79.2). Spouses are each withheld separately under the table (Sec. 2.79.4). — [RR 11-2018, Secs. 9–10](https://bir-cdn.bir.gov.ph/local/pdf/RR%20No.%2011-2018.pdf)
- **Confirmed.** Taxable fringe benefits of managerial and supervisory employees are subject to the 35% final fringe benefits tax on the grossed-up value. They are not part of the compensation run through the table. — [RR 11-2018, Sec. 2.79(B)(5)(b) Step 2(c) and Sec. 2.79(D)](https://bir-cdn.bir.gov.ph/local/pdf/RR%20No.%2011-2018.pdf)
- **Needs verification.** Secondary payroll guides dated 2026 say the 2026 table is unchanged from the January 1, 2023 table. — [pesohub.ph](https://www.pesohub.ph/government/bir/withholding-tax-table-philippines/), [sweldoph.com](https://sweldoph.com/guides/bir-withholding-tax). I found no later RR that replaces Annex "E", which is consistent with that.

### Inferences

- **Interpretation.** For the payroll engine:
    - Default per-period path: (gross pay − non-taxable items − employee-share SSS/PhilHealth/Pag-IBIG − union dues) → split into regular and supplementary → choose the bracket using regular pay only → add supplementary pay to the excess → apply the rate.
    - Track year-to-date figures for every employee anyway. The cumulative average method can be triggered mid-year (a new hire with a prior employer, or commissions that exceed salary), and the year-end annualization needs the totals.
- **Interpretation.** The monthly bracket boundary of ₱20,833 is ₱250,000 ÷ 12. The table's brackets roughly track the annual graduated rates, so per-period withholding approximates the annual tax. Rounding and irregular pay still leave residual differences that the year-end adjustment settles.
- **Interpretation.** Annualizing every pay period as a substitute for the table does not appear in the regulation. The engine should not offer it as the default; use it only for the year-end and separation computations.

### Gaps

- Annex "E" daily bracket 6 is printed as "P 6,034.00.30" in the BIR PDF. The value is probably ₱6,034.30. Check it against the BIR withholding tax calculator or the annex as printed in the Official Gazette before hard-coding it.
- The bi-weekly and "miscellaneous" period rules in Sec. 2.79(B)(5)(c) appear as "xxx" in RR 11-2018, so they were carried over from earlier text that I did not retrieve (the RR 2-98 base text).
- The regulation does not say how to round per-period withholding (to centavos or whole pesos). The BIR illustrations carry centavos.

## Q2. Year-end adjustment: when it is done, and the deadline to refund over-withholding (cite RR 2-98 Sec. 2.79 as amended)

### Takeaway

The annualized computation is done on the last payroll in December, or on the final pay if employment ends before December. Under-withholding is deducted from the last pay. Over-withholding must be credited or refunded **not later than January 25 of the following year**. For an employee separated before December, the refund is due with the last pay. The employer recovers the refund by deducting it from its next 1601-C remittances.

### Cited Findings

- **Confirmed.** Annualized withholding tax method, Sec. 2.79(B)(5)(b). It applies "(1) When the employer-employee relationship is terminated before end of the calendar year; and (2) when computing for the year-end adjustment." The employer "shall determine the amount to be withheld from the compensation on the last month of employment or in December of the current calendar year." — [RR 11-2018, Sec. 2.79(B)(5)(b)](https://bir-cdn.bir.gov.ph/local/pdf/RR%20No.%2011-2018.pdf)
- **Confirmed.** Steps:
    1. Total the year's taxable regular and supplementary pay. For a separation, the period runs from January 1 to termination; for the year-end adjustment, January 1 to December.
    2. Add taxable pay from previous employers in the same year.
    3. Apply the annual graduated rates.
    4. Compare the result with the cumulative tax withheld.
       — [RR 11-2018, Sec. 2.79(B)(5)(b)](https://bir-cdn.bir.gov.ph/local/pdf/RR%20No.%2011-2018.pdf)
- **Confirmed. Deficiency.** It "shall be withheld from the last payment of compensation for the calendar year. If the deficiency withholding tax is more than the amount of the last compensation to be paid … the employer shall be liable to pay the amount of tax which cannot be withheld." Recovery from the employee "is a matter of settlement between the employee and employer." — [RR 11-2018, Sec. 2.79(B)(5)(b) Step 4](https://bir-cdn.bir.gov.ph/local/pdf/RR%20No.%2011-2018.pdf)
- **Confirmed. Excess.** "The excess withholding tax … shall be credited or refunded to the employee not later than January 25 of the following year. However, in case of termination of employment before December, the refund shall be given to the employee at the payment of the last compensation during the year. In return, the employer is entitled to deduct the amount refunded to the employee/s from the remittable amount of taxes withheld from compensation income for the current month in which the refund was made, and in the succeeding months thereafter until the amount refunded by the employer is fully repaid." — [RR 11-2018, Sec. 2.79(B)(5)(b) Step 4](https://bir-cdn.bir.gov.ph/local/pdf/RR%20No.%2011-2018.pdf)
- **Confirmed.** "The annualized computation done for each employee shall be reflected by the employer at the alphabetical list attached to BIR Form No. 1604C." — [RR 11-2018, Sec. 2.79(B)(5)(b)](https://bir-cdn.bir.gov.ph/local/pdf/RR%20No.%2011-2018.pdf)
- **Confirmed.** For non-rank-and-file employees under the annualized computation, compensation is the amount "received for the calendar year". Gross benefits are exempt up to ₱90,000. — [RR 11-2018, Sec. 2.79(C)(1)](https://bir-cdn.bir.gov.ph/local/pdf/RR%20No.%2011-2018.pdf)
- **Needs verification.** RMC 21-2010 is reported to state that the difference between tax due and tax withheld must be withheld in December or refunded, and it refers to the illustration in Sec. 2.79(B)(5)(b). I saw this only in a search-result summary. — [RMC 21-2010 (judiciary e-library)](https://elibrary.judiciary.gov.ph/thebookshelf/showdocs/10/50822)

### Inferences

- **Interpretation.** Engine flow for the year-end adjustment:
    - Run the annualization on the final December payroll.
    - If tax due exceeds tax withheld, withhold the difference from that pay. If the last pay cannot cover it, the employer owes the uncovered amount and should flag it as an employee receivable.
    - If tax withheld exceeds tax due, schedule a refund by January 25 and record an offset against the January 1601-C remittance and later months.
    - For separations, run the same annualization on the final pay and refund any excess immediately.
- **Interpretation.** A refund paid in January is deducted from "the remittable amount … for the current month in which the refund was made." In practice that is the January 1601-C, due February 10. The December 1601-C, due January 15, reports December withholding after the year-end adjustment.

### Gaps

- RR 11-2018 does not spell out how a refund offset appears on the 1601-C. The form's adjustment lines were not reviewed. Check the 1601-C (January 2018 ENCS) guidelines: https://bir-cdn.bir.gov.ph/local/pdf/1601C%20final%20Jan%202018%20with%20DPA.pdf
- Some secondary commentary says the 2316 and final pay are due "within 30 days" of separation. That most likely mixes in DOLE final-pay guidance. The BIR rule (Sec. 2.83.1) is the day of the last payment. I did not verify the DOLE source.

## Q3. 13th month pay and other benefits cap; de minimis list and current ceilings

### Takeaway

The ₱90,000 combined cap on 13th month pay and other benefits is unchanged. No enacted law or RR raising it was found as of November 2026, though bills reportedly exist. The de minimis ceilings were raised by **RR 4-2025** (clothing and the form of achievement awards) and then by **RR 29-2025**, which revised most items. RR 29-2025 took effect on January 6, 2026. De minimis amounts above a ceiling go into the ₱90,000 "other benefits" bucket.

### Cited Findings

- **Confirmed.** The ₱90,000 cap is in Sec. 2.78.1(B)(11). It covers (a) 13th month pay and (b) "Other benefits such as Christmas bonus, productivity incentives, loyalty award, gift in cash or in kind, and other benefits of similar nature … provided that the total amount shall not exceed ninety thousand pesos (₱90,000), which may be increased through rules and regulations issued by the Secretary of Finance … after considering … the inflation rate." — [RR 11-2018, Sec. 2.78.1(B)(11)](https://bir-cdn.bir.gov.ph/local/pdf/RR%20No.%2011-2018.pdf)
- **Confirmed.** BIR Form 2316 (September 2021) item 34 reads "13th Month Pay and Other Benefits (maximum of P90,000)". The excess goes to item 48, "Taxable 13th Month Benefits". — [BIR Form 2316 Sep 2021](https://bir-cdn.bir.gov.ph/local/pdf/2316%20Sep%202021%20ENCS_Final_corrected.pdf)
- **Needs verification.** No enacted increase to the ₱90,000 cap was found. Secondary sources mention 2026 House proposals for ₱120,000 or ₱150,000 and a House panel endorsement around July 2026. I saw these only in search snippets, and older bills date back years. — [Inquirer, earlier bill](https://newsinfo.inquirer.net/546353/new-bill-seeks-larger-tax-exemptions-13th-month-pay-bonuses/amp). Treat the cap as ₱90,000 unless an RR or law says otherwise.
- **Confirmed. De minimis list and ceilings under RR 29-2025** (amends Sec. 2.78.1(A)(3) of RR 2-98 as last amended by RR 4-2025). Old values are from RR 11-2018 unless noted.

    | Item                                                                                                                                                 | Before                                                          | RR 29-2025 ceiling                          |
    | ---------------------------------------------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------- | ------------------------------------------- |
    | (a) Monetized unused vacation leave, private employees                                                                                               | 10 days/yr                                                      | **12 days/yr**                              |
    | (b) Monetized vacation and sick leave, government employees                                                                                          | fully exempt                                                    | fully exempt                                |
    | (c) Medical cash allowance to dependants                                                                                                             | ₱1,500/semester or ₱250/month                                   | **₱2,000/semester or ₱333/month**           |
    | (d) Rice subsidy                                                                                                                                     | ₱2,000/month or 1 sack (50 kg) ≤ ₱2,000                         | **₱2,500/month or 1 sack (50 kg) ≤ ₱2,500** |
    | (e) Uniform and clothing allowance                                                                                                                   | ₱6,000/yr (RR 11-2018), raised to ₱7,000/yr (RR 4-2025)         | **₱8,000/yr**                               |
    | (f) Actual medical assistance (medical/healthcare needs, annual check-up, maternity, routine consultations)                                          | ₱10,000/yr                                                      | **₱12,000/yr**                              |
    | (g) Laundry allowance                                                                                                                                | ₱300/month                                                      | **₱400/month**                              |
    | (h) Employee achievement awards (length of service or safety; cash, gift certificate or tangible property; under a written, non-discriminatory plan) | ₱10,000/yr (RR 4-2025 first allowed cash and gift certificates) | **₱12,000/yr**                              |
    | (i) Christmas and major anniversary gifts                                                                                                            | ₱5,000/yr                                                       | **₱6,000/yr**                               |
    | (j) Daily meal allowance for overtime and night/graveyard shift                                                                                      | 25% of basic minimum wage, per region                           | **30% of basic minimum wage, per region**   |
    | (k) CBA plus productivity incentive benefits, combined                                                                                               | ₱10,000/yr                                                      | **₱12,000/yr**                              |

    Sources: [RR 29-2025, Sec. 1](https://bir-cdn.bir.gov.ph/BIR/pdf/RR%20No.%2029-2025.pdf) (OCR of the scanned BIR copy); [RR 11-2018, Sec. 2.78.1(A)(3)](https://bir-cdn.bir.gov.ph/local/pdf/RR%20No.%2011-2018.pdf) for items (a)–(e) before amendment; [RR 4-2025, Sec. 1](https://bir-cdn.bir.gov.ph/BIR/pdf/RR%20No.%204-2025.pdf) for (e) at ₱7,000 and (h) in cash or gift certificate at ₱10,000. The old values for items (f)–(k) are cross-checked against [Vialto Partners alert](https://vialtopartners.com/regional-alerts/philippines-payroll-and-compensation-small-perks-big-changes-updated-de-minimis-benefits-thresholds). They come from earlier RRs, not from RR 11-2018 text, which shows those items as "xxx".

- **Confirmed.** RR 4-2025 is dated January 30, 2025. Its subject is "Increasing the Clothing Allowance Pursuant to Republic Act No. 11975, the Fiscal Year 2024 General Appropriations Act, and Employees Achievement Awards". It takes effect 15 days after publication. — [RR 4-2025](https://bir-cdn.bir.gov.ph/BIR/pdf/RR%20No.%204-2025.pdf)
- **Confirmed.** RR 29-2025 takes effect "after fifteen (15) days following its publication in the Official Gazette or in the BIR Official Website, whichever comes first." The scan carries an "OCT 27 2025" date and a "DEC 22 2025" Records Management stamp. — [RR 29-2025, Sec. 3](https://bir-cdn.bir.gov.ph/BIR/pdf/RR%20No.%2029-2025.pdf)
- **Needs verification, but corroborated by two independent secondary sources.** RR 29-2025 was posted on the BIR website on December 22, 2025 and is effective **January 6, 2026**. — [PMAP Circular 2025-095](https://pmap.org.ph/circular-no-2025-095-bir-revenue-regulations-no-29-2025-expanded-de-minimis-benefits-for-employees/); [Vialto Partners](https://vialtopartners.com/regional-alerts/philippines-payroll-and-compensation-small-perks-big-changes-updated-de-minimis-benefits-thresholds). Grant Thornton confirms the December 22, 2025 issuance and the 15-day rule. — [Grant Thornton](https://www.grantthornton.com.ph/technical-alerts/tax-alert/2025/increasing-the-ceiling-of-non-taxable-de-minimis-benefits/)
- **Needs verification.** Treatment of excess: "Any excess of the prescribed thresholds may be included with the annual tax-exempt ceiling of PHP90,000," and any remainder is taxable compensation, or fringe benefits tax where applicable. Only the listed items qualify as de minimis. — [Vialto Partners](https://vialtopartners.com/regional-alerts/philippines-payroll-and-compensation-small-perks-big-changes-updated-de-minimis-benefits-thresholds). This is long-standing BIR doctrine, but RR 29-2025 itself does not restate it. The source text I would expect is RR 5-2011 or RR 1-2015, which I did not retrieve.
- **Confirmed.** De minimis benefits are "not subject to income tax as well as withholding tax on compensation income of both managerial, and rank and file employees." — [RR 11-2018, Sec. 2.78.1(A)(3)](https://bir-cdn.bir.gov.ph/local/pdf/RR%20No.%2011-2018.pdf). Form 2316 item 35 is "De Minimis Benefits". — [BIR Form 2316](https://bir-cdn.bir.gov.ph/local/pdf/2316%20Sep%202021%20ENCS_Final_corrected.pdf)

### Inferences

- **Interpretation.** Model the rules by effective date:
    - Clothing allowance: ₱6,000 before RR 4-2025, ₱7,000 from its effectivity (around mid-February 2025), ₱8,000 from January 6, 2026.
    - All other RR 29-2025 values apply from January 6, 2026.
- **Interpretation.** Ordering per employee per year:
    1. Apply each de minimis ceiling item by item.
    2. Add any excess to the "13th month and other benefits" bucket.
    3. Cap that bucket at ₱90,000.
    4. Treat the remainder as taxable supplementary compensation for rank-and-file employees.
       For managerial and supervisory staff, some excess benefits may instead be fringe benefits subject to the 35% fringe benefits tax.
- **Interpretation.** The meal allowance ceiling (j) depends on the regional minimum wage, so the engine needs a regional wage-order table.

### Gaps

- No "RR 5-2024" on de minimis was found. The amendment chain I could confirm is RR 11-2018 → RR 4-2025 → RR 29-2025.
- I found no rule on how annual de minimis ceilings apply to benefits paid between January 1 and 5, 2026, before RR 29-2025 took effect. It is also unclear whether the new annual ceilings apply to all of 2026 or are pro-rated.
- I could not read the PwC 2026 article on de minimis ceilings (HTTP 403): https://www.pwc.com/ph/en/tax/tax-publications/taxwise-or-otherwise/2026/the-rise-of-de-minimis-benefit-ceilings-modest-but-meaningful.html

## Q4. Mandatory contributions (SSS, PhilHealth, Pag-IBIG) and union dues: excluded from taxable compensation?

### Takeaway

Yes, but only the **employee share**. It is exempt from withholding and excluded from taxable compensation. The employer's share is not part of the employee's compensation.

### Cited Findings

- **Confirmed.** Exempt from withholding under Sec. 2.78.1(B)(12): "GSIS, SSS, Medicare and Pag-Ibig contributions, and union dues of individual employees." — [RR 11-2018, Sec. 2.78.1(B)(12)](https://bir-cdn.bir.gov.ph/local/pdf/RR%20No.%2011-2018.pdf)
- **Confirmed.** Step 1 of the table computation excludes "non-taxable benefits and mandatory contributions." The illustrations state compensation "net of SSS/GSIS, PHIC, HDMF employee share only." — [RR 11-2018, Sec. 2.79(B)(3) and Illustrations 13–14](https://bir-cdn.bir.gov.ph/local/pdf/RR%20No.%2011-2018.pdf)
- **Confirmed.** RR 8-2018: "Taxable income for compensation earners is the gross compensation income less non-taxable income/benefits such as … de minimis benefits, and employee's share in the SSS, GSIS, PHIC, Pag-ibig contributions and union dues." — [RR 8-2018](https://bir-cdn.bir.gov.ph/local/pdf/RR%20No.%208-2018.pdf)
- **Confirmed.** Form 2316 item 36 reads "SSS, GSIS, PHIC & PAG-IBIG Contributions and Union Dues (Employee share only)" and is reported as non-taxable. — [BIR Form 2316 Sep 2021](https://bir-cdn.bir.gov.ph/local/pdf/2316%20Sep%202021%20ENCS_Final_corrected.pdf)

### Inferences

- **Interpretation.** Exclude only the mandatory employee share. Voluntary contributions above the mandatory amount, such as voluntary Pag-IBIG MP2 or upgraded Pag-IBIG contributions, should not be assumed exempt. The regulation's phrasing ("contributions") is not explicit on this; flag it for CPA review.

### Gaps

- No BIR text was found on voluntary or excess contributions (for example, Pag-IBIG above the mandatory share, or the SSS WISP mandatory provident fund).

## Q5. Minimum wage earners and employees earning ₱250,000 or less: withholding treatment and Form 2316 items

### Takeaway

For a minimum wage earner (MWE), the statutory minimum wage, holiday pay, overtime pay, night shift differential and qualifying hazard pay are exempt from income tax and withholding. Any other taxable pay from the same employer is withheld on using the table. Separately, compensation of ₱250,000 or less for the year is exempt from withholding. Form 2316 must still be issued to MWEs and to employees with no tax withheld. Exempt amounts go in Part IV-B.A, items 29–33, and item 11 flags an MWE.

### Cited Findings

- **Confirmed.** Sec. 2.78.1(B)(13) exempts "Compensation income of Minimum Wage Earners (MWEs) who work in the private sector and being paid the Statutory Minimum Wage (SMW), as fixed by [RTWPB]/[NWPC], applicable to the place where he/she is assigned." The exemption also covers "the holiday pay, overtime pay, night shift differential pay, and hazard pay, earned by the aforementioned MWE." — [RR 11-2018, Sec. 2.78.1(B)(13)](https://bir-cdn.bir.gov.ph/local/pdf/RR%20No.%2011-2018.pdf)
- **Confirmed.** Hazard pay qualifies only for work in "danger or strife-torn areas, disease-infested places, or in distressed or isolated stations and camps." Other hazard pay is taxable. The employer must show MWE hazard pay in the alphalist, with a DOLE certification attached to the 1604-C. — [RR 11-2018, Sec. 2.78.1(B)(13)](https://bir-cdn.bir.gov.ph/local/pdf/RR%20No.%2011-2018.pdf)
- **Confirmed.** "Additional compensation such as commissions, honoraria, fringe benefits, benefits in excess of the allowable statutory amount of ₱90,000.00, taxable allowances, and other taxable income given to an MWE by the same employer … shall be subject to withholding tax using the withholding tax table." — [RR 11-2018, Sec. 2.78.1(B)(13)](https://bir-cdn.bir.gov.ph/local/pdf/RR%20No.%2011-2018.pdf)
- **Confirmed.** In Sec. 2.79(A), no withholding is required on an MWE's statutory minimum wage, holiday pay, overtime, night differential or hazard pay, and an MWE with additional compensation "shall be taxable only on such additional compensation received." — [RR 11-2018, Sec. 2.79(A)](https://bir-cdn.bir.gov.ph/local/pdf/RR%20No.%2011-2018.pdf)
- **Confirmed.** "Any reduction or diminution of wages for purposes of exemption from income tax shall constitute misrepresentation" and leads to disallowance of the employer's compensation expense. — [RR 11-2018, Sec. 2.78.1(B)(13)](https://bir-cdn.bir.gov.ph/local/pdf/RR%20No.%2011-2018.pdf)
- **Confirmed.** A second item, also numbered "(13)" in RR 11-2018, exempts "Compensation during the year not exceeding Two hundred fifty thousand pesos (₱250,000)" from withholding. — [RR 11-2018, Sec. 2.78.1(B)](https://bir-cdn.bir.gov.ph/local/pdf/RR%20No.%2011-2018.pdf)
- **Confirmed.** Illustration 4 (an MWE whose ₱260,000 total includes a ₱20,000 commission) concludes that the taxable portion is below ₱250,000 and taxed at 0%: "Taxpayer's income as MWE does not exceed P250,000; hence, not subject to…" — [RR 11-2018, Illustration 4](https://bir-cdn.bir.gov.ph/local/pdf/RR%20No.%2011-2018.pdf)
- **Confirmed.** Form 2316 must be issued "to employees classified as MWEs and to other employees whose compensation were not subjected to withholding tax." It must show "The amount of statutory minimum wage received if employee is MWE" and "Overtime pay, holiday pay, night shift differential pay, and hazard pay received if employee is MWE." — [RR 11-2018, Sec. 2.83.1](https://bir-cdn.bir.gov.ph/local/pdf/RR%20No.%2011-2018.pdf)
- **Confirmed.** Form 2316 (September 2021) items:
    - Item 9: statutory minimum wage rate per day.
    - Item 10: statutory minimum wage rate per month.
    - Item 11: checkbox "Minimum Wage Earner (MWE) whose compensation is exempt from withholding tax and not subject to income tax".
    - Non-taxable/exempt section: item 29 "Basic Salary (including the exempt P250,000 & below) or the Statutory Minimum Wage of the MWE"; item 30 Holiday Pay (MWE); item 31 Overtime Pay (MWE); item 32 Night Shift Differential (MWE); item 33 Hazard Pay (MWE); item 34 13th month and other benefits (maximum ₱90,000); item 35 De Minimis; item 36 contributions and union dues (employee share); item 37 Salaries and Other Forms of Compensation; item 38 total.
    - Taxable section: items 39–52 (regular: basic, representation, transportation, COLA, fixed housing, others; supplementary: commission, profit sharing, fees, taxable 13th month benefits, hazard pay, overtime pay, others).
    - Summary: items 19–28 (gross, less non-taxable, taxable from present employer, taxable from previous employer, tax due, taxes withheld by present and previous employers, 5% PERA tax credit).
      — [BIR Form 2316 Sep 2021](https://bir-cdn.bir.gov.ph/local/pdf/2316%20Sep%202021%20ENCS_Final_corrected.pdf)

### Inferences

- **Interpretation.** For a non-MWE employee whose basic salary is ₱250,000 or less for the year, Form 2316 puts that basic salary in item 29 as exempt, not in item 39. The form's label ("including the exempt P250,000 & below") supports this. It matches the BIR alphalist practice of a separate schedule for employees whose income is ₱250,000 or below. I did not check that schedule against the alphalist format.
- **Interpretation.** For an MWE, the engine must split pay into exempt components (minimum wage, holiday, overtime, night differential, qualifying hazard pay) and taxable additions (commissions, taxable allowances, benefits above ₱90,000). It applies the table only to the taxable additions, and the year-end annualization on those additions may still produce zero tax under ₱250,000.
- **Interpretation.** Whether an employee is an MWE depends on the regional wage order where they are assigned. An employee paid above the minimum wage is not an MWE, and all of their pay is taxable compensation subject to the table.

### Gaps

- RR 11-2018 does not say whether an employee who is paid exactly the minimum wage but receives a mid-year raise above it loses MWE status for the whole year or only from the raise. Older RR 10-2008 and RR 11-2018 illustrations may address this, and I did not review them.
- Alphalist schedule codes for MWEs and for employees earning ₱250,000 or below: the 1604-C alphalist format PDF (https://bir-cdn.bir.gov.ph/local/pdf/1604C%20Alphalist%20Format%20-%20final.pdf) was not reviewed.

## Q6. Deadlines: 1601-C, eFPS staggering, 1604-C, Form 2316 issuance and BIR submission, substituted filing

### Takeaway

| Obligation                                                          | Deadline                                                                            |
| ------------------------------------------------------------------- | ----------------------------------------------------------------------------------- |
| 1601-C, January–November (manual or eBIRForms)                      | 10th of the following month                                                         |
| 1601-C, December (manual or eBIRForms)                              | January 15                                                                          |
| 1601-C via eFPS                                                     | RR 26-2002 groups: A = 15 days, B = 14, C = 13, D = 12, E = 11 days after month-end |
| 1604-C with alphalist                                               | January 31                                                                          |
| 2316 to employees                                                   | On or before January 31, or the day of the last pay if separated                    |
| BIR copies of 2316 for substituted-filing employees, with Annex "F" | February 28, as scanned soft copies                                                 |

The BIR extended the 2316 submission deadline to March 31 in 2024 (RMC 29-2024). Extensions happen most years, so the engine should store deadlines as configurable data.

### Cited Findings

- **Confirmed.** BIR Forms page, 1601-C:
    - January to November: "On or before the tenth (10th) day of the following month in which withholding was made" (manual).
    - December: "On or before January 15 of the following year" (manual).
    - eFPS: "In accordance with the schedule set forth in RR No. 26-2002 … Group A: Fifteen (15) days following end of the month; Group B: Fourteen (14) …; Group C: Thirteen (13) …; Group D: Twelve (12) …; Group E: Eleven (11) days following end of the month," for both the January–November and December returns.
    - "This return shall be filed in triplicate by every Withholding Agent (WA)/payor required to deduct and withhold taxes on compensation paid to employees."
    - Form version: 1601-C January 2018 ENCS.
      — [BIR Forms page, 1601-C](https://www.bir.gov.ph/bir-forms) (CMS entry; form PDF: https://bir-cdn.bir.gov.ph/local/pdf/1601C%20final%20Jan%202018%20with%20DPA.pdf)
- **Confirmed.** BIR Forms page, 1604-C: filed "by every employer or withholding agent/payor who is either an individual, estate, trust, partnership, corporation …" — "On or before January 31 of the year following the calendar year in which the compensation payment and other income payments were paid or accrued." Form version: January 2018. — [BIR Forms page, 1604-C](https://www.bir.gov.ph/bir-forms) (guidelines: https://bir-cdn.bir.gov.ph/local/pdf/1604-C%20Guidelines%20Jan%202018%20Final.pdf)
- **Confirmed.** Form 2316 to employees: "on or before January 31 of the succeeding calendar year, or if employment is terminated before the close of such calendar year, on the day on which the last payment of compensation is made." It is prepared in triplicate:
    - Original: employee.
    - Duplicate: BIR.
    - Triplicate: employer, "retained for a period of ten (10) years".
      It must be signed by both employer and employee under penalties of perjury. — [RR 11-2018, Sec. 2.83.1](https://bir-cdn.bir.gov.ph/local/pdf/RR%20No.%2011-2018.pdf); same dates on the [BIR Forms page, 2316](https://www.bir.gov.ph/bir-forms)
- **Confirmed.** Substituted-filing employees sign the 2316 "immediately" and return it to the employer. The employer gives back the original. The duplicate is submitted "to the concerned BIR office not later than February 28 of the succeeding year, with accompanying Certified List of Employees Qualified for Substituted Filing of ITR (Annex 'F')." The stamped list is "tantamount to the substituted filing of ITR." Employees who do not qualify for substituted filing get two copies and file their own ITR by April 15. — [RR 11-2018, Sec. 2.83.1](https://bir-cdn.bir.gov.ph/local/pdf/RR%20No.%2011-2018.pdf)
- **Confirmed.** Who qualifies for substituted filing: employees receiving "purely compensation income, regardless of amount, from only one employer in the Philippines for the calendar year, the income tax of which has been withheld correctly … (tax due equals tax withheld)." Not qualified:
    - (A) Concurrent or successive multiple employers during the year.
    - (B) Tax not withheld correctly (tax due ≠ tax withheld).
    - (C) Other non-business income not subject to final tax.
    - (D) An employee whose spouse falls under (A)–(C).
    - (E) Non-resident aliens engaged in trade or business.
      — [RR 11-2018, Sec. 2.83.4](https://bir-cdn.bir.gov.ph/local/pdf/RR%20No.%2011-2018.pdf)
- **Confirmed.** Penalties: failure to issue the 2316 to an employee is "a ground for the mandatory audit of payor's all internal revenue tax liabilities upon verified complaint." Failure to file or submit the 2316 on time is penalized under NIRC Sec. 250 for each failure, and under Sec. 255 if it happens two consecutive years. — [RR 11-2018, Sec. 2.83.1](https://bir-cdn.bir.gov.ph/local/pdf/RR%20No.%2011-2018.pdf)
- **Confirmed.** RR 2-2015 (amending Sec. 2.83): for substituted filing, the employer scans the duplicate originals instead of submitting hard copies. They are stored as PDF files named "Surname_TIN_Period" on a DVD-R and submitted "not later than February 28 following the close of the calendar year, together with a notarized Certification (Annex 'C')." — [RR 2-2015, Sec. 2](https://www.deloitte.com/content/dam/assets-zone1/southeast-asia/en/docs/services/tax/2024/ph-revenue-regulations-2-2015.pdf)
- **Confirmed.** RR 16-2021 (dated June 11, 2021) further amends Sec. 2.83. The employer scans the 2316s, stores the soft copies "using the file format and naming conventions prescribed for the available modes of submission facilities of the BIR," and submits them through those facilities. This replaces the DVD-R requirement. It applies to all taxpayers, whether or not they are registered with the Large Taxpayers Service. — [RR 16-2021, Secs. 2–3](https://bir-cdn.bir.gov.ph/local/pdf/RR%20No.%2016-2021.pdf) (OCR quality of the scan is poor, but these passages are legible)
- **Confirmed.** RMC 29-2024 (February 26, 2024) extended the BIR-copy 2316 deadline "from February 28, 2024 to March 31, 2024." It limits the required attachments to the Sworn Declaration (Annex "C", RR 2-2015) and the Annex "F" certification (RR 11-2018). — [RMC 29-2024](https://bir-cdn.bir.gov.ph/BIR/pdf/RMC%20No.%2029-2024.pdf)
- **Needs verification.** A December 2025 1601-C example from secondary tax calendars: non-eFPS filing and payment January 15, 2026; eFPS e-filing staggered (for example Group B January 14, Group A January 15); eFPS e-payment January 20, 2026. — summary of [Forvis Mazars January 2026 calendar](https://www.forvismazars.com/ph/en/insights/tax-deadlines/january-2026-tax-calendar) and the [PwC January 2026 calendar](https://www.pwc.com/ph/en/client-accounting-services/2026-tax-calendar/2026-january-tax-calendar.html) (both blocked or not read in full). The e-payment date is not on the BIR Forms page.
- **Needs verification.** For taxable year 2025, a payroll vendor relays an RDO 033 notice giving a February 28, 2026 deadline for the BIR copy of the 2316. Requirements listed: two notarized copies of Annex "F" with a ₱30 documentary stamp, two notarized Sworn Declarations, a USB drive of the scanned signed 2316s, and printed proof of the online alphalist validation. — [payrollsolutions.ph](https://www.payrollsolutions.ph/articles/156). This applies to one RDO only.
- **Needs verification.** In 2024, alphalist submission for taxable year 2023 was extended to March 18, 2024 (RMC 25-2024). The BIR advisory specified USB memory sticks for the scanned 2316s. — [KPMG Special InTAX, Feb 2024](https://kpmg.com/ph/en/home/insights/2024/02/special-intax-february-2024-issue-1-volume-6.html)

### Inferences

- **Interpretation.** A small self-employed employer is typically a non-eFPS filer using eBIRForms. Its default calendar:
    - 1601-C by the 10th of each following month, and January 15 for December.
    - 1604-C with the alphalist by January 31.
    - 2316 to employees by January 31, or on the final pay date.
    - Scanned 2316 BIR copies and Annex "F" by February 28.
      The eFPS group offsets should be a configurable option.
- **Interpretation.** The 1604-C description on the BIR site explicitly includes an "individual" employer, so a sole proprietor with employees files the 1604-C and alphalist like any other employer.
- **Interpretation.** Weekend and holiday roll-forward: tax calendars move deadlines to the next business day. I did not confirm a primary rule for this here; it is usually based on RMC practice.

### Gaps

- I did not obtain the RR 26-2002 text (BIR CDN returned 403) or the 1601-C guidelines text, so the eFPS e-payment deadline and holiday rules are unverified against primary sources.
- I found no primary issuance on whether the BIR extended the taxable-year 2025 (filed in 2026) deadlines for the 1604-C or the 2316 submission.
- The EOPT Act (RA 11976, 2024) and its RRs were not reviewed for any effect on compensation-withholding deadlines or venue. I found no indication that they changed the 1601-C or 1604-C dates.

## Q7. Recent issuances (2021–2026) affecting compensation withholding

### Takeaway

The main recent changes are:

- The de minimis ceiling increases in RR 4-2025 (January 2025) and RR 29-2025 (effective January 6, 2026).
- The September 2021 revision of Form 2316.
- RR 16-2021, which moved 2316 submission to scanned soft copies via BIR facilities.
- RMC 29-2024, which ended the COVID-era acceptance of BIR copies without the employee's signature for substituted filers.

The withholding table (Annex "E", 2023) and the ₱90,000 cap are unchanged.

### Cited Findings

- **Confirmed.** The current Form 2316 is the "September 2021 (ENCS)" version, linked from the BIR Forms page as "2316 Sep 2021 ENCS_Final_corrected." It includes a Data Privacy Act consent and a substituted-filing declaration referencing "Revenue Regulations (RR) No. 3-2002, as amended." — [BIR Form 2316 Sep 2021](https://bir-cdn.bir.gov.ph/local/pdf/2316%20Sep%202021%20ENCS_Final_corrected.pdf); [BIR Forms page](https://www.bir.gov.ph/bir-forms)
- **Confirmed.** RMC 29-2024: BIR copies of the 2316 without the employee's signature were allowed under RMC 18-2021 because of COVID-19. That allowance "shall no longer be allowed, more particularly for those employees who are qualified for substituted filing." Copies already submitted in the old 2018 format need not be resubmitted "provided that the computation … is based on the 2023 income tax rates." — [RMC 29-2024](https://bir-cdn.bir.gov.ph/BIR/pdf/RMC%20No.%2029-2024.pdf)
- **Confirmed.** RR 16-2021 introduced scanned soft-copy submission of the 2316 through BIR submission facilities, replacing RR 2-2015's DVD-R procedure. — [RR 16-2021](https://bir-cdn.bir.gov.ph/local/pdf/RR%20No.%2016-2021.pdf)
- **Confirmed.** RR 4-2025 (January 30, 2025) and RR 29-2025 (posted December 22, 2025) are covered under Q3. — [RR 4-2025](https://bir-cdn.bir.gov.ph/BIR/pdf/RR%20No.%204-2025.pdf); [RR 29-2025](https://bir-cdn.bir.gov.ph/BIR/pdf/RR%20No.%2029-2025.pdf)
- **Needs verification.** RMC 29-2021 (February 26, 2021) reportedly allowed electronic signatures on certain BIR forms, including the 2316. RMC 18-2021 allowed unsigned employee copies for taxable year 2020. — [PwC, "BIR form 2316, substituted filing and e-signatures"](https://www.pwc.com/ph/en/tax/tax-publications/taxwise-or-otherwise/2021/bir-form-2316-substituted-filing-and-e-signatures.html); [Grant Thornton on e-signatures](https://www.grantthornton.com.ph/insights/articles-and-updates1/lets-talk-tax/e-signatures-for-certain-tax-returns-a-gift-to-taxpayers/). Secondary sources disagree on whether e-signatures remain acceptable after RMC 29-2024. RMC 29-2024 itself addresses missing signatures, not electronic ones.
- **Needs verification.** 2026 RMCs found in searches concern the annual ITR deadline (RMC 30-2026 extended the taxable-year 2025 ITRs to May 15, 2026, and RMC 39-2026 extended the attachments), the electronic audited financial statements system (RMC 46-2026), and disaster-area extensions (RMC 89-2026). None of them appears to change compensation-withholding mechanics. — [PwC Tax Alert 12 (RMC 30-2026)](https://www.pwc.com/ph/en/tax/tax-publications/tax-alerts/2026/tax-alert-12.html); [Grant Thornton on RMC 89-2026](https://www.grantthornton.com.ph/insights/articles-and-updates1/tax-notes/rmc-no-89-2026-tax-compliance-deadlines-extended-for-taxpayers-in-areas-affected-by-southwest-monsoon/)

### Inferences

- **Interpretation.** For the payroll feature, the rules that need versioning by date are: the withholding table (2018–2022 versus 2023 onward), the de minimis ceilings (pre-2025, RR 4-2025, and RR 29-2025 from January 6, 2026), and filing deadlines, which should be data rather than code because yearly RMC extensions are common.
- **Interpretation.** A digitally generated 2316 should follow the September 2021 layout, items 1–56. Because RMC 29-2024 requires signatures, the engine should produce a printable form for the employer and employee to sign, with a scanned soft copy for BIR submission.

### Gaps

- I did not review any 2025–2026 RMC that specifically governs the taxable-year 2025 alphalist or 2316 submission mode (for example a BIR eSubmission portal versus USB). The current national submission channel needs verification with the employer's RDO.
- I found no 2024–2026 RR changing the Annex "E" table or Secs. 2.79 and 2.83 beyond what is above. A full review of BIR's 2026 RR list was not done.
