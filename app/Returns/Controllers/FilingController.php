<?php

namespace App\Returns\Controllers;

use App\Http\Controllers\Controller;
use App\Notices\Services\Notices;
use App\Profiles\Models\TaxProfile;
use App\Returns\Models\Filing;
use App\Returns\Requests\StoreFilingRequest;
use App\Returns\Requests\UpdateFilingPaymentRequest;
use App\Returns\Services\Amendments;
use App\Returns\Services\ReturnForm;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class FilingController extends Controller
{
    public function store(StoreFilingRequest $request, ReturnForm $returns, Amendments $amendments, Notices $notices): RedirectResponse
    {
        $data = $request->safe()->except(['paid', 'amendment']);
        $profile = TaxProfile::current();

        $withholding = in_array($data['form'], ['0619-E', '1601-EQ', '1604-E', '1601-C'], true);
        $amendment = $request->boolean('amendment')
            ? $amendments->for($profile, (int) $data['year'], (int) $data['quarter'])
            : null;

        if ($request->boolean('amendment') && ($amendment === null || $amendment['form'] !== $data['form'])) {
            return back()->withErrors(['form' => 'This return matches what was filed; there is nothing to amend.']);
        }

        // Freeze the figures as filed, so history never shifts under them.
        $filing = Filing::create([
            ...$data,
            'amendment' => $amendment['number'] ?? 0,
            'snapshot' => match (true) {
                $withholding => null,
                $amendment !== null => $amendments->snapshot($profile, $amendment),
                default => $returns->snapshot($profile, (int) $data['year'], (int) $data['quarter'], $data['form']),
            },
        ]);

        $what = match (true) {
            $filing->amendment > 0 => "Q{$filing->quarter}'s amended return",
            $filing->form === '0619-E' => CarbonImmutable::create($filing->year, $filing->month)->format('F').' withholding',
            $filing->form === '1601-C' => CarbonImmutable::create($filing->year, $filing->month)->format('F').' payroll withholding',
            $filing->form === '1601-EQ' => "Q{$filing->quarter} withholding",
            $filing->form === '1604-E' => "Your {$filing->year} list of payees (1604-E)",
            $filing->quarter === 4 && ! in_array($filing->form, ['2551Q', '2550Q'], true) => "Your {$filing->year} annual return",
            default => "Q{$filing->quarter} {$filing->year}",
        };
        $notices->record('filing', $this->noticeTitle($filing), null, $withholding
            ? match ($filing->form) {
                '1601-C' => route('payroll.month', [$filing->year, $filing->month], absolute: false),
                '1604-E' => route('withholding.annual', $filing->year, absolute: false),
                default => route('withholding', $filing->year, absolute: false),
            }
            : route('history', ['year' => $filing->year, 'quarter' => $filing->quarter, 'form' => $filing->form], absolute: false));

        return Inertia::flash('toast', "{$what} is marked filed".($filing->isPaid() ? ' and paid' : '').'. Later changes become an amendment.')
            ->back();
    }

    public function update(UpdateFilingPaymentRequest $request, Filing $filing, Notices $notices): RedirectResponse
    {
        $filing->update($request->validated());
        $notices->record('filing', $this->noticeTitle($filing), null, route('history', ['year' => $filing->year, 'quarter' => $filing->quarter, 'form' => $filing->form], absolute: false));

        return Inertia::flash('toast', 'Payment recorded. This return is filed and paid.')->back();
    }

    /**
     * "Q2 filed and paid · ₱4,000"
     */
    private function noticeTitle(Filing $filing): string
    {
        $what = match (true) {
            $filing->form === '0619-E' => CarbonImmutable::create($filing->year, $filing->month)->format('F Y').' 0619-E',
            $filing->form === '1601-C' => CarbonImmutable::create($filing->year, $filing->month)->format('F Y').' 1601-C',
            $filing->form === '1604-E' => "{$filing->year} 1604-E",
            $filing->amendment > 0 => "Q{$filing->quarter} {$filing->form} (amended)",
            $filing->quarter === 4 && in_array($filing->form, ReturnForm::INCOME_TAX_FORMS, true) => "{$filing->year} annual return",
            default => "Q{$filing->quarter} {$filing->form}",
        };

        return $filing->isPaid()
            ? "{$what} filed and paid · ₱".number_format((float) $filing->amount_paid)
            : "{$what} filed";
    }
}
