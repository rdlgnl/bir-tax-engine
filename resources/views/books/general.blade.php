<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>General journal and ledger {{ $period->format('F Y') }} · {{ $profile->full_name }}</title>
    <style>
        @page { margin: 36px 40px 52px; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; color: #0f172a; }
        h1 { font-size: 20px; margin: 0 0 2px; }
        h2 { font-size: 10px; letter-spacing: 0.5px; margin: 20px 0 6px; color: #334155; }
        h3 { font-size: 10px; margin: 14px 0 4px; }
        .muted { color: #475569; }
        .notice { border: 1px solid #e3e7ec; background: #f4f6f8; padding: 8px 10px; margin: 12px 0 4px; font-size: 9px; color: #334155; }
        table { width: 100%; border-collapse: collapse; }
        thead { display: table-header-group; }
        tr { page-break-inside: avoid; }
        th { text-align: left; font-size: 9px; color: #334155; border-bottom: 1px solid #cbd2da; padding: 4px 5px; }
        td { border-bottom: 1px solid #eef1f4; padding: 4px 5px; vertical-align: top; }
        .num { text-align: right; white-space: nowrap; }
        .credit { padding-left: 22px; }
        .entry-start td { border-top: 1px solid #cbd2da; }
        .empty td { color: #475569; font-style: italic; }
        .total td { font-weight: bold; border-top: 2px solid #0f172a; border-bottom: none; }
        .facts td { border: none; padding: 2px 6px 2px 0; }
        .foot { position: fixed; bottom: -30px; left: 0; right: 0; font-size: 9px; color: #475569; }
    </style>
</head>
<body>
    <div class="foot">
        <span>{{ $profile->full_name }} · {{ $period->format('F Y') }}</span>
    </div>

    <h1>General journal and ledger · {{ $period->format('F Y') }}</h1>
    <div class="muted">Prepared {{ $preparedOn->format('F j, Y') }}</div>

    <table class="facts" style="margin-top: 10px;">
        <tr><td class="muted">Taxpayer</td><td>{{ $profile->full_name }}{{ $profile->trade_name ? ' · '.$profile->trade_name : '' }}</td></tr>
        <tr><td class="muted">TIN</td><td>{{ $profile->tin ?? 'Not registered' }}{{ $profile->rdo_code ? ' · RDO '.$profile->rdo_code : '' }}</td></tr>
        <tr><td class="muted">Books</td><td>{{ $booksType->label() }}</td></tr>
    </table>

    <div class="notice">
        On a cash basis, like the cash journals: money received and paid, and taxes paid with returns.
        Balances start on January 1; bring last year's balances forward by hand on the first page of the year.
    </div>

    <h2>GENERAL JOURNAL (PHP)</h2>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Ref.</th>
                <th>Account and explanation</th>
                <th class="num">Debit</th>
                <th class="num">Credit</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($journal as $entry)
                @foreach ($entry['lines'] as $i => $line)
                    <tr @class(['entry-start' => $i === 0])>
                        <td>{{ $i === 0 ? \Carbon\CarbonImmutable::parse($entry['date'])->format('M j') : '' }}</td>
                        <td>{{ $i === 0 ? $entry['ref'] : '' }}</td>
                        <td @class(['credit' => $line['credit'] > 0])>{{ $line['account'] }} {{ $accounts[$line['account']] }}</td>
                        <td class="num">{{ $line['debit'] > 0 ? number_format($line['debit'], 2) : '' }}</td>
                        <td class="num">{{ $line['credit'] > 0 ? number_format($line['credit'], 2) : '' }}</td>
                    </tr>
                @endforeach
                <tr>
                    <td></td><td></td>
                    <td class="muted" colspan="3">{{ $entry['description'] }}</td>
                </tr>
            @empty
                <tr class="empty"><td colspan="5">No entries this month.</td></tr>
            @endforelse
            <tr class="total">
                <td colspan="3">Total</td>
                <td class="num">{{ number_format($ledger['debits'], 2) }}</td>
                <td class="num">{{ number_format($ledger['credits'], 2) }}</td>
            </tr>
        </tbody>
    </table>

    <h2 style="page-break-before: always;">GENERAL LEDGER (PHP)</h2>
    @forelse ($ledger['accounts'] as $account)
        <h3>{{ $account['code'] }} {{ $account['name'] }}</h3>
        <table class="ledger">
            <thead>
                <tr>
                    <th style="width: 44px;">Date</th>
                    <th style="width: 92px;">Ref.</th>
                    <th>Explanation</th>
                    <th class="num" style="width: 72px;">Debit</th>
                    <th class="num" style="width: 72px;">Credit</th>
                    <th class="num" style="width: 78px;">Balance</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ $period->format('M j') }}</td>
                    <td></td>
                    <td class="muted">Balance forward</td>
                    <td></td><td></td>
                    <td class="num">{{ number_format($account['opening'], 2) }}</td>
                </tr>
                @foreach ($account['postings'] as $posting)
                    <tr>
                        <td>{{ \Carbon\CarbonImmutable::parse($posting['date'])->format('M j') }}</td>
                        <td>{{ $posting['ref'] }}</td>
                        <td>{{ $posting['description'] }}</td>
                        <td class="num">{{ $posting['debit'] > 0 ? number_format($posting['debit'], 2) : '' }}</td>
                        <td class="num">{{ $posting['credit'] > 0 ? number_format($posting['credit'], 2) : '' }}</td>
                        <td class="num">{{ number_format($posting['balance'], 2) }}</td>
                    </tr>
                @endforeach
                <tr class="total">
                    <td colspan="3">{{ $period->format('F') }}</td>
                    <td class="num">{{ number_format($account['debits'], 2) }}</td>
                    <td class="num">{{ number_format($account['credits'], 2) }}</td>
                    <td class="num">{{ number_format($account['closing'], 2) }}</td>
                </tr>
            </tbody>
        </table>
    @empty
        <p class="muted"><em>No accounts with entries yet this year.</em></p>
    @endforelse

    <div class="notice" style="margin-top: 18px;">
        Prepared by the taxpayer with BuwisBuddy from their own records. It is not issued or certified by the BIR.
    </div>
</body>
</html>
