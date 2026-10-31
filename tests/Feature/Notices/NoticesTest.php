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

test('what needs attention now leads the notifications', function () {
    $this->get('/notifications')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('notifications')
        ->where('new.0.title', '₱14,000 due in 20 days')
        ->where('new.0.detail', 'Q3 income tax · file and pay by Mon, Nov 16')
        ->where('new.0.url', '/file/2026/3/1701Q')
        ->where('new.1.title', 'Time for a backup')
        ->where('new.2.title', 'Acme Co.’s 2307 is missing')
        ->where('earlier', []));
});

test('a payment that moves the estimate is noted', function () {
    $this->post('/transactions', [
        'type' => 'payment', 'occurred_on' => '2026-10-27', 'counterparty' => 'Studio Uno',
        'amount' => '10000', 'tax_withheld' => '0',
    ])->assertSessionHasNoErrors();

    expect(Notice::query()->sole())->toMatchArray([
        'kind' => 'estimate',
        'title' => '2026 annual estimate went up ₱800',
        'detail' => 'After Studio Uno’s payment',
    ]);
});

test('an expense on 8% moves nothing, so nothing is noted', function () {
    $this->post('/transactions', [
        'type' => 'expense', 'occurred_on' => '2026-10-27', 'counterparty' => 'Globe',
        'amount' => '1500', 'tax_withheld' => '0', 'category' => 'internet',
    ])->assertSessionHasNoErrors();

    expect(Notice::query()->count())->toBe(0);
});

test('filing, paying and 2307s are noted', function () {
    $this->post('/filings', ['year' => 2026, 'quarter' => 3, 'form' => '1701Q', 'filed_on' => '2026-10-27', 'paid' => true, 'amount_paid' => 14000, 'paid_on' => '2026-10-27', 'payment_channel' => 'online'])
        ->assertSessionHasNoErrors();
    $this->post('/certificates', [
        'payor_name' => 'Acme Co.', 'payor_tin' => '008-123-456-000', 'year' => 2026, 'quarter' => 3,
        'amount_paid' => '20000', 'tax_withheld' => '1000', 'atc_code' => 'WI010',
    ])->assertSessionHasNoErrors();

    expect(Notice::query()->orderBy('id')->pluck('title')->all())->toBe([
        'Q3 1701Q filed and paid · ₱14,000',
        '2307 from Acme Co. matched',
    ]);
});

test('mark all read clears the count; new things count again', function () {
    $this->get('/home')->assertInertia(fn (Assert $page) => $page->where('unreadNotices', 3));

    $this->post('/notifications/read');
    $this->get('/home')->assertInertia(fn (Assert $page) => $page->where('unreadNotices', 0));
    // What still needs attention stays at the top.
    $this->get('/notifications')->assertInertia(fn (Assert $page) => $page->has('new', 3));

    $this->travel(1)->minutes();
    $this->post('/transactions', [
        'type' => 'payment', 'occurred_on' => '2026-10-27', 'counterparty' => 'Studio Uno',
        'amount' => '10000', 'tax_withheld' => '0',
    ]);

    $this->get('/home')->assertInertia(fn (Assert $page) => $page->where('unreadNotices', 1));
});

test('erasing everything clears the notifications', function () {
    Notice::query()->create(['kind' => 'backup', 'title' => 'Backup saved']);
    Setting::put('backup_reminder', false);

    $this->delete('/data', ['confirmation' => 'ERASE']);

    expect(Notice::query()->count())->toBe(0);
});
