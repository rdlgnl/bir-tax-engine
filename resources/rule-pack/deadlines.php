<?php

/*
|--------------------------------------------------------------------------
| Rule pack · Deadlines
|--------------------------------------------------------------------------
|
| When each return, certificate and declaration is due.
|
| Part of config/tax.php, reviewed and versioned on its own: its version
| and sign-off are under `parts.deadlines` there. Amounts are in pesos.
|
*/

return [

    // A 2307 is due from the client within this many days after quarter end.
    'certificate_grace_days' => 20,

    'deadlines' => [
        // Quarterly income tax (1701Q): month and day, same year.
        'income_tax_quarterly' => [1 => [5, 15], 2 => [8, 15], 3 => [11, 15]],
        // Annual income tax (1701A / 1701): month and day of the next year.
        'income_tax_annual' => [4, 15],
        // Annual income tax over this can be paid in two equal halves, the
        // second by this month and day (NIRC Sec. 56(A)(2)).
        'installment_above' => 2_000,
        'second_installment' => [10, 15],
        // Percentage tax (2551Q): days after quarter end.
        'percentage_tax_days_after_quarter' => 25,
        // Quarterly VAT (2550Q) and its SLSP: days after the quarter ends.
        'vat_days_after_quarter' => 25,
        // Sworn declaration to clients: month and day of each year.
        'sworn_declaration' => [1, 15],
    ],

];
