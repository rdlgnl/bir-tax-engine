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
    protected $signature = 'forms:calibrate {form : A form in the rule pack, e.g. 1701Q, 2551Q or 1905} {--grid : Draw a coordinate grid over the form}';

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
        Storage::disk('local')->put($path, $this->option('grid') ? $this->withGrid($filler, $pdf) : $pdf);

        $this->info('Wrote '.Storage::disk('local')->path($path));

        return self::SUCCESS;
    }

    private function withGrid(OfficialFormFiller $filler, string $filled): string
    {
        $pdf = new Fpdi('P', 'pt', [612, 936]);
        $pages = $pdf->setSourceFile(StreamReader::createByString($filled));
        $pdf->SetFont('Helvetica', '', 5);
        $pdf->SetLineWidth(0.2);

        for ($page = 1; $page <= $pages; $page++) {
            $template = $pdf->importPage($page);
            [$width, $height] = $filler->pageSize($pdf, $template);
            $pdf->AddPage('P', [$width, $height]);
            $pdf->useTemplate($template);

            for ($x = 0; $x <= $width; $x += 10) {
                $pdf->SetDrawColor(255, $x % 50 ? 190 : 0, $x % 50 ? 190 : 0);
                $pdf->Line($x, 0, $x, $height);
            }

            for ($y = 0; $y <= $height; $y += 10) {
                $pdf->SetDrawColor($y % 50 ? 190 : 0, $y % 50 ? 190 : 0, 255);
                $pdf->Line(0, $y, $width, $y);

                if ($y % 50 === 0) {
                    $pdf->Text(1, $y - 1, (string) $y);
                }
            }

            for ($x = 50; $x < $width; $x += 50) {
                $pdf->Text($x + 1, 6, (string) $x);
            }
        }

        return $pdf->Output('S');
    }
}
