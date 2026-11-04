<?php

use App\Assistant\Services\AssistantSettings;
use App\Data\Models\Setting;
use App\Data\Services\LocalData;
use App\Profiles\Models\TaxProfile;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    TaxProfile::factory()->create();
});

$claude = ['enabled' => true, 'provider' => 'claude', 'model' => 'claude-opus-5-5'];

test('the assistant is off by default, with no key', function () {
    $this->get('/data')->assertInertia(fn (Assert $page) => $page
        ->where('assistant.enabled', false)
        ->where('assistant.provider', 'claude')
        ->where('assistant.model', 'claude-opus-5-5')
        ->where('assistant.has_key', false));

    $this->get('/ask')->assertInertia(fn (Assert $page) => $page->component('ask')->where('ready', false)->where('enabled', false));
    $this->post('/ask', ['question' => 'What is due?'])->assertSessionHasErrors('question');
});

test('the key is kept encrypted and never sent back to the page', function () use ($claude) {
    $this->put('/data/assistant', [...$claude, 'key' => 'sk-ant-secret-123'])->assertSessionHasNoErrors()->assertRedirect();

    $raw = (string) DB::table('app_settings')->where('key', 'assistant')->value('value');
    expect($raw)->not->toContain('sk-ant-secret-123')
        ->and(AssistantSettings::current()->key())->toBe('sk-ant-secret-123')
        ->and(AssistantSettings::current()->ready())->toBeTrue();

    $page = $this->get('/data')->assertInertia(fn (Assert $page) => $page->where('assistant.has_key', true)->where('assistant.enabled', true));
    expect($page->getContent())->not->toContain('sk-ant-secret-123');
});

test('a blank key keeps the saved one, and it can be removed', function () use ($claude) {
    $this->put('/data/assistant', [...$claude, 'key' => 'sk-ant-secret-123']);
    $this->put('/data/assistant', [...$claude, 'model' => 'claude-sonnet-5-5', 'key' => ''])->assertSessionHasNoErrors();

    expect(AssistantSettings::current())->model->toBe('claude-sonnet-5-5')
        ->and(AssistantSettings::current()->key())->toBe('sk-ant-secret-123');

    $this->put('/data/assistant', [...$claude, 'enabled' => false, 'remove_key' => true])->assertSessionHasNoErrors();
    expect(AssistantSettings::current()->hasKey())->toBeFalse();
});

test('Claude needs a key to turn on; OpenAI-compatible servers need an address and a model', function () use ($claude) {
    $this->put('/data/assistant', $claude)->assertSessionHasErrors('key');
    $this->put('/data/assistant', [...$claude, 'model' => 'claude-instant'])->assertSessionHasErrors('model');
    $this->put('/data/assistant', ['enabled' => true, 'provider' => 'openai', 'model' => '', 'base_url' => 'not a url'])->assertSessionHasErrors(['model', 'base_url']);

    // A local server may need no key.
    $this->put('/data/assistant', ['enabled' => true, 'provider' => 'openai', 'model' => 'llama3.2', 'base_url' => 'http://localhost:11434/v1/'])->assertSessionHasNoErrors();
    expect(AssistantSettings::current())
        ->baseUrl->toBe('http://localhost:11434/v1')
        ->and(AssistantSettings::current()->ready())->toBeTrue()
        ->and(AssistantSettings::current()->label())->toBe('llama3.2 at localhost');
});

test('the key stays out of backups, and erasing the data removes it', function () use ($claude) {
    $this->put('/data/assistant', [...$claude, 'key' => 'sk-ant-secret-123']);

    expect(LocalData::TABLES)->not->toContain('app_settings');

    $this->delete('/data', ['confirmation' => 'ERASE']);
    expect(Setting::get('assistant'))->toBeNull();
});

test('a key the app can no longer read counts as none', function () {
    Setting::put('assistant', ['enabled' => true, 'provider' => 'claude', 'model' => 'claude-opus-5-5', 'key' => 'not-encrypted-with-this-app-key']);

    expect(AssistantSettings::current()->key())->toBeNull()
        ->and(AssistantSettings::current()->ready())->toBeFalse();
});
