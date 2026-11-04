<?php

use App\Tax\Services\RuleReview;
use Carbon\CarbonImmutable;
use Database\Seeders\DemoTaxpayerSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-27 19:00'));
    $this->seed(DemoTaxpayerSeeder::class);
});

$signOff = ['by' => 'Maria Santos, CPA', 'on' => '2026-11-20', 'source' => 'RR 8-2018; NIRC Sec. 24'];

test('a sign-off counts only with who, a date and the source', function () use ($signOff) {
    expect(RuleReview::signOff($signOff))->toBe($signOff)
        ->and(RuleReview::signOff(['by' => 'Maria Santos, CPA', 'on' => 'last week', 'source' => 'RR 8-2018']))->toBeNull()
        ->and(RuleReview::signOff(['by' => '', 'on' => '2026-11-20', 'source' => 'RR 8-2018']))->toBeNull()
        ->and(RuleReview::signOff(null))->toBeNull();
});

test('the pack is reviewed once every part is signed off, and each rule shows its own', function () use ($signOff) {
    expect(RuleReview::packReviewed())->toBeFalse();

    foreach (array_keys(config('tax.parts')) as $part) {
        config(["tax.parts.{$part}.reviewed" => $signOff]);
    }
    config(['tax.rules.IT-8PCT-PURE.reviewed' => $signOff]);

    expect(RuleReview::packReviewed())->toBeTrue();

    $this->get('/tax/3')->assertInertia(fn (Assert $page) => $page
        ->where('rulePack.reviewed', true)
        ->where('explanation.rules.0.id', 'IT-8PCT-PURE')
        ->where('explanation.rules.0.reviewed', $signOff));
    $this->get('/rules')->assertInertia(fn (Assert $page) => $page->where('parts.0.reviewed', $signOff));
});

test('rules:status lists what is left to sign off, and --strict fails until it is done', function () use ($signOff) {
    $this->artisan('rules:status')->expectsOutputToContain('not yet reviewed')->assertSuccessful();
    $this->artisan('rules:status --strict')->assertFailed();

    foreach (array_keys(config('tax.parts')) as $part) {
        config(["tax.parts.{$part}.reviewed" => $signOff]);
    }
    foreach (array_keys(config('tax.rules')) as $rule) {
        config(["tax.rules.{$rule}.reviewed" => $signOff]);
    }

    $this->artisan('rules:status --strict')->expectsOutputToContain('Maria Santos, CPA')->assertSuccessful();
});
