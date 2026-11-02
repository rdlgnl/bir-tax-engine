<?php

/*
|--------------------------------------------------------------------------
| Rule pack · Form editions
|--------------------------------------------------------------------------
|
| The BIR form editions the app fills, and their PDF templates.
|
| Part of config/tax.php, reviewed and versioned on its own: its version
| and sign-off are under `parts.forms` there. Amounts are in pesos.
|
*/

return [

    // The BIR's own forms the downloads are filled on. `template` names the
    // PDF and its field map in resources/forms/bir; a new BIR edition is a new
    // template (scripts/bir-forms/fetch.sh) and a new map. The income tax
    // forms say "DO NOT enter Centavos", so their amounts are whole pesos.
    'forms' => [
        '1701Q' => ['edition' => 'January 2018 (ENCS)', 'template' => '1701Q-2018-01', 'whole_pesos' => true],
        '1701-MS' => ['edition' => 'August 2024 (ENCS)', 'template' => '1701-MS-2024-08', 'whole_pesos' => true],
        '1701A' => ['edition' => 'January 2018 (ENCS)', 'template' => '1701A-2018-01', 'whole_pesos' => true],
        '1701' => ['edition' => 'January 2018 (ENCS)', 'template' => '1701-2018-01', 'whole_pesos' => true],
        '2551Q' => ['edition' => 'January 2018 (ENCS)', 'template' => '2551Q-2018-01', 'whole_pesos' => false],
        '2550Q' => ['edition' => 'April 2024 (ENCS)', 'template' => '2550Q-2024-04', 'whole_pesos' => false],
        // Withholding agent: monthly and quarterly remittance, the payee's certificate.
        '0619-E' => ['edition' => 'January 2018', 'template' => '0619-E-2018-01', 'whole_pesos' => false],
        '1601-EQ' => ['edition' => 'January 2019 (ENCS)', 'template' => '1601-EQ-2019-01', 'whole_pesos' => false],
        '1604-E' => ['edition' => 'January 2018 (ENCS)', 'template' => '1604-E-2018-01', 'whole_pesos' => false],
        '2000' => ['edition' => 'January 2018', 'template' => '2000-2018-01', 'whole_pesos' => false],
        '1601-C' => ['edition' => 'January 2018 (ENCS)', 'template' => '1601-C-2018-01', 'whole_pesos' => false],
        '2316' => ['edition' => 'September 2021 (ENCS)', 'template' => '2316-2021-09', 'whole_pesos' => false],
        '1604-C' => ['edition' => 'January 2018', 'template' => '1604-C-2018-01', 'whole_pesos' => false],
        '2307' => ['edition' => 'January 2018 (ENCS)', 'template' => '2307-2018-01', 'whole_pesos' => false],
        // A corporation the taxpayer runs.
        '1702Q' => ['edition' => 'January 2018 (ENCS)', 'template' => '1702Q-2018-01', 'whole_pesos' => true],
        '1702-RT' => ['edition' => 'January 2018 (ENCS)', 'template' => '1702-RT-2018-01', 'whole_pesos' => true],
        // Registration forms: updating the COR, and the loose-leaf books permit.
        '1905' => ['edition' => 'October 2025 (ENCS)', 'template' => '1905-2025-10', 'whole_pesos' => true],
        '1900' => ['edition' => 'October 2025 (ENCS)', 'template' => '1900-2025-10', 'whole_pesos' => true],
    ],

];
