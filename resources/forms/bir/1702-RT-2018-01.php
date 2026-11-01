<?php

/*
| Field map: BIR Form 1702-RT, January 2018 (ENCS).
|
| Where each box sits on 1702-RT-2018-01.pdf, in points from the page's
| top-left corner, measured from the boxes drawn in the PDF itself. Page 1
| holds the totals (Part II); page 2 the computation (Part IV). Pages 3
| and 4 are the accountant's schedules; only their headers and the total
| of Schedule I are mapped. Check with `php artisan forms:calibrate 1702-RT`.
*/

$comb = fn (int $page, float $x, float $to, int $cells, float $y) => ['page' => $page, 'type' => 'comb', 'x' => $x, 'to' => $to, 'cells' => $cells, 'y' => $y];
$check = fn (int $page, float $x, float $y) => ['page' => $page, 'type' => 'check', 'x' => $x, 'y' => $y];
$text = fn (int $page, float $x, float $to, float $y) => ['page' => $page, 'type' => 'text', 'x' => $x, 'to' => $to, 'y' => $y];
// Every amount sits in the same twelve boxes at the right of its row.
$amount = fn (int $page, float $y) => ['page' => $page, 'type' => 'amount', 'x' => 411.7, 'to' => 593.5, 'cells' => 12, 'y' => $y];

return [
    // Page 1 header.
    '1.calendar' => $check(1, 49.8, 128.9),
    '2.month' => $comb(1, 33.8, 60.6, 2, 162.5),
    '2.year' => $comb(1, 82.6, 109.2, 2, 162.5),
    '3.no' => $check(1, 203.3, 144.7),
    '4.no' => $check(1, 299.4, 144.7),
    'atc.mcit' => $check(1, 563.5, 137.9),
    'atc.code' => $text(1, 357.0, 390.0, 157.0),
    'atc.description' => $text(1, 408.0, 548.0, 157.0),
    'atc.other' => $check(1, 563.5, 150.7),

    // Part I: background information.
    'tin.1' => $comb(1, 214.8, 260.3, 3, 197.5),
    'tin.2' => $comb(1, 275.6, 321.1, 3, 197.5),
    'tin.3' => $comb(1, 336.2, 381.7, 3, 197.5),
    '7' => $comb(1, 533.3, 578.7, 3, 197.5),
    '8.1' => $comb(1, 17.3, 593.5, 38, 227.5),
    '8.2' => $comb(1, 17.3, 593.5, 38, 246.5),
    '8.3' => $comb(1, 17.3, 593.5, 38, 266.5),
    '9.1' => $comb(1, 17.3, 593.5, 38, 296.5),
    '9.2' => $comb(1, 17.3, 593.5, 38, 316.5),
    '9.3' => $comb(1, 17.3, 472.7, 30, 335.5),
    '9A' => $comb(1, 533.3, 593.5, 4, 335.5),
    '11' => $comb(1, 427.1, 593.5, 11, 354.5),
    '12' => $comb(1, 108.7, 593.5, 32, 374.5),
    '13.itemized' => $check(1, 135.5, 381.4),
    '13.osd' => $check(1, 353.0, 381.4),

    // Part II: total tax payable.
    '14' => $amount(1, 428.5),
    '15' => $amount(1, 447.5),
    '16' => $amount(1, 467.5),
    '17' => $amount(1, 497.5),
    '18' => $amount(1, 516.5),
    '19' => $amount(1, 536.5),
    '20' => $amount(1, 555.5),
    '21' => $amount(1, 574.5),

    // Page headers.
    'p2.tin' => $comb(2, 17.3, 153.8, 9, 168.5),
    'p2.name' => $text(2, 232.0, 593.5, 168.5),
    'p3.tin' => $comb(3, 17.3, 153.8, 9, 119.5),
    'p3.name' => $text(3, 232.0, 593.5, 119.5),
    'p4.tin' => $comb(4, 17.3, 153.8, 9, 119.5),
    'p4.name' => $text(4, 232.0, 592.7, 119.5),

    // Part IV: computation of tax.
    '27' => $amount(2, 203.5),
    '28' => $amount(2, 222.5),
    '29' => $amount(2, 242.5),
    '30' => $amount(2, 261.5),
    '31' => $amount(2, 280.5),
    '32' => $amount(2, 300.5),
    '33' => $amount(2, 319.5),
    '34' => $amount(2, 350.5),
    '35' => $amount(2, 369.5),
    '36' => $amount(2, 388.5),
    '37' => $amount(2, 408.5),
    '38' => $amount(2, 439.5),
    '39' => $amount(2, 458.5),
    '40' => $comb(2, 548.3, 578.4, 2, 477.5),
    '41' => $amount(2, 496.5),
    '42' => $amount(2, 516.5),
    '43' => $amount(2, 535.5),
    '44' => $amount(2, 565.5),
    '45' => $amount(2, 585.5),
    '46' => $amount(2, 604.5),
    '47' => $amount(2, 623.5),
    '48' => $amount(2, 643.5),
    '49' => $amount(2, 662.5),
    '50' => $amount(2, 682.5),
    '51' => $amount(2, 701.5),
    '52' => $amount(2, 720.5),
    '55' => $amount(2, 790.5),
    '56' => $amount(2, 809.5),

    // Part VI, Schedule I: the total of the itemized deductions.
    's1.18' => $amount(3, 662.5),
];
