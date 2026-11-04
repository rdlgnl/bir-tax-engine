<?php

namespace App\Assistant\Services;

use App\Assistant\Exceptions\AssistantUnavailable;
use App\Assistant\Providers\Document;
use App\Assistant\Providers\ProviderFactory;
use App\Tax\Services\TaxRules;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;

/**
 * Reads a letter from the BIR with the assistant: what kind it is, its date,
 * the office, the return and period it names, the reply date, and what it
 * asks. Only when the taxpayer asks, for that letter: the letter itself goes
 * to their provider, their name and TIN on it.
 *
 * What comes back only fills in the letter form, checked against the same
 * rules the form keeps; the taxpayer reviews it before saving.
 *
 * @phpstan-type Reading array{is_bir_letter: bool, kind: string, dated_on: string|null, office: string|null, form: string|null, year: int|null, quarter: int|null, month: int|null, reply_by: string|null, summary: string}
 */
class LetterReader
{
    /** A photo longer than this on either side is made smaller first. */
    private const MAX_SIDE = 2000;

    public function __construct(private ProviderFactory $providers, private TaxRules $rules) {}

    /**
     * @return Reading
     *
     * @throws AssistantUnavailable when the assistant is off or can't read it
     */
    public function read(UploadedFile $file): array
    {
        $settings = AssistantSettings::current();

        if (! $settings->ready()) {
            throw new AssistantUnavailable('Turn the assistant on and add your API key first, under Profile & settings, Your data.');
        }

        $answer = $this->providers->for($settings)->read($this->document($file), $this->instructions(), $this->schema());

        return $this->checked($answer);
    }

    /**
     * What the provider is asked to fill in.
     *
     * @return array<string, mixed>
     */
    public function schema(): array
    {
        $orNull = fn (array $type) => ['anyOf' => [$type, ['type' => 'null']]];
        $properties = [
            'is_bir_letter' => ['type' => 'boolean'],
            'kind' => ['type' => 'string', 'enum' => array_keys((array) config('letters.kinds'))],
            'dated_on' => $orNull(['type' => 'string', 'format' => 'date']),
            'office' => $orNull(['type' => 'string']),
            'form' => $orNull(['type' => 'string', 'enum' => array_values((array) config('letters.forms'))]),
            'year' => $orNull(['type' => 'integer']),
            'quarter' => $orNull(['type' => 'integer']),
            'month' => $orNull(['type' => 'integer']),
            'reply_by' => $orNull(['type' => 'string', 'format' => 'date']),
            'summary' => ['type' => 'string'],
        ];

        return ['type' => 'object', 'properties' => $properties, 'required' => array_keys($properties), 'additionalProperties' => false];
    }

    public function instructions(): string
    {
        $kinds = collect((array) config('letters.kinds'))->map(fn (array $kind, string $key) => "  - {$key}: {$kind['label']}")->implode("\n");
        $forms = implode(', ', (array) config('letters.forms'));
        $today = CarbonImmutable::now()->toDateString();

        return <<<TEXT
            This is a letter or notice a taxpayer received, normally from the Philippine Bureau of Internal Revenue (BIR). Read it and fill in the answer:

            - is_bir_letter: whether it comes from the BIR.
            - kind: which of these it is:
            {$kinds}
            - dated_on: the date on the letter, as YYYY-MM-DD.
            - office: who sent it, such as "Revenue District Office 028 - Quezon City".
            - form: the return it names, if it's one of: {$forms}.
            - year, quarter (1 to 4) and month (1 to 12): the period it's about, as far as it says.
            - reply_by: the date it asks the taxpayer to reply or comply by, as YYYY-MM-DD, only if the letter gives one.
            - summary: two to four plain sentences on what it asks the taxpayer to do, and by when. Don't repeat their name, TIN or address. If it's a Letter of Authority or an assessment, or mentions an audit, end with: talk to a CPA or tax lawyer before replying.

            Use null for anything the letter doesn't say; don't guess. Today is {$today}.
            TEXT;
    }

    /**
     * The upload as the provider gets it: a PDF as is, a large photo made
     * smaller (providers limit an image's size, and read a smaller one as well).
     */
    private function document(UploadedFile $file): Document
    {
        $type = (string) $file->getMimeType();
        $contents = (string) file_get_contents($file->getRealPath());

        if ($type === 'application/pdf' || ! function_exists('imagecreatefromstring')) {
            return new Document($type, $contents);
        }

        $image = @imagecreatefromstring($contents);

        if ($image === false) {
            return new Document($type, $contents);
        }

        $width = imagesx($image);
        $height = imagesy($image);
        $scale = self::MAX_SIDE / max($width, $height);

        if ($scale >= 1 && strlen($contents) < 4_000_000) {
            return new Document($type, $contents);
        }

        $smaller = imagescale($image, max(1, (int) round($width * min(1, $scale))), max(1, (int) round($height * min(1, $scale))));
        ob_start();
        imagejpeg($smaller !== false ? $smaller : $image, null, 85);

        return new Document('image/jpeg', (string) ob_get_clean());
    }

    /**
     * The answer, kept to what the letter form accepts; anything else is left blank.
     *
     * @param  array<string, mixed>  $answer
     * @return Reading
     */
    private function checked(array $answer): array
    {
        $kinds = array_keys((array) config('letters.kinds'));
        $forms = (array) config('letters.forms');
        $int = fn (string $key, int $min, int $max) => is_int($answer[$key] ?? null) && $answer[$key] >= $min && $answer[$key] <= $max ? $answer[$key] : null;
        $month = $int('month', 1, 12);
        $isLetter = ($answer['is_bir_letter'] ?? true) === true;
        $datedOn = $this->date($answer['dated_on'] ?? null);

        return [
            'is_bir_letter' => $isLetter,
            'kind' => $isLetter && in_array($answer['kind'] ?? null, $kinds, true) ? (string) $answer['kind'] : 'other',
            'dated_on' => $datedOn !== null && $datedOn <= CarbonImmutable::now()->toDateString() ? $datedOn : null,
            'office' => is_string($answer['office'] ?? null) && trim($answer['office']) !== '' ? mb_substr(trim($answer['office']), 0, 120) : null,
            'form' => in_array($answer['form'] ?? null, $forms, true) ? (string) $answer['form'] : null,
            'year' => $int('year', $this->rules->firstDate()->year, 2100),
            'quarter' => $int('quarter', 1, 4) ?? ($month !== null ? (int) ceil($month / 3) : null),
            'month' => $month,
            'reply_by' => $this->date($answer['reply_by'] ?? null),
            'summary' => is_string($answer['summary'] ?? null) ? mb_substr(trim($answer['summary']), 0, 1500) : '',
        ];
    }

    private function date(mixed $value): ?string
    {
        if (! is_string($value) || preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $parts) !== 1) {
            return null;
        }

        return checkdate((int) $parts[2], (int) $parts[3], (int) $parts[1]) ? $value : null;
    }
}
