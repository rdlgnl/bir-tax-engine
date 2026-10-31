<?php

/*
| Field map: BIR Form 1701-MS, August 2024 (ENCS).
|
| Where each box sits on 1701-MS-2024-08.pdf, in points from the page's
| top-left corner. The BIR's file is fillable, so the boxes come from its
| own form fields (position, and the number of character boxes in each);
| the filled download draws on the page like the other forms. Only column
| A (the taxpayer) is mapped. Check with `php artisan forms:calibrate 1701-MS`.
*/

$comb = fn (int $page, float $x, float $to, int $cells, float $y) => ['page' => $page, 'type' => 'comb', 'x' => $x, 'to' => $to, 'cells' => $cells, 'y' => $y];
$amount = fn (int $page, float $x, float $to, float $y) => ['page' => $page, 'type' => 'amount', 'x' => $x, 'to' => $to, 'cells' => 9, 'y' => $y];
$text = fn (int $page, float $x, float $to, float $y) => ['page' => $page, 'type' => 'text', 'x' => $x, 'to' => $to, 'y' => $y];
$check = fn (int $page, float $x, float $y) => ['page' => $page, 'type' => 'check', 'x' => $x, 'y' => $y];

return [
    // Page 1 header.
    '1.month' => $comb(1, 127.1, 154.5, 2, 104.0),
    '1.year' => $comb(1, 156.4, 211.3, 4, 104.2),
    '2.no' => $check(1, 359.1, 95.2),
    '3.no' => $check(1, 554.6, 95.2),

    // Part I, column A: the taxpayer.
    '6' => $text(1, 208.2, 397.7, 155.2),
    '7' => $text(1, 208.2, 397.7, 167.0),
    '8' => $text(1, 208.8, 397.1, 182.5),
    '9' => $text(1, 208.2, 397.7, 207.9),
    '10' => $text(1, 208.2, 397.7, 219.5),
    '11.business' => $check(1, 209.0, 224.8),
    '11.profession' => $check(1, 209.0, 237.8),
    '11.mixed' => $check(1, 325.2, 224.8),
    '12.graduated' => $check(1, 208.1, 252.0),
    '12.eight_percent' => $check(1, 313.7, 251.6),
    '17.itemized' => $check(1, 209.2, 355.9),
    '17.osd' => $check(1, 209.2, 369.7),
    '18' => $text(1, 215.3, 255.1, 398.3),

    // Part II, column A: total income tax payable.
    '22A' => $amount(1, 328.5, 460.2, 479.8),
    '23A' => $amount(1, 328.5, 460.2, 493.6),
    '24A' => $amount(1, 328.5, 459.8, 507.1),
    '25A' => $amount(1, 329.2, 460.4, 520.9),
    '27A' => $amount(1, 328.9, 460.1, 547.5),
    '29A' => $amount(1, 327.1, 460.6, 617.3),
    '30' => $amount(1, 403.5, 537.0, 632.7),
    '33' => $text(1, 27.5, 301.4, 742.9),

    // Page 2 header.
    'p2.tin' => $comb(2, 15.9, 134.6, 9, 95.5),
    'p2.last_name' => $comb(2, 210.1, 597.3, 24, 95.9),

    // Part IV B1, column A: graduated rates.
    'iv.5' => $amount(2, 328.2, 461.6, 235.4),
    'iv.7' => $amount(2, 328.0, 461.0, 264.4),
    'iv.8' => $amount(2, 327.7, 460.7, 279.4),
    'iv.9' => $amount(2, 327.4, 460.4, 293.9),
    'iv.10A' => $amount(2, 328.4, 461.8, 320.7),
    'iv.10D' => $amount(2, 327.8, 460.8, 364.7),
    'iv.11' => $amount(2, 328.2, 461.6, 389.1),
    'iv.12' => $amount(2, 328.2, 461.2, 403.6),
    'iv.14' => $amount(2, 327.6, 460.6, 433.1),
    'iv.15' => $amount(2, 327.9, 460.9, 447.9),
    'iv.18B' => $amount(2, 327.6, 460.6, 516.3),

    // Part IV B2, column A: 8%.
    'iv.19' => $amount(2, 328.0, 461.4, 548.0),
    'iv.21' => $amount(2, 328.2, 461.2, 577.7),
    'iv.22' => $amount(2, 328.1, 461.1, 592.8),
    'iv.23' => $amount(2, 327.1, 461.7, 611.2),
    'iv.24' => $amount(2, 327.9, 460.9, 626.0),
    'iv.25' => $amount(2, 327.5, 460.5, 641.8),

    // Part V, column A: tax credits and payments.
    'v.2' => $amount(2, 329.2, 462.2, 689.2),
    'v.3' => $amount(2, 328.9, 462.2, 702.3),
    'v.4' => $amount(2, 328.9, 461.9, 717.0),
    'v.10' => $amount(2, 328.7, 461.7, 798.8),
];
