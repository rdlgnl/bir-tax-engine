<?php

use App\Data\Models\Setting;
use App\Notices\Models\Notice;
use Carbon\CarbonImmutable;
use Database\Seeders\DemoTaxpayerSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-27 19:00'));
    $this->seed(DemoTaxpayerSeeder::class);
});

test('Home keeps a note of the numbers under the pack in use', function () {
    $this->get('/home')->assertInertia(fn (Assert $page) => $page->where('ruleUpdate', null));

    expect(Setting::get('rule_pack_seen'))->toMatchArray([
        'version' => '2026.10',
        'numbers' => ['quarter_label' => 'Q4 / annual', 'quarter' => 6100, 'annual' => 6100, 'next_deadline' => '2026-11-16'],
    ]);
});

test('a new pack shows what changed and the numbers before and after', function () {
    $this->get('/home');
    config(['tax.version' => '2026.11', 'tax.changes' => ['2026.11' => [
        ['area' => 'Deadline calendar', 'title' => '2027 holidays added', 'summary' => 'Deadlines on them move to the next working day.'],
    ]]]);

    $this->get('/home')->assertInertia(fn (Assert $page) => $page->where('ruleUpdate', '2026.11'));
    $this->get('/rules')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('tax/rules')
        ->where('pending', true)
        ->where('previousVersion', '2026.10')
        ->where('changes.0.title', '2027 holidays added')
        ->where('before.annual', 6100)
        ->where('after.annual', 6100));

    $this->post('/rules/seen')->assertRedirect('/home');

    expect(Notice::query()->sole()->title)->toBe('Rule pack 2026.11 installed · no change to your numbers');
    $this->get('/home')->assertInertia(fn (Assert $page) => $page->where('ruleUpdate', null));
});

test('the rules page also describes the pack in use', function () {
    $this->get('/rules')->assertInertia(fn (Assert $page) => $page
        ->where('version', '2026.10')
        ->where('pending', false)
        ->where('before', null)
        ->has('changes', 3));
});
