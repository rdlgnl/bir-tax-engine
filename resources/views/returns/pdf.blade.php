<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>BIR Form {{ $layout['form'] }} · {{ $layout['period'] }}</title>
    <style>
        @page { margin: 36px 40px; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #0f172a; }
        .notice { border: 1px solid #f3c77a; background: #fff7eb; color: #92400e; padding: 8px 10px; margin-bottom: 16px; font-size: 10px; }
        .head { width: 100%; border-bottom: 2px solid #0f172a; padding-bottom: 8px; margin-bottom: 12px; }
        .head td { vertical-align: bottom; }
        .muted { color: #475569; }
        .title { font-size: 17px; font-weight: bold; }
        .form-no { text-align: right; font-size: 13px; font-weight: bold; }
        h2 { font-size: 10px; letter-spacing: 0.5px; margin: 16px 0 6px; }
        table.grid { width: 100%; border-collapse: collapse; }
        table.grid td { border: 1px solid #e3e7ec; padding: 6px 8px; }
        .value { text-align: right; font-family: 'DejaVu Sans Mono', monospace; background: #fef9c3; }
        .total td { font-weight: bold; }
        .sign { margin-top: 28px; }
        .foot { margin-top: 18px; border-top: 1px dashed #cbd2da; padding-top: 8px; font-size: 9px; color: #475569; }
    </style>
</head>
<body>
    <div class="notice">
        Print-ready summary prepared by BuwisBuddy from your records. It follows the wording of BIR Form
        {{ $layout['form'] }} but is not the official form: copy these values into eBIRForms or the official form to file.
    </div>

    <table class="head">
        <tr>
            <td>
                <div class="muted">Republic of the Philippines · Bureau of Internal Revenue</div>
                <div class="title">{{ $layout['title'] }}</div>
                <div class="muted">For individuals · {{ $layout['method'] }}</div>
            </td>
            <td class="form-no">BIR Form No.<br>{{ $layout['form'] }}</td>
        </tr>
    </table>

    <h2>PART I · BACKGROUND INFORMATION</h2>
    <table class="grid">
        <tr>
            <td>For the year · quarter</td><td class="value">{{ $layout['period'] }}</td>
            <td>ATC</td><td class="value">{{ $layout['taxpayer']['atc'] }}</td>
        </tr>
        <tr>
            <td>TIN</td><td class="value">{{ $layout['taxpayer']['tin'] ?? 'Not registered' }}</td>
            <td>RDO code</td><td class="value">{{ $layout['taxpayer']['rdo'] ?? '—' }}</td>
        </tr>
        <tr>
            <td>Taxpayer name</td><td class="value" colspan="3">{{ $layout['taxpayer']['name'] }}</td>
        </tr>
        <tr>
            <td>Amended return?</td><td class="value" colspan="3">No</td>
        </tr>
    </table>

    <h2>PART II · COMPUTATION OF TAX ({{ strtoupper($layout['method']) }})</h2>
    <table class="grid">
        @foreach ($layout['lines'] as $line)
            <tr @class(['total' => $line['kind'] === 'total'])>
                <td>{{ $line['label'] }}</td>
                <td class="value">{{ number_format($line['amount'], 2) }}</td>
            </tr>
        @endforeach
    </table>

    <p class="sign">Signature over printed name: ______________________________</p>

    <div class="foot">
        @if ($filedOn)
            Filed {{ $filedOn->format('F j, Y') }}. These are the figures as filed.
        @else
            Prepared {{ now()->format('F j, Y') }}.
        @endif
        Rule pack {{ $layout['rule_pack'] }} · {{ implode(' · ', $layout['rules']) }}.
    </div>
</body>
</html>
