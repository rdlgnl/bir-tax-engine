<?php

/*
| Field map: BIR Form 1601-EQ, January 2019 (ENCS).
|
| Where each box sits on 1601-EQ-2019-01.pdf, in points from the page's
| top-left corner, measured from the boxes drawn in the PDF itself. Part II
| has six ATC rows (items 13 to 18): the code, the tax base, the rate and
| the tax withheld. Check with `php artisan forms:calibrate 1601-EQ`.
*/

$comb = fn (float $x, float $to, int $cells, float $y) => ['page' => 1, 'type' => 'comb', 'x' => $x, 'to' => $to, 'cells' => $cells, 'y' => $y];
$text = fn (float $x, float $to, float $y) => ['page' => 1, 'type' => 'text', 'x' => $x, 'to' => $to, 'y' => $y];
$check = fn (float $x, float $y) => ['page' => 1, 'type' => 'check', 'x' => $x, 'y' => $y];
$base = fn (float $y) => ['page' => 1, 'type' => 'amount', 'x' => 103.9, 'to' => 275.7, 'cells' => 12, 'cents' => [290.1, 318.8], 'y' => $y];
$withheld = fn (float $y) => ['page' => 1, 'type' => 'amount', 'x' => 390.4, 'to' => 548.9, 'cells' => 11, 'cents' => [564.0, 593.8], 'y' => $y];

return [
    '1' => $comb(31.8, 89.4, 4, 125.0),
    '2.1' => $check(117.6, 114.1),
    '2.2' => $check(161.6, 114.5),
    '2.3' => $check(204.6, 114.6),
    '2.4' => $check(248.6, 114.1),
    '3.no' => $check(349.6, 114.4),
    '4.yes' => $check(406.6, 114.1),
    '4.no' => $check(449.6, 114.2),

    'tin.1' => $comb(232.8, 275.8, 3, 158.0),
    'tin.2' => $comb(290.2, 333.2, 3, 158.0),
    'tin.3' => $comb(347.6, 390.6, 3, 158.0),
    '7' => $comb(548.9, 593.8, 3, 158.0),
    '8' => $comb(16.9, 593.8, 40, 187.5),
    '9.1' => $comb(16.9, 593.8, 40, 213.5),
    '9.2' => $comb(16.9, 462.2, 31, 230.8),
    '9A' => $comb(533.9, 593.8, 4, 230.8),
    '10' => $comb(103.6, 275.8, 12, 249.5),
    '11.private' => $check(447.5, 238.1),
    '11.government' => $check(519.6, 238.1),
    '12' => $comb(103.6, 593.8, 34, 268.5),

    '13.atc' => $comb(32.4, 103.9, 5, 310.5),
    '13.base' => $base(310.5),
    '13.rate' => $text(318.8, 390.4, 310.5),
    '13.withheld' => $withheld(310.5),
    '14.atc' => $comb(32.4, 103.9, 5, 328.0),
    '14.base' => $base(328.0),
    '14.rate' => $text(318.8, 390.4, 328.0),
    '14.withheld' => $withheld(328.0),
    '15.atc' => $comb(32.4, 103.9, 5, 345.5),
    '15.base' => $base(345.5),
    '15.rate' => $text(318.8, 390.4, 345.5),
    '15.withheld' => $withheld(345.5),
    '16.atc' => $comb(32.4, 103.9, 5, 363.0),
    '16.base' => $base(363.0),
    '16.rate' => $text(318.8, 390.4, 363.0),
    '16.withheld' => $withheld(363.0),
    '17.atc' => $comb(32.4, 103.9, 5, 380.5),
    '17.base' => $base(380.5),
    '17.rate' => $text(318.8, 390.4, 380.5),
    '17.withheld' => $withheld(380.5),
    '18.atc' => $comb(32.4, 103.9, 5, 398.0),
    '18.base' => $base(398.0),
    '18.rate' => $text(318.8, 390.4, 398.0),
    '18.withheld' => $withheld(398.0),

    '19' => $withheld(415.0),
    '20' => $withheld(432.7),
    '21' => $withheld(450.0),
    '25' => $withheld(520.0),
    '26' => $withheld(537.5),
    '31' => $withheld(624.5),
];
