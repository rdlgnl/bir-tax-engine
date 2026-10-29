<?php

use App\Profiles\Enums\IncomeSource;
use App\Profiles\Enums\SetupStep;
use App\Profiles\Models\TaxProfile;
use App\Tax\Enums\Regime;

test('a freelancer on 8% skips the expenses step', function () {
    $profile = new TaxProfile;
    $profile->income_source = IncomeSource::Business;
    $profile->regime = Regime::EightPercent;

    expect(SetupStep::flow($profile))->not->toContain(SetupStep::Expenses)
        ->and(SetupStep::Method->next($profile))->toBe(SetupStep::Withholding)
        ->and(SetupStep::Withholding->previous($profile))->toBe(SetupStep::Method);
});

test('graduated rates add the expenses step', function () {
    $profile = new TaxProfile;
    $profile->income_source = IncomeSource::Both;
    $profile->regime = Regime::Graduated;

    expect(SetupStep::Method->next($profile))->toBe(SetupStep::Expenses);
});

test('salaried people only answer three steps', function () {
    $profile = new TaxProfile;
    $profile->income_source = IncomeSource::Employed;

    expect(SetupStep::flow($profile))->toBe([SetupStep::Income, SetupStep::About, SetupStep::Summary])
        ->and(SetupStep::Income->previous($profile))->toBeNull();
});
