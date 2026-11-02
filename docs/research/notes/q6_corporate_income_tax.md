# Philippine corporate income tax for small domestic corporations under CREATE (RA 11534) and CREATE MORE (RA 12066), as of late 2026

Status labels used below:

- **Confirmed**: stated in a statute, a BIR issuance or BIR digest, with the source linked.
- **Interpretation**: my reading of confirmed sources, or what practitioners commonly do. No BIR text says it directly.
- **Needs verification**: I could not reach a primary source, or the sources disagree.

Source note: the two bir-cdn.bir.gov.ph PDFs used below (RR 5-2021 and RMC 62-2021) are the BIR's own **digests** of each issuance, not the full signed text. Their wording is the BIR's. The full scanned RMC 62-2021 (mtfcounsel.com copy) could not be machine-read. RMC 36-2024 returned HTTP 403 from both the BIR CDN and batasnatin.

---

## Q1. The 20% rate tests (net taxable income ≤ ₱5M AND total assets ≤ ₱100M excluding land), and how they apply on the 1702Q versus the annual return

### Takeaway

The law and RR 5-2021 set both tests (net taxable income ≤ ₱5,000,000 **and** total assets ≤ ₱100,000,000, excluding the land under the office, plant and equipment). Both must be met. No BIR regulation, RMC or ruling I could find says how to apply the tests on a quarterly 1702Q. Practitioners still treat it as an open question. The annual return (1702-RT) is where the rate is finally settled, and Sec. 76 final-adjustment mechanics true up any quarterly difference. An app should use the annual figures as the authority and treat the quarterly rate choice as provisional.

### Cited Findings

- **Confirmed**: Sec. 27(A) as amended by CREATE sets a 25% rate effective July 1, 2020. "Corporations with net taxable income not exceeding Five million pesos and with total assets not exceeding [₱100M] … excluding land on which the particular business entity's office, plant, and equipment are situated … shall be taxed at twenty percent (20%)." — [RA 11534, Lawphil](https://lawphil.net/statutes/repacts/ra2021/ra_11534_2021.html)
- **Confirmed**: The RR 5-2021 matrix lists 20% effective July 1, 2020 for "corporations with net taxable income not exceeding Five Million Pesos (₱5,000,000) AND total assets not exceeding One Hundred Million (₱100,000,000), excluding the land…". The capitalised "AND" makes it a conjunctive test. RR 5-2021 was issued April 8, 2021. — [BIR digest of RR 5-2021](https://bir-cdn.bir.gov.ph/local/pdf/RR%20No.%205-2021.pdf)
- **Confirmed**: RR 5-2021 requires domestic corporations to "account separately in their Annual Financial Statements (AFS) the cost of the land on which the particular business entity's office, plant and equipment are situated." They may not lump it with other fixed-asset accounts. — [BIR digest of RR 5-2021](https://bir-cdn.bir.gov.ph/local/pdf/RR%20No.%205-2021.pdf)
- **Confirmed (absence of guidance)**: P&A Grant Thornton names the quarterly question as unresolved. It asks whether, "in filing the quarterly ITR," the company will "use as basis the balance of total assets" and net taxable income for the quarter to choose between the 20% and 25% rate, and says taxpayers hope the BIR will clarify. It also asks whether income taxed at special rates counts toward the ₱5M test. — [P&A Grant Thornton, "Addressing corporate income tax concerns under the CREATE law"](https://www.grantthornton.com.ph/insights/articles-and-updates1/lets-talk-tax/addressing-corporate-income-tax-concerns-under-the-create-law/)
- **Confirmed**: The BIR's own digest of RMC 62-2021 (the main CREATE CIT FAQ circular) covers total assets, the land exclusion, MCIT start year, dividends and training deductions. It says **nothing** about quarterly application of the 20% test. — [BIR digest of RMC 62-2021](<https://bir-cdn.bir.gov.ph/local/pdf/RMC%20No.%2062-2021%20(1).pdf>)
- **Confirmed**: Under Sec. 75 NIRC, the quarterly declaration is "a quarterly summary declaration of its gross income and deductions on a cumulative basis" for the preceding quarter(s). Under Sec. 76, the final adjustment return reconciles the quarterly payments with the annual tax due: pay the balance, carry over the excess, or claim a refund or TCC. — [NIRC (RA 8424), Lawphil](https://lawphil.net/statutes/repacts/ra1997/ra_8424_1997.html)
- **Interpretation (commercial guide)**: MPM's 1702Q guide calls the computation "cumulative." Its example applies 20% to **year-to-date** taxable income (Q2: ₱1,600,000 × 20% = ₱320,000) and then deducts Q1 tax paid. — [MPM, BIR Form 1702Q guide](https://mpm.ph/bir-form-1702q/)
- **Interpretation (practice)**: Taxumo says most corporations filing 1702Q "compute the tax rate themselves" (20% or 25%) by applying the thresholds. — [Taxumo, What is BIR Form 1702Q](https://www.taxumo.com/blog/what-is-bir-form-1702q-quarterly-income-tax/)
- **Confirmed**: The 1702Q (January 2018 version) was modified for CREATE in eFPS and eBIRForms. — [P&A Grant Thornton tax alert 2021](https://www.grantthornton.com.ph/alerts-and-publications/technical-alerts/tax-alert/2021/new-bir-form-1702q-january-2018-version-now-available-in-efps-modified-bir-form-1702q-now-available-in-ebirforms-facility/)

### Inferences

- **Interpretation**: The statute's ₱5M test refers to "net taxable income," and Sec. 27 tax is an annual tax. So the test is decided on the annual taxable income on the 1702-RT. On a 1702Q, the most defensible approach is to apply the test to cumulative year-to-date net taxable income, since Sec. 75 makes the quarterly return cumulative, together with total assets at quarter end. A more conservative variant also checks whether projected annual income will exceed ₱5M.
- **Interpretation**: Suppose a corporation qualifies in Q1–Q3 (YTD income ≤ ₱5M) but annual net taxable income exceeds ₱5M, or year-end assets exceed ₱100M. Then the whole year is taxed at 25% on the 1702-RT, and the shortfall is paid with the annual return. The 20% rate is not prorated, and nothing supports splitting the year by quarter. In the reverse case (25% paid in quarters, 20% at year end), the excess quarterly payments become an overpayment under Sec. 76: carry over (irrevocable) or refund/TCC.
- **Interpretation**: A cumulative YTD test means the rate can flip mid-year. For example, YTD ₱4.8M at Q2 is under the cap and YTD ₱5.3M at Q3 is over. The Q3 cumulative tax would then be computed at 25% on the full YTD income, less prior quarterly payments. This follows from the cumulative mechanics but no BIR text illustrates it.
- **App design**: Store the rate decision per return. Show the user which figures drove the choice (YTD NTI, assets basis, date). Warn when projected annual NTI or assets are close to the thresholds. Always recompute at year end from the annual figures.

### Gaps

- No RR, RMC, RMO or published BIR ruling found that addresses the quarterly 20%/25% test. A CPA should confirm the intended convention, and check whether newer 1702Q form guidelines say anything.
- Whether income taxed at special or final rates counts toward the ₱5M test is unresolved per GT. For a small corporation with only regular income, this rarely matters.

---

## Q2. When total assets are measured, and what counts

### Takeaway

No BIR text found fixes an explicit "as of" date for the ₱100M test. RMC 62-2021 ties the land exclusion to land used "during the taxable year for which the 20% Income Tax is imposed," and RR 5-2021 ties the land accounting to the Annual Financial Statements. Together these point to total assets per the AFS for that taxable year, in practice the year-end balance sheet. This is an Interpretation.

### Cited Findings

- **Confirmed**: Total assets "shall be net of depreciation and allowance for bad debts, if any," and exclude the land where the office, plant and equipment are situated. — [BIR digest of RMC 62-2021](<https://bir-cdn.bir.gov.ph/local/pdf/RMC%20No.%2062-2021%20(1).pdf>)
- **Confirmed**: If the financial statements carry the land at cost, the cost is excluded. If they carry it at FMV, the FMV is excluded. — [BIR digest of RMC 62-2021](<https://bir-cdn.bir.gov.ph/local/pdf/RMC%20No.%2062-2021%20(1).pdf>)
- **Confirmed**: The exclusion "is limited to that particular land where the business entity's office, plant and equipment are situated **during the taxable year for which the 20% Income Tax is imposed**." Land held for sale or for investment is not excluded. — [BIR digest of RMC 62-2021](<https://bir-cdn.bir.gov.ph/local/pdf/RMC%20No.%2062-2021%20(1).pdf>)
- **Confirmed**: For partial office use, the excluded value = % of floor area devoted to the entity's office × total land value. — [BIR digest of RMC 62-2021](<https://bir-cdn.bir.gov.ph/local/pdf/RMC%20No.%2062-2021%20(1).pdf>)
- **Confirmed (secondary)**: Land used as a banana plantation, or land being leased out, is not excluded. — [Dumlao summary of RMC 62-2021](https://dumlao.ph/2021/05/); [P&A Grant Thornton](https://www.grantthornton.com.ph/insights/articles-and-updates1/lets-talk-tax/addressing-corporate-income-tax-concerns-under-the-create-law/)
- **Confirmed**: The office, plant and equipment land cost must be shown as a separate AFS line item. — [BIR digest of RR 5-2021](https://bir-cdn.bir.gov.ph/local/pdf/RR%20No.%205-2021.pdf)
- **Low-reliability secondary**: A taxify.ph blog describes total assets as all balance sheet items "as of end of taxable year." The same post misnames CREATE MORE as "RA 11981" (it is RA 12066), so I don't rely on it. — [taxify.ph blog](https://taxify.ph/blog/create-more-act-20-percent-cit-small-corporations-2026/)

### Inferences

- **Interpretation**: For the annual 1702-RT, use total assets per the year-end AFS for that taxable year, net of accumulated depreciation and allowance for bad debts, less the qualifying land (office floor-area proration if partial).
- **Interpretation**: For the 1702Q, there is no audited balance sheet at quarter end. Practical options are the quarter-end balance sheet, or the prior year-end AFS. The app should let the user enter quarter-end total assets and qualifying land, and flag the basis it used.

### Gaps

- No BIR text found that explicitly states the measurement date. Ask a CPA to confirm year-end AFS as the basis, and which figure to use on the 1702Q.

---

## Q3. MCIT rates, the calendar-year 2023 computation, and quarterly MCIT on the 1702Q

### Takeaway

The MCIT rate is 2% of gross income, reduced to 1% from July 1, 2020 to June 30, 2023, and back to 2% from July 1, 2023. For TY2023, RMC 69-2023 splits the year at June 30 (1% before, 2% after). The prorated method divides annual gross income by 12 and applies 1% to the January–June months and 2% to the July–December months. For a calendar year that gives an effective **1.5%** (GT table). RMC 36-2024 (March 11, 2024) clarified the TY2023 MCIT computation, but I could not retrieve its text. On quarterly returns, RR 12-2007 requires MCIT to be computed and compared with normal tax each quarter, on a cumulative basis.

### Cited Findings

- **Confirmed**: Sec. 27(E)(1) as amended reads: "A minimum corporate income tax of two percent (2%) of the gross income as of the end of the taxable year … beginning on the fourth taxable year immediately following the year in which such corporation commenced … effective July 1, 2020 until June 30, 2023, the rate shall be one percent (1%)." — [RA 11534, Lawphil](https://lawphil.net/statutes/repacts/ra2021/ra_11534_2021.html)
- **Confirmed**: The RR 5-2021 matrix lists domestic corporations (both 25% and 20%): MCIT 1% from July 1, 2020 to June 30, 2023, and 2% from July 1, 2023. — [BIR digest of RR 5-2021](https://bir-cdn.bir.gov.ph/local/pdf/RR%20No.%205-2021.pdf)
- **Confirmed (secondary summary of BIR issuance)**: RMC 69-2023 (June 20, 2023, effective July 1, 2023) reverts MCIT to 2%. Its calendar-year 2023 sample applies 1% to Jan 1–Jun 30, 2023 and 2% to Jul 1–Dec 31, 2023, and sums the two. Fiscal years straddling July 1, 2023 split the same way. — [Forvis Mazars on RMC 69-2023](https://forvismazars.com/ph/en/insights/tax-alerts/bir-revenue-memorandum-circular-no.-69-2023); [batasnatin RMC 69-2023](https://batasnatin.com/laws/rmc-no-69-2023-reverts-the-rates-of-percentage-tax-minimum-corporate-income)
- **Confirmed (secondary)**: GT describes the method: "the gross income shall be divided by 12 months to get the average monthly gross income and apply the rate of 1% for the period January 1 to June 30, 2023, and 2% for the period July 1 to December 31, 2023." GT's effective-rate table by period end: 07/31/2023 1.08%, 08/31 1.17%, 09/30 1.25%, 10/31 1.33%, 11/30 1.42%, **12/31/2023 1.50%**, rising to 2.00% at 06/30/2024. — [P&A Grant Thornton, Computation of MCIT for TY2023](https://www.grantthornton.com.ph/insights/articles-and-updates1/tax-notes/computation-of-the-minimum-corporate-income-tax-for-the-taxable-year-2023/)
- **Confirmed (existence only)**: RMC 36-2024, dated March 11, 2024, is titled "Clarification on the manner of computing the MCIT for Taxable Year 2023." Its text could not be retrieved (HTTP 403). — [PwC Tax Alert No. 17 (2024)](https://www.pwc.com/ph/en/tax/tax-publications/tax-alerts/2024/tax-alert-17.html); [batasnatin listing](https://batasnatin.com/laws/rmc-no-36-2024-clarification-on-the-manner-of-computing-the-minimum-corporate)
- **Confirmed (RR 12-2007, still the quarterly MCIT rule)**: "The computation and the payment of MCIT, shall likewise apply at the time of filing the quarterly corporate income tax." Quarterly MCIT = MCIT rate × gross income "as of the end of the taxable quarter." If the computed quarterly MCIT exceeds quarterly normal income tax, MCIT is due. The following may be credited against quarterly MCIT: "Expanded withholding tax, quarterly corporate income tax payments under the normal income tax, and the MCIT paid in the previous taxable quarter/s." However, "excess MCIT from the previous taxable year/s shall not be allowed to be credited" against quarterly MCIT. At year end, quarterly MCIT paid is credited against normal income tax if normal tax turns out higher than annual MCIT. — [RR 12-2007, Lawyerly](https://dev.lawyerly.ph/laws/47521)
- **Confirmed**: RR 12-2007 Sec. 3 requires the MCIT computation to be "done on cumulative basis," comparing cumulative MCIT against cumulative normal tax. — [RR 12-2007, Lawyerly](https://dev.lawyerly.ph/laws/47521)
- **Confirmed**: The MCIT gross income base for sale of goods is "gross sales less sales returns, discounts and allowances and cost of goods sold." For services it is "gross receipts less sales returns, allowances, discounts and cost of services." — [NIRC Sec. 27(E)(4), Lawphil](https://lawphil.net/statutes/repacts/ra1997/ra_8424_1997.html). Passive income subject to final tax is excluded from that base. — [RR 12-2007, Lawyerly](https://dev.lawyerly.ph/laws/47521)
- **Confirmed**: CREATE MORE (RA 12066) does not amend Sec. 27(E). — [RA 12066, Lawphil](https://lawphil.net/statutes/repacts/ra2024/ra_12066_2024.html)

### Inferences

- **Interpretation**: Engine rate table for MCIT (calendar-year filers): TY2020 1.5% transitional (see Q8); TY2021 1%; TY2022 1%; TY2023 1.5% effective (6/12 × 1% + 6/12 × 2%); TY2024 onward 2%.
- **Interpretation**: Fiscal-year MCIT rate for TY2023 is the number of months on or before June 30, 2023 × 1% plus the months after × 2%, divided by 12. This is consistent with the GT table (FYE 9/30/2023: 9×1% + 3×2% = 15/12 = 1.25%).
- **Interpretation (needs verification)**: Quarterly MCIT in 2023 for a calendar-year corporation: Q1 and Q2 (cumulative, all months ≤ June 30) at 1%. Q3 cumulative (9 months: 6 at 1%, 3 at 2%) gives an effective (6×1% + 3×2%)/9 ≈ 1.333% on YTD gross income, using the same monthly-average method. Neither RMC 69-2023 (per summaries) nor the GT article addresses quarters. This only matters for amended or late 2023 returns.
- **Quarterly algorithm (from RR 12-2007)**, per quarter q:
    1. Normal_YTD = rate × YTD taxable income.
    2. MCIT_YTD = MCIT rate × YTD gross income, but only if the corporation is in its MCIT-eligible year.
    3. Tax_due_YTD = max(Normal_YTD, MCIT_YTD).
    4. Less: prior quarters' payments (normal or MCIT), CWT/EWT YTD, and the prior-year excess credit carried over.
    5. **Prior-year excess MCIT carry-forward may offset only normal income tax, never MCIT**, including on quarterly returns.

### Gaps

- Full text of RMC 36-2024 not obtained. Confirm whether it adopts the monthly-average (1.5%) method, and whether it says anything about quarterly returns.
- The figures in RR 12-2007's quarterly illustration (Panday Corp.) are reported to contain internal inconsistencies. Don't use them as test fixtures without checking the original.

---

## Q4. MCIT start year, the 3-year excess MCIT carry-forward, MCIT relief, and NOLCO

### Takeaway

MCIT applies from the 4th taxable year immediately following the year operations commenced. The commencement year is the year of BIR registration (RR 9-98, per RMC 62-2021), so a 2017 registrant first faces MCIT in 2021. Excess MCIT over normal tax carries forward 3 years, creditable against normal income tax only. Regular NOLCO carries forward 3 years. Losses for TY2020 and TY2021 carry forward 5 years (Bayanihan 2 / RR 25-2020).

### Cited Findings

- **Confirmed**: "4th year of business operations" means "fourth taxable year immediately following the year in which such corporation commenced its business operation." For example, if operations commenced in 2017, "MCIT may be imposed beginning the year 2021, if it exceeds the regular Income Tax." The "taxable year in which business operations commenced shall be the year in which the corporation is registered with the BIR, as provided under RR No. 9-98." — [BIR digest of RMC 62-2021](<https://bir-cdn.bir.gov.ph/local/pdf/RMC%20No.%2062-2021%20(1).pdf>)
- **Confirmed**: Under Sec. 27(E)(2), the excess of MCIT over normal income tax is carried forward and "credited against the normal income tax for the three (3) immediately succeeding taxable years." — [NIRC, Lawphil](https://lawphil.net/statutes/repacts/ra1997/ra_8424_1997.html). RR 12-2007 illustrates excess MCIT of ₱25,000 (1998) + ₱40,000 (1999) deducted from 2000 normal tax of ₱100,000, leaving ₱35,000 payable. — [RR 12-2007, Lawyerly](https://dev.lawyerly.ph/laws/47521)
- **Confirmed**: Under Sec. 27(E)(3), the Secretary of Finance may suspend MCIT for a corporation with losses due to "prolonged labor dispute," "force majeure," or "legitimate business reverses." — [NIRC, Lawphil](https://lawphil.net/statutes/repacts/ra1997/ra_8424_1997.html)
- **Confirmed**: Under Sec. 34(D)(3), the net operating loss "shall be carried over as a deduction from gross income for the next three (3) consecutive taxable years." Losses incurred in a year when the taxpayer was exempt are not carried over. NOLCO is allowed only if there was no substantial ownership change, meaning "not less than seventy-five percent (75%) in nominal value of outstanding issued shares" must be held by or on behalf of the same persons. — [NIRC, Lawphil](https://lawphil.net/statutes/repacts/ra1997/ra_8424_1997.html)
- **Confirmed (secondary summary)**: RR 25-2020 implements RA 11494 (Bayanihan 2). Net operating losses for TY2020 and TY2021 may be carried over for the next five consecutive taxable years. They must be shown separately in the ITR and disclosed in the AFS notes, or the claim is disqualified. — [PwC Tax Alert No. 79 (RR 25-2020)](https://www.pwc.com/ph/en/tax/tax-publications/tax-alerts/2020/tax-alert-79.html)
- **Confirmed**: CREATE's 5-year "enhanced NOLCO" (Sec. 294(C)) applies only to registered projects or activities of RBEs under the EDR, not to ordinary corporations. — [RA 11534, Lawphil](https://lawphil.net/statutes/repacts/ra2021/ra_11534_2021.html); CREATE MORE amends Sec. 294(C) EDR items. — [RA 12066, Lawphil](https://lawphil.net/statutes/repacts/ra2024/ra_12066_2024.html)

### Inferences

- **Interpretation**: The MCIT-eligible flag is true when taxable year ≥ (BIR registration year + 4). Store the BIR registration year as the commencement year, not the SEC incorporation date.
- **Interpretation**: Track each year's excess MCIT as a separate bucket that expires after 3 years, applied FIFO and only against normal tax. Track NOLCO per loss year: 3 years in general, 5 years for 2020 and 2021 losses. The NOLCO clock runs regardless of whether it is used.

### Gaps

- Not verified from a primary source: (a) the RR 14-2001 rule that NOLCO cannot be claimed in a year when the corporation uses OSD, while the 3-year period still runs; (b) whether NOLCO is deductible when MCIT applies. Commonly stated in practice, but not fetched. **Needs verification.**

---

## Q5. Corporate OSD: 40% of gross income

### Takeaway

A corporation taxed under Sec. 27(A) may elect OSD of up to 40% of gross income (Sec. 34(L) as amended by RA 9504). For corporations, gross income is net sales less cost of sales or services, unlike individuals, whose OSD base is gross sales or receipts. The election is irrevocable for the taxable year.

### Cited Findings

- **Confirmed**: Sec. 34(L) as amended by RA 9504 reads: "In the case of a corporation subject to tax under section 27(A) and 28(A)(1), it may elect a standard deduction in an amount not exceeding forty percent (40%) of it gross income as defined in Section 32 of this Code." "Such election when made in the return shall be irrevocable for the taxable year for which the return is made." — [RA 9504, Lawphil](https://lawphil.net/statutes/repacts/ra2008/ra_9504_2008.html)
- **Confirmed (secondary summary of RR 16-2008)**: For corporations, OSD is up to 40% of gross income, "where gross income means gross sales less sales returns, discounts, allowances, and cost of goods sold or cost of services." The 40% corporate OSD applied from July 1, 2008. A corporation using OSD must still submit financial statements with its annual return. — [jur.ph summary of RR 16-2008](https://jur.ph/law/summary/implementing-optional-standard-deduction-tax-code-amendment); [Respicio commentary](https://www.respicio.ph/commentaries/optional-standard-deduction-and-income-statement-requirement)
- **Secondary**: Under RR 16-2008, the election made in the first quarter binds the taxpayer for the whole taxable year. — [batasnatin, RR 16-2008 OSD irrevocable election](https://batasnatin.com/laws/tax-OSD-irrevocable-election-RR-16-2008)

### Inferences

- **Interpretation**: Corporate OSD amount = 40% × (net sales − cost of sales/services + other non-final-taxed income), applied to cumulative YTD figures on each 1702Q. Lock the method at the first 1702Q of the year.
- **Interpretation**: Under OSD, taxable income = 60% of gross income. For a small corporation, the ₱5M NTI test therefore corresponds to about ₱8.33M of gross income (net of cost of sales).

### Gaps

- Exact RR 16-2008 text on the quarterly election mechanics, and what counts in a corporation's gross income for OSD (e.g., other income), was not fetched from a primary source. **Needs verification.**

---

## Q6. Deadlines and e-filing (1702Q and 1702-RT)

### Takeaway

File and pay the 1702Q within 60 days after the close of each of the first three quarters. The 1702-RT (final adjustment return) is due on or before the 15th day of the 4th month after year end (April 15 for calendar years). Returns are filed through eFPS, eBIRForms, or an accredited tax software provider (ATSP).

### Cited Findings

- **Confirmed**: Sec. 77(B): quarterly returns "within sixty (60) days following the close of each of the first three (3) quarters." The final adjustment return is filed "on or before the fifteenth (15th) day of April," or, for fiscal-year filers, by the 15th day of the 4th month after the fiscal year closes. — [NIRC, Lawphil](https://lawphil.net/statutes/repacts/ra1997/ra_8424_1997.html)
- **Confirmed**: Sec. 76: excess quarterly payments may be refunded, credited, or carried over. Once the carry-over option is chosen, it "shall be considered irrevocable for that taxable period." — [NIRC, Lawphil](https://lawphil.net/statutes/repacts/ra1997/ra_8424_1997.html)
- **Secondary**: For calendar-year filers the usual 1702Q due dates are May 30 (Q1), August 29 (Q2) and November 29 (Q3). Q3 2026 falls due December 1, 2026, because November 29 is a Sunday and November 30 is Bonifacio Day. Filing channels: "eFPS, eBIRForms … and other ATSP." SAWT goes to esubmission@bir.gov.ph by the return's deadline. — [MPM 1702Q guide](https://mpm.ph/bir-form-1702q/)

### Inferences

- **Interpretation**: The app should compute due dates as close of quarter + 60 days, and the 15th day of the 4th month for the annual return. If the date falls on a weekend or non-working holiday, move it to the next business day, using a holiday calendar.

### Gaps

- Not verified from a primary source: the EOPT Act (RA 11976) "file anywhere" rules and their RRs, current eFPS-mandatory taxpayer classes, and the rule that an eFPS filer must file in eFPS. A small corporation not mandated to use eFPS normally uses eBIRForms.
- The weekend/holiday roll-forward rule is stated only by a secondary source here. **Needs verification.**
- Not verified: 1702-RT attachment requirements (AFS, SAWT, eAFS upload).

---

## Q7. CREATE MORE (RA 12066): changes affecting small domestic corporations

### Takeaway

CREATE MORE (approved November 8, 2024; 20% RBE rate effective November 28, 2024) **does not change** the 25% or 20% (small corporation) rates or the MCIT for ordinary, non-RBE domestic corporations. Its CIT changes are a new 20% rate for RBEs under the Enhanced Deductions Regime (registered-activity income only), EDR deduction changes, and a new Sec. 34(C)(8) deduction for input VAT on local purchases attributable to VAT-exempt sales. RR 7-2025 implements these. A transcription of RA 12066 shows the small-corporation asset cap as "P1,000,000,000." Treat that as a text error: RR 7-2025 (per KPMG and GT) states ₱100M.

### Cited Findings

- **Confirmed**: RA 12066 was approved November 8, 2024. It takes effect 15 days after publication (Sec. 35). The amended Sec. 27(A) keeps the 25% general rate and the 20% small-corporation rate, and adds 20% for "registered business enterprises under the enhanced deductions regime as provided in Section 294(C)" on registered-activity income. Sec. 27(E) (MCIT) is **not** amended. A new Sec. 34(C)(8) reads: "Input tax paid on local purchases attributable to VAT-exempt sales shall be deductible" from gross income. The power-expense and NOLCO items are in Sec. 294(C) (EDR only). — [RA 12066, Lawphil](https://lawphil.net/statutes/repacts/ra2024/ra_12066_2024.html)
- **Discrepancy / Needs verification**: The Lawphil transcription of amended Sec. 27(A) reads "One hundred million pesos (P1,000,000,000)." The words and the figure disagree. — [RA 12066, Lawphil](https://lawphil.net/statutes/repacts/ra2024/ra_12066_2024.html). KPMG's RR 7-2025 summary states the small-corporation test as ≤ Php5M NTI and ≤ Php100M total assets. — [KPMG Special InTAX March 2025](https://kpmg.com/ph/en/insights/2025/03/special-intax-march-2025-issue-3-volume-1.html)
- **Confirmed (secondary summaries of RR 7-2025)**: RR 7-2025 was issued February 27, 2025 and is effective March 14, 2025. It implements Secs. 27, 28 and 34 as amended. Rates: 25% general (July 1, 2020); 20% small domestic corporations (July 1, 2020); 20% RBEs under the EDR (November 28, 2024), "shall only cover the taxable income derived from registered projects or activities." RBEs that already filed for CY2024 may carry forward the excess from the 25%→20% cut "to the succeeding taxable quarter/year." Sec. 34(C)(8) input-VAT deduction is included. — [KPMG Special InTAX March 2025](https://kpmg.com/ph/en/insights/2025/03/special-intax-march-2025-issue-3-volume-1.html); [P&A Grant Thornton on CREATE MORE / RR 7-2025](https://www.grantthornton.com.ph/insights/articles-and-updates1/tax-notes/create-more-reduced-income-tax-rates-for-rbes-and-additional-allowable-deductions-to-gross-income/)
- **Confirmed (secondary)**: GT also reports that under CREATE MORE the EDR power-expense deduction rose from 50% to 100% and the EDR NOLCO period was amended. These are EDR (RBE) items. — [P&A Grant Thornton technical alert 2025](https://www.grantthornton.com.ph/technical-alerts/tax-alert/2025/implementation-of-the-reduced-income-tax-rates-for-rbes-under-the-edr-and-deductibility-of-input-tax-attributable-to-vat-exempt-sales/)

### Inferences

- **Interpretation**: For a small, non-RBE domestic corporation, CREATE MORE changes nothing in the rate or MCIT logic. The one item that can reach it is Sec. 34(C)(8). A corporation with VAT-exempt sales (e.g., a VAT-registered entity with some exempt sales, or possibly a non-VAT entity whose purchases bear VAT) may deduct the input VAT attributable to those sales as a deduction. This applies under itemized deductions only; OSD replaces itemized deductions.
- **Interpretation**: RBE and EDR logic (registered vs non-registered income split, 20% on registered income, enhanced deductions) is outside scope for a typical small corporation. The app can mark it unsupported.

### Gaps

- Whether the Sec. 34(C)(8) deduction is available to non-VAT-registered taxpayers, or only to VAT-registered taxpayers with exempt sales, and its effective date (law effectivity vs RR 7-2025), was not confirmed from the RR text.
- The RA 12066 asset-cap typo ("P1,000,000,000") was not checked against the Official Gazette text. The BIR's RR 7-2025 summary uses ₱100M, so the app should use ₱100M.
- CREATE MORE VAT changes for RBEs (zero-rating, local purchases) were not researched. They are outside small-corporation CIT scope.

---

## Q8. The transition-year 2020 rate (brief)

### Takeaway

For TY2020 (CREATE retroactive to July 1, 2020), RR 5-2021 assumes income and expenses were earned evenly by month and blends the old and new rates by months. For calendar year 2020 the transitory rates are: regular CIT **27.5%** (30%/25%), small corporation **25%** (30%/20%), MCIT **1.5%** (2%/1%).

### Cited Findings

- **Confirmed**: "The taxable income shall be computed without regard to the specific date when sales, purchases and other transactions occur. The income and expenses shall be deemed to have been earned and spent equally for each month of the period" (Sec. 9(B), RR 5-2021). — [BIR digest of RR 5-2021](https://bir-cdn.bir.gov.ph/local/pdf/RR%20No.%205-2021.pdf)
- **Confirmed**: Transitory rates matrix by period end:

    | Period end      | Regular (30/25) | Small corp (30/20) | MCIT (2/1) |
    | --------------- | --------------- | ------------------ | ---------- |
    | FY 7-31-20      | 29.58%          | 29.16%             | 1.91%      |
    | FY 8-31-20      | 29.16%          | 28.33%             | 1.82%      |
    | FY 9-30-20      | 28.75%          | 27.50%             | 1.73%      |
    | FY 10-31-20     | 28.33%          | 26.66%             | 1.64%      |
    | FY 11-30-20     | 27.91%          | 25.83%             | 1.55%      |
    | **CY 12-31-20** | **27.50%**      | **25.00%**         | **1.50%**  |
    | FY 1-31-21      | 27.08%          | 24.16%             | 1.41%      |
    | FY 2-28-21      | 26.66%          | 23.33%             | 1.32%      |
    | FY 3-31-21      | 26.25%          | 22.50%             | 1.23%      |
    | FY 4-30-21      | 25.83%          | 21.66%             | 1.14%      |
    | FY 5-31-21      | 25.41%          | 20.83%             | 1.05%      |
    | FY 6-30-21      | 25.00%          | 20.00%             | 1.00%      |

    Taxpayers who had already filed for TY2020 could amend using these rates and claim refund, TCC, or carry-over. — [BIR digest of RR 5-2021](https://bir-cdn.bir.gov.ph/local/pdf/RR%20No.%205-2021.pdf)

### Inferences

- **Interpretation**: The matrix values are monthly blends: (months before July 2020 × old rate + months from July 2020 × new rate) / 12. The BIR truncates rather than rounds some values (e.g., 29.16% for 29.1667%). If the app needs TY2020 at all, use the table values, not computed values. The FY 7-31-20 MCIT figure of 1.91% follows the same truncation pattern (1.9167%).

### Gaps

- For TY2020, how the 20% test (₱5M/₱100M) interacts with the blended rate is not detailed in the digest. Low priority for an app focused on 2024 onward.
