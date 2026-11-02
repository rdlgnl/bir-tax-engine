<?php

namespace App\Corporations\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A corporation the taxpayer runs. It's a taxpayer of its own: its income
 * comes from its own books, quarter by quarter, not from the transactions.
 *
 * @property int $id
 * @property string $name
 * @property string|null $tin
 * @property string|null $rdo_code
 * @property string|null $registered_address
 * @property string|null $zip_code
 * @property string|null $contact_number
 * @property string|null $email
 * @property int $operations_began the year it registered with the BIR, from which MCIT counts (RR 9-98)
 * @property string $total_assets
 * @property 'itemized'|'osd' $deduction_method
 * @property bool $vat_registered
 * @property bool $withholding_agent
 * @property int $employees
 */
#[Fillable([
    'name',
    'tin',
    'rdo_code',
    'registered_address',
    'zip_code',
    'contact_number',
    'email',
    'operations_began',
    'total_assets',
    'deduction_method',
    'vat_registered',
    'withholding_agent',
    'employees',
])]
class Corporation extends Model
{
    /**
     * The app keeps one corporation.
     */
    public static function current(): ?self
    {
        return self::query()->first();
    }

    /**
     * @return HasMany<CorporationQuarter, $this>
     */
    public function quarters(): HasMany
    {
        return $this->hasMany(CorporationQuarter::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'operations_began' => 'integer',
            'total_assets' => 'decimal:2',
            'vat_registered' => 'boolean',
            'withholding_agent' => 'boolean',
            'employees' => 'integer',
        ];
    }
}
