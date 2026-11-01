<?php

namespace App\Compliance\Services;

use App\Profiles\Models\TaxProfile;
use App\Transactions\Models\Transaction;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use Smalot\PdfParser\Parser;
use Throwable;

/**
 * Reads a client's 2307 from the text in its PDF, on this computer, so the
 * taxpayer checks the details instead of typing them. No AI and no upload:
 * a 2307 is a fixed form, so its fields are found by their shape.
 *
 * Each field says whether it's sure. A client name is sure when it's a
 * client already in the transactions; amounts are sure when the tax
 * withheld is the tax code's rate of the income. A scan or a photo has no
 * text to read, so it's typed in as before.
 *
 * @phpstan-type Field array{value: string|int|float|null, sure: bool}
 * @phpstan-type Reading array{
 *     readable: bool,
 *     fields: array{payor_name: Field, payor_tin: Field, year: Field, quarter: Field, amount_paid: Field, tax_withheld: Field, atc_code: Field},
 * }
 */
class CertificateReader
{
    /**
     * An amount: always two decimals, so amounts a PDF runs together
     * ("15,000.000.00") still come apart.
     */
    private const MONEY = '/\d{1,3}(?:,\d{3})+\.\d{2}|\d+\.\d{2}/';

    /** Rates of tax withheld the BIR uses for income payments. */
    private const COMMON_RATES = [0.005, 0.01, 0.02, 0.05, 0.1, 0.15];

    public function __construct(private CertificateMatcher $matcher) {}

    /**
     * @return Reading
     */
    public function read(string $path, string $mimeType): array
    {
        $text = $mimeType === 'application/pdf' ? $this->firstPageText($path) : '';

        if (trim($text) === '') {
            return ['readable' => false, 'fields' => $this->empty()];
        }

        $lines = array_values(array_filter(array_map(
            fn (string $line) => trim((string) preg_replace('/[ \t]+/', ' ', $line)),
            preg_split('/\R/', $text) ?: [],
        ), fn (string $line) => $line !== ''));

        [$year, $quarter, $periodSure, $text] = $this->period($text);
        [$tin, $tinSure, $tinLine] = $this->payorTin($text, $lines);
        [$name, $nameSure] = $this->payorName($lines, $tinLine);
        [$atc, $atcSure] = $this->atc($text);
        [$paid, $withheld, $amountsSure] = $this->amounts($lines, $atc);

        return [
            'readable' => true,
            'fields' => [
                'payor_name' => ['value' => $name, 'sure' => $nameSure],
                'payor_tin' => ['value' => $tin, 'sure' => $tinSure],
                'year' => ['value' => $year, 'sure' => $periodSure],
                'quarter' => ['value' => $quarter, 'sure' => $periodSure],
                'amount_paid' => ['value' => $paid, 'sure' => $amountsSure],
                'tax_withheld' => ['value' => $withheld, 'sure' => $amountsSure],
                'atc_code' => ['value' => $atc, 'sure' => $atcSure],
            ],
        ];
    }

    /**
     * The 2307's own page: the BIR's form lists every tax code on page 2,
     * which would read as this certificate's.
     */
    private function firstPageText(string $path): string
    {
        try {
            $pages = (new Parser)->parseFile($path)->getPages();

            return isset($pages[0]) ? $pages[0]->getText() : '';
        } catch (Throwable) {
            return '';
        }
    }

    /**
     * The quarter from the "For the period" dates, and the text with the
     * dates blanked out so their digits can't pass for a TIN.
     *
     * @return array{int|null, int|null, bool, string}
     */
    private function period(string $text): array
    {
        $months = 'jan(?:uary)?|feb(?:ruary)?|mar(?:ch)?|apr(?:il)?|may|june?|july?|aug(?:ust)?|sep(?:t(?:ember)?)?|oct(?:ober)?|nov(?:ember)?|dec(?:ember)?';
        $patterns = [
            '/(?<!\d)(\d{2})\/(\d{2})\/(\d{4})(?!\d)/' => fn (array $m) => [$m[3], $m[1], $m[2]],
            '/(?<![\d.,])(\d{2})(\d{2})(\d{4})(?![\d.,])/' => fn (array $m) => [$m[3], $m[1], $m[2]],
            '/(?<!\d)(\d{4})-(\d{2})-(\d{2})(?!\d)/' => fn (array $m) => [$m[1], $m[2], $m[3]],
            "/\\b({$months})\\.? (\\d{1,2}),? (\\d{4})\\b/i" => fn (array $m) => [$m[3], (string) CarbonImmutable::parse("1 {$m[1]} 2000")->month, $m[2]],
        ];
        $dates = [];

        foreach ($patterns as $pattern => $parts) {
            preg_match_all($pattern, $text, $found, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);

            foreach ($found as $match) {
                [$y, $m, $d] = array_map('intval', $parts(array_map(fn (array $group) => $group[0], $match)));

                if ($y >= 2018 && $y <= 2100 && checkdate($m, $d, $y)) {
                    $dates[$match[0][1]] = ['date' => CarbonImmutable::parse(sprintf('%04d-%02d-%02d', $y, $m, $d)), 'length' => strlen($match[0][0])];
                }
            }
        }

        ksort($dates);

        foreach ($dates as $offset => $date) {
            $text = substr_replace($text, str_repeat(' ', $date['length']), $offset, $date['length']);
        }

        $dates = array_column($dates, 'date');
        $from = $dates[0] ?? null;
        $to = collect($dates)->first(fn (CarbonImmutable $date) => $from !== null && $date->greaterThan($from));

        if ($to === null) {
            return [$from?->year, $from?->quarter, false, $text];
        }

        // A certificate covers a quarter, or a month of one.
        $sure = $from->year === $to->year && $from->quarter === $to->quarter;

        return [$to->year, $to->quarter, $sure, $text];
    }

    /**
     * The payor's TIN: the one on the certificate that isn't the
     * taxpayer's own, and the line it's on.
     *
     * @param  list<string>  $lines
     * @return array{string|null, bool, int|null}
     */
    private function payorTin(string $text, array $lines): array
    {
        preg_match_all('/(?<![\d.,])(\d{3})([- ]?)(\d{3})\2(\d{3})(?:\2(\d{3,5}))?(?![\d.,])/', $text, $found, PREG_SET_ORDER);
        $own = substr((string) preg_replace('/\D/', '', (string) TaxProfile::current()->tin), 0, 9);
        $tins = [];

        foreach ($found as $match) {
            $digits = $match[1].$match[3].$match[4];

            if ($digits !== $own && ! isset($tins[$digits])) {
                $tins[$digits] = "{$match[1]}-{$match[3]}-{$match[4]}".(($match[5] ?? '') !== '' ? "-{$match[5]}" : '');
            }
        }

        if ($tins === []) {
            return [null, false, null];
        }

        $digits = (string) array_key_first($tins);
        $line = null;

        foreach ($lines as $i => $text) {
            if (str_contains((string) preg_replace('/\D/', '', $text), $digits)) {
                $line = $i;
                break;
            }
        }

        return [$tins[$digits], count($tins) === 1, $line];
    }

    /**
     * The payor's name: a client already in the transactions if one is
     * printed on its own line, or else the line after the payor's TIN.
     *
     * @param  list<string>  $lines
     * @return array{string|null, bool}
     */
    private function payorName(array $lines, ?int $tinLine): array
    {
        $clients = Transaction::query()->distinct()->pluck('counterparty')
            ->mapWithKeys(fn (string $name) => [$this->matcher->normalize($name) => $name])
            ->filter(fn (string $name, string $key) => $key !== '');

        foreach ($lines as $line) {
            if ($clients->has($this->matcher->normalize($this->withoutLabel($line)))) {
                return [$this->printed($this->withoutLabel($line)), true];
            }
        }

        $next = $tinLine !== null ? ($lines[$tinLine + 1] ?? null) : null;
        $name = $next !== null ? $this->withoutLabel($next) : '';

        return preg_match('/[a-z]{2}/i', $name) === 1 && ! preg_match('/name|address|payor|payee|\(/i', $name)
            ? [$this->printed($name), false]
            : [null, false];
    }

    /**
     * The first tax code on the certificate, sure if the rule pack knows it.
     *
     * @return array{string|null, bool}
     */
    private function atc(string $text): array
    {
        // Codes are three digits, and may run into the amount after them.
        if (preg_match('/(?<![A-Z0-9])(W[A-Z]\d{3})/', $text, $match) !== 1) {
            return [null, false];
        }

        return [$match[1], config("tax.withholding.atcs.{$match[1]}") !== null];
    }

    /**
     * The total income and the tax withheld for the quarter: the last two
     * amounts on the last row of amounts that isn't all zero, the totals row.
     *
     * @param  list<string>  $lines
     * @return array{float|null, float|null, bool}
     */
    private function amounts(array $lines, ?string $atc): array
    {
        $row = null;

        foreach ($lines as $line) {
            preg_match_all(self::MONEY, $line, $found);
            $amounts = array_map(fn (string $amount) => (float) str_replace(',', '', $amount), $found[0]);

            if (count($amounts) >= 2 && $amounts[count($amounts) - 2] > 0) {
                $row = $amounts;
            }
        }

        if ($row === null) {
            return [null, null, false];
        }

        [$paid, $withheld] = array_slice($row, -2);

        if ($withheld <= 0 || $withheld >= $paid) {
            return [$paid, $withheld, false];
        }

        $rate = config("tax.withholding.atcs.{$atc}.rate");
        $rates = $rate !== null ? [(float) $rate] : self::COMMON_RATES;
        $sure = collect($rates)->contains(fn (float $r) => abs($paid * $r - $withheld) <= 1.0);

        return [$paid, $withheld, $sure];
    }

    /**
     * "Payor's Name: LUMEN DIGITAL INC." is "LUMEN DIGITAL INC.".
     */
    private function withoutLabel(string $line): string
    {
        return trim(Str::contains($line, ':') ? Str::afterLast($line, ':') : $line);
    }

    /**
     * Names printed in capitals read better in title case.
     */
    private function printed(string $name): string
    {
        return Str::upper($name) === $name ? Str::title(Str::lower($name)) : $name;
    }

    /**
     * @return array{payor_name: Field, payor_tin: Field, year: Field, quarter: Field, amount_paid: Field, tax_withheld: Field, atc_code: Field}
     */
    private function empty(): array
    {
        $none = ['value' => null, 'sure' => false];

        return [
            'payor_name' => $none,
            'payor_tin' => $none,
            'year' => $none,
            'quarter' => $none,
            'amount_paid' => $none,
            'tax_withheld' => $none,
            'atc_code' => $none,
        ];
    }
}
