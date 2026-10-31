<?php

namespace App\Transactions\Controllers;

use App\Http\Controllers\Controller;
use App\Notices\Services\Notices;
use App\Transactions\Requests\ConfirmImportRequest;
use App\Transactions\Requests\StatementColumnsRequest;
use App\Transactions\Requests\UploadStatementRequest;
use App\Transactions\Services\StatementImport;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Importing payments from a statement: choose the file, check how its lines
 * were sorted, answer the ones only you can, then import. The file is read
 * on this computer and removed once imported or cancelled.
 */
class TransactionImportController extends Controller
{
    private const FILE = 'imports/pending.csv';

    private const META = 'imports/pending.json';

    /** Rows sent to the page; the summary counts them all. */
    private const SHOWN = 1000;

    public function __construct(private StatementImport $statements) {}

    public function show(): Response
    {
        $meta = $this->meta();

        if ($meta === null) {
            return Inertia::render('transactions/import', ['statement' => null]);
        }

        $statement = $this->statements->read($this->path(), $meta['mapping']);
        $rows = $statement['matched'] ? $this->statements->classify($statement['lines'], CarbonImmutable::now()) : [];
        $imports = array_values(array_filter($rows, fn (array $r) => $r['status'] === 'import'));
        $count = fn (string $status) => count(array_filter($rows, fn (array $r) => $r['status'] === $status));

        return Inertia::render('transactions/import', [
            'statement' => [
                'name' => $meta['name'],
                'columns' => $statement['columns'],
                'mapping' => $statement['mapping'],
                'matched' => $statement['matched'],
                'lines' => count($statement['lines']),
            ],
            'summary' => [
                'payments' => count($imports),
                'total' => round(array_sum(array_column($imports, 'amount')), 2),
                'clients' => count(array_unique(array_column($imports, 'client'))),
                'ignored' => $count('ignore'),
                'duplicates' => $count('duplicate'),
                'answers' => $count('answer'),
                'filed' => $count('filed'),
                'invalid' => $count('invalid'),
            ],
            'rows' => array_slice($rows, 0, self::SHOWN),
        ]);
    }

    public function upload(UploadStatementRequest $request): RedirectResponse
    {
        $file = $request->file('statement');
        $disk = Storage::disk('local');
        $disk->putFileAs('imports', $file, 'pending.csv');
        $disk->put(self::META, (string) json_encode(['name' => basename($file->getClientOriginalName()), 'mapping' => null]));

        $statement = $this->statements->read($this->path());

        if ($statement['columns'] === [] || ($statement['matched'] && $statement['lines'] === [])) {
            $this->cancel();

            return back()->withErrors(['statement' => 'No lines could be read from this file. Is it the statement CSV?']);
        }

        return redirect()->route('transactions.import');
    }

    public function columns(StatementColumnsRequest $request): RedirectResponse
    {
        $meta = $this->meta() ?? abort(404);
        $meta['mapping'] = [
            'date' => $request->integer('date'),
            'description' => $request->integer('description'),
            'amount' => $request->filled('amount') ? $request->integer('amount') : null,
            'credit' => $request->filled('credit') ? $request->integer('credit') : null,
            'debit' => $request->filled('debit') ? $request->integer('debit') : null,
        ];
        Storage::disk('local')->put(self::META, (string) json_encode($meta));

        return back();
    }

    public function confirm(ConfirmImportRequest $request, Notices $notices): RedirectResponse
    {
        $meta = $this->meta() ?? abort(404);
        $today = CarbonImmutable::now();
        $statement = $this->statements->read($this->path(), $meta['mapping']);
        $rows = $this->statements->classify($statement['lines'], $today);
        $result = $this->statements->import($rows, (array) $request->validated('answers', []), $today);

        $this->cancel();

        if ($result['imported'] > 0) {
            $notices->record('import', sprintf('Imported %d %s · ₱%s', $result['imported'], $result['imported'] === 1 ? 'payment' : 'payments', number_format($result['total'])),
                "From {$meta['name']}", route('transactions.index', absolute: false));
        }

        Inertia::flash('toast', $result['imported'] === 0
            ? 'Nothing new to import.'
            : sprintf('Imported %d %s. Your tax is up to date.', $result['imported'], $result['imported'] === 1 ? 'payment' : 'payments')
                .($result['skipped'] > 0 ? " {$result['skipped']} in a filed quarter were left out." : ''));

        return redirect()->route('transactions.index');
    }

    public function cancel(): RedirectResponse
    {
        Storage::disk('local')->delete([self::FILE, self::META]);

        return redirect()->route('transactions.import');
    }

    /**
     * @return array{name: string, mapping: array{date: int|null, description: int|null, amount: int|null, credit: int|null, debit: int|null}|null}|null
     */
    private function meta(): ?array
    {
        $disk = Storage::disk('local');

        if (! $disk->exists(self::FILE) || ! $disk->exists(self::META)) {
            return null;
        }

        $meta = json_decode((string) $disk->get(self::META), true);

        return is_array($meta) ? ['name' => (string) ($meta['name'] ?? 'statement.csv'), 'mapping' => $meta['mapping'] ?? null] : null;
    }

    private function path(): string
    {
        return Storage::disk('local')->path(self::FILE);
    }
}
