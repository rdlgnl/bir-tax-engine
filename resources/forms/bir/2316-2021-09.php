<?php

/*
| Field map: BIR Form 2316, September 2021 (ENCS).
|
| Where each box sits on 2316-2021-09.pdf, in points from the page's
| top-left corner, measured from the white boxes drawn in the PDF itself.
| The form has open boxes rather than character cells, so text sits at the
| box's left and amounts against its right edge. Parts I, II and IV-A are on
| the left; Part IV-B, the details of compensation, on the right.
| Check with `php artisan forms:calibrate 2316`.
*/

$text = fn (float $x, float $to, float $y) => ['page' => 1, 'type' => 'text', 'x' => $x, 'to' => $to, 'y' => $y];
$figure = fn (float $x, float $to, float $y) => ['page' => 1, 'type' => 'figure', 'x' => $x, 'to' => $to, 'y' => $y];
$check = fn (float $x, float $y) => ['page' => 1, 'type' => 'check', 'x' => $x, 'y' => $y];
$summary = fn (float $y) => $figure(207.4, 309.2, $y);
$details = fn (float $y) => $figure(483.8, 585.6, $y);

return [
    '1' => $text(124.8, 195.7, 105.5),
    '2.from' => $text(386.8, 456.7, 105.5),
    '2.to' => $text(512.4, 582.3, 105.5),

    // Part I: the employee.
    '3.1' => $text(86.2, 124.1, 134.0),
    '3.2' => $text(135.9, 173.8, 134.0),
    '3.3' => $text(185.6, 223.5, 134.0),
    '3.branch' => $text(235.2, 306.1, 134.0),
    '4' => $text(42.2, 253.6, 159.5),
    '5' => $text(264.5, 302.5, 159.5),
    '6' => $text(42.6, 253.6, 187.5),
    '6A' => $text(261.4, 309.4, 187.5),

    // Part II: the employer (present).
    '12.1' => $text(86.2, 124.1, 346.5),
    '12.2' => $text(135.9, 173.8, 346.5),
    '12.3' => $text(185.6, 223.5, 346.5),
    '12.branch' => $text(235.2, 306.1, 346.5),
    '13' => $text(42.2, 308.4, 372.0),
    '14' => $text(42.6, 253.6, 398.0),
    '14A' => $text(259.3, 307.3, 398.0),
    '15.main' => $check(115.9, 406.2),
    '15.secondary' => $check(199.6, 406.2),

    // Part IV-A: summary.
    '19' => $summary(523.5),
    '20' => $summary(543.0),
    '21' => $summary(562.5),
    '22' => $summary(582.0),
    '23' => $summary(601.0),
    '24' => $summary(620.5),
    '25A' => $summary(640.0),
    '25B' => $summary(659.5),
    '26' => $summary(679.5),
    '27' => $summary(699.5),
    '28' => $summary(719.0),

    // Part IV-B: details of compensation from the present employer.
    '38' => $details(328.0),
    '39' => $details(365.5),
    '52' => $details(678.0),
];
