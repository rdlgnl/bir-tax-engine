<?php

use App\Assistant\Exceptions\AssistantUnavailable;
use App\Assistant\Providers\Document;
use App\Assistant\Providers\Provider;
use App\Assistant\Providers\ProviderFactory;
use App\Assistant\Providers\Turn;
use App\Assistant\Services\AssistantSettings;
use App\Assistant\Services\TaxLookups;
use App\Profiles\Models\TaxProfile;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * A provider that reads every letter the same way, or fails.
 */
class LetterReadingProvider implements Provider
{
    public int $reads = 0;

    /** @param array<string, mixed>|AssistantUnavailable $answer */
    public function __construct(private array|AssistantUnavailable $answer) {}

    public function ask(array $history, string $question, TaxLookups $lookups): Turn
    {
        return new Turn('', $history, []);
    }

    public function read(Document $document, string $instructions, array $schema): array
    {
        $this->reads++;

        return $this->answer instanceof AssistantUnavailable ? throw $this->answer : $this->answer;
    }
}

function readingWith(array|AssistantUnavailable $answer): LetterReadingProvider
{
    $provider = new LetterReadingProvider($answer);
    app()->instance(ProviderFactory::class, new class($provider) extends ProviderFactory
    {
        public function __construct(private LetterReadingProvider $provider) {}

        public function for(AssistantSettings $settings): Provider
        {
            return $this->provider;
        }
    });

    return $provider;
}

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-11-04 10:00'));
    TaxProfile::factory()->create();
});

$letter = fn () => UploadedFile::fake()->create('letter.pdf', 200, 'application/pdf');

test('without the assistant on, the page offers to turn it on and reading is refused', function () use ($letter) {
    $provider = readingWith(['kind' => 'unfiled', 'summary' => 'x']);

    $this->get('/letters/new')->assertInertia(fn (Assert $page) => $page
        ->component('letters/create')
        ->where('assistant.ready', false)
        ->where('reading', null));

    $this->post('/letters/read', ['file' => $letter(), 'consent' => true])->assertSessionHasErrors(['file' => 'Turn the assistant on and add your API key first, under Profile & settings, Your data.']);
    expect($provider->reads)->toBe(0);
});

test('the letter is read only with consent, and fills in the form once', function () use ($letter) {
    AssistantSettings::current()->save(['enabled' => true, 'provider' => 'claude', 'model' => 'claude-opus-5-5', 'base_url' => null], 'sk-ant-test');
    $provider = readingWith(['is_bir_letter' => true, 'kind' => 'unpaid', 'form' => '2551Q', 'year' => 2026, 'quarter' => 2, 'month' => null, 'dated_on' => '2026-10-30', 'office' => 'RDO 028', 'reply_by' => '2026-11-15', 'summary' => 'It asks you to pay the Q2 percentage tax.']);

    $this->get('/letters/new')->assertInertia(fn (Assert $page) => $page->where('assistant', ['ready' => true, 'provider' => 'Claude Opus 5.5']));

    $this->from('/letters/new')->post('/letters/read', ['file' => $letter()])->assertSessionHasErrors('consent');
    expect($provider->reads)->toBe(0);

    $this->from('/letters/new')->post('/letters/read', ['file' => $letter(), 'consent' => true])->assertRedirect('/letters/new');

    $this->get('/letters/new')->assertInertia(fn (Assert $page) => $page
        ->where('reading.kind', 'unpaid')
        ->where('reading.form', '2551Q')
        ->where('reading.quarter', 2)
        ->where('reading.reply_by', '2026-11-15')
        ->where('reading.summary', 'It asks you to pay the Q2 percentage tax.'));

    // Flashed: the next visit starts blank.
    $this->get('/letters/new')->assertInertia(fn (Assert $page) => $page->where('reading', null));
});

test('a letter the assistant can’t read says why, on the file', function () use ($letter) {
    AssistantSettings::current()->save(['enabled' => true, 'provider' => 'claude', 'model' => 'claude-opus-5-5', 'base_url' => null], 'sk-ant-test');
    readingWith(new AssistantUnavailable("Anthropic didn't accept your API key."));

    $this->from('/letters/new')->post('/letters/read', ['file' => $letter(), 'consent' => true])
        ->assertSessionHasErrors(['file' => "Anthropic didn't accept your API key."]);
    $this->post('/letters/read', ['file' => UploadedFile::fake()->create('letter.docx', 10), 'consent' => true])->assertSessionHasErrors('file');
});
