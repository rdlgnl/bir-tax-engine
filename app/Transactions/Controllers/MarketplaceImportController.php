<?php

namespace App\Transactions\Controllers;

use App\Http\Controllers\Controller;
use App\Notices\Services\Notices;
use App\Transactions\Requests\PayoutColumnsRequest;
use App\Transactions\Requests\UploadPayoutReportRequest;
use App\Transactions\Services\MarketplaceImport;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Importing an online marketplace's payout report (design 11-06): choose the
 * platform and file, check what was found and how it's recorded, then
 * import. The file is read on this computer and removed once imported or
 * cancelled.
 *
 * @phpstan-import-type Mapping from MarketplaceImport
 */
class MarketplaceImportController extends Controller
{
    private const FILE = 'imports/marketplace.csv';

    private const META = 'imports/marketplace.json';

    public function __construct(private MarketplaceImport $reports) {}

    public function show(): Response
    {
        $meta = $this->meta();

        if ($meta === null) {
            return Inertia::render('transactions/marketplace', [
                'report' => null,
                'platforms' => config('import.marketplace.platforms'),
            ]);
        }

        $report = $this->reports->read($this->path(), $meta['mapping']);
        $sorted = $report['matched'] ? $this->reports->sort($report['orders'], $meta['platform'], CarbonImmutable::now()) : [];
        $dates = array_filter(array_column($report['orders'], 'date'));

        return Inertia::render('transactions/marketplace', [
            'report' => [
                'name' => $meta['name'],
                'platform' => $meta['platform'],
                'columns' => $report['columns'],
                'mapping' => $report['mapping'],
                'matched' => $report['matched'],
                'from' => $dates === [] ? null : min($dates),
                'to' => $dates === [] ? null : max($dates),
            ],
            'summary' => $report['matched'] ? $this->reports->summary($sorted) : null,
            'rate' => (float) config('import.marketplace.withholding_rate'),
            'exemptUpTo' => (float) config('import.marketplace.exempt_up_to'),
            'platforms' => config('import.marketplace.platforms'),
        ]);
    }

    public function upload(UploadPayoutReportRequest $request): RedirectResponse
    {
        $file = $request->file('report');
        $disk = Storage::disk('local');
        $disk->putFileAs('imports', $file, 'marketplace.csv');
        $disk->put(self::META, (string) json_encode([
            'name' => basename($file->getClientOriginalName()),
            'platform' => Str::squish((string) $request->validated('platform')),
            'mapping' => null,
        ]));

        $report = $this->reports->read($this->path());

        if ($report['columns'] === [] || ($report['matched'] && $report['orders'] === [])) {
            $this->cancel();

            return back()->withErrors(['report' => 'No orders could be read from this file. Is it the payout report CSV?']);
        }

        return redirect()->route('transactions.marketplace');
    }

    public function columns(PayoutColumnsRequest $request): RedirectResponse
    {
        $meta = $this->meta() ?? abort(404);
        $optional = fn (string $key) => $request->filled($key) ? $request->integer($key) : null;
        $meta['mapping'] = [
            'date' => $request->integer('date'),
            'order' => $optional('order'),
            'sales' => $request->integer('sales'),
            'fees' => $optional('fees'),
            'withheld' => $optional('withheld'),
            'payout' => $optional('payout'),
        ];
        Storage::disk('local')->put(self::META, (string) json_encode($meta));

        return back();
    }

    public function confirm(Notices $notices): RedirectResponse
    {
        $meta = $this->meta() ?? abort(404);
        $report = $this->reports->read($this->path(), $meta['mapping']);
        abort_unless($report['matched'], 404);
        $result = $this->reports->import($this->reports->sort($report['orders'], $meta['platform'], CarbonImmutable::now()), $meta['platform']);

        $this->cancel();

        if ($result['orders'] > 0) {
            $notices->record('import', sprintf('Imported %d %s orders · ₱%s', $result['orders'], $meta['platform'], number_format($result['sales'])),
                "From {$meta['name']}", route('transactions.index', absolute: false));
        }

        Inertia::flash('toast', $result['orders'] === 0
            ? 'Nothing new to import.'
            : sprintf('Imported %d %s. Your tax is up to date.', $result['orders'], $result['orders'] === 1 ? 'order' : 'orders'));

        return redirect()->route('transactions.index');
    }

    public function cancel(): RedirectResponse
    {
        Storage::disk('local')->delete([self::FILE, self::META]);

        return redirect()->route('transactions.marketplace');
    }

    /**
     * @return array{name: string, platform: string, mapping: Mapping|null}|null
     */
    private function meta(): ?array
    {
        $disk = Storage::disk('local');

        if (! $disk->exists(self::FILE) || ! $disk->exists(self::META)) {
            return null;
        }

        $meta = json_decode((string) $disk->get(self::META), true);

        if (! is_array($meta)) {
            return null;
        }

        $mapping = is_array($meta['mapping'] ?? null) ? $meta['mapping'] : null;
        $at = fn (string $key) => isset($mapping[$key]) && is_int($mapping[$key]) ? $mapping[$key] : null;

        return [
            'name' => (string) ($meta['name'] ?? 'payouts.csv'),
            'platform' => (string) ($meta['platform'] ?? 'Marketplace'),
            'mapping' => $mapping === null ? null : [
                'date' => $at('date'), 'order' => $at('order'), 'sales' => $at('sales'),
                'fees' => $at('fees'), 'withheld' => $at('withheld'), 'payout' => $at('payout'),
            ],
        ];
    }

    private function path(): string
    {
        return Storage::disk('local')->path(self::FILE);
    }
}
