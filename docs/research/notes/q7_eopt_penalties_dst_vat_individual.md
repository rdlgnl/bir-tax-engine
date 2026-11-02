# Q7: EOPT changes, penalties, DST on loans, VAT threshold and carry-over, individual income tax options and deadlines (Philippines, as of November 2026)

Legend: **[Confirmed]** = read in the primary text (statute, BIR RR or digest, or BIR form) at the cited URL. **[Confirmed-secondary]** = several reputable tax-firm alerts agree but I did not read the primary text. **[Interpretation]** = my reading of the cited text. **[Needs verification]** = not settled by what I could read.

Headline corrections for the app's rule pack:

1. **DST on loans changed on July 1, 2025.** CMEPA (RA 12214), implemented by RR 19-2025, rewrote Sec. 179 to "75% of 1% of the issue price". The old "₱1.50 on each ₱200 **or fractional part thereof**" wording is gone, so there is no more rounding up to the next ₱200 block for documents made on or after July 1, 2025. The 365-day proration stays.
2. **RR 6-2024's 50% compromise reduction covers only criminal violations of Secs. 113, 237 and 238 (invoicing and registration) not involving fraud.** It is not a general cut to the late-filing compromise under Sec. 255 / RMO 7-2015. The reduced information-return fine is ₱500 per failure, capped at ₱12,500 per calendar year.
3. **Monthly 2550M is optional, not abolished.** Quarterly filing and payment has been mandatory since January 1, 2023, but RMC 52-2023 lets VAT-registered persons keep filing 2550M monthly.
4. **Taxpayer classification is a status the BIR assigns and notifies, not a figure the taxpayer recomputes each year from last year's sales.** Existing registrants were seeded from TY2022 gross sales and new registrants from their registration declaration. The BIR reclassifies periodically, and the taxpayer can request an update through ORUS.

---

## 1. EOPT (RA 11976) and invoicing: does the invoice replace the official receipt, effective April 27, 2024 (RR 7-2024)?

### Takeaway

Confirmed in substance. RA 11976 was approved January 5, 2024. RR 7-2024 (with transitory rules amended by RR 11-2024) took effect April 27, 2024, 15 days after it was posted on the BIR website on April 12, 2024. From that date the invoice is the primary evidence of sale for both goods and services. Official receipts survive only as supplementary documents, or as converted "invoices" during the transition.

### Cited Findings

- [Confirmed] RA 11976 was approved January 5, 2024. Sec. 51 of the Act: it "shall take effect fifteen (15) days after its publication in the Official Gazette or in a newspaper of general circulation." — [RA 11976, Lawphil](https://www.lawphil.net/statutes/repacts/ra2024/ra_11976_2024.html)
- [Confirmed] RA 11976 amends NIRC Sec. 113(A): a VAT-registered person "shall issue a VAT invoice for every sale, barter, exchange, or lease of goods or properties". Sec. 237(A), as amended, requires registered sales or commercial invoices for sales of ₱500 or more; smaller sales may be covered by one cumulative invoice at the end of the day, and VAT-registered persons must issue an invoice regardless of amount. — [RA 11976, Lawphil](https://www.lawphil.net/statutes/repacts/ra2024/ra_11976_2024.html)
- [Confirmed] Sec. 48 of RA 11976 (transitory clause) gives taxpayers six months from the effectivity of the implementing RRs to comply with the VAT and percentage-tax amendments. — [RA 11976, Lawphil](https://www.lawphil.net/statutes/repacts/ra2024/ra_11976_2024.html)
- [Confirmed-secondary] RR 7-2024 is "effective April 27, 2024, 15 days after the date of publication on the BIR official website on April 12, 2024". RR 11-2024 (dated June 13, 2024) later amended its transitory provisions and extended the compliance deadlines. — [Grant Thornton: clarification on RR 7-2024 as amended by RR 11-2024](https://www.grantthornton.com.ph/insights/articles-and-updates1/tax-notes/clarification-on-the-invoicing-requirements-per-rr-no-7-2024-as-amended-by-rr-no-11-2024/); [PwC: Compliance change on invoicing under EoPT](https://www.pwc.com/ph/en/tax/tax-publications/taxwise-or-otherwise/2024/compliance-change-on-invoicing-under-eopt.html); [PwC Tax Alert 27 (RR 11-2024)](https://www.pwc.com/ph/en/tax/tax-publications/tax-alerts/2024/tax-alert-27.html)
- [Confirmed-secondary] Unused manual or loose-leaf ORs may be used as invoices by striking out "Official Receipt" and stamping "Invoice". An OR that is not converted is not evidence of a sale of goods or services. Issuing ORs as the primary document after the transition can be treated as failure to issue an invoice. — [Grant Thornton: amendments and extension of deadlines](https://www.grantthornton.com.ph/insights/articles-and-updates1/tax-notes/amendments-and-extension-of-deadlines-for-compliance-with-the-invoicing-requirements-under-eopt-act/); [KPMG: update to registration and invoicing](https://kpmg.com/ph/en/home/insights/2024/07/compliance-an-update-to-registration-procedures-and-invoicing-requirements.html)
- Conflict: secondary sources date RR 7-2024 itself to either March 22, 2024 or April 11, 2024. The April 27, 2024 effective date is the same in all of them. — [Forvis Mazars: VAT invoicing under EOPT](https://forvismazars.com/ph/en/insights/tax-alerts/ease-of-paying-taxes-act/vat-invoicing-requirements-eopt-act)

### Inferences

- [Interpretation] For the app, April 27, 2024 is the right switch date for "invoice, not OR" as the primary sales document. A rule that treats ORs as valid primary evidence after that date should be retired. Converted ORs need only a transitional flag.
- [Interpretation] VAT on services is still a sale-based concept under EOPT (output VAT on the invoice rather than on collection). Where the app computes VAT, services should follow invoice-date logic for transactions from April 27, 2024 (consistent with the repo's "payments settle recorded invoices" model).

### Gaps

- [Needs verification] The exact end date of the OR-to-invoice conversion grace period after RR 11-2024. Sources cite October 27, 2024, December 31, 2024, or "until fully consumed". I did not read RR 11-2024 itself.
- [Needs verification] The calendar date RA 11976 took effect. January 22, 2024 is widely cited but I found no primary source for it; the statute says only 15 days after publication.

---

## 2. Taxpayer classification under RR 8-2024: thresholds, basis and effects

### Takeaway

Thresholds confirmed. Micro is under ₱3M, small ₱3M to under ₱20M, medium ₱20M to under ₱1B, large ₱1B and up. They are measured on gross sales for a taxable year: business income only, net of VAT, with no other deductions. The classification is assigned and notified by the BIR, not recomputed automatically each year. Existing registrants were seeded from TY2022 gross sales, and new registrants start from their registration declaration.

### Cited Findings

- [Confirmed] RR 8-2024 was issued April 11, 2024 and implements NIRC Sec. 21(b) as amended by RA 11976. Micro: gross sales for a taxable year below ₱3,000,000. Small: ₱3,000,000 to below ₱20,000,000. Medium: ₱20,000,000 to below ₱1,000,000,000. Large: ₱1,000,000,000 and above. — [BIR RR 8-2024 digest](https://bir-cdn.bir.gov.ph/BIR/pdf/RR%208-2024.pdf)
- [Confirmed] "Gross sales shall refer to total sales revenue, net of VAT, if applicable, during the taxable year, without any other deductions. Gross sales shall only cover business income, excluding compensation income earned under employer-employee relationship, passive income under Sections 24, 25, 27 and 28, and income excluded under Section 32(B)". — [BIR RR 8-2024 digest](https://bir-cdn.bir.gov.ph/BIR/pdf/RR%208-2024.pdf)
- [Confirmed] Initial classification. Taxpayers registered in 2022 or earlier are classified on TY2022 gross sales. Those without TY2022 data, and those registered in 2023 or in 2024 before the RR took effect, start as MICRO, except VAT-registered taxpayers, who start as SMALL. Those registering after the RR took effect are classified "based on its declaration in the Registration Forms ... and shall remain as such unless reclassified." The BIR notifies taxpayers of their classification or reclassification under a separate issuance. — [BIR RR 8-2024 digest](https://bir-cdn.bir.gov.ph/BIR/pdf/RR%208-2024.pdf)
- [Confirmed-secondary] RMO 37-2024 (issued around August 30 to September 4, 2024) sets the procedure. The BIR National Office may reclassify periodically, every two years, using the latest ITRs or VAT returns. Taxpayers may request reclassification through ORUS or at the RDO; requests to move up are automatically approved, while requests to move down need approval. Once the BIR has reclassified a taxpayer, another request in the same taxable year is generally barred unless there are compelling reasons. — [Grant Thornton: guidelines for classification](https://www.grantthornton.com.ph/insights/articles-and-updates1/tax-notes/eopt-is-here-guidelines-for-classification-of-business-taxpayers/)
- [Confirmed] Effects for micro and small taxpayers under RA 11976 Sec. 45: an ITR of at most two pages, the reduced penalties (section 3 below), and a reduced compromise rate. — [RA 11976, Lawphil](https://www.lawphil.net/statutes/repacts/ra2024/ra_11976_2024.html)
- [Confirmed-secondary] 2026 development. RR 4-2026 (issued and posted June 22, 2026) offers micro taxpayers a one-time abatement of delinquent accounts, assessments and open stop-filer cases as of December 31, 2025. Total basic tax and/or penalties per taxable year must not exceed ₱80,000. The abatement fee is ₱5,000, paid on BIR Form 0605, and the availment window runs to December 31, 2026 unless extended. RMC 84-2026 clarifies it. — [Grant Thornton Tax Alert on RR 4-2026](https://www.grantthornton.com.ph/technical-alerts/tax-alert/2026/one-time-abatement-of-taxes-and-or-penalties-for-micro-taxpayers/); [Manila Bulletin, July 25, 2026](https://mb.com.ph/2026/07/25/bir-clarifies-rules-to-help-small-businesses-clear-back-taxes)

### Inferences

- [Interpretation] "Classification = prior year's gross sales" is a reasonable estimate the app can show, but it is not the legal rule. The legal status is whatever the BIR has assigned (visible in ORUS). The app should store the classification as a user-entered or BIR-notified field per tax year, offer a "suggested from last year's gross sales" hint, and warn when the two disagree.
- [Interpretation] For a mixed-income earner, only business gross sales count toward the threshold. Compensation and passive income are excluded.

### Gaps

- [Needs verification] Whether a reclassification takes effect immediately or from the next taxable year. RMO 37-2024 itself was not read.

---

## 3. Reduced penalties for micro and small taxpayers (RR 6-2024)

### Takeaway

Mostly confirmed, with one important narrowing. RR 6-2024 (issued April 11, 2024; implements RA 11976 Sec. 45) sets:

- a 10% surcharge, instead of 25%, for ordinary late filing or payment; 50% still applies for willful neglect or fraud;
- interest at 50% of the Sec. 249 rate, i.e. 6%;
- a ₱500 fine per information-return failure, capped at ₱12,500 a year;
- a 50% compromise reduction only for non-fraud violations of Secs. 113, 237 and 238.

It applies prospectively.

### Cited Findings

- [Confirmed] RR 6-2024, issued April 11, 2024, "implements Section 45 of Republic Act (RA) No. 11976 ... on the imposition of reduced interest and penalty rates for micro and small taxpayers." — [BIR RR 6-2024 digest](https://bir-cdn.bir.gov.ph/BIR/pdf/RR%206-2024.pdf)
- [Confirmed] A 10% penalty applies in three cases: (A) failure to file any return and pay the tax due on the date prescribed; (B) failure to pay a deficiency tax within the time in the notice of assessment; (C) failure to pay all or part of the tax shown on a return, or the full tax due where no return is required, by the due date. — [BIR RR 6-2024 digest](https://bir-cdn.bir.gov.ph/BIR/pdf/RR%206-2024.pdf)
- [Confirmed] Two provisos. First, "no penalty shall be imposed to an amendment of a tax return if the covered taxpayer filed the initial tax return and paid the tax due thereon, on or before the prescribed due date". Second, on audit, a penalty is imposed on the deficiency only "if the particular tax return being audited was found to have been filed beyond the prescribed period". — [BIR RR 6-2024 digest](https://bir-cdn.bir.gov.ph/BIR/pdf/RR%206-2024.pdf)
- [Confirmed] Willful neglect to file, or a false or fraudulent return, still carries 50%. A substantial under-declaration (more than 30% of declared sales or income) or overstatement of deductions (more than 30%) is prima facie evidence of fraud. — [BIR RR 6-2024 digest](https://bir-cdn.bir.gov.ph/BIR/pdf/RR%206-2024.pdf)
- [Confirmed] Interest: "an interest at the reduced rate of fifty percent (50%) of the interest rate mandated in Section 249 ... the legal interest imposable to cover taxpayers shall be six percent (6%). In case a new legal interest rate is prescribed, the Commissioner ... shall issue a separate Circular." — [BIR RR 6-2024 digest](https://bir-cdn.bir.gov.ph/BIR/pdf/RR%206-2024.pdf)
- [Confirmed] Information returns: ₱500 for each failure to file an information return, statement or list, keep a record, or supply information, "upon notice and demand". The total for a calendar year may not exceed ₱12,500. — [BIR RR 6-2024 digest](https://bir-cdn.bir.gov.ph/BIR/pdf/RR%206-2024.pdf)
- [Confirmed] Compromise: "In case of criminal violation by covered taxpayers of Sections 113, 237, and 238 of the Tax Code, not involving fraud, a reduced compromise penalty rate of fifty percent (50%) of the applicable rate or amount of compromise under Annex 'A' of Revenue Memorandum Order No. 7-2015". The statute (RA 11976 Sec. 45) says "at least fifty percent (50%)". — [BIR RR 6-2024 digest](https://bir-cdn.bir.gov.ph/BIR/pdf/RR%206-2024.pdf); [RA 11976, Lawphil](https://www.lawphil.net/statutes/repacts/ra2024/ra_11976_2024.html)
- [Confirmed] "These Regulations shall apply prospectively in accordance with Section 51 of RA No. 11976." — [BIR RR 6-2024 digest](https://bir-cdn.bir.gov.ph/BIR/pdf/RR%206-2024.pdf)
- [Confirmed-secondary] RR 6-2024 was posted April 12, 2024 and took effect 15 days later, on April 27, 2024. — [Grant Thornton Tax Alert on RR 6-2024 / RR 8-2024](https://www.grantthornton.com.ph/alerts-and-publications/technical-alerts/tax-alert/2024/implementation-of-the-amendments-introduced-by-eopt-act-on-the-classification-of-taxpayers-and-administrative-penalties/); [KPMG Special InTAX April 2024](https://kpmg.com/ph/en/insights/2024/04/special-intax-april-2024-issue-1-volume-3.html)

### Inferences

- [Interpretation] **Correct the app if it halves the late-filing compromise (Sec. 255 / RMO 7-2015 schedule) for micro and small taxpayers.** RR 6-2024 halves compromise only for Secs. 113, 237 and 238: invoicing and registration-related violations such as failure to issue an invoice or to keep or register books.
- [Interpretation] Period of application. "Prospective" most naturally covers violations occurring (due dates falling) on or after April 27, 2024. For returns due before that date, the regular 25% and 12% apply.
- [Interpretation] For interest running across April 27, 2024 on a pre-existing unpaid amount, a split computation (12% before, 6% after) is defensible but not confirmed. Treat as **Needs verification** (see Gaps).
- [Interpretation] The app should apply the reduced rates by the taxpayer's classification for the period of the violation. Small taxpayers (₱3M to under ₱20M) get the same reduced rates as micro.
- [Interpretation] The "no penalty on amended return" proviso matters for the app's amended-return flow. If the original was filed and paid on time, an amendment that increases tax carries no 10% surcharge for micro and small taxpayers. Interest on the additional tax is not addressed by the proviso, so it presumably still runs.

### Gaps

- [Needs verification] How the BIR treats interest that straddles April 27, 2024, and whether the 10% and 6% apply to deficiency assessments for pre-effectivity periods issued after it. The full RR text and any RMC were not read. RMC 87-2024 (an EOPT Q&A) may address it.

---

## 4. Regular penalties: NIRC Sec. 248 (25%/50%) and Sec. 249 (12% interest)

### Takeaway

Confirmed. The surcharge is 25% for ordinary cases and 50% for willful neglect or fraud. Interest is double the BSP legal rate for loans or forbearance of money; the BSP rate is 6%, so the tax interest rate is 12% a year from January 1, 2018. Deficiency and delinquency interest cannot be imposed at the same time.

### Cited Findings

- [Confirmed] RA 11976 Sec. 41 amends Sec. 248 and keeps "a penalty equivalent to twenty-five percent (25%) of the amount due" in the listed cases. — [RA 11976, Lawphil](https://www.lawphil.net/statutes/repacts/ra2024/ra_11976_2024.html)
- [Confirmed] Sec. 248(A) cases as listed on the BIR's penalties page (pre-EOPT text): failure to file and pay on the due date; filing with the wrong internal revenue officer; failure to pay a deficiency within the assessment deadline; failure to pay the tax shown on a return. — BIR Penalties page (bir.gov.ph CMS dataset "Penalties", template 199, read locally from the BIR CMS API).
- [Confirmed] TRAIN (RA 10963) Sec. 249(A): "interest at the rate of double the legal interest rate for loans or forbearance of any money ... as set by the Bangko Sentral ng Pilipinas", from the due date until full payment. Deficiency and delinquency interest may not be imposed simultaneously. Deficiency interest runs until full payment or until the Commissioner's notice and demand, whichever comes first. TRAIN took effect January 1, 2018. — [RA 10963, Lawphil](https://lawphil.net/statutes/repacts/ra2017/ra_10963_2017.html)
- [Confirmed-secondary] RR 21-2018 (September 14, 2018) fixes the Sec. 249 rate at 12% (twice the BSP's 6%). For pre-2018 liabilities, 20% applies until December 31, 2017 and 12% from January 1, 2018. — [Grant Thornton: TRAIN updates deficiency and delinquency interest](https://www.grantthornton.com.ph/insights/articles-and-updates1/tax-notes/train-updates-deficiency-and-delinquency-interest/); [Roque Law: BIR clarifies 12% interest](https://roquelaw.com.ph/bir-clarifies-imposition-of-12-interest-on-unpaid-taxes/); [Platon Martinez on RR 21-2018](https://platonmartinez.com/articles/bir-revenue-regulations-no-21-2018-14-september-2018)
- Note: the BIR's own "Penalties" tax-information page still quotes the pre-TRAIN Sec. 249 text ("twenty percent (20%) per annum"). It is out of date, and the app should not copy it. — BIR Penalties page (CMS dataset, template 199).

### Inferences

- [Interpretation] After EOPT, the "filing with the wrong office" case has no practical effect, because Sec. 51(B) as amended lets returns be filed with any authorized agent bank, RDO (through a revenue collection officer) or authorized tax software provider. RR 6-2024 lists only three cases for micro and small taxpayers, consistent with that. — [RA 11976, Lawphil](https://www.lawphil.net/statutes/repacts/ra2024/ra_11976_2024.html)
- [Interpretation] The app should compute interest as simple interest at 12% a year (6% for micro and small) on the unpaid basic tax, by days elapsed. Day-count conventions (365 vs actual) are not specified in what I read.

### Gaps

- [Needs verification] Whether BIR interest uses a 365-day year or actual days in leap years. RR 21-2018's full text was not read.
- [Needs verification] The regular Sec. 250 information-return fine (believed to be ₱1,000 per failure, capped at ₱25,000 a year) was not confirmed from a source here.

---

## 5. Documentary stamp tax on loan agreements and debt instruments (Sec. 179), Form 2000, ATC, who pays

### Takeaway

**Correction.** For documents made on or after **July 1, 2025**, Sec. 179 (as amended by CMEPA, RA 12214 Sec. 21; implemented by RR 19-2025) imposes **75% of 1% (0.75%) of the issue price**, with no "₱200 or fractional part" rounding. Debt instruments with terms under one year are prorated by days over 365, and only one DST applies to a loan agreement and the notes, mortgage or other security issued to secure it. For documents made January 1, 2018 to June 30, 2025, the TRAIN rule applies: ₱1.50 on each ₱200 or fractional part of the issue price, with the same 365-day proration. File BIR Form 2000 within 5 days after the close of the month the document was made, under ATC DS 106. Any party to the document is liable; if one party is exempt, the non-exempt party is directly liable. Banks and other industries mandated to use eDST pay through that system.

### Cited Findings

- [Confirmed] TRAIN (RA 10963) Sec. 179: "a documentary stamp tax of One peso and fifty centavos (₱1.50) on each Two hundred pesos (₱200), or fractional part thereof, of the issue price"; terms under one year are prorated "in accordance with the ratio of its term in number of days to three hundred sixty-five (365) days"; "only one documentary stamp tax shall be imposed on either loan agreement, or promissory notes issued to secure such loan." Effective January 1, 2018. — [RA 10963, Lawphil](https://lawphil.net/statutes/repacts/ra2017/ra_10963_2017.html)
- [Confirmed] RR 19-2025, issued August 5, 2025, covers documents and loan agreements "in respect of the transactions made or accomplished on July 1, 2025 onwards." New Sec. 179 text: "On every original issue of debt instruments, there shall be collected a documentary stamp tax of SEVENTY-FIVE PERCENT OF ONE PERCENT (75% OF 1%) of the issue price of any such debt instrument: Provided, That for such debt instruments with terms of less than one (1) year, the documentary stamp tax to be collected shall be of a proportional amount in accordance with the ratio of its term in number of days to three hundred sixty-five (365) days: Provided, further, That only one documentary stamp tax shall be imposed on the loan agreement and promissory notes, mortgage, security interest over personal property, and other contracts issued to secure such loan." — [BIR RR 19-2025 digest](https://bir-cdn.bir.gov.ph/BIR/pdf/RR%20No.%2019-2025%20Digest.pdf)
- [Confirmed] RR 19-2025 adds that when a loan agreement and its securing promissory note, mortgage or other contracts are executed together, only one DST is imposed, on "whichever will yield a higher tax". Where a single instrument covers the loan, note, pledge and mortgage together, the Sec. 195 mortgage DST applies, computed on the full amount of the loan or credit. — [BIR RR 19-2025 digest](https://bir-cdn.bir.gov.ph/BIR/pdf/RR%20No.%2019-2025%20Digest.pdf)
- [Confirmed] CMEPA also cut Sec. 174 (original issue of shares) to 0.75% of par value from ₱2.00 per ₱200, and Sec. 176 to 0.75%. — [BIR RR 19-2025 digest](https://bir-cdn.bir.gov.ph/BIR/pdf/RR%20No.%2019-2025%20Digest.pdf); [Grant Thornton Tax Alert on RR 19-2025](https://www.grantthornton.com.ph/technical-alerts/tax-alert/2025/implementation-of-the-revised-documentary-stamp-tax-rates-and-exemptions-under-cmepa/)
- [Confirmed-secondary] RA 12214 was signed May 29, 2025 and took effect July 1, 2025. — [Grant Thornton: key highlights of CMEPA](https://www.grantthornton.com.ph/insights/articles-and-updates1/tax-notes/key-highlights-of-capital-markets-efficiency-promotion-act-cmepa/); [KPMG: Stamp duty and debt, May 2026](https://kpmg.com/ph/en/insights/2026/05/stamp-duty-and-debt-key-considerations.html)
- [Confirmed] ATC. The BIR Form 2000 (January 2018 ENCS) ATC table lists "DS 106 – Original issue of all Debt Instruments – P 1.50 / P 200 of issue price or a fraction of 365 days". DS 121 is Mortgages, Pledges and Deeds of Trust. — [BIR Form 2000 (Jan 2018)](https://bir-cdn.bir.gov.ph/local/pdf/2000-DST%20Jan%202018%20final.pdf)
- [Confirmed] Filing deadline and who files (BIR Form 2000 description). It is filed by "the person making, signing, issuing, accepting, or transferring documents, instruments, loan agreements", "within five (5) days after the close of the month when the taxable document was made, signed, issued, accepted or transferred". Taxpayers in industries mandated to use eDST, and those who opt in, file under RR 7-2009. "Whenever one party to the taxable document enjoys exemption from the tax herein imposed, the other party thereto who is not exempt shall be the one directly liable for the tax." — BIR Forms page, "Documentary Stamp Tax Return" dataset (bir.gov.ph CMS, template 168), linking [Form 2000](https://bir-cdn.bir.gov.ph/local/pdf/2000-DST%20Jan%202018%20final.pdf) and [Form 2000 guidelines](https://bir-cdn.bir.gov.ph/local/pdf/2000%20Guide%20Jan%202018.pdf)
- [Confirmed-secondary] DST is an excise on the transaction, not the document. The CTA held that a loan whose proceeds were used outside the Philippines had a foreign situs and was not subject to DST. Sec. 173's shifting rule "does not operate to create liability for DST when there is none to begin with." — [KPMG: Stamp duty and debt, May 2026](https://kpmg.com/ph/en/insights/2026/05/stamp-duty-and-debt-key-considerations.html)

### Inferences

- [Interpretation] App formula, documents on or after July 1, 2025: `DST = 0.0075 × issue_price × min(1, term_days/365)`. Before that (2018 to June 30, 2025): `DST = 1.50 × ceil(issue_price/200) × min(1, term_days/365)`. Example: a ₱1,000,100 one-year loan was ₱7,501.50 under TRAIN and is ₱7,500.75 under CMEPA.
- [Interpretation] The BIR's Form 2000 is still the January 2018 version and its DS 106 label still shows "P 1.50 / P 200". The ATC code is unchanged, but the label lags the law.
- [Interpretation] When a bank is the lender, the bank as an eDST-mandated user files and pays through eDST and usually passes the cost to the borrower by contract. Legally, Sec. 173 makes all parties liable, so a self-employed borrower of a bank loan normally has no Form 2000 to file. When neither party is an eDST user (e.g., a private loan from a relative or a non-bank lender), either party may file Form 2000; the parties decide who bears the cost.

### Gaps

- [Needs verification] Whether the BIR has issued a revised Form 2000 or ATC table after CMEPA. None was found.
- [Needs verification] The eDST mandate list (RR 7-2009 and later amendments) was not read directly.
- [Needs verification] How RR 19-2025 treats rounding (e.g., to centavos) under the new percentage rate. The digest is silent.

---

## 6. VAT: ₱3,000,000 threshold, excess input VAT carry-over, quarterly 2550Q, status of 2550M

### Takeaway

The threshold is ₱3,000,000 under Sec. 109(BB) as amended by TRAIN, with no automatic inflation adjustment in that paragraph. Under Sec. 110(B) (as amended by RA 9361; not changed by EOPT), excess input VAT is "carried over to the succeeding quarter or quarters", with no taxable-year cut-off. 2550Q is due within 25 days after the close of each quarter, and quarterly filing and payment has been mandatory since January 1, 2023. Monthly 2550M is optional, not abolished (RMC 52-2023).

### Cited Findings

- [Confirmed] TRAIN Sec. 109(BB) exempts sales "where the gross annual sales and/or receipts do not exceed the amount of Three million pesos (₱3,000,000)". The TRAIN text's three-year CPI adjustment applies to Sec. 109(P) residential property, not to (BB). — [RA 10963, Lawphil](https://lawphil.net/statutes/repacts/ra2017/ra_10963_2017.html)
- [Confirmed] BIR VAT FAQ: a person must register for VAT if gross sales or receipts "for the past twelve (12) months, other than those that are exempt under Section 109 (A) to (U), have exceeded Three Million Pesos" or there are reasonable grounds to believe they will exceed it in the next 12 months. — BIR "Value-Added Tax" tax-information dataset (bir.gov.ph CMS, template 193).
- [Confirmed] RA 9361 (approved November 21, 2006) Sec. 110(B): "If at the end of any taxable quarter the output tax exceeds the input tax, the excess shall be paid by the VAT-registered person. If the input tax exceeds the output tax, the excess shall be carried over to the succeeding quarter or quarters: Provided, however, that any input tax attributable to zero-rated sales ... may at his option be refunded or credited against other internal revenue taxes, subject to the provisions of Section 112." The 70% input-tax cap introduced by RA 9337 does not appear in this text. — [RA 9361, Lawphil](https://lawphil.net/statutes/repacts/ra2006/ra_9361_2006.html)
- [Confirmed] RA 11976 amends Sec. 110 to add "Purchase of services on which a value-added tax has accrued" as creditable, and adds Sec. 110(D) (output VAT on uncollected receivables). It does not change the Sec. 110(B) carry-over language. It also amends Sec. 112 (refunds). — [RA 11976, Lawphil](https://www.lawphil.net/statutes/repacts/ra2024/ra_11976_2024.html)
- [Confirmed-secondary] The CTA En Banc reads Sec. 110(B) as plain: excess input VAT carries over to succeeding quarters, and only input VAT attributable to zero-rated sales may be refunded. — [Batas Natin digest on Sec. 110](https://batasnatin.com/laws/tax-input-VAT-credit-Sec-110)
- [Confirmed] TRAIN Sec. 114(A): returns are filed "within twenty-five (25) days following the close of each taxable quarter"; "beginning January 1, 2023, the filing and payment required under this Subsection" follows that quarterly period. — [RA 10963, Lawphil](https://lawphil.net/statutes/repacts/ra2017/ra_10963_2017.html)
- [Confirmed] BIR guidance: file the quarterly VAT return "on or before the 25th day following the end of the taxable quarter using BIR Form No. 2550Q" (January 2023 ENCS version). RMC 52-2023 (May 10, 2023): "While the Tax Code now mandates the filing of VAT returns and payment ... on a quarterly basis, VAT-registered persons may continue to file and pay the VAT on a monthly basis and still use BIR Form No. 2550M." — BIR "Value-Added Tax" tax-information dataset (bir.gov.ph CMS, template 193).
- Note: the same BIR VAT FAQ still mentions an "annual registration fee of P500.00". RA 11976 removed that fee, so this BIR page is stale on that point. — BIR VAT dataset; [BIR EOPT flyer](https://bir-cdn.bir.gov.ph/BIR/pdf/flyer-eopt.pdf)

### Inferences

- [Interpretation] The statute limits carry-over only to "succeeding quarter or quarters", so excess input VAT carries across taxable years until used. The app should carry forward the unutilized balance from Q4 to Q1 of the next year, not reset it in January.
- [Interpretation] The app should not model 2550M as "abolished". It is optional, and mandatory compliance is quarterly. A monthly 2550M filer still files 2550Q, and the 2550M payments are credited against it.

### Gaps

- [Needs verification] Whether any 2024 to 2026 RR set a time limit on carried-over input VAT. None was found; the "5-year carry-over" heading on one commentary site is unsupported by the statute.
- [Needs verification] The 2550Q filing rule for a non-VAT taxpayer who crosses ₱3M mid-quarter (two business-tax returns in one quarter) is described in RR 8-2018 (section 7 below). Form-level mechanics were not checked.

---

## 7. Individual income tax: 8% option, ₱250,000 reduction, election, crossing ₱3M, OSD

### Takeaway

All confirmed from RR 8-2018. The 8% option is available only to a taxpayer who is not VAT-registered and is not subject to other percentage taxes besides Sec. 116, with gross sales or receipts plus other non-operating income not exceeding ₱3M. The ₱250,000 reduction applies only to purely self-employed individuals and professionals, not mixed-income earners. The option is elected on the 1st-quarter return (or the initial quarter return for a new business) and is irrevocable for the year; without an election, graduated rates apply. Crossing ₱3M switches the taxpayer automatically to graduated rates with credit for 8% payments already made. OSD is 40% of gross sales or receipts for individuals, but 40% of gross income for corporations.

### Cited Findings

- [Confirmed] RR 8-2018 (issued February 20, 2018; digest). Purely self-employed or professional individuals whose "gross sales/receipts and other non-operating income does not exceed the Value-Added Tax (VAT) threshold" may choose graduated rates or "the eight percent (8%) tax on gross sales or receipts and other non-operating income in excess of Two Hundred Fifty Thousand Pesos (₱ 250,000.00), in lieu of the graduated income tax rates under Section 24(A) and the Percentage Tax under Section 116". — [BIR RR 8-2018 digest](https://bir-cdn.bir.gov.ph/local/pdf/Digest%20RR%208-2018_copy.pdf)
- [Confirmed] Election: "Unless the taxpayer signifies the intention to elect the 8% Income Tax rate in the 1st Quarter Percentage and/or Income Tax Return, or on the initial quarter return of the taxable year after the commencement of a new business/practice of profession, the taxpayer shall be considered as having availed of the graduated rates ... Such election shall be irrevocable and no amendment of option shall be made for the said taxable year." — [BIR RR 8-2018 digest](https://bir-cdn.bir.gov.ph/local/pdf/Digest%20RR%208-2018_copy.pdf)
- [Confirmed] Exclusions: the 8% option "is not available to a VAT-registered taxpayer, regardless of the amount of gross sales/receipts, and to a taxpayer who is subject to other Percentage Taxes under Title V ... except those subject under Section 116". Partners of a general professional partnership cannot use it for their distributive share. — [BIR RR 8-2018 digest](https://bir-cdn.bir.gov.ph/local/pdf/Digest%20RR%208-2018_copy.pdf)
- [Confirmed] Crossing ₱3M: "A taxpayer shall automatically be subject to the graduated rates ... even if the flat 8% Income Tax rate option is initially selected, when taxpayer's gross sales/receipts and other non-operating income exceeded the VAT threshold during the taxable year ... and shall be allowed a tax credit for the previous quarter/s Income Tax payment/s under the 8% Income Tax rate option." The taxpayer must update registration "within the month following the month s/he exceeded the VAT threshold" and is "liable to VAT prospectively starting on the first day of the month following the month when the threshold is breached". Percentage tax is due on sales from the start of the year until VAT liability begins, "without imposition of penalty if timely paid on the immediately succeeding month/quarter". An ITR showing sales over the threshold needs audited financial statements. — [BIR RR 8-2018 digest](https://bir-cdn.bir.gov.ph/local/pdf/Digest%20RR%208-2018_copy.pdf)
- [Confirmed] Mixed income: the 8% option "in excess of P 250,000.00 is available only to purely self-employed individuals and/or professionals. The said P 250,000.00 is not applicable to mixed income earners since it is already incorporated in the first tier of the graduated Income Tax rates applicable to compensation income." The business portion under 8% is taxed on total gross sales or receipts plus other non-operating income. If business gross exceeds the VAT threshold, graduated rates apply. — [BIR RR 8-2018 digest](https://bir-cdn.bir.gov.ph/local/pdf/Digest%20RR%208-2018_copy.pdf)
- [Confirmed] Statute: TRAIN Sec. 24(A)(2)(b) (8% on gross above ₱250,000) and Sec. 24(A)(2)(c) (mixed income: 8% or graduated if not above the Sec. 109(BB) threshold, graduated if above). — [RA 10963, Lawphil](https://lawphil.net/statutes/repacts/ra2017/ra_10963_2017.html)
- [Confirmed] OSD: "In case of individual taxpayers, OSD shall be computed at the rate of forty percent (40%) of gross sales/receipts ... Corporations may elect standard deduction in an amount not exceeding forty percent (40%) of its gross income." The OSD election is made on the first-quarter (or initial-quarter) return, is irrevocable for the year, and individuals claiming it need not attach financial statements. No deductions are allowed for those taxed at 8%. — [BIR RR 8-2018 digest](https://bir-cdn.bir.gov.ph/local/pdf/Digest%20RR%208-2018_copy.pdf)
- [Confirmed] Graduated rates from January 1, 2023 onward: 0% up to ₱250k; 15% over ₱250k; ₱22,500 + 20% over ₱400k; ₱102,500 + 25% over ₱800k; ₱402,500 + 30% over ₱2M; ₱2,202,500 + 35% over ₱8M. — [BIR RR 8-2018 digest](https://bir-cdn.bir.gov.ph/local/pdf/Digest%20RR%208-2018_copy.pdf)
- [Confirmed] RR 8-2018 also says a VAT taxpayer who has not exceeded the threshold in the immediately preceding three years may opt out of VAT and use 8%, but must update registration "on or before the first quarter of a taxable year". — [BIR RR 8-2018 digest](https://bir-cdn.bir.gov.ph/local/pdf/Digest%20RR%208-2018_copy.pdf)

### Inferences

- [Interpretation] The app's test is "not VAT-registered AND gross sales/receipts + other non-operating income ≤ ₱3,000,000". The cut-off is "exceed", so exactly ₱3,000,000 still qualifies. This is a different boundary from the micro/small classification (under ₱3M vs ₱3M and up); keep the two tests separate.
- [Interpretation] The election is captured on the Q1 1701Q (or the initial-quarter 1701Q or 2551Q). A late or missing Q1 election defaults the whole year to graduated rates.

### Gaps

- None material. The full RR 8-2018 text (Sec. 2 and Sec. 3 numbering) was not cross-checked, only the BIR digest.

---

## 8. Deadlines and percentage tax: 1701Q, annual ITR, 2551Q; 3% vs 1% under CREATE

### Takeaway

1701Q is due May 15, August 15 and November 15 (Sec. 74(A)). The annual ITR is due April 15 (Sec. 51(C)(1)); tax over ₱2,000 may be paid in two equal installments, the second by October 15. 2551Q is quarterly, within 25 days after quarter end. Percentage tax under Sec. 116 is 3%, except 1% from July 1, 2020 to June 30, 2023 under CREATE; it reverted to 3% from July 1, 2023 (RMC 69-2023).

### Cited Findings

- [Confirmed] "Individuals engaged in business/practice of profession, regardless of amount of sales/receipts, are required to file Quarterly Income Tax Return on or before May 15, August 15 and November 15 ... pursuant to Section 74(A) ... and to file an annual Income Tax Return ... April 15 as provided under Section 51(C)(1)". If tax due exceeds ₱2,000, it may be paid in two equal installments, the second "on or before October 15". — [BIR RR 8-2018 digest](https://bir-cdn.bir.gov.ph/local/pdf/Digest%20RR%208-2018_copy.pdf)
- [Confirmed] 1701-MS guidelines (August 2024): "The return shall be filed and the tax shall be paid on or before the 15th day of April of each year covering income of the preceding taxable year." It is filed electronically, with manual filing allowed if platforms are unavailable. — [BIR 1701-MS Guidelines, Aug 2024](https://bir-cdn.bir.gov.ph/BIR/pdf/1701-MS%20Guide%20August%202024%20ENCS_Final.pdf)
- [Confirmed] TRAIN Sec. 116: non-VAT persons "shall pay a tax equivalent to three percent (3%) of his gross quarterly sales or receipts". — [RA 10963, Lawphil](https://lawphil.net/statutes/repacts/ra2017/ra_10963_2017.html)
- [Confirmed] BIR 2551Q guidance: filed by persons who are not VAT-registered and whose annual gross sales or receipts do not exceed ₱3,000,000, exempt from VAT under Sec. 109(BB). — BIR "Percentage Tax" tax-information dataset (bir.gov.ph CMS, template 186).
- [Confirmed-secondary] CREATE (RA 11534): 1% of gross sales or receipts from July 1, 2020 to June 30, 2023. RMC 69-2023 (June 20, 2023) announced the reversion to 3% effective July 1, 2023. — [Forvis Mazars on RMC 69-2023](https://forvismazars.com/ph/en/insights/tax-alerts/bir-revenue-memorandum-circular-no.-69-2023); [Grant Thornton: reversion of rates](https://www.grantthornton.com.ph/insights/articles-and-updates1/tax-notes/reversion-of-rates-of-percentage-tax-minimum-corporate-income-tax-and-regular-income-tax-on-proprietary-educational-institutions-and-not-for-profit-hospitals); [PwC Tax Summaries](https://taxsummaries.pwc.com/philippines/individual/significant-developments)

### Inferences

- [Interpretation] For quarters straddling the change (Q3 2020 and Q3 2023), the rate follows the sale or receipt date: 1% for July 1, 2020 onward and 3% for July 1, 2023 onward. Q1 and Q2 2020 were 3%, and Q2 2023 was 1%.

### Gaps

- [Needs verification] The 2551Q "25 days after the end of each taxable quarter" deadline (Sec. 128(A)(1) as amended by TRAIN) was not read in a primary text this session. It is widely stated and consistent with 2550Q, but should be checked against Sec. 128 or the 2551Q guidelines.
- [Needs verification] The lawphil TRAIN text includes a proviso exempting self-employed individuals and professionals with gross ≤ ₱500,000 from the 3% tax from January 1, 2019. I believe this proviso was vetoed in the President's TRAIN veto message, but did not confirm. Do not implement it without checking.

---

## 9. Annual ITR forms: 1701-MS vs 1701A vs 1701

### Takeaway

1701-MS (August 2024 version) is the annual ITR for individuals classified as micro or small, including mixed-income earners, who have a single activity and a single tax regime. Anyone with two or more activities or multiple tax regimes or rates must use 1701. 1701A is for purely self-employed individuals and professionals on the 8% rate or graduated rates with OSD. 1701 covers everyone else, including mixed-income earners outside 1701-MS and itemized-deduction filers.

### Cited Findings

- [Confirmed] 1701-MS guidelines: "This return shall be filed by the following individuals classified as micro (those with gross sales of less than P 3,000,000) or small (those with gross sales of P 3,000,000 to less than P 20,000,000) taxpayers". This covers resident citizens, resident aliens, non-resident citizens and non-resident aliens engaged in business, fiduciaries, and "An individual engaged in trade or business or in the exercise of their profession and receiving compensation income as well." — [BIR 1701-MS Guidelines, Aug 2024](https://bir-cdn.bir.gov.ph/BIR/pdf/1701-MS%20Guide%20August%202024%20ENCS_Final.pdf)
- [Confirmed] 1701-MS form (August 2024), Item 12: "If with two (2) or more activities OR multiple tax regimes or tax rates, please use BIR Form No. 1701". Item 17 lets the filer choose itemized deduction or OSD "[40% of Gross Sales/Revenues/Fees". The form is 2 pages, consistent with RA 11976 Sec. 45's two-page cap for micro and small. — BIR Form 1701-MS (August 2024), read from local copy of the BIR CDN PDF; [RA 11976, Lawphil](https://www.lawphil.net/statutes/repacts/ra2024/ra_11976_2024.html)
- [Confirmed-secondary] For TY2025 (filed in 2026), RMC 36-2026 and RMC 37-2026 guide filing of 1701-MS, 1701 and 1701A. RMC 37-2026 reportedly enables e-filing of 1701-MS through Offline eBIRForms 7.9.6. Some sources say micro and small taxpayers may use 1701-MS, 1701 or 1701A regardless of their COR. — [PwC Tax Alert 19 (2026)](https://www.pwc.com/ph/en/tax/tax-publications/tax-alerts/2026/tax-alert-19.html); [Respicio: mixed-income ITR filing](https://www.respicio.ph/commentaries/how-to-file-income-tax-return-for-mixed-income-earners-in-the-philippines)
- [Confirmed-secondary] 1701A is for purely self-employed individuals and professionals on 8%, or on graduated rates with OSD. Mixed-income earners outside 1701-MS file 1701. — [Grant Thornton: enhanced BIR Form 1701](https://www.grantthornton.com.ph/alerts-and-publications/technical-alerts/tax-alert/2019/enhanced-bir-form-1701-for-individuals-engaged-in-businessprofession-including-mixed-income-earners-estates-and-trusts/); [Wise: 1701 vs 1701A](https://wise.com/ph/blog/1701-vs-1701a)

### Inferences

- [Interpretation] App routing: micro or small with a single activity and single regime → 1701-MS, with 1701A or 1701 still accepted. Purely self-employed, 8% or graduated with OSD, not using 1701-MS → 1701A. Mixed income, itemized deductions, multiple activities or regimes, or medium and large → 1701.
- [Interpretation] The 1701-MS cites OSD as "Sec. 34(K)", while RR 8-2018 places OSD at Sec. 34(L). This looks like a form-label quirk and should not drive app logic.

### Gaps

- [Needs verification] Whether 1701-MS is mandatory or optional for micro and small filers in TY2025 and TY2026. The text of RMC 36-2026 and 37-2026 was not read (PwC page blocked).

---

## 10. Weekend and holiday rule for deadlines (next working day): legal basis

### Takeaway

In practice, a BIR deadline that falls on a Saturday, Sunday or holiday moves to the next working day for filing and payment (manual and electronic). The usual legal basis is the Administrative Code of 1987 (EO 292), Book I, Chapter 8 ("Legal Periods"), together with BIR circulars such as RMC 65-2016. I could not confirm the exact section number: Sec. 31 is commonly cited, and one source says Sec. 28.

### Cited Findings

- [Confirmed-secondary] "Under Section 28 of the Administrative Code of the Philippines, where the day, or the last day for doing any act required or permitted by law falls on a regular holiday, or a special day, the act may be done on the next succeeding business day." — [Grant Thornton: Tick tock, click, file, pay](https://www.grantthornton.com.ph/insights/articles-and-updates1/lets-talk-tax/tick-tock-click-file-pay/)
- [Confirmed-secondary] RMC 65-2016: if a deadline falls on a Saturday, Sunday or holiday, filing and payment, including electronic filing and payment, move to the next business day. — [Forvis Mazars on RMC 65-2016](https://www.forvismazars.com/ph/en/insights/tax-alerts/bir-rmc-65-2016); [Grant Thornton copy of RMC 65-2016](https://www.grantthornton.com.ph/globalassets/1.-member-firms/philippines/tax-alerts/2016/06.27.2016/rmc-no-65-2016.pdf)
- [Confirmed-secondary] The rule applies to eFPS, eBIRForms and manual filers alike. — [Roque Law: deadlines for filing returns and tax payments](https://roquelaw.com.ph/deadlines-for-filing-returns-and-tax-payments/)

### Inferences

- [Interpretation] The app should roll a deadline forward to the next day that is neither a weekend nor a declared regular or special non-working holiday. That needs a per-year holiday calendar (proclamations), plus support for ad hoc BIR deadline extensions announced by RMC.

### Gaps

- [Needs verification] The exact Administrative Code section (Sec. 28 vs Sec. 31 of Book I, Chapter 8). The EO 292 text was not read. The Code speaks of "regular holiday or special day"; Saturdays and Sundays are covered by BIR practice and RMCs rather than by the Code's text.
- [Needs verification] Whether eFPS group-staggered deadlines interact with the rollover. EFPS filers have their own staggered dates under separate issuances, which were not researched here.
