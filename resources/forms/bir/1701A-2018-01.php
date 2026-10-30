<?php

/*
| Field map: BIR Form 1701A, January 2018 (ENCS).
|
| Where each box sits on 1701A-2018-01.pdf, in points from the page's
| top-left corner, measured from the boxes drawn in the PDF itself. See
| App\Returns\Forms\OfficialFormFiller for the placement types; check the
| result with `php artisan forms:calibrate 1701A`.
*/

$comb = fn (int $page, float $x, float $to, int $cells, float $y) => ['page' => $page, 'type' => 'comb', 'x' => $x, 'to' => $to, 'cells' => $cells, 'y' => $y];
$check = fn (int $page, float $x, float $y) => ['page' => $page, 'type' => 'check', 'x' => $x, 'y' => $y];
// Part II (page 1) and Part IV (page 2), column A) Taxpayer/Filer.
$partTwo = fn (float $y) => ['page' => 1, 'type' => 'amount', 'x' => 358.6, 'to' => 478.9, 'cells' => 8, 'y' => $y];
$partFour = fn (float $y) => ['page' => 2, 'type' => 'amount', 'x' => 356.5, 'to' => 477.8, 'cells' => 8, 'y' => $y];

return [
    // Page 1 header.
    '1' => $comb(1, 114.0, 197.6, 6, 124.5),
    '2.yes' => $check(1, 299.8, 110.9),
    '2.no' => $check(1, 343.4, 110.9),
    '3.yes' => $check(1, 493.6, 111.1),
    '3.no' => $check(1, 543.4, 111.0),

    // Part I: background information on the taxpayer.
    'tin.1' => $comb(1, 28.7, 70.2, 3, 169.5),
    'tin.2' => $comb(1, 84.2, 126.5, 3, 169.5),
    'tin.3' => $comb(1, 140.7, 183.2, 3, 169.5),
    '5' => $comb(1, 298.2, 342.3, 3, 169.5),
    '6.single_proprietor' => $check(1, 393.2, 155.5),
    '6.professional' => $check(1, 508.6, 156.6),
    'atc.II012' => $check(1, 159.5, 175.0),
    'atc.II014' => $check(1, 348.0, 175.0),
    'atc.II015' => $check(1, 159.2, 188.8),
    'atc.II017' => $check(1, 347.8, 188.0),
    '8' => $comb(1, 14.9, 599.0, 40, 230.5),
    '9.1' => $comb(1, 14.9, 599.0, 40, 259.5),
    '9.2' => $comb(1, 14.9, 462.9, 31, 278.5),
    '9A' => $comb(1, 538.8, 599.0, 4, 278.5),
    '10' => $comb(1, 14.9, 126.5, 8, 306.5),
    '11' => $comb(1, 126.5, 599.0, 32, 306.5),
    '12' => $comb(1, 14.9, 240.6, 16, 335.5),
    '13.yes' => $check(1, 270.4, 323.3),
    '13.no' => $check(1, 312.8, 323.5),
    '15' => $comb(1, 14.9, 197.6, 13, 364.5),
    '19.graduated_osd' => $check(1, 82.2, 388.9),
    '19.eight_percent' => $check(1, 269.4, 389.3),

    // Part II: total tax payable.
    '20A' => $partTwo(448.5),
    '21A' => $partTwo(467.5),
    '22A' => $partTwo(485.5),
    '23A' => $partTwo(503.5),
    '24A' => $partTwo(521.5),
    '25A' => $partTwo(540.5),
    '26A' => $partTwo(558.5),
    '27A' => $partTwo(576.5),
    '28A' => $partTwo(594.5),
    '29A' => $partTwo(613.5),
    '30' => ['page' => 1, 'type' => 'amount', 'x' => 418.2, 'to' => 540.8, 'cells' => 8, 'y' => 631.5],

    // Page 2 header.
    'p2.tin' => $comb(2, 14.9, 135.4, 9, 106.5),
    'p2.last_name' => ['page' => 2, 'type' => 'text', 'x' => 208.8, 'to' => 598.9, 'y' => 106.5],

    // Part IV.A: graduated rates with OSD.
    '36A' => $partFour(158.5),
    '37A' => $partFour(175.5),
    '38A' => $partFour(192.5),
    '39A' => $partFour(208.5),
    '40A' => $partFour(225.5),
    '43A' => $partFour(290.5),
    '44A' => $partFour(306.5),
    '45A' => $partFour(322.5),
    '46A' => $partFour(338.5),

    // Part IV.B: 8% rate.
    '47A' => $partFour(367.5),
    '48A' => $partFour(384.5),
    '49A' => $partFour(401.5),
    '52A' => $partFour(466.5),
    '53A' => $partFour(483.5),
    '54A' => $partFour(502.5),
    '55A' => $partFour(519.5),
    '56A' => $partFour(536.5),

    // Part IV.C: tax credits and payments.
    '57A' => $partFour(564.5),
    '58A' => $partFour(580.5),
    '59A' => $partFour(596.5),
    '60A' => $partFour(612.5),
    '61A' => $partFour(628.5),
    '62A' => $partFour(644.5),
    '63A' => $partFour(660.5),
    '64A' => $partFour(675.5),
    '65A' => $partFour(692.5),
];
