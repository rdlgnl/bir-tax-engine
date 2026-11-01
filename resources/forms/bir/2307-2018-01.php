<?php

/*
| Field map: BIR Form 2307, January 2018 (ENCS).
|
| Where each box sits on 2307-2018-01.pdf, in points from the page's
| top-left corner, measured from the lines drawn in the PDF itself. Part III
| is a table of open cells: amounts are written against the cell's right
| edge. The first three rows are mapped. Check with
| `php artisan forms:calibrate 2307`.
*/

$comb = fn (float $x, float $to, int $cells, float $y) => ['page' => 1, 'type' => 'comb', 'x' => $x, 'to' => $to, 'cells' => $cells, 'y' => $y];
$text = fn (float $x, float $to, float $y) => ['page' => 1, 'type' => 'text', 'x' => $x, 'to' => $to, 'y' => $y];
$figure = fn (float $x, float $to, float $y) => ['page' => 1, 'type' => 'figure', 'x' => $x, 'to' => $to, 'y' => $y];

return [
    // 1: the quarter, MM/DD/YYYY.
    '1.from' => $comb(151.0, 257.8, 8, 120.0),
    '1.to' => $comb(398.6, 505.3, 8, 120.0),

    // Part I: payee.
    'payee.tin.1' => $comb(205.9, 246.1, 3, 151.0),
    'payee.tin.2' => $comb(257.6, 297.7, 3, 151.0),
    'payee.tin.3' => $comb(309.4, 349.5, 3, 151.0),
    'payee.name' => $text(34.0, 590.0, 177.0),
    'payee.address' => $text(34.0, 535.0, 206.5),
    'payee.zip' => $comb(540.5, 593.9, 4, 206.5),

    // Part II: payor.
    'payor.tin.1' => $comb(206.3, 246.4, 3, 266.5),
    'payor.tin.2' => $comb(258.2, 298.4, 3, 266.5),
    'payor.tin.3' => $comb(310.1, 350.3, 3, 266.5),
    'payor.name' => $text(34.0, 590.0, 292.5),
    'payor.address' => $text(34.0, 535.0, 321.8),
    'payor.zip' => $comb(540.5, 593.9, 4, 321.8),

    // Part III: income payments subject to expanded withholding.
    'row1.description' => $text(19.4, 176.9, 375.5),
    'row1.atc' => $text(176.9, 218.7, 375.5),
    'row1.month1' => $figure(218.7, 292.5, 375.5),
    'row1.month2' => $figure(292.5, 366.3, 375.5),
    'row1.month3' => $figure(366.3, 438.3, 375.5),
    'row1.total' => $figure(438.3, 510.3, 375.5),
    'row1.withheld' => $figure(510.3, 594.0, 375.5),
    'row2.description' => $text(19.4, 176.9, 389.2),
    'row2.atc' => $text(176.9, 218.7, 389.2),
    'row2.month1' => $figure(218.7, 292.5, 389.2),
    'row2.month2' => $figure(292.5, 366.3, 389.2),
    'row2.month3' => $figure(366.3, 438.3, 389.2),
    'row2.total' => $figure(438.3, 510.3, 389.2),
    'row2.withheld' => $figure(510.3, 594.0, 389.2),
    'row3.description' => $text(19.4, 176.9, 402.9),
    'row3.atc' => $text(176.9, 218.7, 402.9),
    'row3.month1' => $figure(218.7, 292.5, 402.9),
    'row3.month2' => $figure(292.5, 366.3, 402.9),
    'row3.month3' => $figure(366.3, 438.3, 402.9),
    'row3.total' => $figure(438.3, 510.3, 402.9),
    'row3.withheld' => $figure(510.3, 594.0, 402.9),
    'total.month1' => $figure(218.7, 292.5, 512.5),
    'total.month2' => $figure(292.5, 366.3, 512.5),
    'total.month3' => $figure(366.3, 438.3, 512.5),
    'total.total' => $figure(438.3, 510.3, 512.5),
    'total.withheld' => $figure(510.3, 594.0, 512.5),
];
