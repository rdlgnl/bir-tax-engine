<?php

namespace App\Returns\Forms;

use LogicException;
use setasign\Fpdi\Fpdi;

/**
 * Writes values onto the BIR's own PDF of a form.
 *
 * The BIR's PDFs have no form fields, so every value is drawn at the spot
 * its box sits on the page. Where each box sits is the form's field map in
 * resources/forms/bir, keyed by item ("26A", "tin.1", "atc.II015").
 * Coordinates are in points from the top-left corner of the page.
 *
 * @phpstan-type Box array{
 *     page: int,
 *     type: 'text'|'comb'|'amount',
 *     x: float,
 *     to: float,
 *     y: float,
 *     cells?: int,
 *     cents?: array{float, float},
 * }
 * @phpstan-type Placement Box|array{page: int, type: 'check', x: float, y: float}
 * @phpstan-type Mark array{page: int, x: float, y: float, text: string, size: float, bold: bool}
 *
 * Placement types: `text` is written left-aligned from x to `to`; `comb`
 * puts one character in each of `cells` boxes between x and `to`; `amount`
 * is a comb filled from the right, with `cents` the centavo boxes if the
 * form has them; `check` marks the box whose top-left corner is x, y. For
 * everything but `check`, y is the baseline.
 */
class OfficialFormFiller
{
    private const PAGE = [612, 936];

    private const SIZE = 9.0;

    private const CHECK_BOX = 12.4;

    /**
     * The filled form as PDF bytes.
     *
     * @param  array<array-key, string|int|float|bool|null>  $values
     */
    public function render(string $form, array $values): string
    {
        $pdf = $this->document();
        $pages = $pdf->setSourceFile($this->template($form));
        $marks = collect($this->marks($form, $values))->groupBy('page');

        for ($page = 1; $page <= $pages; $page++) {
            $pdf->AddPage('P', self::PAGE);
            $pdf->useTemplate($pdf->importPage($page));

            foreach ($marks->get($page, []) as $mark) {
                $pdf->SetFont('Helvetica', $mark['bold'] ? 'B' : '', $mark['size']);
                $pdf->Text($mark['x'], $mark['y'], $this->encode($mark['text']));
            }
        }

        return $pdf->Output('S');
    }

    /**
     * Every piece of text the filled form will carry, and where.
     *
     * @param  array<array-key, string|int|float|bool|null>  $values
     * @return list<Mark>
     */
    public function marks(string $form, array $values): array
    {
        $map = $this->map($form);
        $wholePesos = (bool) config("tax.forms.{$form}.whole_pesos");
        $pdf = $this->document();
        $marks = [];

        foreach ($values as $item => $value) {
            if (! isset($map[$item])) {
                throw new LogicException("BIR Form {$form} has no box for item {$item}.");
            }

            if ($value === null || $value === '' || $value === false) {
                continue;
            }

            $place = $map[$item];

            array_push($marks, ...match ($place['type']) {
                'check' => [$this->mark($place['page'], $place['x'] + (self::CHECK_BOX - $this->width($pdf, 'X', 10, true)) / 2, $place['y'] + 9.4, 'X', 10, true)],
                'text' => $this->text($pdf, $place, (string) $value),
                'comb' => $this->comb($pdf, $place, (string) $value),
                'amount' => $this->amount($pdf, $place, (float) $value, $wholePesos),
            });
        }

        return $marks;
    }

    public function template(string $form): string
    {
        return resource_path('forms/bir/'.config("tax.forms.{$form}.template").'.pdf');
    }

    /**
     * @return array<array-key, Placement>
     */
    public function map(string $form): array
    {
        return require resource_path('forms/bir/'.config("tax.forms.{$form}.template").'.php');
    }

    /**
     * @param  Box  $place
     * @return list<Mark>
     */
    private function text(Fpdi $pdf, array $place, string $value, float $size = 8.0): array
    {
        $room = $place['to'] - $place['x'] - 2;

        while ($size > 5 && $this->width($pdf, $value, $size) > $room) {
            $size -= 0.5;
        }

        return [$this->mark($place['page'], $place['x'] + 1.5, $place['y'], $value, $size)];
    }

    /**
     * One character per box. A value too long for its boxes is written as
     * plain text across them rather than cut short.
     *
     * @param  Box  $place
     * @return list<Mark>
     */
    private function comb(Fpdi $pdf, array $place, string $value, bool $fromRight = false): array
    {
        $chars = mb_str_split($value);
        $cells = $place['cells'] ?? 1;

        if (count($chars) > $cells) {
            return $this->text($pdf, $place, $value, self::SIZE);
        }

        $pitch = ($place['to'] - $place['x']) / $cells;
        $first = $fromRight ? $cells - count($chars) : 0;
        $marks = [];

        foreach ($chars as $i => $char) {
            if ($char === ' ') {
                continue;
            }

            $centre = $place['x'] + ($first + $i + 0.5) * $pitch;
            $marks[] = $this->mark($place['page'], $centre - $this->width($pdf, $char, self::SIZE) / 2, $place['y'], $char, self::SIZE);
        }

        return $marks;
    }

    /**
     * Digits only, filled from the right: the boxes' thicker ticks already
     * group the thousands. Overpayments go in parentheses, as the forms
     * write them.
     *
     * @param  Box  $place
     * @return list<Mark>
     */
    private function amount(Fpdi $pdf, array $place, float $value, bool $wholePesos): array
    {
        $centavos = (int) round(abs($value) * 100);
        $pesos = $wholePesos || ! isset($place['cents']) ? (string) intdiv($centavos + 50, 100) : (string) intdiv($centavos, 100);

        if (isset($place['cents']) && ! $wholePesos) {
            [$from, $to] = $place['cents'];
            $cents = $this->comb($pdf, ['page' => $place['page'], 'type' => 'comb', 'x' => $from, 'to' => $to, 'cells' => 2, 'y' => $place['y']], sprintf('%02d', $centavos % 100));
        }

        $digits = $value < 0 ? "({$pesos})" : $pesos;

        return [...$this->comb($pdf, $place, $digits, fromRight: true), ...$cents ?? []];
    }

    /**
     * @return Mark
     */
    private function mark(int $page, float $x, float $y, string $text, float $size, bool $bold = false): array
    {
        return ['page' => $page, 'x' => round($x, 2), 'y' => round($y, 2), 'text' => $text, 'size' => $size, 'bold' => $bold];
    }

    private function width(Fpdi $pdf, string $text, float $size, bool $bold = false): float
    {
        $pdf->SetFont('Helvetica', $bold ? 'B' : '', $size);

        return $pdf->GetStringWidth($this->encode($text));
    }

    private function document(): Fpdi
    {
        $pdf = new Fpdi('P', 'pt', self::PAGE);
        $pdf->SetAutoPageBreak(false);
        $pdf->SetMargins(0, 0);

        return $pdf;
    }

    /**
     * FPDF's built-in fonts are Windows-1252, which covers Ñ.
     */
    private function encode(string $text): string
    {
        return (string) iconv('UTF-8', 'windows-1252//TRANSLIT//IGNORE', $text);
    }
}
