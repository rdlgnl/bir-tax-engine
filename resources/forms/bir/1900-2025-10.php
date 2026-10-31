<?php

/*
| Field map: BIR Form 1900, October 2025 (ENCS).
|
| Where each box sits on 1900-2025-10.pdf, in points from the page's
| top-left corner, measured from the boxes drawn in the PDF itself. The
| page is 612 × 1008 (legal), longer than the returns. See
| App\Returns\Forms\OfficialFormFiller for the placement types; check the
| result with `php artisan forms:calibrate 1900`.
*/

$comb = fn (float $x, float $to, int $cells, float $y) => ['page' => 1, 'type' => 'comb', 'x' => $x, 'to' => $to, 'cells' => $cells, 'y' => $y];
$text = fn (float $x, float $to, float $y) => ['page' => 1, 'type' => 'text', 'x' => $x, 'to' => $to, 'y' => $y];
$check = fn (float $x, float $y) => ['page' => 1, 'type' => 'check', 'x' => $x, 'y' => $y];
// 7: the head office, as the first row of the list of branches.
$branch = fn (float $x, float $to) => $text($x, $to, 304.0);
// 9A: one book per row.
$book = fn (int $row) => $text(31.3, 201.6, 444.0 + 14.65 * $row);

return [
    'tin.1' => $comb(215.6, 259.1, 3, 143.5),
    'tin.2' => $comb(273.6, 316.9, 3, 143.5),
    'tin.3' => $comb(331.4, 375.0, 3, 143.5),
    '2' => $comb(549.3, 593.1, 3, 143.5),
    '3' => $text(30.6, 578.8, 171.0),
    '4.1' => $text(30.6, 463.6, 201.0),
    '4.2' => $text(30.6, 463.6, 221.5),
    '4A' => $text(535.0, 578.8, 221.5),
    '5' => $text(30.6, 172.7, 251.0),
    '6' => $text(186.9, 578.8, 251.0),

    '7.tin' => $branch(31.7, 143.2),
    '7.branch' => $branch(143.7, 187.0),
    '7.name' => $branch(187.5, 345.6),
    '7.rdo' => $branch(346.1, 389.0),
    '7.address' => $branch(389.5, 578.4),

    '8.books' => $check(32.9, 385.6),

    '9A.1' => $book(0),
    '9A.2' => $book(1),
    '9A.3' => $book(2),
    '9A.4' => $book(3),

    // 10: the printed name above the signature line.
    '10' => $text(60.0, 270.0, 640.0),
];
