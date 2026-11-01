<?php

/*
| Field map: BIR Form 1604-C, January 2018.
|
| Where each box sits on 1604-C-2018-01.pdf, in points from the page's
| top-left corner, measured from the boxes drawn in the PDF itself. Part II
| has a line per month's 1601-C (date, taxes withheld) and, in its
| continuation, the adjustment, penalties and total remitted.
| Check with `php artisan forms:calibrate 1604-C`.
*/

$comb = fn (float $x, float $to, int $cells, float $y) => ['page' => 1, 'type' => 'comb', 'x' => $x, 'to' => $to, 'cells' => $cells, 'y' => $y];
$text = fn (float $x, float $to, float $y) => ['page' => 1, 'type' => 'text', 'x' => $x, 'to' => $to, 'y' => $y];
$figure = fn (float $x, float $to, float $y) => ['page' => 1, 'type' => 'figure', 'x' => $x, 'to' => $to, 'y' => $y];
$check = fn (float $x, float $y) => ['page' => 1, 'type' => 'check', 'x' => $x, 'y' => $y];

$items = [
    // 1: the year's last two digits; the form prints "20".
    '1' => $comb(171.6, 208.5, 2, 134.0),
    '2.yes' => $check(306.2, 119.9),
    '2.no' => $check(356.8, 120.5),

    // Part I: background information.
    'tin.1' => $comb(237.9, 279.4, 3, 169.0),
    'tin.2' => $comb(293.8, 335.5, 3, 169.0),
    'tin.3' => $comb(349.9, 392.6, 3, 169.0),
    'tin.branch' => $comb(407.0, 476.4, 5, 169.0),
    '5' => $comb(546.7, 591.2, 3, 169.0),
    '6' => $comb(23.3, 591.2, 40, 200.5),
    '7.1' => $comb(23.3, 591.2, 40, 232.5),
    '7.2' => $comb(23.3, 462.5, 31, 253.0),
    '7A' => $comb(531.8, 591.2, 4, 253.0),
    '8.private' => $check(177.5, 258.7),
    '8.government' => $check(240.5, 259.1),
    '8A.yes' => $check(493.0, 258.8),
    '8A.no' => $check(542.8, 258.8),
    '9' => $comb(23.3, 182.3, 11, 303.5),
    '10' => $comb(182.3, 591.2, 29, 303.5),
    // The refund boxes are smaller than the rest; the mark is centred on them.
    '11.yes' => $check(213.0, 314.9),
    '11.no' => $check(259.4, 314.9),
    '11A' => $comb(476.4, 591.2, 8, 323.5),
    '12' => ['page' => 1, 'type' => 'amount', 'x' => 182.2, 'to' => 349.9, 'cells' => 12, 'cents' => [364.9, 392.6], 'y' => 344.5],
    '13' => $comb(561.5, 591.2, 2, 344.5),
];

// Part II: a line per month, then the total.
foreach ([...range(1, 12), 'total'] as $i => $month) {
    $y = round(398.0 + $i * 13.45, 1);
    $items["m.{$month}.date"] = $text(97.2, 224.8, $y);
    $items["m.{$month}.withheld"] = $figure(474.8, 591.7, $y);

    $y = round(602.0 + $i * 13.45, 1);
    $items["m.{$month}.adjustment"] = $figure(97.2, 262.1, $y);
    $items["m.{$month}.penalties"] = $figure(262.1, 427.0, $y);
    $items["m.{$month}.remitted"] = $figure(427.0, 591.5, $y);
}

return $items;
