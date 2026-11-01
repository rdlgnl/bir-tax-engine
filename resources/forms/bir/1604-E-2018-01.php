<?php

/*
| Field map: BIR Form 1604-E, January 2018 (ENCS).
|
| Where each box sits on 1604-E-2018-01.pdf, in points from the page's
| top-left corner, measured from the boxes drawn in the PDF itself. Page 1
| has the background information and Schedule 1, a line per quarter's
| 1601-EQ (Schedule 2 is for Form 1606 and stays blank). Page 2 has the
| first three lines of the alphalist; the full list goes in the data file.
| Check with `php artisan forms:calibrate 1604-E`.
*/

$comb = fn (float $x, float $to, int $cells, float $y, int $page = 1) => ['page' => $page, 'type' => 'comb', 'x' => $x, 'to' => $to, 'cells' => $cells, 'y' => $y];
$text = fn (float $x, float $to, float $y, int $page = 1) => ['page' => $page, 'type' => 'text', 'x' => $x, 'to' => $to, 'y' => $y];
$figure = fn (float $x, float $to, float $y, int $page = 1) => ['page' => $page, 'type' => 'figure', 'x' => $x, 'to' => $to, 'y' => $y];
$check = fn (float $x, float $y) => ['page' => 1, 'type' => 'check', 'x' => $x, 'y' => $y];

$quarter = fn (float $y) => [
    'date' => $text(85.3, 161.7, $y),
    'withheld' => $figure(332.7, 413.7, $y),
    'penalties' => $figure(413.7, 503.9, $y),
    'total' => $figure(503.9, 591.2, $y),
];

$payee = fn (float $y) => [
    'seq' => $text(23.3, 65.4, $y, 2),
    'tin' => $text(65.9, 151.1, $y, 2),
    'name' => $text(151.6, 307.4, $y, 2),
    'atc' => $text(307.9, 352.0, $y, 2),
    'income' => $figure(352.5, 451.3, $y, 2),
    'rate' => $text(451.8, 490.9, $y, 2),
    'withheld' => $figure(491.4, 590.0, $y, 2),
];

$items = [
    // 1: the year's last two digits; the form prints "20".
    '1' => $comb(171.6, 208.5, 2, 140.0),
    '2.yes' => $check(306.2, 126.0),
    '2.no' => $check(356.8, 126.5),
    '3' => $comb(553.1, 591.0, 2, 140.0),

    'tin.1' => $comb(235.6, 277.6, 3, 174.5),
    'tin.2' => $comb(292.0, 334.0, 3, 174.5),
    'tin.3' => $comb(348.4, 390.4, 3, 174.5),
    'tin.branch' => $comb(404.8, 474.9, 5, 174.5),
    '5' => $comb(546.1, 591.2, 3, 174.5),
    '6' => $comb(22.4, 591.2, 40, 206.5),
    '7.1' => $comb(22.4, 591.2, 40, 238.5),
    '7.2' => $comb(22.4, 461.0, 31, 259.0),
    '7A' => $comb(531.1, 591.2, 4, 259.0),
    '8.private' => $check(177.5, 264.7),
    '8.government' => $check(240.5, 265.2),
    '8A.yes' => $check(491.4, 264.8),
    '8A.no' => $check(541.2, 265.0),
    '9' => $comb(21.8, 179.3, 11, 309.5),
    '10' => $comb(179.3, 591.2, 29, 309.5),

    // Page 2 header.
    'p2.tin' => $comb(22.4, 221.2, 14, 136.0, 2),
    'p2.name' => $comb(221.7, 590.4, 26, 136.0, 2),
];

// Schedule 1: a line per quarter, then the total.
foreach ([1 => 394.8, 2 => 409.7, 3 => 424.6, 4 => 439.5, 'total' => 454.5] as $q => $y) {
    foreach ($quarter($y) as $column => $place) {
        $items["s1.{$q}.{$column}"] = $place;
    }
}

// Schedule 3: the first three payees, under the column numbers, then the total of all of them.
foreach ([1 => 230.0, 2 => 243.5, 3 => 256.9] as $n => $y) {
    foreach ($payee($y) as $column => $place) {
        $items["s3.{$n}.{$column}"] = $place;
    }
}

return $items + [
    's3.total.income' => $figure(352.5, 451.3, 270.5, 2),
    's3.total.withheld' => $figure(491.4, 590.0, 270.5, 2),
];
