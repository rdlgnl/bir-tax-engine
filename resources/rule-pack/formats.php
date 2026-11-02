<?php

/*
|--------------------------------------------------------------------------
| Rule pack · File formats
|--------------------------------------------------------------------------
|
| The layouts of the data files the BIR takes: SAWT and SLSP.
|
| Part of config/tax.php, reviewed and versioned on its own: its version
| and sign-off are under `parts.formats` there. Amounts are in pesos.
|
*/

return [

    // Summary Alphalist of Withholding Taxes, the file that goes with an
    // income tax return claiming 2307 credits, in the layout of the BIR's
    // alphalist data entry tool: one header, one line per payor, one control.
    // REVIEW: check the layout and file name against the current alphalist
    // data entry module before relying on them.
    'sawt' => [
        'branch_code' => '0000',
        // {tin} nine digits, {branch} four, {period} MMYYYY of the quarter's last month.
        'file_name' => '{tin}{branch}{period}{form}.DAT',
    ],

    // Summary List of Sales and Purchases, filed with each 2550Q, as the BIR's
    // RELIEF data entry module writes it: a header line, then one line per
    // customer or supplier. Purchases are counted as services, except
    // equipment, as goods.
    // REVIEW: check the layout and file names against the current RELIEF
    // module before relying on them.
    'slsp' => [
        // {tin} nine digits, {kind} S or P, {period} MMYYYY of the quarter's last month.
        'file_name' => '{tin}{kind}{period}.DAT',
        'fiscal_month_end' => 12,
    ],

];
