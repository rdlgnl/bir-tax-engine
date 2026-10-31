<?php

/*
| Field map: BIR Form 2550Q, April 2024 (ENCS).
|
| Where each box sits on 2550Q-2024-04.pdf, in points from the page's
| top-left corner, measured from the boxes drawn in the PDF itself. The
| page is 612 × 1008 (legal). Amounts have twelve peso boxes, a printed
| decimal point, and two centavo boxes. Check the result with
| `php artisan forms:calibrate 2550Q`.
*/

$comb = fn (int $page, float $x, float $to, int $cells, float $y) => ['page' => $page, 'type' => 'comb', 'x' => $x, 'to' => $to, 'cells' => $cells, 'y' => $y];
$check = fn (int $page, float $x, float $y) => ['page' => $page, 'type' => 'check', 'x' => $x, 'y' => $y];
// Part II (page 1): one column.
$partTwo = fn (float $y) => ['page' => 1, 'type' => 'amount', 'x' => 375.4, 'to' => 546.6, 'cells' => 12, 'cents' => [561.8, 591.5], 'y' => $y];
// Part IV (page 2): A sales or purchases, B output or input tax.
$columnA = fn (float $y) => ['page' => 2, 'type' => 'amount', 'x' => 162.2, 'to' => 332.6, 'cells' => 12, 'cents' => [347.4, 375.8], 'y' => $y];
$columnB = fn (float $y) => ['page' => 2, 'type' => 'amount', 'x' => 375.8, 'to' => 546.5, 'cells' => 12, 'cents' => [561.2, 589.1], 'y' => $y];

return [
    // Page 1 header.
    '1.calendar' => $check(1, 74.0, 110.3),
    '1.fiscal' => $check(1, 141.2, 110.0),
    '2' => $comb(1, 304.4, 389.8, 6, 123.0),
    '3.1' => $check(1, 434.6, 109.8),
    '3.2' => $check(1, 470.4, 110.5),
    '3.3' => $check(1, 512.6, 109.9),
    '3.4' => $check(1, 554.3, 110.1),
    '4.from' => $comb(1, 64.0, 176.2, 8, 151.9),
    '4.to' => $comb(1, 204.4, 318.4, 8, 151.9),
    '5.no' => $check(1, 389.6, 138.9),
    '6.no' => $check(1, 533.3, 139.3),

    // Part I: background information.
    'tin.1' => $comb(1, 232.8, 275.4, 3, 183.0),
    'tin.2' => $comb(1, 289.6, 332.6, 3, 183.0),
    'tin.3' => $comb(1, 346.8, 389.6, 3, 183.0),
    '8' => $comb(1, 546.6, 591.5, 3, 183.0),
    '9' => $comb(1, 21.3, 591.5, 40, 212.0),
    '10.1' => $comb(1, 21.3, 591.5, 40, 240.2),
    '10.2' => $comb(1, 21.3, 461.1, 31, 259.0),
    '10A' => $comb(1, 532.1, 591.5, 4, 259.0),
    '11' => $comb(1, 21.3, 190.3, 12, 287.6),
    '12' => $comb(1, 204.4, 591.5, 27, 287.6),
    '13.micro' => $check(1, 161.5, 293.7),
    '13.small' => $check(1, 231.8, 293.6),
    '13.medium' => $check(1, 302.3, 293.6),
    '13.large' => $check(1, 389.6, 294.6),
    '14.no' => $check(1, 235.1, 313.4),

    // Part II: total tax payable.
    '15' => $partTwo(360.5),
    '16' => $partTwo(389.6),
    '20' => $partTwo(462.7),
    '21' => $partTwo(481.0),
    '26' => $partTwo(573.3),

    // Page 2 header.
    'p2.tin' => $comb(2, 21.3, 148.8, 9, 105.7),
    'p2.name' => $comb(2, 219.3, 589.1, 26, 105.7),

    // Part IV: details of VAT computation.
    '31A' => $columnA(145.4),
    '31B' => $columnB(145.4),
    '32A' => $columnA(161.3),
    '33A' => $columnA(177.3),
    '34A' => $columnA(194.5),
    '34B' => $columnB(194.5),
    '37B' => $columnB(242.8),
    '38B' => $columnB(269.1),
    '43B' => $columnB(349.8),
    '44A' => $columnA(379.1),
    '44B' => $columnB(379.1),
    '50A' => $columnA(476.6),
    '50B' => $columnB(476.6),
    '51B' => $columnB(493.1),
    '57B' => $columnB(600.4),
    '59B' => $columnB(632.2),
    '60B' => $columnB(648.1),
    '61B' => $columnB(665.1),
];
