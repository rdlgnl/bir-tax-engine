<?php

namespace App\Returns\Controllers;

use App\Http\Controllers\Controller;
use App\Profiles\Models\TaxProfile;
use App\Returns\Forms\OfficialForms;
use App\Returns\Services\Amendments;
use App\Returns\Services\ReturnForm;
use App\Tax\Services\TaxCalculationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * Amending a filed quarter (design 9-01): the return as filed against the
 * figures now, what the difference costs, and the amended 1701Q.
 */
class AmendmentController extends Controller
{
    public function __construct(
        private Amendments $amendments,
        private ReturnForm $returns,
    ) {}

    public function show(TaxCalculationService $engine, int $year, int $quarter, string $form): Response|RedirectResponse
    {
        $profile = TaxProfile::current();
        $amendment = $form === '1701Q' ? $this->amendments->for($profile, $year, $quarter) : null;

        if ($amendment === null) {
            return redirect()->route('history', ['year' => $year, 'quarter' => $quarter, 'form' => $form]);
        }

        // The next quarter's running total takes in the change and, once paid, the extra paid now.
        $next = $quarter < 3 ? $engine->forYear($profile, $year)['quarters'][$quarter + 1] : null;

        return Inertia::render('returns/amend', [
            'amendment' => $amendment,
            'layout' => $this->returns->layout($this->amendments->snapshot($profile, $amendment)),
            'next' => $next === null || $next['status'] === 'filed' ? null : [
                'quarter' => $next['quarter'],
                'due' => round(max(0.0, $next['due'] - $amendment['extra']), 2),
            ],
        ]);
    }

    public function pdf(Request $request, OfficialForms $forms, int $year, int $quarter, string $form): HttpResponse
    {
        $profile = TaxProfile::current();
        $amendment = $form === '1701Q' ? $this->amendments->for($profile, $year, $quarter) : null;
        abort_if($amendment === null, 404);

        return response($forms->render($this->amendments->snapshot($profile, $amendment)), headers: [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => ($request->boolean('print') ? 'inline' : 'attachment')."; filename={$form}-{$year}-Q{$quarter}-amended.pdf",
        ]);
    }
}
