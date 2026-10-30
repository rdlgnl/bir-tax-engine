<?php

/*
| Field map: BIR Form 2551Q, January 2018 (ENCS).
|
| Where each box sits on 2551Q-2018-01.pdf, in points from the page's
| top-left corner, measured from the boxes drawn in the PDF itself. See
| App\Returns\Forms\OfficialFormFiller for the placement types; check the
| result with `php artisan forms:calibrate 2551Q`.
*/

$comb = fn (int $page, float $x, float $to, int $cells, float $y) => ['page' => $page, 'type' => 'comb', 'x' => $x, 'to' => $to, 'cells' => $cells, 'y' => $y];
$check = fn (int $page, float $x, float $y) => ['page' => $page, 'type' => 'check', 'x' => $x, 'y' => $y];
// Part II (page 1): pesos, then centavos after the decimal point.
$partTwo = fn (float $y) => ['page' => 1, 'type' => 'amount', 'x' => 376.9, 'to' => 547.5, 'cells' => 12, 'cents' => [561.8, 590.1], 'y' => $y];
// Schedule 1 (page 2): tax due column.
$taxDue = fn (float $y) => ['page' => 2, 'type' => 'amount', 'x' => 363.4, 'to' => 533.5, 'cells' => 12, 'cents' => [547.5, 576.0], 'y' => $y];

return [
    // Page 1 header.
    '1.calendar' => $check(1, 85.1, 113.0),
    '1.fiscal' => $check(1, 157.4, 113.2),
    '2' => $comb(1, 136.3, 221.4, 6, 143.5),
    '3.1' => $check(1, 244.4, 126.5),
    '3.2' => $check(1, 286.7, 126.8),
    '3.3' => $check(1, 329.2, 126.5),
    '3.4' => $check(1, 371.2, 126.2),
    '4.yes' => $check(1, 425.6, 127.3),
    '4.no' => $check(1, 467.4, 127.9),

    // Part I: background information.
    'tin.1' => $comb(1, 220.8, 263.4, 3, 176.5),
    'tin.2' => $comb(1, 277.4, 320.1, 3, 176.5),
    'tin.3' => $comb(1, 334.4, 377.1, 3, 176.5),
    '7' => $comb(1, 547.5, 590.1, 3, 176.5),
    '8' => $comb(1, 23.0, 590.1, 40, 205.5),
    '9.1' => $comb(1, 23.0, 590.1, 40, 234.5),
    '9.2' => $comb(1, 23.0, 462.1, 31, 252.5),
    '9A' => $comb(1, 533.1, 590.1, 4, 252.5),
    '10' => $comb(1, 23.0, 192.3, 12, 282.5),
    '11' => $comb(1, 192.3, 590.1, 28, 282.5),
    '12.yes' => $check(1, 191.9, 287.4),
    '12.no' => $check(1, 235.1, 287.6),
    '13.graduated' => $check(1, 181.2, 328.1),
    '13.eight_percent' => $check(1, 348.5, 328.3),

    // Part II: total tax payable.
    '14' => $partTwo(378.5),
    '15' => $partTwo(408.5),
    '16' => $partTwo(426.5),
    '17' => $partTwo(444.5),
    '18' => $partTwo(462.5),
    '19' => $partTwo(481.5),
    '20' => $partTwo(510.5),
    '21' => $partTwo(528.5),
    '22' => $partTwo(547.5),
    '23' => $partTwo(565.5),
    '24' => $partTwo(584.5),

    // Page 2 header.
    'p2.tin' => $comb(2, 23.0, 150.6, 9, 124.5),
    'p2.last_name' => $comb(2, 221.6, 590.1, 26, 124.5),

    // Schedule 1: computation of tax, first row.
    'S1.1.atc' => $comb(2, 37.2, 108.2, 5, 178.5),
    'S1.1.taxable' => ['page' => 2, 'type' => 'amount', 'x' => 108.2, 'to' => 278.2, 'cells' => 12, 'cents' => [292.4, 320.6], 'y' => 178.5],
    'S1.1.rate' => $comb(2, 320.6, 349.1, 2, 178.5),
    'S1.1.due' => $taxDue(178.5),
    'S1.7' => $taxDue(288.5),
];
