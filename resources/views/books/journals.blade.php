<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Books of accounts {{ $period->format('F Y') }} · {{ $profile->full_name }}</title>
    <style>
        @page { margin: 36px 40px 52px; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #0f172a; }
        h1 { font-size: 20px; margin: 0 0 2px; }
        h2 { font-size: 10px; letter-spacing: 0.5px; margin: 20px 0 6px; color: #334155; }
        .muted { color: #475569; }
        .notice { border: 1px solid #e3e7ec; background: #f4f6f8; padding: 8px 10px; margin: 12px 0 4px; font-size: 10px; color: #334155; }
        table { width: 100%; border-collapse: collapse; }
        thead { display: table-header-group; }
        tr { page-break-inside: avoid; }
        th { text-align: left; font-size: 10px; color: #334155; border-bottom: 1px solid #cbd2da; padding: 5px 6px; }
        td { border-bottom: 1px solid #eef1f4; padding: 6px; }
        .num { text-align: right; }
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

    <h1>Books of accounts · {{ $period->format('F Y') }}</h1>
    <div class="muted">Cash receipts and cash disbursements journals · prepared {{ $preparedOn->format('F j, Y') }}</div>

    <table class="facts" style="margin-top: 10px;">
        <tr><td class="muted">Taxpayer</td><td>{{ $profile->full_name }}{{ $profile->trade_name ? ' · '.$profile->trade_name : '' }}</td></tr>
        <tr><td class="muted">TIN</td><td>{{ $profile->tin ?? 'Not registered' }}{{ $profile->rdo_code ? ' · RDO '.$profile->rdo_code : '' }}</td></tr>
        <tr><td class="muted">Books</td><td>{{ $booksType->label() }}</td></tr>
    </table>

    <div class="notice">
        @if ($booksType === \App\Books\Enums\BooksType::LooseLeaf)
            Loose-leaf books: print every month, bind them at year end, and keep the Permit to Use with them.
        @else
            Manual books: copy these entries into your BIR-stamped journals.
        @endif
        Keep books and records for {{ config('tax.books.retention_years') }} years after the return they support is filed.
    </div>

    @php
        // VAT and withholding columns only in months that have them.
        $vat = $totals['vat'] > 0;
        $inputVat = $totals['disbursed_vat'] > 0;
        $agentWithheld = $totals['disbursed_withheld'] > 0;
        $paidColumn = $inputVat || $agentWithheld;
    @endphp

    <h2>CASH RECEIPTS JOURNAL (PHP)</h2>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Received from</th>
                <th>Invoice</th>
                <th class="num">Amount</th>
                @if ($vat)<th class="num">VAT</th>@endif
                <th class="num">Withheld</th>
                <th class="num">Received</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($receipts as $row)
                <tr>
                    <td>{{ \Carbon\CarbonImmutable::parse($row['occurred_on'])->format('M j') }}</td>
                    <td>{{ $row['counterparty'] }}</td>
                    <td>{{ $row['invoice_number'] ?? '—' }}</td>
                    <td class="num">{{ number_format($row['amount'], 2) }}</td>
                    @if ($vat)<td class="num">{{ number_format($row['vat'], 2) }}</td>@endif
                    <td class="num">{{ number_format($row['withheld'], 2) }}</td>
                    <td class="num">{{ number_format($row['received'], 2) }}</td>
                </tr>
            @empty
                <tr class="empty"><td colspan="{{ $vat ? 7 : 6 }}">No money received this month.</td></tr>
            @endforelse
            <tr class="total">
                <td colspan="3">Total</td>
                <td class="num">{{ number_format($totals['amount'], 2) }}</td>
                @if ($vat)<td class="num">{{ number_format($totals['vat'], 2) }}</td>@endif
                <td class="num">{{ number_format($totals['withheld'], 2) }}</td>
                <td class="num">{{ number_format($totals['received'], 2) }}</td>
            </tr>
        </tbody>
    </table>

    <h2>CASH DISBURSEMENTS JOURNAL (PHP)</h2>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Paid to</th>
                <th>Receipt</th>
                <th>For</th>
                <th class="num">Amount</th>
                @if ($inputVat)<th class="num">VAT</th>@endif
                @if ($agentWithheld)<th class="num">Withheld</th>@endif
                @if ($paidColumn)<th class="num">Paid</th>@endif
            </tr>
        </thead>
        <tbody>
            @forelse ($disbursements as $row)
                <tr>
                    <td>{{ \Carbon\CarbonImmutable::parse($row['occurred_on'])->format('M j') }}</td>
                    <td>{{ $row['counterparty'] }}</td>
                    <td>{{ $row['receipt_number'] ?? '—' }}</td>
                    <td>{{ $row['category'] ?? '—' }}</td>
                    <td class="num">{{ number_format($row['amount'], 2) }}</td>
                    @if ($inputVat)<td class="num">{{ number_format($row['vat'], 2) }}</td>@endif
                    @if ($agentWithheld)<td class="num">{{ number_format($row['withheld'], 2) }}</td>@endif
                    @if ($paidColumn)<td class="num">{{ number_format($row['paid'], 2) }}</td>@endif
                </tr>
            @empty
                <tr class="empty"><td colspan="{{ 5 + (int) $inputVat + (int) $agentWithheld + (int) $paidColumn }}">No expenses recorded this month.</td></tr>
            @endforelse
            <tr class="total">
                <td colspan="4">Total</td>
                <td class="num">{{ number_format($totals['disbursed'], 2) }}</td>
                @if ($inputVat)<td class="num">{{ number_format($totals['disbursed_vat'], 2) }}</td>@endif
                @if ($agentWithheld)<td class="num">{{ number_format($totals['disbursed_withheld'], 2) }}</td>@endif
                @if ($paidColumn)<td class="num">{{ number_format($totals['paid'], 2) }}</td>@endif
            </tr>
        </tbody>
    </table>

    <div class="notice" style="margin-top: 18px;">
        Prepared by the taxpayer with BuwisBuddy from their own records. It is not issued or certified by the BIR.
    </div>
</body>
</html>
