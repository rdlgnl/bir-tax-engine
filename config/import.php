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

    // Online marketplace payout reports: a line per order. Sales are what the
    // buyer paid; the platform's fees are an expense and its withholding a
    // credit, confirmed by its 2307.
    // REVIEW: the rate (1% of half the gross remittance) and the ₱500,000
    // sworn-declaration threshold, against the current revenue regulations.
    'marketplace' => [
        'platforms' => ['Shopee', 'Lazada', 'TikTok Shop', 'Zalora'],
        'withholding_rate' => 0.005,
        'exempt_up_to' => 500_000,
        'columns' => [
            'date' => ['order date', 'order creation date', 'order created', 'created at', 'order time', 'date', 'transaction date', 'payout date', 'release date'],
            'order' => ['order id', 'order no', 'order number', 'order sn', 'order', 'reference', 'reference number'],
            'sales' => ['gross sales', 'gross amount', 'order amount', 'order total', 'total amount', 'buyer paid', 'amount paid by buyer', 'product price', 'subtotal', 'sales', 'revenue'],
            'fees' => ['total fees', 'fees', 'fee', 'commission', 'commission fee', 'platform fee', 'transaction fee', 'service fee', 'payment fee'],
            'withheld' => ['withholding tax', 'tax withheld', 'withheld', 'wht', 'withholding', 'cwt', 'creditable withholding tax'],
            'payout' => ['payout id', 'payout', 'settlement id', 'release id', 'statement id'],
        ],
    ],

    // Words that only say what kind of company a client is.
    'company_words' => ['INC', 'INCORPORATED', 'CORP', 'CORPORATION', 'CO', 'COMPANY', 'LTD', 'LIMITED', 'LLC', 'OPC', 'THE'],

];
