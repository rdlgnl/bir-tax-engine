<?php

namespace App\Returns\Commands;

use App\Returns\Forms\OfficialFormFiller;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\Fpdi;
use setasign\Fpdi\PdfParser\StreamReader;

/**
 * For whoever maintains the field maps: fills every box of a form with a
 * sample value, so a misplaced box shows at a glance. --grid adds a 10-point
 * grid labelled in the map's coordinates.
 */
class CalibrateForm extends Command
{
    protected $signature = 'forms:calibrate {form : 1701Q, 1701A, 1701 or 2551Q} {--grid : Draw a coordinate grid over the form}';

    protected $description = 'Fill every box of a BIR form with sample values to check its field map';

    public function handle(OfficialFormFiller $filler): int
    {
        $form = (string) $this->argument('form');

        if (config("tax.forms.{$form}") === null) {
            $this->error("No BIR form {$form} in the rule pack.");

            return self::FAILURE;
        }

        $samples = [];

        foreach ($filler->map($form) as $item => $place) {
            $samples[$item] = match ($place['type']) {
                'check' => true,
                'amount' => 1234567.89,
                'text' => 'DELA CRUZ',
                'comb' => str_repeat('8', $place['cells'] ?? 1),
            };
        }

        $pdf = $filler->render($form, $samples);
        $path = "forms-calibration/{$form}.pdf";
        Storage::disk('local')->put($path, $this->option('grid') ? $this->withGrid($pdf) : $pdf);

        $this->info('Wrote '.Storage::disk('local')->path($path));

        return self::SUCCESS;
    }

    private function withGrid(string $filled): string
    {
        $pdf = new Fpdi('P', 'pt', [612, 936]);
        $pages = $pdf->setSourceFile(StreamReader::createByString($filled));
        $pdf->SetFont('Helvetica', '', 5);
        $pdf->SetLineWidth(0.2);

        for ($page = 1; $page <= $pages; $page++) {
            $pdf->AddPage('P', [612, 936]);
            $pdf->useTemplate($pdf->importPage($page));

            for ($x = 0; $x <= 612; $x += 10) {
                $pdf->SetDrawColor(255, $x % 50 ? 190 : 0, $x % 50 ? 190 : 0);
                $pdf->Line($x, 0, $x, 936);
            }

            for ($y = 0; $y <= 936; $y += 10) {
                $pdf->SetDrawColor($y % 50 ? 190 : 0, $y % 50 ? 190 : 0, 255);
                $pdf->Line(0, $y, 612, $y);

                if ($y % 50 === 0) {
                    $pdf->Text(1, $y - 1, (string) $y);
                }
            }

            for ($x = 50; $x < 612; $x += 50) {
                $pdf->Text($x + 1, 6, (string) $x);
            }
        }

        return $pdf->Output('S');
    }
}
