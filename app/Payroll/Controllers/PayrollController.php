<?php

namespace App\Payroll\Controllers;

use App\Http\Controllers\Controller;
use App\Payroll\Models\Employee;
use App\Payroll\Models\EmployeeCertificate;
use App\Payroll\Requests\SaveEmployeeRequest;
use App\Payroll\Services\Payroll;
use App\Profiles\Models\TaxProfile;
use App\Returns\Forms\Items\Form1601CItems;
use App\Returns\Forms\Items\Form1604CItems;
use App\Returns\Forms\Items\Form2316Items;
use App\Returns\Forms\OfficialFormFiller;
use App\Tax\Services\FilingCalendar;
use App\Tax\Services\Penalties;
use App\Tax\Services\TaxCalculationService;
use App\Tax\Services\TaxRules;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
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

    public function create(TaxRules $rules): Response
    {
        return Inertia::render('payroll/employee', ['employee' => null, 'payday' => null, 'deMinimis' => $this->deMinimis($rules)]);
    }

    public function store(SaveEmployeeRequest $request): RedirectResponse
    {
        $employee = Employee::query()->create($request->validated());
        Inertia::flash('toast', 'Payroll saved.');

        return redirect()->route('payroll.employee', $employee);
    }

    public function show(Employee $employee, FilingCalendar $calendar, TaxRules $rules): Response
    {
        $today = CarbonImmutable::now();
        $year = $this->payroll->employeeYear($employee, $today->year);
        // This month's withholding, or the table's on a month outside the year-end adjustment.
        $month = collect($year['months'])->first(fn (array $m) => $m['month'] === $today->month && ! $m['year_end']);
        $withheld = $month['withheld'] ?? $this->payroll->monthlyWithholding($this->payroll->taxablePay($employee));
        $next = collect($this->payroll->year($today->year, $today))->first(fn (array $m) => $m['status'] !== 'filed');
        $deductions = (float) $employee->contributions + $withheld;

        return Inertia::render('payroll/employee', [
            'employee' => $this->employee($employee),
            'payday' => [
                'pay' => (float) $employee->monthly_pay,
                'de_minimis' => (float) $employee->de_minimis,
                'contributions' => (float) $employee->contributions,
                'taxable' => $this->payroll->taxablePay($employee),
                'withheld' => $withheld,
                'net' => round((float) $employee->monthly_pay + (float) $employee->de_minimis - $deductions, 2),
            ],
            'deMinimis' => $this->deMinimis($rules),
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

    /**
     * The 2316 an employee is given for a year (design 9-06).
     */
    public function certificate(Employee $employee, FilingCalendar $calendar, ?int $year = null): Response
    {
        $year ??= $this->certificateYear();
        $pay = $this->payroll->employeeYear($employee, $year);
        abort_if($pay['months'] === [], 404);
        $given = EmployeeCertificate::query()->where('employee_id', $employee->id)->where('year', $year)->first();

        return Inertia::render('payroll/certificate', [
            'employee' => $this->employee($employee),
            'year' => $year,
            'pay' => $pay,
            'givenOn' => $given?->given_on->toDateString(),
            'dueDate' => $this->payroll->annualDue($year)->toDateString(),
            'refundBy' => $calendar->nextWorkingDay(CarbonImmutable::create($year + 1, 1, 25))->toDateString(),
            'edition' => (string) config('tax.forms.2316.edition'),
        ]);
    }

    public function certificatePdf(Request $request, Employee $employee, int $year, Form2316Items $items, OfficialFormFiller $filler): HttpResponse
    {
        $pay = $this->payroll->employeeYear($employee, $year);
        abort_if($pay['months'] === [], 404);

        return response($filler->render('2316', $items->values(TaxProfile::current(), $employee, $year, $pay)), headers: [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => ($request->boolean('print') ? 'inline' : 'attachment')."; filename=2316-{$year}-".Str::slug($employee->name()).'.pdf',
        ]);
    }

    public function given(Employee $employee, int $year): RedirectResponse
    {
        abort_if($this->payroll->employeeYear($employee, $year)['months'] === [], 404);
        EmployeeCertificate::query()->firstOrCreate(
            ['employee_id' => $employee->id, 'year' => $year],
            ['given_on' => CarbonImmutable::now()->toDateString()],
        );

        return Inertia::flash('toast', "{$employee->first_name}'s 2316 is marked given.")->back();
    }

    /**
     * The 1604-C, the year's list of employees (design 13-06).
     */
    public function annual(int $year): Response
    {
        $annual = $this->payroll->annual($year);
        abort_if($annual['months'] === [], 404);

        return Inertia::render('payroll/annual', [
            'annual' => $annual,
            'edition' => (string) config('tax.forms.1604-C.edition'),
        ]);
    }

    public function annualPdf(Request $request, int $year, Form1604CItems $items, OfficialFormFiller $filler): HttpResponse
    {
        $annual = $this->payroll->annual($year);
        abort_if($annual['months'] === [], 404);

        return response($filler->render('1604-C', $items->values(TaxProfile::current(), $annual)), headers: [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => ($request->boolean('print') ? 'inline' : 'attachment')."; filename=1604-C-{$year}.pdf",
        ]);
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
     * The 2316 is for the year just ended until April, then for this year.
     */
    private function certificateYear(): int
    {
        $today = CarbonImmutable::now();

        return $today->month <= 4 ? $today->year - 1 : $today->year;
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
            'contributions' => (float) $employee->contributions,
            'de_minimis' => (float) $employee->de_minimis,
            'minimum_wage' => $employee->minimum_wage,
            'started_on' => $employee->started_on->toDateString(),
            'ended_on' => $employee->ended_on?->toDateString(),
            'previous_pay' => $employee->previous_pay !== null ? (float) $employee->previous_pay : null,
            'previous_withheld' => $employee->previous_withheld !== null ? (float) $employee->previous_withheld : null,
        ];
    }

    /**
     * The de minimis limits in force today, for the employee form.
     *
     * @return list<array{label: string, amount: float, per: string}>
     */
    private function deMinimis(TaxRules $rules): array
    {
        /** @var array<string, array{label: string, amount: int|float, per: string}> $limits */
        $limits = $rules->on(CarbonImmutable::now())->get('de_minimis');

        return array_values(array_map(fn (array $limit) => ['label' => $limit['label'], 'amount' => (float) $limit['amount'], 'per' => $limit['per']], $limits));
    }
}
