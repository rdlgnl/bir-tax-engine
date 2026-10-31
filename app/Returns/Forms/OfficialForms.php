<?php

namespace App\Returns\Forms;

use App\Returns\Forms\Items\Form1701AItems;
use App\Returns\Forms\Items\Form1701Items;
use App\Returns\Forms\Items\Form1701MSItems;
use App\Returns\Forms\Items\Form1701QItems;
use App\Returns\Forms\Items\Form2551QItems;
use App\Returns\Forms\Items\FormItems;
use App\Returns\Services\ReturnForm;
use LogicException;

/**
 * A return on the BIR's own form: the official PDF with every box the app
 * knows filled in, ready to sign.
 *
 * @phpstan-import-type Snapshot from ReturnForm
 * @phpstan-import-type Values from FormItems
 */
class OfficialForms
{
    public function __construct(private OfficialFormFiller $filler) {}

    /**
     * @param  Snapshot  $snapshot
     */
    public function render(array $snapshot): string
    {
        return $this->filler->render($snapshot['form'], $this->values($snapshot));
    }

    /**
     * @param  Snapshot  $snapshot
     * @return Values
     */
    public function values(array $snapshot): array
    {
        $items = match ($snapshot['form']) {
            '1701Q' => new Form1701QItems,
            '1701-MS' => new Form1701MSItems,
            '1701A' => new Form1701AItems,
            '1701' => new Form1701Items,
            '2551Q' => new Form2551QItems,
            default => throw new LogicException("No official form for BIR Form {$snapshot['form']}."),
        };

        return $items->values($snapshot);
    }

    /**
     * Details the form asks for that the profile doesn't have yet, so they'd
     * print blank, with the setup step that asks for each.
     *
     * @param  Snapshot  $snapshot
     * @return list<array{label: string, step: 'about'|'certificate'}>
     */
    public function missingDetails(array $snapshot): array
    {
        $taxpayer = $snapshot['taxpayer'];
        $percentageTax = $snapshot['form'] === '2551Q';

        $missing = [
            ['last, first and middle name', 'about', $taxpayer['last_name'] === null],
            ['registered address', 'certificate', $taxpayer['address'] === null],
            ['ZIP code', 'certificate', $taxpayer['zip'] === null],
            ['date of birth', 'about', ! $percentageTax && $taxpayer['birth_date'] === null],
            ['email address', 'about', $taxpayer['email'] === null],
            ['contact number', 'about', $snapshot['form'] !== '1701Q' && $taxpayer['contact'] === null],
            ['citizenship', 'about', ! $percentageTax && $taxpayer['citizenship'] === null],
        ];

        return array_values(array_map(
            fn (array $detail) => ['label' => $detail[0], 'step' => $detail[1]],
            array_filter($missing, fn (array $detail) => $detail[2]),
        ));
    }
}
