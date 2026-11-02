<?php

use App\Data\Models\RecordChange;
use App\Data\Services\LocalData;
use App\Payroll\Models\Employee;
use App\Payroll\Models\EmployeeCertificate;
use App\Returns\Models\Filing;
use App\Transactions\Models\Transaction;
use Carbon\CarbonImmutable;
use Database\Seeders\DemoTaxpayerSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-27 19:00'));
    $this->seed(DemoTaxpayerSeeder::class);
});

test('adding, editing and deleting a record leaves its history, which can’t be changed', function () {
    $sale = Transaction::factory()->on('2026-10-10')->create(['counterparty' => 'Kape Collective', 'amount' => 12_000]);
    $sale->update(['amount' => 15_000]);
    $sale->delete();

    $history = RecordChange::query()->where('subject', 'transaction')->where('subject_id', $sale->id)->orderBy('id')->get();

    expect($history->pluck('event')->all())->toBe(['created', 'updated', 'deleted'])
        ->and($history[1]->before)->toMatchArray(['amount' => 12000, 'counterparty' => 'Kape Collective'])
        ->and($history[1]->after)->toBe(['amount' => 15000])
        ->and($history[2]->before)->toMatchArray(['counterparty' => 'Kape Collective', 'amount' => '15000.00'])
        ->and($history[0]->label)->toBe('Payment · Kape Collective · ₱12,000.00 · 2026-10-10')
        ->and(fn () => $history[0]->update(['label' => 'x']))->toThrow(LogicException::class)
        ->and(fn () => $history[0]->delete())->toThrow(LogicException::class);
});

test('a filed return lists the changes since to the records it was computed from', function () {
    Filing::query()->where('year', 2026)->where('quarter', 3)->where('form', '1701Q')->delete();
    $this->post('/filings', ['year' => 2026, 'quarter' => 3, 'form' => '1701Q', 'filed_on' => '2026-10-27', 'paid' => false])->assertSessionHasNoErrors();
    $this->travel(5)->minutes();

    $september = Transaction::query()->where('type', 'payment')->whereBetween('occurred_on', ['2026-09-01', '2026-09-30'])->firstOrFail();
    $this->put("/transactions/{$september->id}", [
        'type' => 'payment', 'occurred_on' => $september->occurred_on->toDateString(), 'counterparty' => $september->counterparty,
        'amount' => (float) $september->amount + 1_000, 'tax_withheld' => (float) $september->tax_withheld,
    ])->assertSessionHasNoErrors();
    // October is after Q3: not part of it.
    Transaction::query()->whereBetween('occurred_on', ['2026-10-01', '2026-10-31'])->firstOrFail()->delete();

    $this->get('/history?year=2026&quarter=3&form=1701Q')->assertInertia(fn (Assert $page) => $page
        ->has('detail.changes', 1)
        ->where('detail.changes.0.event', 'updated')
        ->where('detail.changes.0.cause', 'Edited on Transactions')
        ->where('detail.changes.0.fields.0.field', 'amount'));
});

test('the history is kept in backups', function () {
    expect(LocalData::TABLES)->toContain('record_changes')
        ->and(app(LocalData::class)->rows()['record_changes'])->not->toBeEmpty();
});

test('removing an employee records their 2316s going with them', function () {
    $employee = Employee::query()->create(['first_name' => 'Joy', 'last_name' => 'Ramos', 'monthly_pay' => 30_000, 'started_on' => '2026-01-01']);
    EmployeeCertificate::query()->create(['employee_id' => $employee->id, 'year' => 2025, 'given_on' => '2026-01-20']);

    $this->delete("/payroll/employees/{$employee->id}")->assertRedirect();

    expect(RecordChange::query()->where('event', 'deleted')->whereIn('subject', ['employee', 'employee_certificate'])->orderBy('id')->pluck('subject')->all())
        ->toBe(['employee_certificate', 'employee']);
});
