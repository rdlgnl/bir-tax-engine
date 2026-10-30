<?php

/*
| Field map: BIR Form 1701, January 2018 (ENCS).
|
| Where each box sits on 1701-2018-01.pdf, in points from the page's
| top-left corner, measured from the boxes drawn in the PDF itself. See
| App\Returns\Forms\OfficialFormFiller for the placement types; check the
| result with `php artisan forms:calibrate 1701`.
|
| Item numbers restart in each part, so items past Part II carry their
| schedule or part: "S3A.8" is Part V Schedule 3.A Item 8, "VII.4" is Part
| VII Item 4.
*/

$comb = fn (int $page, float $x, float $to, int $cells, float $y) => ['page' => $page, 'type' => 'comb', 'x' => $x, 'to' => $to, 'cells' => $cells, 'y' => $y];
$check = fn (int $page, float $x, float $y) => ['page' => $page, 'type' => 'check', 'x' => $x, 'y' => $y];
// Column A) Taxpayer/Filer, on page 1 and on pages 2 to 4.
$partTwo = fn (float $y) => ['page' => 1, 'type' => 'amount', 'x' => 328.2, 'to' => 462.9, 'cells' => 9, 'y' => $y];
$columnA = fn (int $page, float $y) => ['page' => $page, 'type' => 'amount', 'x' => 327.8, 'to' => 462.0, 'cells' => 9, 'y' => $y];

return [
    // Page 1 header.
    '1' => $comb(1, 128.1, 183.2, 4, 117.5),
    '2.yes' => $check(1, 299.8, 103.4),
    '2.no' => $check(1, 343.4, 103.4),
    '3.yes' => $check(1, 493.6, 103.7),
    '3.no' => $check(1, 543.4, 103.6),

    // Part I: background information on the taxpayer.
    'tin.1' => $comb(1, 195.4, 238.2, 3, 149.5),
    'tin.2' => $comb(1, 252.8, 296.4, 3, 149.5),
    'tin.3' => $comb(1, 313.2, 357.1, 3, 149.5),
    '5' => $comb(1, 552.4, 598.9, 3, 149.5),
    '6.single_proprietor' => $check(1, 112.6, 153.6),
    '6.professional' => $check(1, 211.6, 153.6),
    '6.compensation_earner' => $check(1, 437.5, 154.0),
    'atc.II011' => $check(1, 31.7, 185.6),
    'atc.II012' => $check(1, 155.0, 171.5),
    'atc.II014' => $check(1, 300.1, 170.9),
    'atc.II013' => $check(1, 460.2, 171.4),
    'atc.II015' => $check(1, 155.2, 185.4),
    'atc.II017' => $check(1, 300.0, 185.3),
    'atc.II016' => $check(1, 460.4, 185.9),
    '8' => $comb(1, 14.9, 598.9, 40, 225.5),
    '9.1' => $comb(1, 14.9, 598.9, 40, 251.5),
    '9.2' => $comb(1, 14.9, 462.9, 31, 267.5),
    '9A' => $comb(1, 538.6, 598.9, 4, 267.5),
    '10' => $comb(1, 14.9, 126.8, 8, 294.5),
    '11' => $comb(1, 126.8, 598.9, 32, 294.5),
    '12' => $comb(1, 14.9, 240.9, 16, 323.5),
    '13.yes' => $check(1, 271.2, 310.1),
    '13.no' => $check(1, 320.0, 309.8),
    '15' => $comb(1, 14.9, 197.8, 13, 349.5),
    '19.yes' => $check(1, 209.3, 367.3),
    '19.no' => $check(1, 261.0, 367.7),
    '20.yes' => $check(1, 514.9, 367.3),
    '20.no' => $check(1, 556.0, 367.7),
    '21.graduated' => $check(1, 62.0, 396.5),
    '21.eight_percent' => $check(1, 62.2, 418.4),
    '21A.itemized' => $check(1, 228.2, 397.0),
    '21A.osd' => $check(1, 359.9, 397.0),

    // Part II: total tax payable.
    '22A' => $partTwo(479.5),
    '23A' => $partTwo(496.5),
    '24A' => $partTwo(514.5),
    '25A' => $partTwo(532.5),
    '26A' => $partTwo(549.5),
    '27A' => $partTwo(566.5),
    '28A' => $partTwo(583.5),
    '29A' => $partTwo(600.5),
    '30A' => $partTwo(617.5),
    '31A' => $partTwo(634.5),
    '32' => ['page' => 1, 'type' => 'amount', 'x' => 386.9, 'to' => 522.4, 'cells' => 9, 'y' => 650.5],

    // Headers of pages 2 to 4.
    'p2.tin' => $comb(2, 14.9, 135.4, 9, 91.5),
    'p2.last_name' => $comb(2, 208.8, 598.9, 26, 91.5),
    'p3.tin' => $comb(3, 14.9, 135.2, 9, 92.5),
    'p3.last_name' => $comb(3, 208.8, 599.0, 26, 92.5),
    'p4.tin' => $comb(4, 14.9, 135.1, 9, 88.5),
    'p4.last_name' => $comb(4, 208.8, 598.9, 26, 88.5),

    // Part V Schedule 3.A: business income at graduated rates.
    'S3A.8' => $columnA(2, 617.5),
    'S3A.9' => $columnA(2, 633.5),
    'S3A.10' => $columnA(2, 649.5),
    'S3A.11' => $columnA(2, 665.5),
    'S3A.12' => $columnA(2, 681.5),
    'S3A.13' => $columnA(2, 706.5),
    'S3A.14' => $columnA(2, 722.5),
    'S3A.15' => $columnA(2, 738.5),
    'S3A.16' => $columnA(2, 754.5),
    'S3A.17' => $columnA(2, 780.5),
    'S3A.18' => $columnA(2, 796.5),
    'S3A.22' => $columnA(2, 865.5),
    'S3A.23' => $columnA(2, 880.5),
    'S3A.24' => $columnA(2, 896.5),
    'S3A.25' => $columnA(2, 917.5),

    // Part V Schedule 3.B: business income at 8%.
    'S3B.26' => $columnA(3, 132.5),
    'S3B.28' => $columnA(3, 175.5),
    'S3B.29' => $columnA(3, 194.5),
    'S3B.30' => $columnA(3, 210.5),
    'S3B.31' => $columnA(3, 226.5),
    'S3B.32' => $columnA(3, 243.5),

    // Part V Schedule 4: ordinary allowable itemized deductions, total.
    'S4.18' => ['page' => 3, 'type' => 'amount', 'x' => 326.5, 'to' => 460.7, 'cells' => 9, 'y' => 607.5],

    // Part VI: summary of income tax due.
    'VI.1' => $columnA(4, 223.5),
    'VI.5' => $columnA(4, 282.5),

    // Part VII: tax credits and payments.
    'VII.1' => $columnA(4, 311.5),
    'VII.2' => $columnA(4, 326.5),
    'VII.3' => $columnA(4, 341.5),
    'VII.4' => $columnA(4, 356.5),
    'VII.10' => $columnA(4, 445.5),
];
