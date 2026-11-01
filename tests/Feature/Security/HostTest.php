<?php

use Carbon\CarbonImmutable;
use Database\Seeders\DemoTaxpayerSeeder;

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-27 19:00'));
    $this->seed(DemoTaxpayerSeeder::class);
});

test('the app answers on this computer, by name or address', function (string $origin) {
    $this->get("{$origin}/home")->assertOk();
})->with(['http://localhost', 'http://localhost:8000', 'http://127.0.0.1:8000', 'http://[::1]:8000']);

test('a request addressed to another host gets nothing (DNS rebinding)', function (string $url) {
    $this->get($url)->assertStatus(400)->assertDontSee('Lumen Digital');
})->with([
    'a page' => 'http://evil.example/home',
    'a download' => 'http://evil.example/books/2026/10/pdf',
    'an export' => 'http://rebind.attacker.test:8000/transactions/search?export=csv',
    'the health check' => 'http://evil.example/up',
]);

test('the host in APP_URL is trusted too', function () {
    config(['app.url' => 'http://bir-tax-engine.test']);

    $this->get('http://bir-tax-engine.test/home')->assertOk();
    $this->get('http://other.test/home')->assertStatus(400);
});
