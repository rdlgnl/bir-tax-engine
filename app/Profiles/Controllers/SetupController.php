<?php

namespace App\Profiles\Controllers;

use App\Http\Controllers\Controller;
use App\Profiles\Enums\SetupStep;
use App\Profiles\Models\TaxProfile;
use App\Profiles\Requests\UpdateSetupStepRequest;
use App\Profiles\Resources\TaxProfileResource;
use App\Returns\Models\Filing;
use App\Tax\Enums\Regime;
use App\Tax\Services\TaxCalculationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Number;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class SetupController extends Controller
{
    public function show(Request $request, TaxCalculationService $engine, ?SetupStep $step = null): Response|RedirectResponse
    {
        $profile = TaxProfile::current() ?? new TaxProfile;
        $step ??= SetupStep::Income;

        if (! $step->appliesTo($profile)) {
            return redirect()->route('setup.show', $step->next($profile));
        }

        $comparison = fn () => $engine->compare(
            (float) $profile->expected_income,
            $request->integer('expenses', $profile->expected_expense_percent ?? 50),
        );

        return Inertia::render('setup/show', [
            'step' => $step->value,
            'previous' => $step->previous($profile)?->value,
            'steps' => array_map(fn (SetupStep $s) => [
                'key' => $s->value,
                'label' => $s->label(),
                'summary' => $this->summary($s, $profile),
                'answered' => $s->isAnswered($profile),
            ], SetupStep::flow($profile)),
            'answers' => [
                'income_source' => $profile->income_source?->value,
                'work_type' => $profile->work_type?->value,
                'profession' => $profile->profession?->value,
                'first_name' => $profile->first_name,
                'middle_name' => $profile->middle_name,
                'last_name' => $profile->last_name,
                'birth_date' => $profile->birth_date?->toDateString(),
                'email' => $profile->email,
                'contact_number' => $profile->contact_number,
                'citizenship' => $profile->citizenship ?? 'Filipino',
                'trade_name' => $profile->trade_name,
                'line_of_business' => $profile->line_of_business,
                'city' => $profile->city,
                'bir_registered' => $profile->bir_registered ?? true,
                'tin' => $profile->tin,
                'rdo_code' => $profile->rdo_code,
                'registered_address' => $profile->registered_address,
                'zip_code' => $profile->zip_code,
                'registered_on' => $profile->registered_on?->toDateString(),
                'registered_income_tax' => $profile->registered_income_tax ?? true,
                'registered_percentage_tax' => $profile->registered_percentage_tax ?? false,
                'vat_registered' => $profile->vat_registered ?? false,
                'withholding_agent' => $profile->withholding_agent ?? false,
                'expected_income' => $profile->expected_income !== null ? (float) $profile->expected_income : null,
                'regime' => $profile->regime?->value,
                'deduction_method' => $profile->deduction_method?->value,
                'expected_expense_percent' => $profile->expected_expense_percent,
                'clients_withhold' => $profile->clients_withhold?->value,
            ],
            'year' => now()->year,
            'comparison' => in_array($step, [SetupStep::Method, SetupStep::Expenses], true) ? $comparison : null,
            'methodLockedOn' => Filing::firstQuarterReturn(now()->year)?->filed_on->toDateString(),
            'profile' => $step === SetupStep::Summary ? TaxProfileResource::make($profile) : null,
        ]);
    }

    public function update(UpdateSetupStepRequest $request, SetupStep $step): RedirectResponse
    {
        $profile = $request->profile();
        $profile->fill($request->validated());

        if ($step === SetupStep::About) {
            $profile->full_name = Str::squish("{$profile->first_name} {$profile->middle_name} {$profile->last_name}");
        }

        if ($profile->regime === Regime::EightPercent) {
            $profile->deduction_method = null;
        }

        $profile->save();

        return redirect()->route('setup.show', $step->next($profile));
    }

    public function complete(): RedirectResponse
    {
        $profile = TaxProfile::current();

        if ($profile === null) {
            return redirect()->route('setup.show');
        }

        foreach (SetupStep::flow($profile) as $step) {
            if (! $step->isAnswered($profile)) {
                return redirect()->route('setup.show', $step)
                    ->withErrors(['setup' => 'Please answer this question first.']);
            }
        }

        $profile->setup_completed_at ??= now();
        $profile->save();

        return redirect()->route('home');
    }

    private function summary(SetupStep $step, TaxProfile $profile): ?string
    {
        return match (true) {
            $step === SetupStep::Income && $profile->income_source !== null => "How you earn: {$profile->income_source->label()}",
            $step === SetupStep::Work && $profile->work_type !== null => "What you do: {$profile->work_type->label()}",
            $step === SetupStep::Estimate && $profile->expected_income !== null => 'Expected income: ₱'.Number::format((float) $profile->expected_income),
            $step === SetupStep::Method && $profile->regime !== null => "Tax method: {$profile->regime->label()}",
            default => null,
        };
    }
}
