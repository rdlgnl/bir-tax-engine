<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Letters from the BIR
    |--------------------------------------------------------------------------
    |
    | The kinds of letter a taxpayer can say they received, and whether it
    | names a return the app can check against their records. Letters that
    | open an audit or assess tax need a CPA or tax lawyer.
    | REVIEW: the kinds and their names against current BIR practice.
    |
    */

    'kinds' => [
        'unfiled' => ['label' => 'Reminder of a return not filed', 'checks' => 'filing', 'help' => false],
        'unpaid' => ['label' => 'Notice of tax not paid', 'checks' => 'filing', 'help' => false],
        'mismatch' => ['label' => 'Letter about a difference in your income', 'checks' => 'income', 'help' => false],
        'authority' => ['label' => 'Letter of Authority (an audit)', 'checks' => null, 'help' => true],
        'assessment' => ['label' => 'Assessment notice (PAN or FAN)', 'checks' => null, 'help' => true],
        'other' => ['label' => 'Something else', 'checks' => null, 'help' => false],
    ],

    // The returns a letter can name.
    'forms' => ['1701Q', '1701', '1701A', '1701-MS', '2551Q', '2550Q', '0619-E', '1601-EQ', '1604-E', '1601-C', '1604-C', '2000'],

];
