# EOPT and CMEPA rewrite BuwisBuddy's tax rules

BuwisBuddy's rule pack 2026.11 gets most headline numbers right. That includes both graduated tables, the 8% option and its ₱250,000 reduction, the 3%/1%/3% percentage tax history, the 25%/20% corporate rates, the MCIT 1%-to-2% reversion, the ₱3,000,000 VAT threshold, and the due dates for the 1701Q, the annual ITR, the 1702Q/1702-RT, the 0619-E, the 1601-EQ and the 1604-E. About fifteen rules still compute a wrong amount or a wrong date. The most serious:

- **Compensation withholding must use the RR 11-2018 Annex "E" table each payroll period, not a 12× annualization.** The December 1601-C is due **January 15**, not January 10.
- **Excess input VAT carries over across years**, not just within one.
- **DST on loans has been 0.75% of the issue price since July 1, 2025** (RA 12214 / RR 19-2025), prorated by days, not months.
- **Invoice-date recognition applies to VAT and percentage tax only from April 27, 2024, and never to income tax.** Income tax follows the method the books use.
- **The EOPT 10%/6% penalties apply only to due dates on or after April 27, 2024**, and they depend on the classification the BIR assigned for that period, not on today's gross.
- **Crossing ₱3M must switch the 8% filer to graduated rates automatically.**
- **Months and quarters with nothing due still owe "no payment" returns** for every tax type registered.

The app also leaves out several things it needs: the ₱90,000 benefits cap, the de minimis ceilings RR 29-2025 raised on January 6, 2026, minimum-wage-earner treatment, the RR 14-2018 VAT-status condition on the 5% professional-fee rate, financial-statement guidance for graduated filers, and NOLCO and excess-MCIT carry-forwards. Two items are urgent as of November 2026. RR 4-2026's one-time abatement for micro taxpayers closes **December 31, 2026**, and covered taxpayers must start e-invoicing by the same date. Some questions are still open and need a CPA's sign-off: the quarterly 20% corporate rate test, the juridical, rental and contractor withholding rates, whether a no-payment 1601-EQ, 1604-E or 1702Q is required, and percentage tax on receivables from before EOPT. bir.gov.ph blocked automated access, so many regulations were read only through the BIR's own digests or tax-firm summaries. The labels below say which.

## How to read the evidence labels in this report

Each rule carries one of four labels:

- **[Confirmed–P]**: the primary text was read. That is the statute on Lawphil, or a BIR-published regulation, digest, form or Forms-page entry on the BIR CDN or CMS.
- **[Confirmed–S]**: the issuance and section are named, but the wording comes from one or more reputable tax-firm summaries (Grant Thornton, KPMG, Deloitte, Forvis Mazars, BDB Law), not from the BIR text.
- **[Interpretation]**: a reading of confirmed material that a CPA should approve.
- **[Needs verification]**: secondary or blog sources only, sources that conflict, or background knowledge.

BIR digests (RR 5-2021, RR 6-2024, RR 8-2018, RR 8-2024, RR 19-2025, RMC 20-2026, RMC 62-2021) carry the BIR's own wording but are not the signed full text. They count as P here, with that caveat. Several 2024–2026 issuances could be reached only through firm alerts, because bir.gov.ph and some firm sites returned HTTP 403.

## Q1: invoice dates drive VAT and percentage tax, not income tax

**Income tax.** The NIRC does not force accrual on individuals. Sec. 43 computes taxable income "in accordance with the method of accounting regularly employed in keeping the books", and Sec. 44 includes income "in the taxable year in which received" unless the permitted method places it elsewhere. Sec. 45 matches deductions to "paid or accrued" according to the method **[Confirmed–P]** ([NIRC, Lawphil](https://lawphil.net/statutes/repacts/ra1997/ra_8424_1997.html)). The Commissioner may impose a method only when the one used does not clearly reflect income.

No source found says EOPT amended Secs. 43–45. Grant Thornton reads RR 3-2024's "billing/billed" substitution as expressly limited to recognising sales "under Title IV (VAT) and Title V (Percentage Tax)" **[Confirmed–S]** ([Grant Thornton on RR 3-2024](https://www.grantthornton.com.ph/alerts-and-publications/technical-alerts/tax-alert/2024/implementation-of-the-amendments-introduced-by-eopt-act-on-vat-and-percentage-tax/)). RR 3-2024 Sec. 5 also states that the new output-VAT credit on uncollected receivables does not affect income-tax bad-debt rules under RR 25-2002, which keeps the two regimes separate **[Confirmed–S]** ([KPMG, April 2024](https://kpmg.com/ph/en/insights/2024/04/special-intax-april-2024-issue-1-volume-3.html)). Deloitte and MTF Counsel describe EOPT as "aligning" income tax and VAT on accrual, but that is commentary written in a VAT context, not a rule ([MTF Counsel](https://mtfcounsel.com/?p=6777)).

**So a cash-basis professional on graduated rates reports income when collected [Interpretation].** For the 8% option, no BIR text found settles whether "gross sales/receipts" means invoiced or collected amounts. RR 8-2018 Sec. 2–3 and RMC 50-2018 were not read **[Needs verification]**. Absent a special rule, the Sec. 43–44 timing would apply.

**Percentage tax.** RA 11976 Sec. 24 rewrote Sec. 116 to "three percent (3%) of his gross quarterly sales", dropping "or receipts" **[Confirmed–P]** ([RA 11976, Lawphil](https://lawphil.net/statutes/repacts/ra2024/ra_11976_2024.html)). RR 3-2024 renames "gross receipts" as "Gross Sales" and makes billing the recognition point for Titles IV and V **[Confirmed–S]** ([Grant Thornton](https://www.grantthornton.com.ph/alerts-and-publications/technical-alerts/tax-alert/2024/implementation-of-the-amendments-introduced-by-eopt-act-on-vat-and-percentage-tax/)).

**Effective date.** The EOPT regulations took effect collectively on **April 27, 2024** **[Confirmed–S]** ([Deloitte](https://www.deloitte.com/southeast-asia/en/services/tax/perspectives/navigating-new-tax-landscape.html); [Forvis Mazars](https://forvismazars.com/ph/en/insights/tax-alerts/ease-of-paying-taxes-act/transitory-provisions-of-eopt-act-issuances)). Sources disagree on when RR 3-2024 was posted (April 12 or April 26), but agree on the effective date. RA 11976 Sec. 48 gave six months to comply **[Confirmed–P]**.

**VAT on services.** RA 11976 Sec. 17 moved the Sec. 108 base to "gross sales" and requires long-term contracts to be invoiced in the month the service is rendered **[Confirmed–P]**. RR 3-2024 Sec. 7(a) is a transitory rule: services rendered and billed before effectivity but uncollected have their output VAT "declared once it has been collected", supported by an invoice. Sec. 7(b) denies the uncollected-receivable credit on goods sold on account before effectivity **[Confirmed–S]** ([KPMG](https://kpmg.com/ph/en/insights/2024/04/special-intax-april-2024-issue-1-volume-3.html)).

**The new uncollected-receivables credit.** EOPT adds Sec. 110(D): a seller may deduct output VAT on uncollected receivables once the agreed payment term lapses, provided the VAT was paid and not claimed as a bad debt. The VAT is added back if the receivable is later recovered **[Confirmed–P]** for the statute ([RA 11976](https://lawphil.net/statutes/repacts/ra2024/ra_11976_2024.html)); the conditions come from MTF Counsel.

**Invoice timing.** From April 27, 2024 the invoice must be issued "at the point of each sale … or for services rendered" (amended Sec. 237(A)). An official receipt survives only as a supplementary document that cannot support input VAT **[Confirmed–P/S]** ([RA 11976](https://lawphil.net/statutes/repacts/ra2024/ra_11976_2024.html); [Grant Thornton on invoicing](https://www.grantthornton.com.ph/insights/articles-and-updates1/lets-talk-tax/invoicing-requirements-under-the-eopt-act/)).

**What this means for the app.** It needs two views of every sale: an invoice-date view and a collection view.

- **2550Q and 2551Q** run on invoice date for invoices dated on or after April 27, 2024. Before that, services run on collection date. Pre-EOPT billed services collected afterwards go into the quarter they are collected, for VAT.
- **Percentage tax on those pre-EOPT receivables** should follow the same rule by analogy, since the old Sec. 116 base was "receipts". **No source states a percentage-tax transitory rule [Needs verification].**
- **The ITR** follows the taxpayer's books method, stored per tax year.
- **Year-end reconciliation:** a cash-basis filer's ITR gross will legitimately differ from the 2551Q/2550Q gross by the change in receivables. The app should explain that difference, because the BIR cross-matches returns.

**Creditable withholding timing.** RR 4-2024 Sec. 7 moved the moment the payor must withhold to the earliest of three events: the income becomes payable, the payor accrues it, or the invoice is issued **[Confirmed–S]** ([KPMG](https://kpmg.com/ph/en/insights/2024/04/special-intax-april-2024-issue-1-volume-3.html)). A 2307 can therefore predate the collection. A cash-basis ITR must still let that CWT be claimed in the year the related income is reported. That matching is usual practice, not sourced text **[Needs verification]** against RR 2-98 Sec. 2.58.3.

**Rule 1.15 is wrong on two counts.** It applies invoice dating to income tax, and it applies it to percentage tax back to 2018. **Rule 2.8 (VAT on invoice date from April 27, 2024) is right but lacks the Sec. 7(a) transitory rule.**

## Q2: e-filing is the default; filled paper PDFs are the exception

**eFPS is mandatory only for listed classes.** These include large taxpayers, the Top 20,000 corporations, the Top 5,000 individuals (RR 6-2009), TAMP taxpayers and accredited importers (RR 10-2014), licensed local contractors (RR 10-2012), government bidders (RR 3-2005), corporations with paid-up capital of ₱10M or more or with a complete CAS (RR 10-2007), and holders of fiscal incentives **[Confirmed–S]** ([Roque Law](https://roquelaw.com.ph/reminder-for-taxpayers-required-to-use-efps/)).

RMC 20-2026, the guideline for filing calendar-year 2025 annual returns, sets three channels **[Confirmed–P]** ([BIR digest of RMC 20-2026](https://bir-cdn.bir.gov.ph/BIR/pdf/RMC%20No.%2020-2026%20Digest.pdf)):

- eFPS for mandated or voluntarily enrolled taxpayers;
- **Offline eBIRForms (v7.9.5) for "non-eFPS taxpayers, including those filing 'No Payment' returns"**;
- BIR-certified tax software providers such as Taxumo and JuanTax, each certified only for particular forms.

A typical freelancer or sole proprietor is therefore a non-eFPS filer who must still e-file. The app should ask whether the user is enrolled in eFPS or has been notified to enrol, rather than assume **[Interpretation]**.

**Manual filing.** RMC 20-2026 allows it in only three cases: a BIR advisory that the platforms are down, a justifiable reason accepted by the Commissioner, or a return not yet on any platform **[Confirmed–P]**. The one routine paper route is **BIR Form 1701-MS** for micro and small taxpayers. It goes to any AAB if tax is payable, or any RDO if not, and no penalty applies for wrong venue **[Confirmed–S]** ([BDB Law reproduction of RMC 20-2026](https://www.bdblaw.com.ph/images/advisory/190_Advisory_RMC_No20-2026.pdf)). That route is confirmed for calendar year 2025 only. Two later items are reported only by secondary sources **[Needs verification]** ([BIR Online Tools](https://bir-online-tools.com/blog/posts/ebirforms-v7960-update-rmc-36-2026/)):

- RMC 36-2026 (April 28, 2026) released eBIRForms v7.9.6.0 with 1701-MS included.
- RMC 37-2026 allowed 1701-MS to be e-filed through that version.

**Printed BIR PDFs.** Under earlier circulars, downloadable PDF forms printed on A4 paper were acceptable whenever manual filing was itself allowed. RMC 24-2018 directed RDOs and AABs to accept them, and RMC 13-2020 told manual filers to print the PDF when a form was not yet in eBIRForms **[Confirmed–S]** ([RMC 24-2018, PwC copy](https://pwc.com/ph/en/tax-alerts/assets/2018/RMC%20No%2024-2018.pdf); [PwC on RMC 13-2020](https://www.pwc.com/ph/en/tax/tax-publications/tax-alerts/2020/tax-alert-10.html)). No issuance from 2024 to 2026 was found restating that acceptance **[Needs verification]**. So a filled BIR PDF is a valid paper return only in these cases: 1701-MS, an advisory-declared outage, a form not on any platform, or Commissioner authorization. For everything else it is a worksheet. The user re-keys it into eBIRForms or a TSP and keeps the TRRC or FRN as proof of filing **[Interpretation]**. The app's "print and file" offer should be reframed along those lines.

**"File and pay anywhere."** RR 4-2024 Sec. 3 and the RMC 87-2024 Q&A made electronic filing the rule. They let payment go through any AAB, any revenue collection officer (cash capped at ₱20,000, checks unlimited) or any ePay channel, and they removed the 25% wrong-venue surcharge. Late filers still compute penalties at their registered RDO **[Confirmed–S]** ([KPMG on RMC 87-2024](https://kpmg.com/ph/en/insights/2025/01/essential-faqs-filing-tax-returns-and-paying-taxes-explained.html)). The ePay gateways named in RMC 20-2026 are LBP Link.BizPortal, UnionBank, and DBP PayTax with MyEG and Maya **[Confirmed–P]** ([BIR digest](https://bir-cdn.bir.gov.ph/BIR/pdf/RMC%20No.%2020-2026%20Digest.pdf)).

**Attachments go through eAFS within 15 days of the filing deadline**, or within 15 days of actual filing if the return is late. This covers 2307s, the SAWT acknowledgment, financial statements and the CPA certificate. Manual RDO submission is allowed only during an eAFS outage **[Confirmed–S]** ([BDB Law](https://www.bdblaw.com.ph/images/advisory/190_Advisory_RMC_No20-2026.pdf)). The BIR extends these windows often. For tax year 2025, RMC 30-2026 moved the annual ITR to May 15, 2026, and RMC 46-2026 gave a penalty-free eAFS window to May 25, 2026 **[Confirmed–S]** ([Grant Thornton on RMC 46-2026](https://www.grantthornton.com.ph/technical-alerts/tax-alert/2026/extended-deadline-for-submission-of-2025-audited-financial-statements-and-other-required-attachments-to-the-annual-income-tax-return/)).

**EIS e-invoicing.** A circular dated September 22, 2026 requires e-invoicing **on or before December 31, 2026** from four groups:

- taxpayers under the Large Taxpayers Service;
- large taxpayers under EOPT;
- small, medium and large taxpayers engaged in e-commerce or internet transactions;
- taxpayers using a computerized accounting system or computerized books.

**Micro taxpayers are not covered** **[Confirmed–S, government news agency]** ([PNA](https://www.pna.gov.ph/article/bir-launches-enhanced-electronic-invoicing)). The circular's number was not found.

## Q3: financial statements follow the deduction method, and audit starts above ₱3M

**Audit threshold.** TRAIN Sec. 71 amended Sec. 232(A): books of persons "whose gross annual sales, earnings, receipts or output exceed Three million pesos (₱3,000,000)" must be audited yearly by an independent CPA **[Confirmed–P]** ([RA 10963, Lawphil](https://lawphil.net/statutes/repacts/ra2017/ra_10963_2017.html)). Before TRAIN the test was quarterly sales above ₱150,000. The test is strictly "exceed", so exactly ₱3,000,000 is not audited. The rest of post-TRAIN Sec. 232(A) was truncated in the copy read, including the simplified-bookkeeping proviso **[Needs verification]**.

**Who attaches FS.** RMC 17-2019 created Form 1701A for purely self-employed filers on OSD or 8% and states they "are no longer required to submit financial statements". Itemized-deduction filers and mixed-income earners use 1701 **[Confirmed–S]** ([Grant Thornton on RMC 17-2019](https://www.grantthornton.com.ph/alerts-and-publications/technical-alerts/tax-alert/2019/new-annual-income-tax-return-for-individuals-availing-osd-and-8-income-tax-rate/)). Practitioner guides list "Account Information Form and/or financial statements" for 1701 filers, plus the CPA certificate and audited FS above ₱3M ([Filepino](https://www.filepino.com/who-is-required-to-submit-an-audited-financial-statement-afs/)). RR 8-2018 adds that an ITR showing sales over the VAT threshold needs audited FS **[Confirmed–P]** ([BIR digest of RR 8-2018](https://bir-cdn.bir.gov.ph/local/pdf/Digest%20RR%208-2018_copy.pdf)).

The app's message to 8% filers ("no FS needed") is right. Its silence toward graduated filers is wrong. Graduated filers on OSD attach nothing. Graduated filers on itemized deductions attach unaudited FS or an AIF, or audited FS with a CPA certificate if gross exceeds ₱3M, uploaded through eAFS. A KPMG-hosted 2018 annex appears to list FS under the 8% entry. It conflicts with RMC 17-2019 and was not opened **[Needs verification]**.

Corporations differ. A corporation on OSD still submits FS (RR 16-2008 summary, [jur.ph](https://jur.ph/law/summary/implementing-optional-standard-deduction-tax-code-amendment)), and SEC audit rules run independently of the BIR's ₱3M test.

**Book formats.** RMC 65-2025 lets registrants choose **one** format **[Confirmed–S]** ([Grant Thornton on RMC 65-2025](https://www.grantthornton.com.ph/insights/articles-and-updates1/tax-notes/bir-clarifies-bookkeeping-options-for-new-business-registrants/)):

- manual books;
- loose-leaf books, which need a Permit to Use;
- computerized books, which need an Acknowledgement Certificate.

Those choosing loose-leaf or computerized books need not also register manual books.

**There is no ₱3M trigger for computerized books.** CAS is mandatory only for large taxpayers (RR 9-2009 Sec. 1.4, 4.1, with no peso threshold) **[Confirmed–P]** ([RR 9-2009, SC e-Library](https://elibrary.judiciary.gov.ph/thebookshelf/showdocs/10/48854)). Any non-large taxpayer who opts for computerized books needs the Acknowledgement Certificate whatever its size (RMO 9-2021) **[Confirmed–S]** ([KPMG copy of RMO 9-2021](https://assets.kpmg.com/content/dam/kpmg/ph/pdf/InTAX/2021/RMO%20No.%209-2021.pdf)). One commercial site claims a ₱3M trigger; it conflicts with RR 9-2009.

Rule 9.2 should be dropped. The app should also not present its own ledger as the user's registered books unless the user has registered it.

**Binding deadlines.** Loose-leaf books are permanently bound and registered within **15 days after year end**, and computerized books within **30 days**. Both come from consistent practitioner sources citing RMC 29-2019 **[Needs verification]** ([Aureada Law](https://www.aureadalaw.com/post/bir-philippines-loose-leaf-books-binding-registration-deadlines-for-2026)).

**Registration now runs strictly through ORUS** under RMC 4-2026, with a QR code fixed to the first page of the bound books **[Confirmed–S]** ([Grant Thornton on RMC 4-2026](https://www.grantthornton.com.ph/technical-alerts/tax-alert/2025/clarification-on-the-mandatory-orus-registration-of-looseleaf-and-computerized-books-and-extension-of-deadlines/)). The BIR extended these deadlines in both recent seasons. For tax year 2024, RMC 6-2025 moved them to January 31 and February 17, 2025 ([Grant Thornton](https://www.grantthornton.com.ph/technical-alerts/tax-alert/2024/registration-of-permanently-bound-loose-leaf-books-of-accounts-invoices-and-computerized-books-of-accounts-and-other-accounting-records-extended-until-january-31-2025-and-february-17-2025-respectively/)).

**Retention is five years** under RA 11976 Sec. 33 amending Sec. 235 **[Confirmed–P]** ([RA 11976](https://lawphil.net/statutes/repacts/ra2024/ra_11976_2024.html)). The clock starts the day after the filing deadline, or on the filing date if the return was late, for the taxable year of the last entry. Records involved in a pending protest or refund are kept until the case is resolved (RR 7-2024) **[Confirmed–S]** ([Grant Thornton](https://www.grantthornton.com.ph/insights/articles-and-updates1/tax-notes/eopt-is-here-updates-on-the-preservation-of-book-of-accounts-and-changes-in-taxpayer-registration/)). The app's 5-year rule is right if it counts from those dates.

One conflict remains. RR 11-2018 Sec. 2.83.1 still requires the employer's copy of each 2316 to be kept for **ten years** **[Confirmed–P]** ([RR 11-2018](https://bir-cdn.bir.gov.ph/local/pdf/RR%20No.%2011-2018.pdf)). Whether EOPT's five-year rule overrides it is unresolved, so the safer default is ten.

## Q4: the withholding table, not annualization, governs each payroll

**The table is mandatory.** RR 2-98 Sec. 2.79(B)(1) as amended by RR 11-2018 says every employer "shall deduct and withhold … a tax determined in accordance with the prescribed withholding tax table". Annex "D" covers 2018–2022 and **Annex "E" covers compensation paid from January 1, 2023** **[Confirmed–P]** ([RR 11-2018](https://bir-cdn.bir.gov.ph/local/pdf/RR%20No.%2011-2018.pdf)).

**How each period is computed.** Total the period's pay, leaving out non-taxable benefits and mandatory contributions. Split it into regular pay (basic salary, fixed allowances) and supplementary pay (commissions, overtime, taxable bonuses). Choose the bracket from regular pay only. Then add the supplementary pay to the excess over the bracket floor (Illustration 9). The monthly Annex "E" brackets **[Confirmed–P]** ([Annex E](https://bir-cdn.bir.gov.ph/local/pdf/Annex%20E%20RR%2011-2018.pdf)):

| Monthly compensation | Withholding                                   |
| -------------------- | --------------------------------------------- |
| ₱20,833 and below    | 0                                             |
| ₱20,833 – ₱33,332    | 15% of the excess over ₱20,833                |
| ₱33,333 – ₱66,666    | ₱1,875.00 + 20% of the excess over ₱33,333    |
| ₱66,667 – ₱166,666   | ₱8,541.80 + 25% of the excess over ₱66,667    |
| ₱166,667 – ₱666,666  | ₱33,541.80 + 30% of the excess over ₱166,667  |
| ₱666,667 and above   | ₱183,541.80 + 35% of the excess over ₱666,667 |

The daily table's top bracket is misprinted as "₱6,034.00.30" in the BIR PDF. The value is probably ₱6,034.30 **[Needs verification]**.

**Two other methods are prescribed, and neither is a free choice.**

- **The cumulative average method** is required in three cases: regular pay is below the taxable level but supplementary pay is paid during the year; supplementary pay equals or exceeds regular pay; or the employee is a new hire who had another employer earlier in the same year. Once triggered, it is used for the rest of the year.
- **The annualized method** applies at the year-end adjustment and when employment ends before December.

Both are **[Confirmed–P]**, Sec. 2.79(B)(5) ([RR 11-2018](https://bir-cdn.bir.gov.ph/local/pdf/RR%20No.%2011-2018.pdf)). No provision lets an employer annualize every period instead of using the table. Rule 4.9's "12 × month ÷ 12" is therefore not the prescribed method. Its results will drift from the table's on irregular pay.

**Year-end adjustment.** Rule 4.9's year-end mechanics are correct. A shortfall is withheld from the last pay, and the employer is liable for any part the pay cannot cover. Excess withholding "shall be credited or refunded to the employee **not later than January 25** of the following year", or at the last pay if the employee separated earlier. The employer recovers the refund by deducting it from that month's and later 1601-C remittances **[Confirmed–P]**.

**Exclusions from taxable pay.**

- **The ₱90,000 cap** on 13th-month pay and other benefits (Sec. 2.78.1(B)(11)) is unchanged. No enacted increase was found; House bills for ₱120,000–₱150,000 have not become law **[Confirmed–P]** for the cap.
- **The employee share of SSS, PhilHealth and Pag-IBIG, plus union dues, is excluded** (Sec. 2.78.1(B)(12); Form 2316 item 36). The employer share is not compensation **[Confirmed–P]**. Whether voluntary contributions above the mandatory share are also excluded is **[Needs verification]**.
- **De minimis ceilings** were raised by RR 4-2025 (clothing to ₱7,000) and then by **RR 29-2025** **[Confirmed–P]** for the ceilings ([RR 29-2025](https://bir-cdn.bir.gov.ph/BIR/pdf/RR%20No.%2029-2025.pdf)). RR 29-2025's effective date of **January 6, 2026** is **[Confirmed–S]** ([PMAP](https://pmap.org.ph/circular-no-2025-095-bir-revenue-regulations-no-29-2025-expanded-de-minimis-benefits-for-employees/); [Vialto](https://vialtopartners.com/regional-alerts/philippines-payroll-and-compensation-small-perks-big-changes-updated-de-minimis-benefits-thresholds)). The new ceilings:

| De minimis item                           | RR 29-2025 ceiling               |
| ----------------------------------------- | -------------------------------- |
| Rice subsidy                              | ₱2,500 a month                   |
| Uniform and clothing allowance            | ₱8,000 a year                    |
| Medical assistance                        | ₱12,000 a year                   |
| Laundry allowance                         | ₱400 a month                     |
| Medical cash allowance to dependants      | ₱2,000 a semester (₱333 a month) |
| Monetized vacation leave                  | 12 days                          |
| Achievement awards                        | ₱12,000                          |
| Christmas gifts                           | ₱6,000                           |
| CBA and productivity incentives, combined | ₱12,000                          |
| Overtime and night-shift meal allowance   | 30% of the regional minimum wage |

Amounts above a de minimis ceiling are added to the ₱90,000 benefits bucket, and anything beyond the cap is taxable. This is long-standing BIR doctrine but is not restated in RR 29-2025 **[Needs verification]**.

**Minimum wage earners and pay of ₱250,000 or less.**

- **Minimum wage earners (MWEs):** the statutory minimum wage, holiday pay, overtime, night differential and qualifying hazard pay are exempt. Other pay from the same employer goes through the table **[Confirmed–P]**.
- **Pay of ₱250,000 or less** for the year is separately exempt from withholding **[Confirmed–P]**.
- **Form 2316 (September 2021):** item 11 flags an MWE. Item 29 is "Basic Salary (including the exempt ₱250,000 & below) or the Statutory Minimum Wage of the MWE", and items 30–33 hold the MWE holiday, overtime, night-differential and hazard pay **[Confirmed–P]** ([Form 2316](https://bir-cdn.bir.gov.ph/local/pdf/2316%20Sep%202021%20ENCS_Final_corrected.pdf)).

Rule 4.12, which puts all pay into taxable basic salary, misstates the form. Pay of ₱250,000 or less belongs in item 29 **[Interpretation]**, based on the form's own label.

**Deadlines.** The BIR Forms page **[Confirmed–P]** ([BIR Forms](https://www.bir.gov.ph/bir-forms)) gives:

- **1601-C:** the 10th of the following month for January–November, and **January 15 for December**. eFPS filers file 11–15 days after month end by group under RR 26-2002.
- **1604-C and its alphalist:** January 31.
- **Form 2316 to employees:** January 31, or on the last payday if the employee separated.

Employers also scan and submit the BIR copies of substituted filers' 2316s, with the Annex "F" list, by **February 28** (RR 11-2018 Sec. 2.83.1, RR 16-2021). RMC 29-2024 moved that date to March 31 for 2024 and ended the COVID-era acceptance of unsigned copies **[Confirmed–P]** ([RMC 29-2024](https://bir-cdn.bir.gov.ph/BIR/pdf/RMC%20No.%2029-2024.pdf)). Rule 4.11's December 10th date is wrong, and the February 28 submission is missing.

## Q5: registered tax types generate returns even when nothing is due

**No-payment returns are filed, not skipped.** RMC 12-2015 requires business taxpayers with no-payment returns to file them electronically. RMC 28-2017 limits manual no-payment filing to senior citizens and PWDs and to a few classes of employees **[Confirmed–S]** ([Grant Thornton on RMC 12-2015](https://www.grantthornton.com.ph/alerts-and-publications/technical-alerts/tax-alert/2015/clarification-on-filing-of-no-payment-returns/); [Grant Thornton on RMC 28-2017](https://www.grantthornton.com.ph/alerts-and-publications/technical-alerts/tax-alert/2017/manual-filing-of-no-payment-returns/)).

| Return                             | Nil filing required?                                                                                                            | Status                                                                                                                                              |
| ---------------------------------- | ------------------------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------- |
| 0619-E                             | Yes. A manual filer with nothing withheld for the month "is required to file 'No Payment' returns through the use of eBIRForms" | [Confirmed–S] ([KPMG, Sept 2018](https://kpmg.com/ph/en/home/insights/2018/09/special-intax-sept2018-issue1-vol1.html)), from a search snippet only |
| 1701Q, and 2551Q for non-8% filers | Yes, even with zero sales; late zero returns draw compromise penalties                                                          | [Confirmed–S] ([FilePino](https://www.filepino.com/zero-business-transactions-mandatory-bir-filing-taxes/))                                         |
| 1601-EQ, 1604-E                    | No source states it directly. The RDO's open-case system follows registered tax types, and "some RDOs still expect" a 1604-E    | [Needs verification] ([Respicio](https://www.respicio.ph/commentaries/resolve-bir-open-cases-for-1601-e-and-1604-e-philippines))                    |
| 1702Q                              | No source covered it                                                                                                            | [Needs verification]                                                                                                                                |

The engine should therefore list every registered period as "due, ₱0", not "no return" **[Interpretation]**. A user with no payees should file Form 1905 to drop the withholding tax types.

**Professional-fee withholding rates.** RR 11-2018 sets **5% when the individual payee's current-year gross is ₱3M or less, and 10% otherwise**. RR 14-2018 added a condition: the 5% rate applies only if the payee's COR shows **non-VAT** registration. A VAT-registered payee is withheld at 10% regardless of income **[Confirmed–S]** ([Grant Thornton on RR 11-2018](https://www.grantthornton.com.ph/alerts-and-publications/technical-alerts/tax-alert/2018/new-withholding-rules-on-payments-of-professional-talent-and-commission-fees-to--self-employed-individuals/); [Grant Thornton on RR 14-2018](https://www.grantthornton.com.ph/alerts-and-publications/technical-alerts/tax-alert/2018/additional-condition-for-5-ewt-on-professional-fees-extended-deadlines-for-sworn-declarations/)).

RMO 38-2018 confirms the codes WI010, WI011, WC010 and WC011, but its rate columns could not be read ([RMO 38-2018](https://archive.ffcccii.org/wp-content/uploads/2018/09/RMO_No.38-2018.pdf)). Two mappings remain open:

- **Individual payees:** WI010 = 5% and WI011 = 10% fits the regulatory structure **[Interpretation]**.
- **Juridical payees:** sources conflict on WC010 10% and **WC011 15% above ₱720,000** **[Needs verification]** ([Sprout](https://guide.sprout.ph/different-tax-percentages-for-consultants)).

Rental (WI100/WC100 at 5%) and contractor (WI120/WC120 at 2%) rates rest only on background knowledge of RR 2-98 Sec. 2.57.2 **[Needs verification]**.

**Sworn declaration and payor deadlines.** Payees give a sworn declaration with a copy of their COR by **January 15**, or before the first payment. The payor files the declarations and a payee list with the BIR by January 31 **[Confirmed–S]** ([Roque Law](https://roquelaw.com.ph/new-withholding-rules-on-payments-of-professional-talent-and-commission-fees/)). The 2307 is due within 20 days after each quarter, or with the payment on request **[Confirmed–S]** ([Respicio](https://www.respicio.ph/commentaries/is-bir-form-2307-issued-in-advance)). Return deadlines are:

- **0619-E:** the 10th of the following month, or the 15th for eFPS filers;
- **1601-EQ:** the last day of the month after the quarter;
- **1604-E:** March 1.

All three are **[Confirmed–S]** ([juan.tax](https://juan.tax/form-1601eq/); [Roque Law on 1604-E](https://roquelaw.com.ph/updated-filing-guidelines-for-bir-forms-1604c-1604f-and-1604e-submission-of-bir-form-2316-without-employee-signature-shall-be-accepted/)). The app's dates are correct; it should add the eFPS 15th.

**E-marketplaces (RR 16-2023).** Operators withhold **1% of one-half of gross remittances, an effective 0.5%**, under ATC WI760/WC760 (RMO 4-2024) **[Confirmed–S]** ([Grant Thornton](https://www.grantthornton.com.ph/insights/articles-and-updates1/tax-notes/online-sellers-subject-to-1-expanded-withholding-tax/); [KPMG, Feb 2024](https://kpmg.com/ph/en/insights/2024/02/special-intax-february-2024-issue-1-volume-1.html)):

- **Base:** gross remittance excludes VAT, returns, discounts and platform fees.
- **Threshold:** ₱500,000 of remittances, measured on the prior year or cumulatively in the current year. It is not a sales test.
- **Avoiding withholding:** a seller under the threshold escapes it only by giving the platform a BIR-received sworn declaration by **January 20** (RMC 8-2024) ([Forvis Mazars](https://www.forvismazars.com/ph/en/insights/tax-alerts/bir-rmc-8-2024)).
- **Dates:** the RR took effect January 11, 2024, but **enforcement began July 15, 2024 for e-marketplaces and October 13, 2024 for digital financial service providers** ([Grant Thornton on DFSPs](https://www.grantthornton.com.ph/insights/articles-and-updates1/tax-notes/bir-extends-the-deadline-for-the-imposition-of-withholding-tax-on-payments-made-by-digital-financial-service-providers-to-online-sellers)).

The app's "2024 →" start date and "annual sales" wording both need tightening.

## Q6: small corporations settle the 20% rate on the annual return

**Rates and tests.** CREATE's Sec. 27(A) taxes corporations at 25%, or at 20% where "net taxable income not exceeding Five million pesos **and** … total assets not exceeding [₱100M] … excluding land" applies, effective July 1, 2020 **[Confirmed–P]** ([RA 11534](https://lawphil.net/statutes/repacts/ra2021/ra_11534_2021.html); [BIR digest of RR 5-2021](https://bir-cdn.bir.gov.ph/local/pdf/RR%20No.%205-2021.pdf)). RMC 62-2021 defines the asset test **[Confirmed–P]** ([BIR digest](<https://bir-cdn.bir.gov.ph/local/pdf/RMC%20No.%2062-2021%20(1).pdf>)):

- Total assets are **net of depreciation and the allowance for bad debts**.
- The exclusion covers only the land under the office, plant and equipment "during the taxable year". It does not cover land held for sale or investment.
- Land is excluded at whatever value the FS carry (cost or FMV), prorated by floor area when only partly used as the office.

RR 5-2021 requires that land to be shown as a separate AFS line. Together these point to the **year-end AFS as the asset measure** **[Interpretation]**; no BIR text fixes an explicit "as of" date.

**The quarterly test has no BIR guidance.** RMC 62-2021 is silent, and Grant Thornton names it as an open question ([Grant Thornton](https://www.grantthornton.com.ph/insights/articles-and-updates1/lets-talk-tax/addressing-corporate-income-tax-concerns-under-the-create-law/)). Sec. 75 makes the 1702Q cumulative, and Sec. 76 trues up at year end **[Confirmed–P]** ([NIRC](https://lawphil.net/statutes/repacts/ra1997/ra_8424_1997.html)). The app's year-to-date test is therefore the most defensible quarterly convention, with three consequences **[Interpretation]**:

- The rate can flip mid-year.
- The 1702-RT decides the whole year at one rate, with no proration.
- Excess quarterly payments become a Sec. 76 overpayment, refundable or carried over. Carry-over is irrevocable.

The app should label the quarterly rate provisional and warn when projected annual NTI or assets approach the caps.

**MCIT rates.** MCIT is 2% of gross income, 1% from July 1, 2020 to June 30, 2023, and 2% again from July 1, 2023 **[Confirmed–P]** ([RA 11534](https://lawphil.net/statutes/repacts/ra2021/ra_11534_2021.html)).

**Tax year 2023 is computed by months, not by quarters.** RMC 69-2023 splits the year at June 30 **[Confirmed–S]** ([Forvis Mazars](https://forvismazars.com/ph/en/insights/tax-alerts/bir-revenue-memorandum-circular-no.-69-2023)). Grant Thornton describes the method: annual gross income ÷ 12, with 1% on January–June and 2% on July–December. That gives **1.5% effective for calendar 2023** **[Confirmed–S]** ([Grant Thornton](https://www.grantthornton.com.ph/insights/articles-and-updates1/tax-notes/computation-of-the-minimum-corporate-income-tax-for-the-taxable-year-2023/)). Rule 8.6 instead applies each quarter's actual gross at that quarter's rate, which diverges whenever income is uneven. RMC 36-2024 (March 11, 2024) clarified the 2023 computation, but its text was not retrieved **[Needs verification]** ([PwC Tax Alert 17](https://www.pwc.com/ph/en/tax/tax-publications/tax-alerts/2024/tax-alert-17.html)).

**Quarterly MCIT.** RR 12-2007 requires MCIT on each 1702Q **[Confirmed–S, via a Lawyerly transcription]** ([RR 12-2007](https://dev.lawyerly.ph/laws/47521)):

- It is computed cumulatively and compared with cumulative normal tax.
- Prior quarters' MCIT, normal-tax payments and CWT may be credited.
- Prior-year excess MCIT may **not** be credited against quarterly MCIT.

**MCIT start and carry-forwards.**

- **Start year:** MCIT begins in the **fourth taxable year after the year of BIR registration** (RMC 62-2021 citing RR 9-98), not after SEC incorporation **[Confirmed–P]**.
- **Excess MCIT:** carries forward three years, against normal tax only (Sec. 27(E)(2)).
- **NOLCO:** carries forward three years, subject to a 75%-ownership continuity test (Sec. 34(D)(3)) **[Confirmed–P]**. **2020 and 2021 losses carry forward five years** under RR 25-2020 (Bayanihan 2) and must be shown separately **[Confirmed–S]** ([PwC Tax Alert 79](https://www.pwc.com/ph/en/tax/tax-publications/tax-alerts/2020/tax-alert-79.html)).
- **NOLCO under OSD:** the rule that NOLCO cannot be claimed in an OSD year while its clock keeps running is **[Needs verification]**.

**Corporate OSD.** OSD is up to 40% of **gross income**, meaning net sales less cost of sales or services, irrevocable for the year (Sec. 34(L), RA 9504) **[Confirmed–P]** ([RA 9504](https://lawphil.net/statutes/repacts/ra2008/ra_9504_2008.html)).

**Deadlines.** The 1702Q is due 60 days after each of the first three quarters, and the 1702-RT on the 15th day of the fourth month after year end (Sec. 77(B)) **[Confirmed–P]**. For 2026, the Q3 1702Q due date of November 29 falls on a Sunday and November 30 is Bonifacio Day, so it rolls to **December 1, 2026** ([MPM](https://mpm.ph/bir-form-1702q/)).

**CREATE MORE (RA 12066, November 2024; RR 7-2025 effective March 14, 2025) leaves the 25%/20% rates and MCIT untouched for non-RBE corporations** **[Confirmed–P/S]** ([RA 12066](https://lawphil.net/statutes/repacts/ra2024/ra_12066_2024.html); [KPMG on RR 7-2025](https://kpmg.com/ph/en/insights/2025/03/special-intax-march-2025-issue-3-volume-1.html)). It changes two things that could matter here:

- The Lawphil transcription prints the asset cap as "One hundred million pesos (P1,000,000,000)". Treat the numeral as a typo and keep ₱100M, which RR 7-2025 uses.
- The one new item that can reach a small corporation is **Sec. 34(C)(8)**: input VAT on local purchases attributable to VAT-exempt sales is deductible, under itemized deductions. Whether non-VAT taxpayers qualify is **[Needs verification]**.

## Q7: penalties, DST, VAT carry-over and the 8% switch need rework

**Taxpayer classification is assigned by the BIR, not recomputed by the taxpayer.** RR 8-2024 sets the tiers **[Confirmed–P]** ([BIR digest of RR 8-2024](https://bir-cdn.bir.gov.ph/BIR/pdf/RR%208-2024.pdf)):

| Tier   | Gross sales for the taxable year |
| ------ | -------------------------------- |
| Micro  | below ₱3M                        |
| Small  | ₱3M to below ₱20M                |
| Medium | ₱20M to below ₱1B                |
| Large  | ₱1B and up                       |

Gross sales here means business revenue net of VAT, excluding compensation and passive income. Existing registrants were seeded from tax year 2022 gross sales. Registrants from 2023 to April 2024 without that data start as micro, or small if VAT-registered. Later registrants start from what they declared at registration, "and shall remain as such unless reclassified". The BIR reclassifies periodically, and taxpayers can ask through ORUS: requests to move up are approved automatically, requests to move down need approval (RMO 37-2024) **[Confirmed–S]** ([Grant Thornton](https://www.grantthornton.com.ph/insights/articles-and-updates1/tax-notes/eopt-is-here-guidelines-for-classification-of-business-taxpayers/)). Whether a reclassification takes effect immediately or the next year is **[Needs verification]**.

**Reduced penalties under RR 6-2024 (micro and small taxpayers)** **[Confirmed–P]** ([BIR digest of RR 6-2024](https://bir-cdn.bir.gov.ph/BIR/pdf/RR%206-2024.pdf)):

- **10% surcharge** for late filing or payment, and for unpaid deficiencies; 50% still applies to willful neglect or fraud.
- **6% interest**, half the Sec. 249 rate.
- **₱500 per information-return failure**, capped at ₱12,500 a year.
- **No penalty on an amended return** if the original was filed and paid on time.
- **A 50% compromise reduction only for non-fraud violations of Secs. 113, 237 and 238**, the invoicing and registration provisions. It is not a general cut to late-filing compromises.
- It "shall apply **prospectively**". The regulation took effect April 27, 2024 **[Confirmed–S]** ([Grant Thornton](https://www.grantthornton.com.ph/alerts-and-publications/technical-alerts/tax-alert/2024/implementation-of-the-amendments-introduced-by-eopt-act-on-the-classification-of-taxpayers-and-administrative-penalties/)).

Rule 5.3 applies 10%/6% to every period by the taxpayer's current gross. It should apply them by the classification in force for each violation, and only to due dates on or after April 27, 2024 **[Interpretation]**. Interest on an amount already unpaid before that date (12% then 6%?) is **[Needs verification]**.

**Regular penalties.** The 25% surcharge survives EOPT (RA 11976 Sec. 41) **[Confirmed–P]**. Interest is double the BSP legal rate, i.e. **12% a year from January 1, 2018** (TRAIN Sec. 249; RR 21-2018) **[Confirmed–P/S]** ([RA 10963](https://lawphil.net/statutes/repacts/ra2017/ra_10963_2017.html); [Grant Thornton on RR 21-2018](https://www.grantthornton.com.ph/insights/articles-and-updates1/tax-notes/train-updates-deficiency-and-delinquency-interest/)). Deficiency and delinquency interest are never imposed together.

Penalty amounts not confirmed:

- the RMO 7-2015 compromise schedule, where the ₱1,000 first-offense figure is secondary;
- the regular Sec. 250 fine, believed to be ₱1,000 per failure capped at ₱25,000 a year;
- the day-count convention for interest.

All three are **[Needs verification]** ([Respicio](https://www.respicio.ph/commentaries/compromise-penalties-for-zero-tax-due-professionals-under-3m-income-philippines)). Not estimating compromise is acceptable, but the app should warn that late nil returns draw it.

**RR 4-2026 abatement for micro taxpayers.** Issued June 22, 2026, it offers a **one-time abatement** of delinquent accounts, assessments and open stop-filer cases as of December 31, 2025 **[Confirmed–S]** ([Grant Thornton on RR 4-2026](https://www.grantthornton.com.ph/technical-alerts/tax-alert/2026/one-time-abatement-of-taxes-and-or-penalties-for-micro-taxpayers/); [Manila Bulletin](https://mb.com.ph/2026/07/25/bir-clarifies-rules-to-help-small-businesses-clear-back-taxes)):

- **Ceiling:** basic tax and penalties of no more than ₱80,000 per taxable year.
- **Fee:** ₱5,000, paid on Form 0605.
- **Window:** to **December 31, 2026**, unless extended. RMC 84-2026 clarifies the rules.

The app's open-case and back-period penalty screens should point eligible micro users to this before the window closes.

**DST on loans.** TRAIN's ₱1.50 per ₱200 "or fractional part thereof" applies to loans made from January 1, 2018 to June 30, 2025. Terms under a year are prorated by **days over 365**, not months **[Confirmed–P]** ([RA 10963](https://lawphil.net/statutes/repacts/ra2017/ra_10963_2017.html)). For documents made **on or after July 1, 2025**, CMEPA (RA 12214) as implemented by RR 19-2025 (August 5, 2025) imposes **"75% of 1%" of the issue price** **[Confirmed–P]** ([BIR digest of RR 19-2025](https://bir-cdn.bir.gov.ph/BIR/pdf/RR%20No.%2019-2025%20Digest.pdf)). It keeps the same day-based proration, has no ₱200 rounding, and charges one DST on the loan and its securing note or mortgage, whichever yields more. Example: a one-year ₱1,000,100 loan owed ₱7,501.50 under TRAIN and owes ₱7,500.75 under CMEPA.

Form 2000 (January 2018) still labels DS106 "₱1.50 / ₱200". The code is unchanged, but the label lags the law **[Confirmed–P]** ([Form 2000](https://bir-cdn.bir.gov.ph/local/pdf/2000-DST%20Jan%202018%20final.pdf)). The return is due five days after the close of the month the document was made. Any party is liable, and the non-exempt party pays if the other is exempt. A bank lender on eDST files for the loan, so a borrower of a private loan is the case that needs Form 2000. Rule 7.3 matches this **[Interpretation]**.

**VAT threshold and carry-over.** The threshold is ₱3,000,000 under Sec. 109(BB), with no automatic inflation adjustment **[Confirmed–P]**. Sec. 110(B), as amended by RA 9361 and left unchanged by EOPT, carries excess input VAT "to the succeeding quarter or quarters", with **no year-end cutoff** **[Confirmed–P]** ([RA 9361](https://lawphil.net/statutes/repacts/ra2006/ra_9361_2006.html)). The CTA reads that text the same way ([Batas Natin](https://batasnatin.com/laws/tax-input-VAT-credit-Sec-110)). Rule 2.7's January reset overstates VAT payable and must go.

EOPT also made "purchase of services on which a value-added tax has accrued" creditable **[Confirmed–P]**.

**2550Q and 2550M.** The 2550Q is due 25 days after the quarter (TRAIN Sec. 114(A)), and quarterly filing has been mandatory since January 1, 2023 **[Confirmed–P]**. The 2550M is **optional, not abolished**. RMC 52-2023 lets VAT-registered persons keep filing monthly **[Confirmed–P]**, from the BIR's VAT tax-information page ([bir.gov.ph](https://www.bir.gov.ph)). That same BIR page lists the 2550Q as the January 2023 edition, while the app uses an April 2024 edition **[Needs verification]**.

**8% rules (all [Confirmed–P] from the RR 8-2018 digest).** The digest ([RR 8-2018](https://bir-cdn.bir.gov.ph/local/pdf/Digest%20RR%208-2018_copy.pdf)) sets these rules:

- **Who can elect it:** a filer who is not VAT-registered, is not subject to percentage taxes other than Sec. 116, and whose gross plus non-operating income does not exceed ₱3M. Exactly ₱3M qualifies.
- **When it is elected:** on the first-quarter (or initial-quarter) 1701Q or 2551Q, irrevocably for the year. Without an election, graduated rates apply.
- **The ₱250,000 reduction:** purely self-employed only.
- **OSD:** 40% of gross sales or receipts for individuals, elected on the same first-quarter return. No deductions are allowed at 8%.

**Crossing ₱3M mid-year triggers an automatic switch to graduated rates**, with credit for the 8% payments already made. The taxpayer must update registration within the month after the breach. VAT applies from the first day of the following month. Percentage tax is due on sales from January until then, "without imposition of penalty if timely paid". Audited FS become mandatory for that year. Rule 1.6 stops computing at this point; it must model the switch.

A VAT taxpayer who has not exceeded the threshold in the preceding three years may also opt back out of VAT and into 8%, by updating registration on or before the first quarter.

**Other deadlines and forms.**

- **Individual returns:** 1701Q is due May 15, August 15 and November 15, and the annual return April 15. Tax over ₱2,000 can be paid in two installments, the second by October 15, which the app should model **[Confirmed–P]**.
- **1701-MS (August 2024):** for micro and small individuals, including mixed-income earners, with a **single activity and single tax regime**. "If with two (2) or more activities OR multiple tax regimes or tax rates, please use BIR Form No. 1701" **[Confirmed–P]** ([1701-MS guidelines](https://bir-cdn.bir.gov.ph/BIR/pdf/1701-MS%20Guide%20August%202024%20ENCS_Final.pdf)). Medium and large individuals must e-file 1701 or 1701A.
- **Weekend and holiday roll-forward:** RMC 65-2016 moves weekend and holiday deadlines to the next business day for manual and electronic filing **[Confirmed–S]** ([Forvis Mazars](https://www.forvismazars.com/ph/en/insights/tax-alerts/bir-rmc-65-2016)). The Administrative Code section is cited variously as Sec. 28 or Sec. 31 **[Needs verification]** ([Grant Thornton](https://www.grantthornton.com.ph/insights/articles-and-updates1/lets-talk-tax/tick-tock-click-file-pay/)).
- **A TRAIN proviso exempting gross of ₱500,000 or less from the 3% tax** appears in the Lawphil text but was probably vetoed. Do not implement it **[Needs verification]**.

## Verdict table for rule pack 2026.11

Label key: P = primary text read; S = firm summary; I = interpretation; NV = needs verification.

| #         | App rule (short)                                                 | Verdict                                                                                                                                                                                                                                          | Basis and label                                                             |
| --------- | ---------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ | --------------------------------------------------------------------------- |
| 1.1       | Graduated table 2018–2022                                        | Correct (not re-read this pass)                                                                                                                                                                                                                  | TRAIN Sec. 24(A); figures match TRAIN, table text not re-read               |
| 1.2       | Graduated table 2023+                                            | **Correct**                                                                                                                                                                                                                                      | RR 8-2018 digest; RR 11-2018 (P)                                            |
| 1.3       | 8% in lieu of graduated and PT                                   | **Correct**                                                                                                                                                                                                                                      | RR 8-2018 digest (P)                                                        |
| 1.4       | ₱250k reduction, purely self-employed                            | **Correct**                                                                                                                                                                                                                                      | RR 8-2018 digest (P)                                                        |
| 1.5       | Mixed income: 8% with no ₱250k reduction                         | **Correct**                                                                                                                                                                                                                                      | RR 8-2018 digest (P)                                                        |
| 1.6       | Above ₱3M: app stops                                             | **Change**: switch automatically to graduated for the year, credit 8% payments, PT to the breach month, VAT from the 1st of the next month, flag AFS; "exceed" boundary (₱3M exactly qualifies); also exclude those under other percentage taxes | RR 8-2018 digest (P)                                                        |
| 1.7       | Election on Q1 1701Q, locked                                     | **Correct**; add the Q1 2551Q / initial-quarter return as election points and default to graduated                                                                                                                                               | RR 8-2018 digest (P)                                                        |
| 1.8       | OSD 40% of gross sales/receipts (individuals)                    | **Correct**                                                                                                                                                                                                                                      | RR 8-2018 digest (P)                                                        |
| 1.9       | Itemized only with invoice in own name                           | Correct in substance                                                                                                                                                                                                                             | I; RR 4-2024 Sec. 6 removed the withholding-for-deductibility condition (S) |
| 1.10      | Cumulative quarterly returns                                     | Correct (not re-verified for individuals)                                                                                                                                                                                                        | Sec. 74; NV                                                                 |
| 1.11      | Unpaid earlier quarter assumed paid                              | Design choice; should compute the penalty and not assume                                                                                                                                                                                         | I                                                                           |
| 1.12–1.13 | Stacking mixed income; overpayment                               | Not re-verified                                                                                                                                                                                                                                  | NV                                                                          |
| 1.14      | 1701-MS / 1701A / 1701 routing                                   | **Change**: 1701-MS only for BIR-classified micro/small with a single activity and regime; medium/large → 1701/1701A e-filed                                                                                                                     | 1701-MS guidelines (P); RMC 20-2026 (P/S)                                   |
| 1.15      | Income on invoice date for IT and PT since 2018                  | **Change**: IT follows the books method per year; PT/VAT on invoice date only for invoices from Apr 27, 2024; services before then on collection                                                                                                 | NIRC 43–45 (P); RA 11976 Sec. 24 (P); RR 3-2024 (S)                         |
| 2.1–2.3   | PT 3% / 1% (Jul 2020–Jun 2023) / 3%                              | **Correct**                                                                                                                                                                                                                                      | RA 11976 Sec. 24 (P); CREATE / RMC 69-2023 (S)                              |
| 2.4       | 8% filers skip 2551Q; COR flag                                   | **Correct**                                                                                                                                                                                                                                      | RR 8-2018 digest (P)                                                        |
| 2.5       | VAT 12% output less input                                        | Correct (rate not re-read)                                                                                                                                                                                                                       | NV for the rate text                                                        |
| 2.6       | Input VAT only on invoices in own name                           | **Correct**; add services on which VAT has accrued; ORs not valid for input VAT                                                                                                                                                                  | RA 11976 Sec. 19 (P); RR 7-2024 (S)                                         |
| 2.7       | Excess input VAT resets each January                             | **Change**: carry over across years until used                                                                                                                                                                                                   | Sec. 110(B), RA 9361 (P)                                                    |
| 2.8       | VAT on services by invoice date from Apr 27, 2024                | Correct; **add** the RR 3-2024 Sec. 7(a) transitory rule and the Sec. 110(D) uncollected-receivable credit                                                                                                                                       | RA 11976 (P); RR 3-2024 (S)                                                 |
| 2.9       | 2551Q / 2550Q due 25 days after quarter                          | 2550Q **correct**; 2551Q NV                                                                                                                                                                                                                      | TRAIN Sec. 114(A) (P); Sec. 128 not read                                    |
| 3.1       | CWT credited in the quarter of payment                           | **Change to**: the quarter named on the 2307; for cash-basis IT, match to the year the income is reported                                                                                                                                        | I; NV on RR 2-98 Sec. 2.58.3                                                |
| 3.2       | 2307 within 20 days after quarter                                | **Correct** (or with payment on request)                                                                                                                                                                                                         | S                                                                           |
| 3.3       | Hint: services 5% / 10% / goods 1%                               | **Change**: 10% also when the payee is VAT-registered or above ₱3M                                                                                                                                                                               | RR 14-2018 (S)                                                              |
| 3.4       | Sworn declaration by Jan 15                                      | **Correct** (or before first payment, with COR)                                                                                                                                                                                                  | RR 11-2018 (S)                                                              |
| 3.5       | Marketplace 0.5%, ₱500k, 2024 →                                  | **Change**: start Jul 15, 2024 (marketplaces) / Oct 13, 2024 (DFSPs); threshold on remittances (prior-year or YTD); declaration by Jan 20; ATC WI760/WC760; net of VAT and fees                                                                  | RR 16-2023, RMC 8-2024, RMO 4-2024 (S)                                      |
| 3.6       | SAWT .DAT layout                                                 | Needs verification                                                                                                                                                                                                                               | NV                                                                          |
| 4.1–4.2   | WI010 5% / WI011 10% by sworn declaration                        | **Change**: 5% only if the payee is ≤₱3M **and** non-VAT per COR; else 10%                                                                                                                                                                       | RR 11-2018, RR 14-2018 (S); code-to-rate mapping I                          |
| 4.3       | WC010 10% to companies                                           | Needs verification; possibly WC010 10% ≤₱720k, WC011 15% above                                                                                                                                                                                   | NV (conflicting)                                                            |
| 4.4–4.5   | Rent 5%; contractors 2%                                          | Needs verification                                                                                                                                                                                                                               | NV                                                                          |
| 4.6       | 0619-E 10th; 1601-EQ last day; 1604-E Mar 1                      | **Correct**; add the eFPS 0619-E 15th                                                                                                                                                                                                            | S                                                                           |
| 4.7       | No return listed for nil periods                                 | **Change**: list no-payment returns for registered tax types; suggest Form 1905 to drop unused types                                                                                                                                             | RMC 12-2015, 28-2017 (S); 1601-EQ/1604-E NV                                 |
| 4.8       | QAP / 1604-E .DAT names                                          | Needs verification                                                                                                                                                                                                                               | NV                                                                          |
| 4.9       | Annualize every month; Jan 25 refund                             | **Change**: Annex "E" table per period, cumulative average in its 3 cases, annualize only at year end / separation; Jan 25 refund is **correct**                                                                                                 | RR 11-2018 Sec. 2.79 (P)                                                    |
| 4.10      | Pay entered after contributions; ₱90k and de minimis not modeled | **Change**: model the ₱90k cap, de minimis by effective date (RR 4-2025, RR 29-2025 from Jan 6, 2026), the employee-share exclusion, and MWE exemptions                                                                                          | RR 11-2018 (P); RR 29-2025 (P, date S)                                      |
| 4.11      | 1601-C 10th incl. December; 1604-C and 2316 Jan 31               | **Change**: December 1601-C due **Jan 15**; add eFPS groups and the Feb 28 BIR copy of 2316 with Annex F; 1604-C and 2316 dates **correct**                                                                                                      | BIR Forms page (P); RR 11-2018 Sec. 2.83 (P)                                |
| 4.12      | All 2316 pay as taxable basic                                    | **Change**: ₱250k-and-below basic and MWE pay to items 29–33; benefits to 34–36                                                                                                                                                                  | Form 2316 Sep 2021 (P); I                                                   |
| 4.13      | 2550Q ATC VT010                                                  | Needs verification                                                                                                                                                                                                                               | NV                                                                          |
| 5.1–5.2   | 25% surcharge; 12% interest                                      | **Correct** (50% for fraud; no double interest)                                                                                                                                                                                                  | RA 11976 Sec. 41 (P); TRAIN Sec. 249 (P); RR 21-2018 (S)                    |
| 5.3       | 10%/6% for all periods by current gross                          | **Change**: only for due dates from Apr 27, 2024, by BIR-assigned micro/small status for the period; add the ₱500/₱12,500 information-return fine and the amended-return proviso                                                                 | RR 6-2024 (P); RR 8-2024 (P)                                                |
| 5.4       | Compromise not estimated                                         | Acceptable; warn on late nil returns; the 50% reduction applies only to Secs. 113/237/238                                                                                                                                                        | RR 6-2024 (P); RMO 7-2015 NV                                                |
| 6.1       | 1701Q May/Aug/Nov 15; annual Apr 15                              | **Correct**; add installment payment (2nd by Oct 15) and RMC extensions as data                                                                                                                                                                  | RR 8-2018 digest (P)                                                        |
| 6.2       | Weekend/holiday → next working day                               | **Correct**                                                                                                                                                                                                                                      | RMC 65-2016 (S); Admin Code section NV                                      |
| 6.3       | Holidays for 2026–2027 only                                      | **Change**: add proclamations for every year computed (2018 onward) plus ad hoc BIR extensions                                                                                                                                                   | I                                                                           |
| 6.4       | Form 2000 within 5 days after month end                          | **Correct**                                                                                                                                                                                                                                      | BIR Forms page (P)                                                          |
| 7.1       | DST ₱1.50/₱200, months proration                                 | **Change**: proration by days/365; from Jul 1, 2025, 0.75% of issue price with no ₱200 rounding; one DST on loan plus security                                                                                                                   | TRAIN Sec. 179 (P); RR 19-2025 digest (P)                                   |
| 7.2       | ATC DS106                                                        | **Correct** (form label outdated)                                                                                                                                                                                                                | Form 2000 (P)                                                               |
| 7.3       | Bank lender files; taxpayer files for a private loan             | Correct in substance                                                                                                                                                                                                                             | Form 2000 description (P); I                                                |
| 8.1–8.2   | 25% / 20% tests                                                  | **Correct**; apply the asset test net of depreciation and bad-debt allowance, excluding office/plant land (prorated)                                                                                                                             | RA 11534 (P); RMC 62-2021 digest (P)                                        |
| 8.3       | 1702Q 20% test on YTD income                                     | Defensible; **needs CPA confirmation**; label provisional and settle on the 1702-RT                                                                                                                                                              | Sec. 75–76 (P); no BIR guidance (S)                                         |
| 8.4       | MCIT from 4th year; higher of the two                            | **Correct**; count from the BIR registration year; compute MCIT on every 1702Q cumulatively                                                                                                                                                      | RMC 62-2021 (P); RR 12-2007 (S)                                             |
| 8.5       | MCIT 1% → 2% Jul 1, 2023                                         | **Correct**                                                                                                                                                                                                                                      | RA 11534 (P)                                                                |
| 8.6       | 2023 MCIT per quarter's actual gross                             | **Change**: annual gross ÷ 12 × months at each rate (1.5% for CY2023); confirm against RMC 36-2024                                                                                                                                               | RMC 69-2023, GT (S); RMC 36-2024 NV                                         |
| 8.7       | Corporate OSD 40% of gross income                                | **Correct** (gross income = net sales less cost of sales/services; FS still required)                                                                                                                                                            | RA 9504 (P); RR 16-2008 (S)                                                 |
| 8.8       | 1702Q 60 days; 1702-RT 15th of the 4th month                     | **Correct**                                                                                                                                                                                                                                      | Sec. 77(B) (P)                                                              |
| 8.9       | IC010 / IC055                                                    | Needs verification                                                                                                                                                                                                                               | NV                                                                          |
| 8.10      | NOLCO and excess MCIT not computed                               | **Change**: track NOLCO (3 years; 5 years for 2020–21 losses) and excess MCIT (3 years, against normal tax only)                                                                                                                                 | NIRC 27(E), 34(D) (P); RR 25-2020 (S)                                       |
| 9.1       | Books kept 5 years                                               | **Correct**: count from the day after the deadline, or the late filing date; longer while a case is pending; keep 2316 copies 10 years                                                                                                           | RA 11976 Sec. 33 (P); RR 11-2018 (P)                                        |
| 9.2       | Computerized books permit above ₱3M                              | **Change**: no threshold; CAS mandatory only for large taxpayers; any CBA needs an Acknowledgement Certificate                                                                                                                                   | RR 9-2009 (P); RMO 9-2021 (S)                                               |
| 9.3       | Loose-leaf bound within 15 days                                  | Correct (NV on primary text); registered via ORUS; computerized 30 days; watch Jan extensions                                                                                                                                                    | RMC 4-2026, 6-2025 (S); RMC 29-2019 NV                                      |
| 9.4       | Books on a cash basis                                            | Correct for IT if applied consistently; keep an invoice-date sales register for VAT/PT                                                                                                                                                           | NIRC 43 (P); I                                                              |
| 9.5–9.7   | Chart of accounts, bookings                                      | Not reviewed (accounting design)                                                                                                                                                                                                                 | —                                                                           |
| 9.8       | FS: 8% none; graduated silent                                    | **Change**: graduated + OSD none; graduated + itemized → FS/AIF, audited with CPA certificate if gross > ₱3M; via eAFS within 15 days                                                                                                            | TRAIN Sec. 232(A) (P); RMC 17-2019 (S)                                      |
| 10.1      | Form editions                                                    | Partly confirmed: 2316 Sep 2021, 1601-C, 1604-C and 2000 Jan 2018, 1701-MS Aug 2024 (P); 2550Q edition conflicts (Jan 2023 per BIR page); others NV                                                                                              | BIR Forms page (P)                                                          |
| 10.2–10.4 | Rounding, income tax ATCs, letter kinds                          | Not covered by the research                                                                                                                                                                                                                      | NV                                                                          |
| —         | Nil months and quarters not listed                               | Same as 4.7: **Change**                                                                                                                                                                                                                          | —                                                                           |
| —         | Filled BIR PDFs offered for printing and filing                  | **Change**: default to eBIRForms/TSP/eFPS; paper only for 1701-MS (CY2025 confirmed), outages, forms not on any platform, CIR leave                                                                                                              | RMC 20-2026 (P); RMC 87-2024 (S)                                            |

## Prioritized changes for the app

### Confirmed corrections (encode now)

| Priority | Change to the rule pack or behaviour                                                                                                                                                                                                                                                                    | Effective dates and basis                                                           |
| -------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------- |
| 1        | Replace 12× annualization with the Annex "E" per-period table (bracket on regular pay, supplementary added to the excess). Add the cumulative-average triggers. Annualize only at December or separation. Keep the Jan 25 refund and its offset against 1601-C                                          | Annex D 2018–2022, Annex E from Jan 1, 2023; RR 11-2018 Sec. 2.79 (P)               |
| 2        | December 1601-C due **January 15**; add the Feb 28 scanned 2316 BIR-copy obligation with Annex "F"; eFPS group offsets as an option                                                                                                                                                                     | BIR Forms page; RR 11-2018 Sec. 2.83; RR 16-2021 (P)                                |
| 3        | Carry excess input VAT from Q4 into the next year; remove the January reset                                                                                                                                                                                                                             | Sec. 110(B) as amended by RA 9361 (P)                                               |
| 4        | DST: prorate terms under a year by days/365. For documents dated on or after **Jul 1, 2025**, use 0.75% of the issue price with no ₱200 rounding, and one DST on the loan and its security                                                                                                              | TRAIN Sec. 179 (P); RA 12214 / RR 19-2025 (P)                                       |
| 5        | Split recognition by tax. PT and VAT on invoice date for invoices from **Apr 27, 2024**. Pre-EOPT billed services: VAT on collection. Income tax by the user's books method, stored per year. Add an ITR-vs-2551Q/2550Q receivables reconciliation and the Sec. 110(D) credit for post-EOPT receivables | RA 11976 Secs. 17, 19, 24 (P); RR 3-2024 Sec. 5, 7 (S)                              |
| 6        | EOPT penalties: 10% / 6% / ₱500 (cap ₱12,500) only for due dates from **Apr 27, 2024**, by the BIR-assigned micro/small status stored per year (with a suggestion from last year's gross). No surcharge on a timely-filed original's amendment                                                          | RR 6-2024; RR 8-2024 (P); RMO 37-2024 (S)                                           |
| 7        | Prompt eligible micro users about the RR 4-2026 abatement (≤₱80,000 per year, ₱5,000 fee, Form 0605), **open until Dec 31, 2026**                                                                                                                                                                       | RR 4-2026, RMC 84-2026 (S)                                                          |
| 8        | Model the 8%→graduated switch on crossing ₱3M: credit for 8% payments, 2551Q to the breach month, VAT from the next month, registration update reminder, AFS flag                                                                                                                                       | RR 8-2018 digest (P)                                                                |
| 9        | List no-payment returns for every registered tax type (0619-E, 1701Q, 2551Q at minimum); suggest Form 1905 to drop unused types                                                                                                                                                                         | RMC 12-2015, RMC 28-2017 (S)                                                        |
| 10       | Payroll exclusions: ₱90,000 cap; de minimis ceilings by date (clothing ₱6,000 → ₱7,000 under RR 4-2025 → ₱8,000; all RR 29-2025 values from **Jan 6, 2026**); employee-share contributions; MWE components; 2316 items 29–36                                                                            | RR 11-2018 (P); RR 4-2025, RR 29-2025 (P/S)                                         |
| 11       | Professional-fee rate: 5% only when the payee is ≤₱3M **and** non-VAT per COR; apply the same in the client-withholding hint                                                                                                                                                                            | RR 14-2018 (S)                                                                      |
| 12       | FS guidance: graduated + itemized → FS/AIF (audited with CPA certificate above ₱3M); OSD and 8% → none; corporations always; eAFS within 15 days of the deadline                                                                                                                                        | Sec. 232(A) (P); RMC 17-2019 (S); RMC 20-2026 (S)                                   |
| 13       | Filing channel: present e-filing (eBIRForms/TSP/eFPS) as the default; label filled PDFs as worksheets except 1701-MS and the documented exceptions; "pay anywhere" guidance; keep TRRC/FRN                                                                                                              | RMC 20-2026 (P); RMC 87-2024 (S)                                                    |
| 14       | Books: remove the ₱3M computerized-books rule; note ORUS registration and the Acknowledgement Certificate for any CBA; do not present app data as registered books                                                                                                                                      | RR 9-2009 (P); RMO 9-2021, RMC 65-2025, RMC 4-2026 (S)                              |
| 15       | Corporations: compute MCIT on each 1702Q cumulatively; set the MCIT start from the BIR registration year; add NOLCO (3/5 years) and excess-MCIT (3 years, normal tax only) ledgers; 2023 MCIT by monthly average (1.5% CY)                                                                              | RR 12-2007 (S); RMC 62-2021 (P); NIRC 27(E), 34(D) (P); RR 25-2020, RMC 69-2023 (S) |
| 16       | Route 1701-MS only for micro/small with a single activity and regime                                                                                                                                                                                                                                    | 1701-MS guidelines Aug 2024 (P)                                                     |
| 17       | E-marketplace rule: start dates (Jul 15 / Oct 13, 2024), remittance-based ₱500k test, Jan 20 declaration, WI760/WC760                                                                                                                                                                                   | RR 16-2023, RMC 8-2024, RMO 4-2024 (S)                                              |
| 18       | Calendar: holiday proclamations for 2018 onward; deadlines and extensions as data; annual ITR installment (2nd by Oct 15); 0619-E eFPS 15th                                                                                                                                                             | RR 8-2018 (P); RMC 65-2016 (S)                                                      |
| 19       | Notices: 2550M is optional (quarterly 2550Q is mandatory); EIS by **Dec 31, 2026** only for e-commerce, CAS/CBA and large taxpayers, never micro                                                                                                                                                        | BIR VAT page / RMC 52-2023 (P); PNA (S)                                             |

### Items still needing a CPA's confirmation

| Item                                        | What to confirm before encoding                                                                    | Current best reading                                  |
| ------------------------------------------- | -------------------------------------------------------------------------------------------------- | ----------------------------------------------------- |
| Quarterly 20% corporate test (row 8.3)      | YTD vs annualized vs annual-only; which total-assets figure on the 1702Q                           | YTD cumulative NTI, provisional; year-end AFS decides |
| WC010/WC011, WI100/WC100, WI120/WC120       | Current ATC rates and the ₱720k juridical split                                                    | 10%/15%; 5%; 2% (unverified)                          |
| Nil 1601-EQ, 1604-E, 1702Q                  | Whether a no-payment return is mandatory                                                           | File them (open-case risk)                            |
| PT on pre-Apr 27, 2024 service receivables  | Whether a percentage-tax transitory rule mirrors RR 3-2024 Sec. 7(a)                               | Tax on collection                                     |
| 8% base for a cash-basis filer              | Invoiced or collected gross (RR 8-2018, RMC 50-2018)                                               | Follows the books method                              |
| CWT year matching                           | Whether a 2307 issued before collection is claimed in the income year (RR 2-98 Sec. 2.58.3)        | Match to the year the income is reported              |
| Interest straddling Apr 27, 2024; day count | Split 12%/6%? 365-day or actual-day year                                                           | Split by period (unconfirmed)                         |
| RMC 36-2024                                 | Confirms the 1.5% monthly-average method and any quarterly treatment for 2023                      | Monthly average                                       |
| NOLCO with OSD, NOLCO with MCIT             | Whether OSD years bar NOLCO while the clock runs                                                   | Barred; clock runs                                    |
| Compromise schedule and Sec. 250 fine       | RMO 7-2015 amounts; ₱1,000 / ₱25,000 cap                                                           | Do not estimate; warn                                 |
| Loose-leaf / computerized binding           | Primary RMC 29-2019 text; yearly extensions                                                        | 15 / 30 days, check each January                      |
| 2551Q deadline                              | Sec. 128 text                                                                                      | 25 days after the quarter                             |
| Form editions                               | 2550Q (Jan 2023 vs Apr 2024); 1701Q/1701A/1701/2551Q/0619-E/1601-EQ/1702Q/1702-RT current versions | Check the BIR Forms page                              |
| Annex "E" daily top bracket                 | ₱6,034.30 vs the misprint                                                                          | ₱6,034.30                                             |
| Voluntary contributions                     | Excluded from taxable pay?                                                                         | Not excluded                                          |
| 1701-MS for CY2026                          | Manual route continues; e-filing via v7.9.6.0 (RMC 36/37-2026)                                     | Both routes                                           |
| Third-party-filled BIR PDFs                 | RDO/AAB acceptance in 2026                                                                         | Accepted only where manual filing is allowed          |
| TRAIN ₱500k PT exemption proviso            | Vetoed or law?                                                                                     | Do not implement                                      |
| Sec. 34(C)(8) input-VAT deduction           | Availability to non-VAT corporations; start date                                                   | Itemized only                                         |
| Classification timing                       | Whether reclassification applies immediately or the next year                                      | Store per year                                        |
| Retention reckoning                         | Whether an RMC deadline extension moves the 5-year start; 2316 10-year rule vs EOPT                | Use the later date; keep 2316 copies 10 years         |
| ATCs and layouts                            | VT010, IC010/IC055, II012–II017, PT010; SAWT/QAP/alphalist .DAT formats; 10.2 rounding             | Unverified                                            |

## Conclusion

The pattern behind the errors matters more than any single row. The rule pack was built as one timeless set of rates, but Philippine small-business tax since 2018 has been a series of **dated regime switches**:

- TRAIN on January 1, 2018 and again on January 1, 2023;
- CREATE on July 1, 2020 and its reversion on July 1, 2023;
- EOPT on April 27, 2024;
- CMEPA on July 1, 2025;
- RR 29-2025 on January 6, 2026.

Each overlays transitory rules for transactions that straddle the switch. Most of the corrections above are effective-date bugs: invoice dating pushed back to 2018, EOPT penalties applied to pre-EOPT periods, TRAIN DST applied after July 2025, a holiday calendar that starts in 2026. The durable fix is architectural. Every rule needs an effective-from date, every sale needs both an invoice date and a collection date, and classification, method elections and registered tax types need to be stored per tax year as the BIR recorded them, not derived from today's figures.

The second takeaway is about what the app is in the BIR's eyes. Its output is a worksheet, not a filing and not registered books, until the user puts it through eBIRForms, a TSP or ORUS. Framing it that way protects users from treating a printed PDF or an unregistered ledger as compliance. The CPA's time is best spent on the short list where the law is silent: the quarterly 20% test, the juridical withholding rates, nil 1601-EQ/1604-E/1702Q, and pre-EOPT percentage tax. The confirmed corrections can ship without waiting, starting with payroll withholding and VAT carry-over. Users who qualify for the abatement need to be told before December 31, 2026.
