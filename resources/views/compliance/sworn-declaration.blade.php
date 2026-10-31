<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Sworn declarations {{ $declaration['year'] }} · {{ $declaration['name'] }}</title>
    <style>
        @page { margin: 60px 64px; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 11.5px; line-height: 1.6; color: #0f172a; }
        .page { page-break-after: always; }
        .page:last-child { page-break-after: auto; }
        h1 { font-size: 13px; text-align: center; letter-spacing: 0.3px; margin: 0 0 4px; }
        .sub { text-align: center; color: #475569; font-size: 10px; margin-bottom: 28px; }
        p { margin: 0 0 12px; text-align: justify; }
        ol { padding-left: 18px; margin: 0 0 18px; }
        li { margin-bottom: 8px; }
        .fill { font-weight: bold; }
        .sign { margin-top: 40px; }
        .line { display: inline-block; border-top: 1px solid #0f172a; width: 230px; padding-top: 3px; font-size: 10px; text-align: center; }
        .jurat { margin-top: 48px; font-size: 10.5px; }
        .foot { position: fixed; bottom: -40px; left: 0; right: 0; font-size: 8.5px; color: #475569; }
    </style>
</head>
<body>
    <div class="foot">Prepared with BuwisBuddy from the taxpayer's records. Wording follows the income payee's sworn declaration in RR 11-2018; check it with your notary.</div>

    @foreach ($clients as $client)
        <div class="page">
            <h1>INCOME PAYEE'S SWORN DECLARATION OF GROSS RECEIPTS/SALES</h1>
            <div class="sub">For taxable year {{ $declaration['year'] + 1 }}</div>

            <p>
                I, <span class="fill">{{ $declaration['name'] }}</span>, of legal age, with Taxpayer Identification Number
                <span class="fill">{{ $declaration['tin'] ?? '____________________' }}</span>, registered with Revenue District Office
                <span class="fill">{{ $declaration['rdo'] ?? '_____' }}</span> as a
                <span class="fill">{{ $declaration['vat'] ? 'VAT' : 'non-VAT' }}</span> taxpayer, under oath declare that:
            </p>

            <ol>
                @if ($declaration['over_limit'])
                    <li>My gross receipts/sales for taxable year {{ $declaration['year'] }} exceeded ₱{{ number_format($declaration['limit']) }}.</li>
                    <li>I am giving this declaration to <span class="fill">{{ mb_strtoupper($client['name']) }}</span>, which shall withhold {{ rtrim(rtrim(number_format($declaration['rate_without'], 1), '0'), '.') }}% from my {{ $declaration['fees'] }}.</li>
                @else
                    <li>My gross receipts/sales for taxable year {{ $declaration['year'] }} did not exceed ₱{{ number_format($declaration['limit']) }}, and I expect the same for taxable year {{ $declaration['year'] + 1 }}.</li>
                    <li>I am giving this declaration to <span class="fill">{{ mb_strtoupper($client['name']) }}</span> so that it withholds {{ rtrim(rtrim(number_format($declaration['rate'], 1), '0'), '.') }}% from my {{ $declaration['fees'] }}.</li>
                    <li>I will notify you if my gross receipts/sales exceed ₱{{ number_format($declaration['limit']) }} within the year.</li>
                @endif
            </ol>

            <div class="sign">
                <span class="line">{{ $declaration['name'] }}<br>Signature over printed name</span>
                &nbsp;&nbsp;&nbsp;&nbsp;
                <span class="line">Date</span>
            </div>

            <p class="jurat">
                SUBSCRIBED AND SWORN to before me this ____ day of ______________ {{ $declaration['year'] + 1 }}, affiant exhibiting to me
                competent evidence of identity: ______________________ issued on ______________ at ______________.
            </p>
            <p>Notary Public</p>
            <p style="font-size: 10px; color: #475569;">Doc. No. ____ · Page No. ____ · Book No. ____ · Series of {{ $declaration['year'] + 1 }}</p>
        </div>
    @endforeach
</body>
</html>
