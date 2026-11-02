<?php

namespace App\Profiles\Resources;

use App\Profiles\Enums\ClientsWithhold;
use App\Profiles\Enums\IncomeSource;
use App\Profiles\Enums\WorkType;
use App\Profiles\Models\TaxProfile;
use App\Tax\Enums\DeductionMethod;
use App\Tax\Enums\Regime;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The profile as the setup summary and profile page show it: how the BIR
 * sees this taxpayer, which taxes apply, and what to do next.
 *
 * @mixin TaxProfile
 */
class TaxProfileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $fromCor = $this->tin ? 'from COR' : 'your answers';
        $eightPercent = $this->regime === Regime::EightPercent;

        return [
            'full_name' => $this->full_name,
            'first_name' => $this->firstName(),
            'initials' => $this->initials(),
            'line_of_business' => $this->line_of_business,
            'tin' => $this->tin,
            'rdo_code' => $this->rdo_code,
            'regime' => $this->regime?->value,
            'regime_label' => $this->regimeLabel(),
            'classification' => array_values(array_filter([
                ['label' => match ($this->income_source) {
                    IncomeSource::Employed => 'Individual, employed',
                    IncomeSource::Both => 'Individual, job + business',
                    default => 'Individual, self-employed',
                }, 'source' => 'your answers'],
                $this->work_type ? ['label' => match ($this->work_type) {
                    WorkType::Products => 'Sells products',
                    WorkType::Services => 'Provides services',
                    WorkType::Both => 'Sells products and services',
                }, 'source' => 'your answers'] : null,
                $this->income_source === IncomeSource::Employed ? null : [
                    'label' => $this->vat_registered ? 'VAT-registered' : 'Not VAT-registered',
                    'source' => $fromCor,
                ],
                (float) $this->expected_income < config('tax.classification.micro_below') && $this->expected_income !== null
                    ? ['label' => 'Micro taxpayer (under ₱3M)', 'source' => 'estimate']
                    : null,
                $this->regime ? ['label' => $this->regimeLabel().' income tax', 'source' => 'your choice'] : null,
            ])),
            'taxes' => $this->income_source === IncomeSource::Employed ? [
                ['label' => 'Income tax', 'detail' => 'Withheld by your employer through payroll'],
            ] : [
                ['label' => 'Income tax', 'detail' => '3 quarterly + 1 annual return'],
                ['label' => 'Percentage tax', 'detail' => $eightPercent ? 'Not needed on 8%' : 'Quarterly 2551Q, 3% of sales'],
                ['label' => 'Tax withheld by clients', 'detail' => $this->clients_withhold === ClientsWithhold::No ? 'Not expected' : 'Credited automatically'],
                ['label' => 'VAT', 'detail' => $this->vat_registered ? 'Quarterly 2550Q, 12% of sales less VAT on purchases' : 'Not registered'],
            ],
            'registration' => [
                ['label' => 'Income tax', 'status' => $this->registered_income_tax ? 'Registered' : 'Not registered', 'note' => null],
                ['label' => 'Percentage tax', 'status' => $this->registered_percentage_tax ? 'Registered' : 'Not registered',
                    'note' => $this->corOutdated() ? 'Not needed on 8%. Update suggested' : null],
                ['label' => 'VAT', 'status' => $this->vat_registered ? 'Registered' : 'Not registered', 'note' => null],
            ],
            'todos' => array_values(array_filter([
                ['label' => 'Add your '.now()->year.' income so far', 'detail' => null],
                ! $this->bir_registered
                    ? ['label' => 'Register with the BIR', 'detail' => 'Form 1901, at your RDO or online']
                    : null,
                $this->corOutdated()
                    ? ['label' => 'Update your COR', 'detail' => 'Percentage tax is still listed (Form 1905)',
                        // The update page needs a finished setup.
                        'href' => $this->isComplete() ? route('registration', absolute: false) : null]
                    : null,
            ])),
        ];
    }

    private function regimeLabel(): ?string
    {
        return match (true) {
            $this->regime === Regime::EightPercent => 'Flat 8%',
            $this->regime === Regime::Graduated && $this->deduction_method === DeductionMethod::Itemized => 'Graduated rates, actual expenses',
            $this->regime === Regime::Graduated => 'Graduated rates, flat 40% deduction',
            default => null,
        };
    }
}
