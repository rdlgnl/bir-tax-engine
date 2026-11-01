<?php

use App\Profiles\Models\TaxProfile;
use App\Returns\Forms\OfficialFormFiller;
use App\Returns\Forms\OfficialForms;
use App\Returns\Models\Filing;
use App\Returns\Services\ReturnForm;
use App\Tax\Enums\DeductionMethod;
use App\Transactions\Models\Transaction;
use Carbon\CarbonImmutable;
use Database\Seeders\DemoTaxpayerSeeder;
use Inertia\Testing\AssertableInertia as Assert;
use setasign\Fpdi\Fpdi;
use setasign\Fpdi\PdfParser\StreamReader;

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-27 19:00'));
});

function pageCount(string $pdf): int
{
    return (new Fpdi)->setSourceFile(StreamReader::createByString($pdf));
}

function formValues(string $form, int $year, int $quarter): array
{
    $snapshot = app(ReturnForm::class)->snapshot(TaxProfile::current(), $year, $quarter, $form);

    return app(OfficialForms::class)->values($snapshot);
}

test('every form in the rule pack has its BIR template, as downloaded, readable page by page', function (string $form) {
    $template = config("tax.forms.{$form}.template");
    $manifest = json_decode(file_get_contents(resource_path('forms/bir/manifest.json')), true);
    $filler = app(OfficialFormFiller::class);

    expect($manifest[$template]['form'])->toBe($form)
        ->and($manifest[$template]['edition'])->toBe(config("tax.forms.{$form}.edition"))
        ->and(hash_file('sha256', resource_path("forms/bir/originals/{$template}.pdf")))->toBe($manifest[$template]['sha256'])
        ->and(pageCount(file_get_contents($filler->template($form))))->toBe(['1701Q' => 2, '1701-MS' => 2, '1701A' => 2, '1701' => 4, '2551Q' => 2, '2550Q' => 2, '1905' => 4, '1900' => 1, '0619-E' => 1, '1601-EQ' => 2, '1604-E' => 2, '2307' => 2][$form]);

    // The 1900 and 2550Q are legal size, the 0619-E letter; the rest are long bond.
    $height = match ($form) {
        '1900', '2550Q' => 1008,
        '0619-E' => 792,
        default => 936,
    };

    foreach ($filler->map($form) as $item => $place) {
        expect($place['x'])->toBeBetween(0, 612, "item {$item}")
            ->and($place['to'] ?? $place['x'])->toBeBetween($place['x'], 612, "item {$item}")
            ->and($place['y'])->toBeBetween(0, $height, "item {$item}");
    }
})->with(['1701Q', '1701-MS', '1701A', '1701', '2551Q', '2550Q', '1905', '1900', '0619-E', '1601-EQ', '1604-E', '2307']);

test('the Q3 1701Q on 8% fills every item the design shows', function () {
    $this->seed(DemoTaxpayerSeeder::class);

    expect(formValues('1701Q', 2026, 3))->toMatchArray([
        '1' => '2026', '2.3' => true, '3.no' => true,
        'tin.1' => '123', 'tin.2' => '456', 'tin.3' => '789', '6' => '028',
        '7.single_proprietor' => true, 'atc.II015' => true, '16.eight_percent' => true,
        '9' => 'DELA CRUZ, JUAN SANTOS',
        '10.1' => '12 MABINI ST., BRGY. MALAYA, QUEZON CITY', '10A' => '1101',
        '11' => '06151994', '12' => 'JUAN@EXAMPLE.COM', '13' => 'FILIPINO',
        'p2.tin' => '123456789', 'p2.last_name' => 'DELA CRUZ',
        '47A' => 200000, '49A' => 200000, '50A' => 400000, '51A' => 600000,
        '52A' => 250000, '53A' => 350000, '54A' => 28000,
        '56A' => 4000, '57A' => 8000, '58A' => 2000, '62A' => 14000, '63A' => 14000, '68A' => 14000,
        '26A' => 28000, '27A' => 14000, '28A' => 14000, '30A' => 14000, '31' => 14000,
    ]);
});

test('a graduated 1701Q declares this quarter and carries the previous quarter forward', function () {
    TaxProfile::factory()->graduated(DeductionMethod::Osd)->create(['profession' => 'cpa_lawyer']);
    Transaction::factory()->on('2026-05-10')->create(['amount' => 300_000]);
    Transaction::factory()->on('2026-08-10')->create(['amount' => 200_000]);

    expect(formValues('1701Q', 2026, 3))->toMatchArray([
        '7.professional' => true, 'atc.II014' => true, '16.graduated' => true, '16A.osd' => true,
        '36A' => 200000, '38A' => 200000, '40A' => 80000, '41A' => 120000,
        '42A' => 180000, '45A' => 300000, '46A' => 7500,
        '62A' => 0, '63A' => 7500, '26A' => 7500, '30A' => 7500,
    ])->not->toHaveKey('39A');
});

test('the income tax forms are in whole pesos and their totals add up as printed', function () {
    TaxProfile::factory()->create();
    Transaction::factory()->on('2026-02-10')->create(['amount' => 300_000.50]);

    $values = formValues('1701Q', 2026, 1);

    expect($values)->toMatchArray(['47A' => 300001, '51A' => 300001, '52A' => 250000, '53A' => 50001, '54A' => 4000, '63A' => 4000])
        ->and($values['53A'])->toBe($values['51A'] - $values['52A']);
});

test('the annual 1701A takes the whole year', function () {
    $this->seed(DemoTaxpayerSeeder::class);

    expect(formValues('1701A', 2026, 4))->toMatchArray([
        '1' => '122026', '15' => '09171234567', '19.eight_percent' => true,
        '47A' => 695000, '49A' => 695000, '53A' => 695000, '54A' => 250000, '55A' => 445000, '56A' => 35600,
        '58A' => 18000, '59A' => 10000, '60A' => 1500, '64A' => 29500, '65A' => 6100,
        '20A' => 35600, '21A' => 29500, '22A' => 6100, '29A' => 6100, '30' => 6100,
    ]);
});

test('the 1701-MS on 8% takes the whole year, as the design shows it', function () {
    $this->seed(DemoTaxpayerSeeder::class);

    expect(formValues('1701-MS', 2026, 4))->toMatchArray([
        '1.month' => '12', '1.year' => '2026', '2.no' => true, '3.no' => true,
        '6' => '123-456-789-000', '7' => '028', '8' => 'DELA CRUZ, JUAN SANTOS',
        '11.business' => true, '12.eight_percent' => true, '12.graduated' => false, '18' => 'II015',
        'iv.19' => 695000, 'iv.21' => 695000, 'iv.22' => 250000, 'iv.23' => 445000, 'iv.24' => 35600, 'iv.25' => 35600,
        'v.2' => 18000, 'v.3' => 10000, 'v.4' => 1500, 'v.10' => 29500,
        '22A' => 35600, '23A' => 35600, '24A' => 29500, '25A' => 6100, '27A' => 6100, '29A' => 6100, '30' => 6100,
        'p2.tin' => '123456789', 'p2.last_name' => 'DELA CRUZ',
    ]);
});

test('the 1701-MS on graduated rates uses the OSD or itemized lines', function (DeductionMethod $method, array $lines) {
    TaxProfile::factory()->graduated($method)->create(['profession' => 'cpa_lawyer']);
    Transaction::factory()->on('2026-03-10')->create(['amount' => 500_000]);
    Transaction::factory()->on('2026-03-12')->expense(validInvoice: true)->create(['amount' => 100_000]);

    $values = formValues('1701-MS', 2026, 4);

    expect($values)->toMatchArray(['11.profession' => true, '12.graduated' => true, '18' => 'II014', 'iv.5' => 500000, 'iv.7' => 500000] + $lines)
        ->and($values['iv.15'])->toBe($values['iv.12'])
        ->and($values['22A'])->toBe($values['iv.18B']);
})->with([
    'OSD' => [DeductionMethod::Osd, ['17.osd' => true, 'iv.11' => 200000, 'iv.12' => 300000, 'iv.18B' => 7500]],
    'itemized' => [DeductionMethod::Itemized, ['17.itemized' => true, 'iv.9' => 500000, 'iv.10A' => 100000, 'iv.10D' => 100000, 'iv.12' => 400000, 'iv.18B' => 22500]],
]);

test('a 1701 on itemized deductions fills Schedule 3.A and the credits on page 4', function () {
    TaxProfile::factory()->graduated(DeductionMethod::Itemized)->create(['income_source' => 'both']);
    Transaction::factory()->on('2026-03-10')->create(['amount' => 500_000]);
    Transaction::factory()->on('2026-03-12')->expense(validInvoice: true)->create(['amount' => 100_000]);

    expect(formValues('1701', 2026, 4))->toMatchArray([
        '1' => '2026', '6.single_proprietor' => true, '6.compensation_earner' => true, 'atc.II013' => true,
        '21.graduated' => true, '21A.itemized' => true,
        'S3A.8' => 500000, 'S3A.13' => 100000, 'S3A.16' => 100000, 'S4.18' => 100000,
        'S3A.18' => 400000, 'S3A.24' => 400000, 'S3A.25' => 22500,
        'VI.1' => 22500, 'VI.5' => 22500, '22A' => 22500,
    ]);
});

test('the 2551Q keeps centavos', function () {
    TaxProfile::factory()->graduated()->create();
    Transaction::factory()->on('2026-02-10')->create(['amount' => 180_016.70]);

    expect(formValues('2551Q', 2026, 1))->toMatchArray([
        '1.calendar' => true, '2' => '122026', '3.1' => true, '13.graduated' => true,
        'S1.1.atc' => 'PT010', 'S1.1.taxable' => 180016.7, 'S1.1.rate' => ' 3',
        'S1.1.due' => 5400.5, 'S1.7' => 5400.5, '14' => 5400.5, '19' => 5400.5, '24' => 5400.5,
    ]);
});

test('values land in their boxes: one digit per box, amounts from the right, an X in checkboxes', function () {
    $marks = collect(app(OfficialFormFiller::class)->marks('1701Q', [
        'tin.1' => '123',
        '26A' => 14000,
        '3.no' => true,
        '9' => 'PEÑA, ANA',
        '13' => str_repeat('X', 17),
    ]));

    $tin = $marks->take(3);
    expect($tin->pluck('text')->all())->toBe(['1', '2', '3'])
        ->and($tin->every(fn (array $m) => $m['x'] > 198.6 && $m['x'] < 241.8))->toBeTrue();

    // 8 boxes from 362.4 to 479.2: "14000" fills the last five.
    $amount = $marks->slice(3, 5)->values();
    expect($amount->pluck('text')->implode(''))->toBe('14000')
        ->and($amount->first()['x'])->toBeGreaterThan(362.4 + 3 * 14.6)
        ->and($amount->last()['x'])->toBeGreaterThan(479.2 - 14.6);

    expect($marks->firstWhere('bold', true))->toMatchArray(['text' => 'X', 'page' => 1])
        ->and($marks->pluck('text'))->toContain('Ñ')
        // Too long for its 16 boxes: written across them instead of cut short.
        ->and($marks->last()['text'])->toBe(str_repeat('X', 17));
});

test('a value for a box the form doesn’t have is a bug, not a silent blank', function () {
    app(OfficialFormFiller::class)->marks('1701Q', ['99Z' => 1]);
})->throws(LogicException::class, 'BIR Form 1701Q has no box for item 99Z.');

test('the download is the BIR form itself, every page of it', function (string $url, int $pages) {
    TaxProfile::factory()->graduated()->create();
    Transaction::factory()->on('2026-08-10')->create(['amount' => 200_000]);

    $pdf = $this->get($url)->assertOk()->assertHeader('Content-Type', 'application/pdf')->getContent();

    expect(pageCount($pdf))->toBe($pages);
})->with([
    '1701Q' => ['/file/2026/3/1701Q/pdf', 2],
    '2551Q' => ['/file/2026/3/2551Q/pdf', 2],
]);

test('returns frozen before the official forms take the missing details from the profile', function () {
    $this->seed(DemoTaxpayerSeeder::class);
    $this->post('/filings', ['year' => 2026, 'quarter' => 3, 'form' => '1701Q', 'filed_on' => '2026-10-27', 'paid' => false]);

    $filing = Filing::query()->where('quarter', 3)->sole();
    $old = $filing->snapshot;
    unset($old['previous_figures']);
    $old['taxpayer'] = ['name' => 'JUAN DELA CRUZ', 'tin' => '123-456-789-000', 'rdo' => '028', 'atc' => 'II015'];
    $filing->update(['snapshot' => $old]);

    $snapshot = app(ReturnForm::class)->snapshotOf($filing->fresh(), TaxProfile::current());

    expect(app(OfficialForms::class)->values($snapshot))->toMatchArray(['10A' => '1101', '50A' => 400000, '63A' => 14000]);
    $this->get('/file/2026/3/1701Q/pdf')->assertOk();
});

test('the preview names details that would print blank, and where to add them', function () {
    $this->seed(DemoTaxpayerSeeder::class);
    TaxProfile::current()->update(['registered_address' => null, 'birth_date' => null, 'contact_number' => null]);

    // 1701Q has no contact number box, so that one doesn't matter here.
    $this->get('/file/2026/3/1701Q/form')->assertInertia(fn (Assert $page) => $page
        ->where('layout.edition', 'January 2018 (ENCS)')
        ->where('missingDetails', [
            ['label' => 'registered address', 'step' => 'certificate'],
            ['label' => 'date of birth', 'step' => 'about'],
        ]));
});

test('a profile with only a full name still prints surname first, and setup suggests the split', function () {
    $this->seed(DemoTaxpayerSeeder::class);
    TaxProfile::current()->update(['full_name' => 'Juan Santos Dela Cruz', 'last_name' => null, 'first_name' => null, 'middle_name' => null]);

    expect(formValues('1701Q', 2026, 3))->toMatchArray(['9' => 'DELA CRUZ, JUAN SANTOS', 'p2.last_name' => 'DELA CRUZ']);

    $this->get('/file/2026/3/1701Q/form')->assertInertia(fn (Assert $page) => $page
        ->where('layout.taxpayer.name', 'DELA CRUZ, JUAN SANTOS')
        ->where('missingDetails', []));

    $this->get('/setup/about')->assertInertia(fn (Assert $page) => $page
        ->where('answers.last_name', 'Dela Cruz')
        ->where('answers.first_name', 'Juan Santos')
        ->where('answers.middle_name', null));
});
