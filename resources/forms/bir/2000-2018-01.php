<?php

/*
| Field map: BIR Form 2000, January 2018 (ENCS).
|
| Where each box sits on 2000-2018-01.pdf, in points from the page's
| top-left corner, measured from the boxes drawn in the PDF itself. Page 1
| has the background information, the other party and Part II; page 2
| has Schedule 1, a line per document with its ATC, tax base, rate and tax.
| Amounts have eleven peso cells, a cell for the point, and two for
| centavos. Check with `php artisan forms:calibrate 2000`.
*/

$comb = fn (float $x, float $to, int $cells, float $y, int $page = 1) => ['page' => $page, 'type' => 'comb', 'x' => $x, 'to' => $to, 'cells' => $cells, 'y' => $y];
$text = fn (float $x, float $to, float $y, int $page = 1) => ['page' => $page, 'type' => 'text', 'x' => $x, 'to' => $to, 'y' => $y];
$check = fn (float $x, float $y) => ['page' => 1, 'type' => 'check', 'x' => $x, 'y' => $y];
$partTwo = fn (float $y) => ['page' => 1, 'type' => 'amount', 'x' => 388.9, 'to' => 543.3, 'cells' => 11, 'cents' => [557.4, 585.1], 'y' => $y];
$base = fn (float $y) => ['page' => 2, 'type' => 'amount', 'x' => 95.5, 'to' => 249.8, 'cells' => 11, 'cents' => [264.2, 292.2], 'y' => $y];
$due = fn (float $y) => ['page' => 2, 'type' => 'amount', 'x' => 390.3, 'to' => 544.5, 'cells' => 11, 'cents' => [559.1, 586.5], 'y' => $y];

return [
    // 1: the month, MM/YYYY.
    '1' => $comb(164.1, 248.4, 6, 126.5),
    '2.yes' => $check(347.0, 110.2),
    '2.no' => $check(389.1, 110.2),
    '3' => $comb(557.4, 585.1, 2, 126.5),

    // Part I: background information.
    'tin.1' => $comb(220.4, 262.4, 3, 158.5),
    'tin.2' => $comb(276.5, 318.6, 3, 158.5),
    'tin.3' => $comb(332.7, 374.8, 3, 158.5),
    'tin.branch' => $comb(388.9, 459.1, 5, 158.5),
    '5' => $comb(529.3, 571.4, 3, 158.5),
    '6' => $comb(24.6, 585.1, 40, 187.0),
    '7.1' => $comb(24.6, 585.1, 40, 216.0),
    '7.2' => $comb(24.6, 459.1, 31, 234.3),
    '7A' => $comb(529.3, 585.1, 4, 234.3),
    '8' => $comb(24.6, 192.1, 12, 263.0),
    '9' => $comb(192.1, 585.1, 28, 263.0),
    '10.creditor' => $check(194.0, 269.0),
    '10.debtor' => $check(358.8, 268.4),
    '10.none' => $check(522.0, 268.4),
    '11' => $comb(24.6, 346.8, 23, 328.0),
    '12' => $comb(388.9, 585.1, 14, 328.0),
    '13.edst' => $check(137.2, 333.7),
    '13.constructive' => $check(244.1, 333.8),
    '13.loose' => $check(381.8, 333.8),

    // Part II: computation of payable.
    '14' => $partTwo(394.0),
    '15A' => $partTwo(412.3),
    '15B' => $partTwo(430.6),
    '15C' => $partTwo(448.8),
    '15D' => $partTwo(467.1),
    '16' => $partTwo(485.3),
    '17A' => $partTwo(503.6),
    '17B' => $partTwo(521.8),
    '17C' => $partTwo(540.1),
    '17D' => $partTwo(558.4),
    '18' => $partTwo(576.6),

    // Page 2 header.
    'p2.tin' => $comb(26.9, 222.5, 14, 117.5, 2),
    'p2.name' => $comb(222.5, 586.5, 26, 117.5, 2),

    // Schedule 1: the first line, and the total.
    's1.1.atc' => $comb(25.0, 95.5, 5, 162.0, 2),
    's1.1.base' => $base(162.0),
    's1.1.rate' => $text(292.2, 390.3, 162.0, 2),
    's1.1.due' => $due(162.0),
    's1.total' => $due(216.5),
];
