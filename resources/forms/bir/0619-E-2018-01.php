<?php

/*
| Field map: BIR Form 0619-E, January 2018.
|
| Where each box sits on 0619-E-2018-01.pdf, in points from the page's
| top-left corner, measured from the boxes drawn in the PDF itself. The
| page is letter size (612 × 792). The ATC (WME10) and tax type (WE) are
| printed on the form. Check with `php artisan forms:calibrate 0619-E`.
*/

$comb = fn (float $x, float $to, int $cells, float $y) => ['page' => 1, 'type' => 'comb', 'x' => $x, 'to' => $to, 'cells' => $cells, 'y' => $y];
$check = fn (float $x, float $y) => ['page' => 1, 'type' => 'check', 'x' => $x, 'y' => $y];
$amount = fn (float $y) => ['page' => 1, 'type' => 'amount', 'x' => 391.6, 'to' => 550.3, 'cells' => 11, 'cents' => [564.7, 593.0], 'y' => $y];

return [
    '1' => $comb(45.8, 131.3, 6, 146.5),
    '2' => $comb(145.7, 260.6, 8, 146.5),
    '3.no' => $check(319.2, 134.4),
    '4.yes' => $check(376.8, 134.5),
    '4.no' => $check(420.6, 134.6),

    'tin.1' => $comb(217.8, 261.1, 3, 179.5),
    'tin.2' => $comb(275.8, 319.0, 3, 179.5),
    'tin.3' => $comb(333.8, 377.0, 3, 179.5),
    '8' => $comb(535.9, 579.1, 3, 179.5),
    '9' => $comb(16.9, 593.0, 40, 209.5),
    '10.1' => $comb(16.9, 593.0, 40, 237.5),
    '10.2' => $comb(16.9, 463.9, 31, 256.0),
    '10A' => $comb(535.9, 593.0, 4, 256.0),
    '11' => $comb(102.9, 275.9, 12, 277.0),
    '12.private' => $check(445.4, 262.2),
    '12.government' => $check(516.5, 262.1),
    '13' => $comb(16.9, 593.0, 40, 302.5),

    '14' => $amount(336.5),
    '16' => $amount(373.0),
    '18' => $amount(475.5),
];
