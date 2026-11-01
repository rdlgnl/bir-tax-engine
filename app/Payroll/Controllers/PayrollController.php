<?php

namespace App\Payroll\Controllers;

use App\Http\Controllers\Controller;
use App\Payroll\Models\Employee;
use App\Payroll\Requests\SaveEmployeeRequest;
use App\Payroll\Services\Payroll;
use App\Profiles\Models\TaxProfile;
use App\Returns\Forms\Items\Form1601CItems;
use App\Returns\Forms\OfficialFormFiller;
use App\Tax\Services\FilingCalendar;
use App\Tax\Services\Penalties;
use App\Tax\Services\TaxCalculationService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * Paying employees (design 9-05): each one's pay and the tax withheld from
 * it, and each month's 1601-C (13-05) remitting that tax.
 *
 * @phpstan-import-type RemittanceMonth from Payroll
 * @phpstan-import-type Penalty from Penalties
 */
class PayrollController extends Controller
{
    public function __construct(private Payroll $payroll) {}

    public function index(?int $year = null): Response
    {
        $year ??= CarbonImmutable::now()->year;

        return Inertia::render('payroll/index', [
            'year' => $year,
            'employees' => Employee::query()->orderBy('last_name')->get()->map(fn (Employee $e) => [
                ...$this->employee($e),
                'year' => $this->payroll->employeeYear($e, $year),
            ])->all(),
            'months' => $this->payroll->year($year),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('payroll/employee', ['employee' => null, 'payday' => null]);
    }

    public function store(SaveEmployeeRequest $request): RedirectResponse
    {
        $employee = Employee::query()->create($request->validated());
        Inertia::flash('toast', 'Payroll saved.');

        return redirect()->route('payroll.employee', $employee);
    }

    public function show(Employee $employee, FilingCalendar $calendar): Response
    {
        $today = CarbonImmutable::now();
        $year = $this->payroll->employeeYear($employee, $today->year);
        $withheld = $this->payroll->monthlyWithholding((float) $employee->monthly_pay);
        $next = collect($this->payroll->year($today->year, $today))->first(fn (array $m) => $m['status'] !== 'filed');

        return Inertia::render('payroll/employee', [
            'employee' => $this->employee($employee),
            'payday' => [
                'withheld' => $withheld,
                'net' => round((float) $employee->monthly_pay - $withheld, 2),
            ],
            'yearEnd' => [
                'year' => $today->year,
                ...$year,
                // Withheld before the last month, which the year-end adjustment settles.
                'withheld_before' => round(array_sum(array_column(array_slice($year['months'], 0, -1), 'withheld')), 2),
                'refund_by' => $calendar->nextWorkingDay(CarbonImmutable::create($today->year + 1, 1, 25))->toDateString(),
            ],
            'nextRemittance' => $next,
            'annualDue' => $calendar->nextWorkingDay(CarbonImmutable::create($today->year + 1, 1, 31))->toDateString(),
            'regime' => TaxProfile::current()?->regime?->value,
        ]);
    }

    public function update(SaveEmployeeRequest $request, Employee $employee): RedirectResponse
    {
        $employee->update($request->validated());

        return Inertia::flash('toast', 'Payroll saved.')->back();
    }

    public function destroy(Employee $employee): RedirectResponse
    {
        $employee->delete();
        Inertia::flash('toast', 'Employee removed.');

        return redirect()->route('payroll');
    }

    public function month(int $year, int $month, Penalties $penalties, TaxCalculationService $engine): Response
    {
        $remittance = $this->remittance($year, $month);

        return Inertia::render('payroll/month', [
            'year' => $year,
            'remittance' => $remittance,
            'penalties' => $this->penalties($year, $remittance, $penalties, $engine),
            'edition' => (string) config('tax.forms.1601-C.edition'),
            'employees' => Employee::query()->get()->filter(fn (Employee $e) => $e->workedIn($year, $month))
                ->map(fn (Employee $e) => ['name' => $e->name(), 'tin' => $e->tin])->values()->all(),
        ]);
    }

    public function monthPdf(Request $request, int $year, int $month, Form1601CItems $items, OfficialFormFiller $filler, Penalties $penalties, TaxCalculationService $engine): HttpResponse
    {
        $remittance = $this->remittance($year, $month);
        $values = $items->values(TaxProfile::current(), $year, $remittance, $this->penalties($year, $remittance, $penalties, $engine));

        return response($filler->render('1601-C', $values), headers: [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => ($request->boolean('print') ? 'inline' : 'attachment').sprintf('; filename=1601-C-%d-%02d.pdf', $year, $month),
        ]);
    }

    /**
     * @return RemittanceMonth
     */
    private function remittance(int $year, int $month): array
    {
        abort_if(CarbonImmutable::create($year, $month)->isAfter(CarbonImmutable::now()), 404);

        foreach ($this->payroll->year($year) as $remittance) {
            if ($remittance['month'] === $month) {
                return $remittance;
            }
        }

        abort(404);
    }

    /**
     * @param  RemittanceMonth  $remittance
     * @return Penalty|null
     */
    private function penalties(int $year, array $remittance, Penalties $penalties, TaxCalculationService $engine): ?array
    {
        if ($remittance['status'] !== 'overdue' || $remittance['withheld'] <= 0) {
            return null;
        }

        return $penalties->late($remittance['withheld'], CarbonImmutable::parse($remittance['due_date']), CarbonImmutable::now(), $engine->forYear(TaxProfile::current(), $year)['gross']);
    }

    /**
     * @return array<string, mixed>
     */
    private function employee(Employee $employee): array
    {
        return [
            'id' => $employee->id,
            'name' => $employee->name(),
            'last_name' => $employee->last_name,
            'first_name' => $employee->first_name,
            'middle_name' => $employee->middle_name,
            'tin' => $employee->tin,
            'position' => $employee->position,
            'monthly_pay' => (float) $employee->monthly_pay,
            'started_on' => $employee->started_on->toDateString(),
            'ended_on' => $employee->ended_on?->toDateString(),
        ];
    }
}
