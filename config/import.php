<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Statement import
    |--------------------------------------------------------------------------
    |
    | Reading bank, e-wallet and platform payout statements (CSV) on this
    | computer. Money in is matched to known clients; everything else is
    | either set aside or asked about.
    |
    */

    // Payout platforms: a word in the statement line, and the client name to record.
    'platforms' => [
        'UPWORK' => 'Upwork',
        'FIVERR' => 'Fiverr',
        'ONLINEJOBS' => 'OnlineJobs.ph',
        'TOPTAL' => 'Toptal',
        'PAYONEER' => 'Payoneer',
        'DEEL' => 'Deel',
        'SHOPEE' => 'Shopee',
        'LAZADA' => 'Lazada',
        'TIKTOK SHOP' => 'TikTok Shop',
        'PAYPAL' => 'PayPal',
    ],

    // Money in that isn't income: bank interest is already taxed at source,
    // reversals and refunds give back the taxpayer's own money.
    'not_income' => ['INTEREST', 'INT CREDIT', 'REVERSAL', 'REFUND', 'CASHBACK', 'REBATE', 'RETURNED', 'OWN ACCOUNT'],

    // A statement line is the same as a recorded payment within this many days.
    'duplicate_window_days' => 5,

    'max_rows' => 5000,

    // Words that only say what kind of company a client is.
    'company_words' => ['INC', 'INCORPORATED', 'CORP', 'CORPORATION', 'CO', 'COMPANY', 'LTD', 'LIMITED', 'LLC', 'OPC', 'THE'],

];
