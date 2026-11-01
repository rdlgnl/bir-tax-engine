<?php

/*
| Field map: BIR Form 1702Q, January 2018 (ENCS).
|
| Where each box sits on 1702Q-2018-01.pdf, in points from the page's
| top-left corner, measured from the boxes drawn in the PDF itself. Page 1
| holds the totals (Part II); page 2 the schedules they come from. Check
| the result with `php artisan forms:calibrate 1702Q`.
*/

$comb = fn (int $page, float $x, float $to, int $cells, float $y) => ['page' => $page, 'type' => 'comb', 'x' => $x, 'to' => $to, 'cells' => $cells, 'y' => $y];
$check = fn (int $page, float $x, float $y) => ['page' => $page, 'type' => 'check', 'x' => $x, 'y' => $y];
$text = fn (int $page, float $x, float $to, float $y) => ['page' => $page, 'type' => 'text', 'x' => $x, 'to' => $to, 'y' => $y];
// Every amount sits in the same twelve boxes at the right of its row.
$amount = fn (int $page, float $y) => ['page' => $page, 'type' => 'amount', 'x' => 411.7, 'to' => 593.5, 'cells' => 12, 'y' => $y];

return [
    // Page 1 header.
    '1.calendar' => $check(1, 49.8, 123.1),
    '2.month' => $comb(1, 33.8, 60.6, 2, 159.5),
    '2.year' => $comb(1, 82.6, 109.2, 2, 159.5),
    '3.1' => $check(1, 155.4, 138.5),
    '3.2' => $check(1, 182.8, 138.7),
    '3.3' => $check(1, 211.8, 138.2),
    '4.no' => $check(1, 299.4, 140.2),
    'atc.mcit' => $check(1, 563.5, 133.3),
    'atc.code' => $text(1, 357.0, 390.0, 152.5),
    'atc.description' => $text(1, 408.0, 548.0, 152.5),
    'atc.other' => $check(1, 563.5, 146.2),

    // Part I: background information.
    'tin.1' => $comb(1, 214.8, 260.3, 3, 193.5),
    'tin.2' => $comb(1, 275.6, 321.1, 3, 193.5),
    'tin.3' => $comb(1, 336.2, 381.7, 3, 193.5),
    '7' => $comb(1, 533.3, 578.7, 3, 193.5),
    '8.1' => $comb(1, 17.3, 593.5, 38, 224.5),
    '8.2' => $comb(1, 17.3, 593.5, 38, 242.5),
    '9.1' => $comb(1, 17.3, 593.5, 38, 272.5),
    '9.2' => $comb(1, 17.3, 472.7, 30, 290.5),
    '9A' => $comb(1, 533.3, 593.5, 4, 290.5),
    '10' => $comb(1, 17.3, 184.6, 11, 321.5),
    '11' => $comb(1, 184.6, 593.5, 27, 321.5),
    '12.itemized' => $check(1, 135.5, 327.5),
    '12.osd' => $check(1, 353.0, 327.5),
    '13.no' => $check(1, 220.8, 344.4),

    // Part II: total tax payable.
    '14' => $amount(1, 395.5),
    '15' => $amount(1, 414.5),
    '16' => $amount(1, 434.5),
    '17' => $amount(1, 453.5),
    '18' => $amount(1, 473.5),
    '19' => $amount(1, 492.5),
    '20' => $amount(1, 511.5),
    '21' => $amount(1, 531.5),
    '22' => $amount(1, 550.5),
    '23' => $amount(1, 570.5),
    '24' => $amount(1, 589.5),
    '25' => $amount(1, 610.5),

    // Page 2 header.
    'p2.tin' => $comb(2, 17.3, 153.8, 9, 106.5),
    'p2.name' => $text(2, 232.0, 593.5, 106.5),

    // Schedule 2: declaration this quarter, regular rate.
    's2.1' => $amount(2, 394.5),
    's2.2' => $amount(2, 411.5),
    's2.3' => $amount(2, 428.5),
    's2.4' => $amount(2, 445.5),
    's2.5' => $amount(2, 462.5),
    's2.6' => $amount(2, 479.5),
    's2.7' => $amount(2, 496.5),
    's2.8' => $amount(2, 513.5),
    's2.9' => $amount(2, 530.5),
    's2.10' => $comb(2, 548.3, 578.4, 2, 547.5),
    's2.11' => $amount(2, 565.5),
    's2.12' => $amount(2, 582.5),
    's2.13' => $amount(2, 600.5),

    // Schedule 3: minimum corporate income tax.
    's3.1' => $amount(2, 633.5),
    's3.2' => $amount(2, 650.5),
    's3.3' => $amount(2, 668.5),
    's3.4' => $amount(2, 684.5),
    's3.6' => $amount(2, 721.5),

    // Schedule 4: tax credits and payments.
    's4.1' => $amount(2, 755.5),
    's4.2' => $amount(2, 772.5),
    's4.3' => $amount(2, 789.5),
    's4.4' => $amount(2, 806.5),
    's4.5' => $amount(2, 823.5),
    's4.6' => $amount(2, 840.5),
    's4.7' => $amount(2, 904.5),
];
