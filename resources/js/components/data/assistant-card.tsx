import { useForm } from '@inertiajs/react';
import { ChoiceChips } from '@/components/choice-chips';
import { FieldError } from '@/components/field-error';
import { SectionCard } from '@/components/section-card';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { settings as saveSettings } from '@/routes/assistant';

export type AssistantSettings = {
    enabled: boolean;
    provider: 'claude' | 'openai';
    model: string;
    base_url: string;
    has_key: boolean;
    claude_models: { value: string; label: string }[];
};

/**
 * The assistant's settings: off by default; the provider, the model and the
 * taxpayer's own API key, which the page never gets back once saved.
 */
export function AssistantCard({ settings }: { settings: AssistantSettings }) {
    const form = useForm({
        enabled: settings.enabled,
        provider: settings.provider,
        model: settings.model,
        base_url: settings.base_url,
        key: '',
        remove_key: false,
    });
    const claude = form.data.provider === 'claude';
    const firstClaudeModel = settings.claude_models[0].value;

    return (
        <SectionCard
            title="Assistant (AI)"
            action={
                <label className="flex items-center gap-2 text-sm font-bold">
                    <Checkbox
                        checked={form.data.enabled}
                        onCheckedChange={(checked) =>
                            form.setData('enabled', checked === true)
                        }
                    />
                    On
                </label>
            }
        >
            <p className="-mt-2 mb-4 text-sm text-muted-foreground">
                Optional · off by default
            </p>
            <form
                noValidate
                className="flex flex-col gap-4"
                onSubmit={(event) => {
                    event.preventDefault();
                    form.submit(saveSettings(), {
                        preserveScroll: true,
                        onSuccess: () => form.reset('key', 'remove_key'),
                    });
                }}
            >
                <div className="flex flex-col gap-1.5">
                    <span className="text-sm font-bold">Provider</span>
                    <ChoiceChips
                        label="Provider"
                        value={form.data.provider}
                        onChange={(provider) =>
                            form.setData((data) => ({
                                ...data,
                                provider,
                                model:
                                    provider === 'claude'
                                        ? firstClaudeModel
                                        : settings.provider === 'openai'
                                          ? settings.model
                                          : '',
                            }))
                        }
                        options={[
                            { value: 'claude', label: 'Claude (Anthropic)' },
                            { value: 'openai', label: 'OpenAI or compatible' },
                        ]}
                    />
                </div>

                {claude ? (
                    <div className="flex flex-col gap-1.5">
                        <Label htmlFor="assistant_model" className="font-bold">
                            Model
                        </Label>
                        <select
                            id="assistant_model"
                            value={form.data.model}
                            onChange={(event) =>
                                form.setData('model', event.target.value)
                            }
                            className="h-11 rounded-xl border border-input bg-card px-3 text-sm"
                        >
                            {settings.claude_models.map((model) => (
                                <option key={model.value} value={model.value}>
                                    {model.label}
                                </option>
                            ))}
                        </select>
                        <FieldError message={form.errors.model} />
                    </div>
                ) : (
                    <>
                        <div className="flex flex-col gap-1.5">
                            <Label
                                htmlFor="assistant_base_url"
                                className="font-bold"
                            >
                                API address
                            </Label>
                            <Input
                                id="assistant_base_url"
                                value={form.data.base_url}
                                onChange={(event) =>
                                    form.setData('base_url', event.target.value)
                                }
                                className="h-11 rounded-xl"
                            />
                            <p className="text-[13px] text-muted-foreground">
                                OpenAI's, or any API with the same chat
                                completions, such as OpenRouter or Ollama on
                                this computer.
                            </p>
                            <FieldError message={form.errors.base_url} />
                        </div>
                        <div className="flex flex-col gap-1.5">
                            <Label
                                htmlFor="assistant_model_name"
                                className="font-bold"
                            >
                                Model
                            </Label>
                            <Input
                                id="assistant_model_name"
                                value={form.data.model}
                                placeholder="As your provider names it"
                                onChange={(event) =>
                                    form.setData('model', event.target.value)
                                }
                                className="h-11 rounded-xl"
                            />
                            <FieldError message={form.errors.model} />
                        </div>
                    </>
                )}

                <div className="flex flex-col gap-1.5">
                    <Label htmlFor="assistant_key" className="font-bold">
                        Your API key
                    </Label>
                    <Input
                        id="assistant_key"
                        type="password"
                        autoComplete="off"
                        value={form.data.key}
                        placeholder={
                            settings.has_key && !form.data.remove_key
                                ? 'Saved · paste a new one to replace it'
                                : claude
                                  ? 'Paste your Anthropic API key'
                                  : 'Paste your API key, if it needs one'
                        }
                        onChange={(event) =>
                            form.setData('key', event.target.value)
                        }
                        className="h-11 rounded-xl"
                    />
                    {settings.has_key && (
                        <label className="flex items-center gap-2 text-sm">
                            <Checkbox
                                checked={form.data.remove_key}
                                onCheckedChange={(checked) =>
                                    form.setData('remove_key', checked === true)
                                }
                            />
                            Remove the saved key
                        </label>
                    )}
                    <FieldError message={form.errors.key} />
                </div>

                <p className="text-[13px] text-muted-foreground">
                    When it's on, your question and only the numbers needed to
                    answer it go to that provider; your name, TIN and address
                    never do. Tax amounts are always computed on this computer.
                    The key stays here, encrypted, and isn't in backups.
                </p>

                <Button
                    type="submit"
                    disabled={form.processing}
                    className="self-start rounded-xl font-bold"
                >
                    Save
                </Button>
            </form>
        </SectionCard>
    );
}
