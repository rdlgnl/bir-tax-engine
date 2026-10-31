<?php

/*
| Field map: BIR Form 1905, October 2025 (ENCS).
|
| Where each box sits on 1905-2025-10.pdf, in points from the page's
| top-left corner, measured from the boxes drawn in the PDF itself. Only
| the items the app fills are mapped: the taxpayer, the 8% option, and
| cancelling a tax type. See App\Returns\Forms\OfficialFormFiller for the
| placement types; check the result with `php artisan forms:calibrate 1905`.
*/

$comb = fn (int $page, float $x, float $to, int $cells, float $y) => ['page' => $page, 'type' => 'comb', 'x' => $x, 'to' => $to, 'cells' => $cells, 'y' => $y];
$text = fn (int $page, float $x, float $to, float $y) => ['page' => $page, 'type' => 'text', 'x' => $x, 'to' => $to, 'y' => $y];
$check = fn (int $page, float $x, float $y) => ['page' => $page, 'type' => 'check', 'x' => $x, 'y' => $y];

return [
    // Part I: taxpayer information.
    'tin.1' => $comb(1, 31.6, 74.5, 3, 162.5),
    'tin.2' => $comb(1, 88.8, 131.8, 3, 162.5),
    'tin.3' => $comb(1, 146.3, 189.6, 3, 162.5),
    '2' => $comb(1, 305.1, 348.2, 3, 162.5),
    '3' => $text(1, 377.1, 579.4, 162.5),
    '4' => $text(1, 23.0, 589.0, 191.0),

    // Part II, 6: other updates.
    '6.books' => $check(1, 422.8, 267.2),
    '6.eight_percent' => $check(1, 422.8, 285.8),

    // 7G (page 2): a cancelled tax type. The ATC column is for the BIR.
    '7G' => $check(2, 32.3, 212.4),
    '7G.cancelled' => $text(2, 45.8, 232.9, 262.5),
    '7G.cancelled_form' => $text(2, 232.9, 348.2, 262.5),
    '7G.cancelled_date' => $comb(2, 463.8, 579.4, 8, 262.5),

    // 11 (page 3): other update, in words, and when it takes effect.
    '11.1' => $text(3, 23.0, 589.0, 524.0),
    '11.2' => $text(3, 23.0, 589.0, 542.0),
    '11.date' => $comb(3, 45.9, 161.2, 8, 573.5),

    // 12: the printed name above the signature line.
    '12' => $text(3, 45.0, 245.0, 677.0),
];
