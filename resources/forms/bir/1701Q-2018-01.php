<?php

/*
| Field map: BIR Form 1701Q, January 2018 (ENCS).
|
| Where each box sits on 1701Q-2018-01.pdf, in points from the page's
| top-left corner, measured from the boxes drawn in the PDF itself. See
| App\Returns\Forms\OfficialFormFiller for the placement types; check the
| result with `php artisan forms:calibrate 1701Q`.
*/

$comb = fn (int $page, float $x, float $to, int $cells, float $y) => ['page' => $page, 'type' => 'comb', 'x' => $x, 'to' => $to, 'cells' => $cells, 'y' => $y];
$check = fn (int $page, float $x, float $y) => ['page' => $page, 'type' => 'check', 'x' => $x, 'y' => $y];
// Part III (page 1) and Part V (page 2), column A) Taxpayer/Filer.
$partThree = fn (float $y) => ['page' => 1, 'type' => 'amount', 'x' => 362.4, 'to' => 479.2, 'cells' => 8, 'y' => $y];
$partFive = fn (float $y) => ['page' => 2, 'type' => 'amount', 'x' => 355.7, 'to' => 476.2, 'cells' => 8, 'y' => $y];

return [
    // Page 1 header.
    '1' => $comb(1, 87.7, 145.6, 4, 109.5),
    '2.1' => $check(1, 189.6, 95.9),
    '2.2' => $check(1, 233.6, 95.9),
    '2.3' => $check(1, 291.5, 94.8),
    '3.yes' => $check(1, 423.2, 95.8),
    '3.no' => $check(1, 464.9, 96.8),

    // Part I: background information on the taxpayer.
    'tin.1' => $comb(1, 198.6, 241.8, 3, 140.5),
    'tin.2' => $comb(1, 256.6, 299.6, 3, 140.5),
    'tin.3' => $comb(1, 314.2, 357.4, 3, 140.5),
    '6' => $comb(1, 533.0, 581.0, 3, 140.5),
    '7.single_proprietor' => $check(1, 112.9, 145.3),
    '7.professional' => $check(1, 220.4, 145.8),
    'atc.II012' => $check(1, 156.8, 162.4),
    'atc.II014' => $check(1, 302.6, 161.8),
    'atc.II013' => $check(1, 464.5, 163.3),
    'atc.II015' => $check(1, 157.0, 176.3),
    'atc.II017' => $check(1, 303.0, 175.7),
    'atc.II016' => $check(1, 464.8, 176.8),
    '9' => $comb(1, 15.6, 595.5, 40, 216.5),
    '10.1' => $comb(1, 15.6, 595.5, 40, 244.5),
    '10.2' => $comb(1, 15.6, 475.6, 32, 261.5),
    '10A' => $comb(1, 533.0, 595.5, 4, 261.5),
    '11' => $comb(1, 15.6, 127.3, 8, 288.5),
    '12' => $comb(1, 127.3, 595.5, 32, 288.5),
    '13' => $comb(1, 15.6, 241.8, 16, 316.5),
    '15.no' => $check(1, 550.2, 304.3),
    '16.graduated' => $check(1, 59.9, 330.7),
    '16.eight_percent' => $check(1, 60.5, 352.2),
    '16A.itemized' => $check(1, 227.3, 330.4),
    '16A.osd' => $check(1, 358.0, 330.2),

    // Part III: total tax payable.
    '26A' => $partThree(600.5),
    '27A' => $partThree(616.5),
    '28A' => $partThree(632.5),
    '29A' => $partThree(648.5),
    '30A' => $partThree(664.5),
    '31' => ['page' => 1, 'type' => 'amount', 'x' => 420.9, 'to' => 536.9, 'cells' => 8, 'y' => 680.5],

    // Page 2 header.
    'p2.tin' => $comb(2, 13.4, 133.9, 9, 112.5),
    'p2.last_name' => ['page' => 2, 'type' => 'text', 'x' => 207.4, 'to' => 597.5, 'y' => 112.5],

    // Part V, Schedule I: graduated rates.
    '36A' => $partFive(175.5),
    '37A' => $partFive(191.5),
    '38A' => $partFive(207.5),
    '39A' => $partFive(234.5),
    '40A' => $partFive(260.5),
    '41A' => $partFive(276.5),
    '42A' => $partFive(292.5),
    '43A' => $partFive(308.5),
    '44A' => $partFive(325.5),
    '45A' => $partFive(341.5),
    '46A' => $partFive(357.5),

    // Part V, Schedule II: 8% rate.
    '47A' => $partFive(389.5),
    '48A' => $partFive(405.5),
    '49A' => $partFive(421.5),
    '50A' => $partFive(436.5),
    '51A' => $partFive(452.5),
    '52A' => $partFive(470.5),
    '53A' => $partFive(486.5),
    '54A' => $partFive(503.5),

    // Part V, Schedule III: tax credits and payments.
    '55A' => $partFive(534.5),
    '56A' => $partFive(550.5),
    '57A' => $partFive(566.5),
    '58A' => $partFive(582.5),
    '59A' => $partFive(598.5),
    '60A' => $partFive(614.5),
    '61A' => $partFive(630.5),
    '62A' => $partFive(646.5),
    '63A' => $partFive(665.5),

    // Part V, Schedule IV: penalties.
    '64A' => $partFive(697.5),
    '65A' => $partFive(713.5),
    '66A' => $partFive(729.5),
    '67A' => $partFive(745.5),
    '68A' => $partFive(764.5),
];
