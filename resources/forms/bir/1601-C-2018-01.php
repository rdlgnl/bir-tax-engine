<?php

/*
| Field map: BIR Form 1601-C, January 2018 (ENCS).
|
| Where each box sits on 1601-C-2018-01.pdf, in points from the page's
| top-left corner, measured from the boxes drawn in the PDF itself. Part II
| runs from the month's compensation (item 14) to the amount still due
| (item 36), one row each. Check with `php artisan forms:calibrate 1601-C`.
*/

$comb = fn (float $x, float $to, int $cells, float $y) => ['page' => 1, 'type' => 'comb', 'x' => $x, 'to' => $to, 'cells' => $cells, 'y' => $y];
$check = fn (float $x, float $y) => ['page' => 1, 'type' => 'check', 'x' => $x, 'y' => $y];
$amount = fn (float $y) => ['page' => 1, 'type' => 'amount', 'x' => 390.6, 'to' => 548.9, 'cells' => 11, 'cents' => [564.0, 593.8], 'y' => $y];

return [
    // 1: the month, MMYYYY.
    '1' => $comb(46.2, 132.0, 6, 124.5),
    '2.yes' => $check(183.6, 112.2),
    '2.no' => $check(231.5, 112.3),
    '3.yes' => $check(304.6, 112.4),
    '3.no' => $check(345.2, 111.8),

    // Part I: background information.
    'tin.1' => $comb(232.8, 275.8, 3, 155.0),
    'tin.2' => $comb(290.2, 333.2, 3, 155.0),
    'tin.3' => $comb(347.6, 390.6, 3, 155.0),
    'tin.branch' => $comb(404.9, 476.6, 5, 155.0),
    '7' => $comb(548.9, 593.8, 3, 155.0),
    '8' => $comb(18.4, 593.8, 40, 181.5),
    '9.1' => $comb(18.4, 593.8, 40, 207.5),
    '9.2' => $comb(18.4, 462.2, 31, 223.5),
    '9A' => $comb(533.9, 593.8, 4, 223.5),
    '10' => $comb(103.9, 275.8, 12, 241.5),
    '11.private' => $check(447.5, 230.4),
    '11.government' => $check(519.6, 230.4),
    '12' => $comb(103.9, 593.8, 34, 257.5),
    '13.yes' => $check(175.2, 265.9),
    '13.no' => $check(218.6, 265.8),

    // Part II: computation of tax.
    '14' => $amount(306.5),
    '15' => $amount(332.0),
    '16' => $amount(348.0),
    '17' => $amount(364.0),
    '18' => $amount(380.0),
    '19' => $amount(396.0),
    '20' => $amount(412.0),
    '21' => $amount(428.0),
    '22' => $amount(443.9),
    '23' => $amount(459.2),
    '24' => $amount(475.8),
    '25' => $amount(491.7),
    '26' => $amount(507.7),
    '27' => $amount(523.6),
    '28' => $amount(539.6),
    '30' => $amount(572.1),
    '31' => $amount(588.1),
    '32' => $amount(604.1),
    '33' => $amount(620.0),
    '34' => $amount(636.0),
    '35' => $amount(652.0),
    '36' => $amount(667.9),
];
