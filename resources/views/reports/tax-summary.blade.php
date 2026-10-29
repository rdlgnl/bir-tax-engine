<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Tax summary {{ $year }} · {{ $profile['full_name'] }}</title>
    <style>
        @page { margin: 36px 40px; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #0f172a; }
        h1 { font-size: 20px; margin: 0 0 2px; }
        h2 { font-size: 10px; letter-spacing: 0.5px; margin: 18px 0 6px; color: #334155; }
        .muted { color: #475569; }
        .notice { border: 1px solid #e3e7ec; background: #f4f6f8; padding: 8px 10px; margin: 12px 0 4px; font-size: 10px; color: #334155; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; font-size: 10px; color: #334155; border-bottom: 1px solid #cbd2da; padding: 5px 6px; }
        td { border-bottom: 1px solid #eef1f4; padding: 6px; }
        .num { text-align: right; }
        .total td { font-weight: bold; border-top: 2px solid #0f172a; border-bottom: none; }
        .facts td { border: none; padding: 2px 6px 2px 0; }
        .foot { margin-top: 18px; font-size: 9px; color: #475569; }
    </style>
</head>
<body>
    <h1>Tax summary {{ $year }}</h1>
    <div class="muted">{{ $profile['full_name'] }} · prepared {{ $preparedOn->format('F j, Y') }}</div>

    <table class="facts" style="margin-top: 10px;">
        <tr><td class="muted">TIN</td><td>{{ $profile['tin'] ?? 'Not registered' }}{{ $profile['rdo_code'] ? ' · RDO '.$profile['rdo_code'] : '' }}</td></tr>
        @if ($profile['line_of_business'])
            <tr><td class="muted">Line of work</td><td>{{ $profile['line_of_business'] }}</td></tr>
        @endif
        <tr><td class="muted">Income tax method</td><td>{{ $profile['regime_label'] ?? 'Not chosen' }}</td></tr>
    </table>

    <div class="notice">
        Prepared by the taxpayer with BuwisBuddy from their own records. It is not issued or certified by the BIR;
        the filed returns and payment confirmations are the proof of filing.
    </div>

    <h2>BY QUARTER (PHP)</h2>
    <table>
        <tr>
            <th>Period</th>
            <th class="num">Sales</th>
            <th class="num">Withheld by clients</th>
            <th class="num">Income tax due</th>
            @if ($result['regime'] === 'graduated')
                <th class="num">Percentage tax</th>
            @endif
            <th class="num">Paid</th>
            <th>Status</th>
        </tr>
        @foreach ($result['quarters'] as $quarter => $q)
            @php($filing = $incomeFilings->get($quarter))
            <tr>
                <td>{{ $quarter === 4 ? 'Q4 / annual' : 'Q'.$quarter }}</td>
                <td class="num">{{ number_format($q['quarter_gross'], 2) }}</td>
                <td class="num">{{ number_format($q['quarter_withheld'], 2) }}</td>
                <td class="num">{{ number_format($q['due'], 2) }}</td>
                @if ($result['regime'] === 'graduated')
                    <td class="num">{{ number_format((float) $q['percentage_tax'], 2) }}</td>
                @endif
                <td class="num">{{ $filing?->amount_paid !== null ? number_format((float) $filing->amount_paid, 2) : '—' }}</td>
                <td>
                    @if ($filing)
                        Filed {{ $filing->filed_on->format('M j, Y') }}
                    @elseif ($q['status'] === 'open')
                        In progress
                    @else
                        Due {{ \Carbon\CarbonImmutable::parse($q['due_date'])->format('M j, Y') }}
                    @endif
                </td>
            </tr>
        @endforeach
        <tr class="total">
            <td>Year</td>
            <td class="num">{{ number_format($result['gross'], 2) }}</td>
            <td class="num">{{ number_format($result['quarters'][4]['withheld'], 2) }}</td>
            <td class="num">{{ number_format(collect($result['quarters'])->sum('due'), 2) }}</td>
            @if ($result['regime'] === 'graduated')
                <td class="num">{{ number_format(collect($result['quarters'])->sum('percentage_tax'), 2) }}</td>
            @endif
            <td class="num">{{ number_format($paid, 2) }}</td>
            <td></td>
        </tr>
    </table>
    <p class="muted">"Income tax due" is what each return asks you to pay after client withholding. Income tax for the year,
        on {{ number_format($result['gross'], 2) }} in sales, is {{ number_format($result['quarters'][4]['income_tax'], 2) }}:
        {{ number_format($result['quarters'][4]['withheld'], 2) }} withheld by clients plus
        {{ number_format(collect($result['quarters'])->sum('due'), 2) }} due with the returns.</p>

    <h2>RETURNS FILED</h2>
    @if ($filings->isEmpty())
        <p class="muted">No returns marked filed for {{ $year }} yet.</p>
    @else
        <table>
            <tr><th>Form</th><th>Period</th><th>Filed</th><th>Reference</th><th class="num">Paid</th></tr>
            @foreach ($filings as $filing)
                <tr>
                    <td>{{ $filing->form }}</td>
                    <td>{{ $filing->quarter === 4 && $filing->form !== '2551Q' ? 'Annual' : 'Q'.$filing->quarter }} {{ $filing->year }}</td>
                    <td>{{ $filing->filed_on->format('M j, Y') }}</td>
                    <td>{{ $filing->reference_number ?? '—' }}</td>
                    <td class="num">{{ $filing->amount_paid !== null ? number_format((float) $filing->amount_paid, 2) : 'Not paid yet' }}</td>
                </tr>
            @endforeach
        </table>
    @endif

    <div class="foot">Figures follow BuwisBuddy rule pack {{ config('tax.version') }}.</div>
</body>
</html>
