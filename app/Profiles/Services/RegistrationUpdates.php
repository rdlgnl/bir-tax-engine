<?php

namespace App\Profiles\Services;

use App\Profiles\Models\TaxProfile;
use App\Tax\Services\TaxCalculationService;
use Carbon\CarbonImmutable;

/**
 * The registration updates a self-employed taxpayer may need, read from
 * the COR details in the profile and the choices made in the app.
 *
 * @phpstan-type Update array{key: string, label: string, detail: string, status: 'suggested'|'not_needed'|'optional'|'orus'}
 */
class RegistrationUpdates
{
    public function __construct(private TaxCalculationService $engine) {}

    /**
     * @return list<Update>
     */
    public function for(TaxProfile $profile, CarbonImmutable $today): array
    {
        $limit = (float) config('tax.eight_percent.gross_limit');
        $gross = $this->engine->forYear($profile, $today->year, $today)['gross'];

        return [
            [
                'key' => 'remove_percentage_tax',
                'label' => 'Remove percentage tax',
                'detail' => $profile->corOutdated() ? 'Because you chose 8%' : 'Only when you choose 8%',
                'status' => $profile->corOutdated() ? 'suggested' : 'not_needed',
            ],
            [
                'key' => 'register_vat',
                'label' => 'Register for VAT',
                'detail' => 'When sales pass ₱'.number_format($limit),
                'status' => ! $profile->vat_registered && $gross > $limit ? 'suggested' : 'not_needed',
            ],
            [
                'key' => 'change_address',
                'label' => 'Change address or RDO',
                'detail' => 'After you move',
                'status' => 'optional',
            ],
            [
                'key' => 'classification',
                'label' => 'Change taxpayer classification',
                'detail' => 'Micro / small / medium',
                'status' => 'orus',
            ],
        ];
    }
}
