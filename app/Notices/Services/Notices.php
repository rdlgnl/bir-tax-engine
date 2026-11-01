<?php

namespace App\Notices\Services;

use App\Compliance\Services\TaxObligations;
use App\Data\Models\Setting;
use App\Data\Services\Backups;
use App\Notices\Models\Notice;
use App\Profiles\Models\TaxProfile;
use App\Returns\Services\Amendments;
use App\Tax\Services\TaxCalculationService;
use App\Transactions\Enums\CertificateStatus;
use App\Transactions\Models\Transaction;
use Carbon\CarbonImmutable;
use Closure;

/**
 * Notifications: what needs attention now (worked out on the spot) and
 * what happened (recorded as it happens). "Read" is a moment: everything
 * recorded before it, and every current item seen then, counts as read.
 *
 * @phpstan-type Item array{key: string, kind: string, title: string, detail: string|null, url: string|null, at: string}
 */
class Notices
{
    public function __construct(
        private TaxCalculationService $engine,
        private TaxObligations $obligations,
        private Backups $backups,
        private Amendments $amendments,
    ) {}

    public function record(string $kind, string $title, ?string $detail = null, ?string $url = null): void
    {
        Notice::query()->create(compact('kind', 'title', 'detail', 'url'));
    }

    /**
     * Runs a change to the records and notes it when it moved the estimate
     * of the quarter it touched. Filed quarters don't move.
     */
    public function trackEstimate(int $year, int $quarter, string $because, Closure $change): void
    {
        $profile = TaxProfile::current();
        $before = $profile ? $this->estimate($profile, $year, $quarter) : null;

        $change();

        $after = $profile ? $this->estimate($profile, $year, $quarter) : null;

        if ($before === null || $after === null || abs($after - $before) < 1) {
            return;
        }

        $label = $quarter === 4 ? "{$year} annual" : "Q{$quarter}";
        $this->record(
            'estimate',
            sprintf('%s estimate went %s ₱%s', $label, $after > $before ? 'up' : 'down', number_format(abs($after - $before))),
            $because,
            route('tax.show', $quarter, absolute: false),
        );
    }

    /**
     * What needs attention today.
     *
     * @return list<Item>
     */
    public function live(TaxProfile $profile, CarbonImmutable $today): array
    {
        $items = [];
        $next = $this->obligations->nextPayment($profile, $this->engine->forYear($profile, $today->year, $today), $today);
        $quarter = $next['quarter'];

        if ($quarter !== null && $quarter['days_left'] <= 30 && $quarter['due'] > 0) {
            $days = $quarter['days_left'];
            $items[] = $this->item(
                "due:{$next['year']}-{$quarter['quarter']}-{$quarter['form']}",
                'due',
                '₱'.number_format($quarter['due']).' '.match (true) {
                    $days < 0 => 'overdue',
                    $days === 0 => 'due today',
                    default => "due in {$days} ".($days === 1 ? 'day' : 'days'),
                },
                ($quarter['quarter'] === 4 ? 'Annual' : "Q{$quarter['quarter']}").' income tax · file and pay by '
                    .CarbonImmutable::parse($quarter['due_date'])->format('D, M j'),
                route('returns.show', [$next['year'], $quarter['quarter'], $quarter['form']], absolute: false),
                $today,
            );
        }

        foreach ($this->amendments->needed($profile, $today) as $amendment) {
            $items[] = $this->item(
                "amend:{$amendment['year']}-{$amendment['quarter']}-{$amendment['number']}-{$amendment['amended']['due']}",
                'amend',
                "Q{$amendment['quarter']} {$amendment['year']} changed after you filed it",
                $amendment['extra'] > 0
                    ? 'Amend it: ₱'.number_format($amendment['extra']).' more to pay'
                    : 'Amend it so the figures on file match',
                route('returns.amend', [$amendment['year'], $amendment['quarter'], $amendment['form']], absolute: false),
                $today,
            );
        }

        if (($reminder = $this->backups->reminder($today)) !== null) {
            $days = $reminder['days_since'];
            $items[] = $this->item(
                'backup:'.($this->backups->last()['at'] ?? 'never'),
                'backup',
                'Time for a backup',
                $days === null ? 'You haven’t backed up yet' : "Last backup was {$days} days ago",
                route('backup', absolute: false),
                $today,
            );
        }

        $missing = Transaction::query()->where('tax_withheld', '>', 0)->whereNull('certificate_received_on')->get()
            ->filter(fn (Transaction $t) => $t->certificateStatus($today) === CertificateStatus::Missing);

        if ($missing->isNotEmpty()) {
            $clients = $missing->pluck('counterparty')->unique()->values();
            $items[] = $this->item(
                'missing:'.$missing->pluck('id')->sort()->implode(','),
                'certificate',
                $clients->count() === 1 ? "{$clients[0]}’s 2307 is missing" : "{$clients->count()} clients’ 2307s are missing",
                'Without them, the tax they withheld can’t be credited',
                route('compliance.show', absolute: false),
                $today,
            );
        }

        return $items;
    }

    /**
     * @return array{new: list<Item>, earlier: list<Item>}
     */
    public function feed(TaxProfile $profile, CarbonImmutable $today): array
    {
        $readAt = $this->readAt();
        $stored = Notice::query()->latest('created_at')->latest('id')->limit(60)->get()
            ->map(fn (Notice $n) => $this->item("notice:{$n->id}", $n->kind, $n->title, $n->detail, $n->url, $n->created_at));

        $isNew = fn (array $i) => $readAt === null || $i['at'] > $readAt->toIso8601String();

        return [
            'new' => [...$this->live($profile, $today), ...array_values($stored->filter($isNew)->all())],
            'earlier' => array_values($stored->reject($isNew)->all()),
        ];
    }

    public function unreadCount(TaxProfile $profile, CarbonImmutable $today): int
    {
        $readAt = $this->readAt();
        $seen = (array) Setting::get('notices_seen_live', []);
        $live = collect($this->live($profile, $today))->reject(fn (array $i) => in_array($i['key'], $seen, true))->count();
        $stored = Notice::query()->when($readAt, fn ($q) => $q->where('created_at', '>', $readAt))->count();

        return $live + $stored;
    }

    public function markAllRead(TaxProfile $profile, CarbonImmutable $now): void
    {
        Setting::put('notices_read_at', $now->toIso8601String());
        Setting::put('notices_seen_live', array_column($this->live($profile, $now), 'key'));
    }

    private function readAt(): ?CarbonImmutable
    {
        $at = Setting::get('notices_read_at');

        return is_string($at) ? CarbonImmutable::parse($at) : null;
    }

    private function estimate(TaxProfile $profile, int $year, int $quarter): ?float
    {
        $result = $this->engine->forYear($profile, $year);
        $q = $result['quarters'][$quarter] ?? null;

        return $result['status'] !== 'ok' || $q === null || $q['status'] === 'filed' ? null : (float) $q['due'];
    }

    /**
     * @return Item
     */
    private function item(string $key, string $kind, string $title, ?string $detail, ?string $url, CarbonImmutable $at): array
    {
        return ['key' => $key, 'kind' => $kind, 'title' => $title, 'detail' => $detail, 'url' => $url, 'at' => $at->toIso8601String()];
    }
}
