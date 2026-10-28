<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head } from '@inertiajs/vue3';

type Example = {
    id: string;
    title: string;
    description: string;
    raw_text: string;
};

type RepairResult = {
    job_id: number | null;
    json: string | null;
    valid: boolean;
    errors: string[];
    repair_source: string;
    llm_used: boolean;
    changes: string[];
};

const props = defineProps<{ examples: Example[]; llmEnabled: boolean }>();
const rawText = ref(props.examples[0]?.raw_text ?? '');
const selectedExample = ref(props.examples[0]?.id ?? '');
const result = ref<RepairResult | null>(null);
const busy = ref(false);
const copied = ref(false);
const requestError = ref('');

const lineCount = computed(() => Math.max(rawText.value.split('\n').length, 1));
const statusLabel = computed(() => {
    if (!result.value) return 'Ready to inspect';
    return result.value.valid ? 'Schema valid' : 'Needs attention';
});

function chooseExample(example: Example) {
    selectedExample.value = example.id;
    rawText.value = example.raw_text;
    result.value = null;
    requestError.value = '';
}

async function repairJson() {
    busy.value = true;
    requestError.value = '';
    copied.value = false;

    try {
        const response = await fetch('/repair', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN':
                    document.querySelector<HTMLMetaElement>(
                        'meta[name="csrf-token"]',
                    )?.content ?? '',
            },
            body: JSON.stringify({ raw_text: rawText.value }),
        });

        if (response.status === 422) {
            const body = await response.json();
            requestError.value = Object.values(body.errors ?? {})
                .flat()
                .join(' ');
            return;
        }

        result.value = (await response.json()) as RepairResult;
    } catch {
        requestError.value =
            'The desk could not reach the repair service. Please try again.';
    } finally {
        busy.value = false;
    }
}

async function copyResult() {
    if (!result.value?.json) return;
    await navigator.clipboard.writeText(result.value.json);
    copied.value = true;
    window.setTimeout(() => (copied.value = false), 1800);
}
</script>

<template>
    <Head title="JSON Repair Desk">
        <meta
            name="description"
            content="Repair malformed JSON and validate Harbor Desk event payloads."
        />
    </Head>

    <div class="min-h-screen bg-[#f6f8f7] text-[#172321]">
        <header class="border-b border-[#e5ebe8] bg-white">
            <div
                class="mx-auto flex max-w-[1440px] items-center justify-between px-5 py-4 sm:px-8"
            >
                <a
                    href="/"
                    class="flex items-center gap-3"
                    aria-label="JSON Repair Desk home"
                >
                    <span
                        class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#173f38] text-[#b9f4d7]"
                    >
                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            class="h-6 w-6"
                            aria-hidden="true"
                        >
                            <path
                                d="M8 4H6a2 2 0 0 0-2 2v2c0 2-1 3-2 4 1 1 2 2 2 4v2a2 2 0 0 0 2 2h2M16 4h2a2 2 0 0 1 2 2v2c0 2 1 3 2 4-1 1-2 2-2 4v2a2 2 0 0 1-2 2h-2M10 9l-2 3 2 3m4-6 2 3-2 3"
                                stroke="currentColor"
                                stroke-width="1.7"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />
                        </svg>
                    </span>
                    <span>
                        <span
                            class="block text-[15px] font-semibold tracking-[-0.03em]"
                            >JSON Repair Desk</span
                        >
                        <span
                            class="mt-0.5 block text-[11px] font-medium tracking-[0.12em] text-[#7b8984] uppercase"
                            >Harbor Desk · Developer tools</span
                        >
                    </span>
                </a>
                <div
                    class="flex items-center gap-2 rounded-full border border-[#dce9e2] bg-[#f5fbf7] px-3 py-1.5 text-xs font-medium text-[#3b735a]"
                >
                    <span class="h-1.5 w-1.5 rounded-full bg-[#45ad76]"></span>
                    {{
                        llmEnabled
                            ? 'Gemini enabled'
                            : 'Local mode · no API key'
                    }}
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-[1440px] px-5 py-8 sm:px-8 lg:py-11">
            <section
                class="mb-8 flex flex-col justify-between gap-5 md:flex-row md:items-end"
            >
                <div class="max-w-2xl">
                    <div
                        class="mb-3 flex items-center gap-2 text-xs font-semibold tracking-[0.12em] text-[#688179] uppercase"
                    >
                        <span class="h-px w-6 bg-[#84a996]"></span>
                        Payload workbench
                        <span class="text-[#b3c0ba]">/</span> v1.0
                    </div>
                    <h1
                        class="text-3xl font-semibold tracking-[-0.045em] text-[#172c27] sm:text-[40px]"
                    >
                        Make messy JSON<br class="hidden sm:block" />
                        production-ready.
                    </h1>
                    <p
                        class="mt-3 max-w-xl text-[15px] leading-7 text-[#6d7b76]"
                    >
                        Repair common syntax slips, then check every payload
                        against the Harbor Desk event contract.
                    </p>
                </div>
                <div
                    class="flex items-center gap-3 rounded-2xl border border-[#e1e9e5] bg-white px-4 py-3 shadow-[0_2px_10px_rgba(24,54,44,0.025)]"
                >
                    <div class="flex -space-x-2">
                        <span
                            class="flex h-8 w-8 items-center justify-center rounded-full border-2 border-white bg-[#d9eee2] text-[#31684d]"
                        >
                            <svg
                                viewBox="0 0 20 20"
                                fill="none"
                                class="h-4 w-4"
                            >
                                <path
                                    d="m5 10 3 3 7-7"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                />
                            </svg>
                        </span>
                        <span
                            class="flex h-8 w-8 items-center justify-center rounded-full border-2 border-white bg-[#e6eaf6] font-mono text-[11px] text-[#57668f]"
                            >{ }</span
                        >
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-[#33433d]">
                            Deterministic first
                        </p>
                        <p class="mt-0.5 text-[11px] text-[#87938e]">
                            AI is an optional fallback
                        </p>
                    </div>
                </div>
            </section>

            <section
                class="mb-5 grid gap-3 sm:grid-cols-3"
                aria-label="Example payloads"
            >
                <button
                    v-for="(example, index) in examples"
                    :key="example.id"
                    type="button"
                    class="group flex min-h-[82px] items-start gap-3 rounded-2xl border px-4 py-3.5 text-left transition-colors"
                    :class="
                        selectedExample === example.id
                            ? 'border-[#b9d9c7] bg-[#f1f8f3]'
                            : 'border-[#e2e9e5] bg-white hover:border-[#cbdad1]'
                    "
                    @click="chooseExample(example)"
                >
                    <span
                        class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-lg font-mono text-[11px] font-semibold"
                        :class="
                            selectedExample === example.id
                                ? 'bg-[#dcefe2] text-[#3b7657]'
                                : 'bg-[#f0f3f1] text-[#75827c]'
                        "
                        >0{{ index + 1 }}</span
                    >
                    <span class="min-w-0">
                        <span
                            class="block text-sm font-semibold text-[#34433d]"
                            >{{ example.title }}</span
                        >
                        <span
                            class="mt-1 block truncate text-xs text-[#87938d]"
                            >{{ example.description }}</span
                        >
                    </span>
                    <svg
                        v-if="selectedExample === example.id"
                        viewBox="0 0 20 20"
                        fill="none"
                        class="mt-1 ml-auto h-4 w-4 shrink-0 text-[#4d8b66]"
                    >
                        <path
                            d="m5 10 3 3 7-7"
                            stroke="currentColor"
                            stroke-width="1.8"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        />
                    </svg>
                </button>
            </section>

            <section
                class="grid overflow-hidden rounded-[22px] border border-[#dfe7e3] bg-white shadow-[0_8px_30px_rgba(26,55,45,0.045)] lg:grid-cols-2"
            >
                <div
                    class="min-w-0 border-b border-[#e7ece9] lg:border-r lg:border-b-0"
                >
                    <div
                        class="flex h-[58px] items-center justify-between border-b border-[#edf1ef] px-5 sm:px-6"
                    >
                        <div class="flex items-center gap-2.5">
                            <span
                                class="h-2 w-2 rounded-full bg-[#e8a85e]"
                            ></span>
                            <h2 class="text-sm font-semibold text-[#35443e]">
                                Input payload
                            </h2>
                            <span
                                class="rounded-md bg-[#f1f4f2] px-1.5 py-0.5 font-mono text-[10px] text-[#84918b]"
                                >JSON</span
                            >
                        </div>
                        <button
                            type="button"
                            class="text-xs font-medium text-[#71817a] transition hover:text-[#264c3b]"
                            @click="
                                rawText = '';
                                selectedExample = '';
                                result = null;
                            "
                        >
                            Clear
                        </button>
                    </div>
                    <div class="flex min-h-[360px] bg-[#fbfcfb] py-5">
                        <div
                            class="w-12 shrink-0 pr-3 text-right font-mono text-[12px] leading-[24px] text-[#b5c0ba] select-none"
                            aria-hidden="true"
                        >
                            <div v-for="line in lineCount" :key="line">
                                {{ line }}
                            </div>
                        </div>
                        <textarea
                            v-model="rawText"
                            aria-label="JSON payload to repair"
                            spellcheck="false"
                            class="min-h-[320px] w-full resize-y border-0 bg-transparent pr-5 font-mono text-[13px] leading-[24px] text-[#35443e] outline-none placeholder:text-[#bdc6c1] focus:ring-0 sm:pr-7"
                            placeholder="Paste a Harbor Desk event payload..."
                            @input="
                                result = null;
                                selectedExample = '';
                            "
                        ></textarea>
                    </div>
                    <div
                        class="flex flex-col gap-3 border-t border-[#edf1ef] px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6"
                    >
                        <p class="text-[11px] text-[#94a099]">
                            Up to 20 KB
                            <span class="mx-1.5 text-[#d1d8d4]">·</span>
                            Gemini only runs when configured and needed
                        </p>
                        <button
                            type="button"
                            :disabled="busy || !rawText.trim()"
                            class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#17473b] px-4 py-2.5 text-xs font-semibold text-white shadow-[0_3px_8px_rgba(23,71,59,0.16)] transition hover:bg-[#103a30] disabled:cursor-not-allowed disabled:opacity-50"
                            @click="repairJson"
                        >
                            <svg
                                v-if="busy"
                                class="h-4 w-4 animate-spin"
                                viewBox="0 0 24 24"
                                fill="none"
                            >
                                <circle
                                    cx="12"
                                    cy="12"
                                    r="9"
                                    stroke="currentColor"
                                    stroke-opacity=".25"
                                    stroke-width="3"
                                />
                                <path
                                    d="M21 12a9 9 0 0 0-9-9"
                                    stroke="currentColor"
                                    stroke-width="3"
                                    stroke-linecap="round"
                                />
                            </svg>
                            <svg
                                v-else
                                viewBox="0 0 20 20"
                                fill="none"
                                class="h-4 w-4"
                            >
                                <path
                                    d="m11.5 2-8 9h5l-.5 7 8-9h-5l.5-7Z"
                                    stroke="currentColor"
                                    stroke-width="1.5"
                                    stroke-linejoin="round"
                                />
                            </svg>
                            {{ busy ? 'Repairing…' : 'Repair & validate' }}
                        </button>
                    </div>
                    <p
                        v-if="requestError"
                        role="alert"
                        class="border-t border-[#f2dfd9] bg-[#fff9f7] px-6 py-3 text-xs text-[#a34e3e]"
                    >
                        {{ requestError }}
                    </p>
                </div>

                <div class="min-w-0 bg-[#fcfdfc]">
                    <div
                        class="flex h-[58px] items-center justify-between border-b border-[#edf1ef] px-5 sm:px-6"
                    >
                        <div class="flex items-center gap-2.5">
                            <span
                                class="h-2 w-2 rounded-full"
                                :class="
                                    result
                                        ? result.valid
                                            ? 'bg-[#4bb17a]'
                                            : 'bg-[#d5a053]'
                                        : 'bg-[#b8c2bd]'
                                "
                            ></span>
                            <h2 class="text-sm font-semibold text-[#35443e]">
                                Repaired output
                            </h2>
                        </div>
                        <span
                            class="rounded-full px-2.5 py-1 text-[10px] font-semibold"
                            :class="
                                result
                                    ? result.valid
                                        ? 'bg-[#e6f5eb] text-[#39805a]'
                                        : 'bg-[#fff4e5] text-[#a36e28]'
                                    : 'bg-[#f0f3f1] text-[#829089]'
                            "
                            >{{ statusLabel }}</span
                        >
                    </div>

                    <div
                        v-if="!result"
                        class="flex min-h-[360px] flex-col items-center justify-center px-8 py-12 text-center"
                    >
                        <div
                            class="relative mb-5 flex h-[72px] w-[72px] items-center justify-center rounded-[22px] border border-[#e4ece7] bg-white text-[#9bb5a6] shadow-[0_4px_16px_rgba(30,64,47,0.045)]"
                        >
                            <svg
                                viewBox="0 0 36 36"
                                fill="none"
                                class="h-9 w-9"
                            >
                                <path
                                    d="M12 5H9a3 3 0 0 0-3 3v3c0 3-1.5 5-3 7 1.5 2 3 4 3 7v3a3 3 0 0 0 3 3h3m12-26h3a3 3 0 0 1 3 3v3c0 3 1.5 5 3 7-1.5 2-3 4-3 7v3a3 3 0 0 1-3 3h-3M16 13l-3 5 3 5m4-10 3 5-3 5"
                                    stroke="currentColor"
                                    stroke-width="1.6"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                />
                            </svg>
                            <span
                                class="absolute -top-1 -right-1 flex h-5 w-5 items-center justify-center rounded-full border-2 border-[#fcfdfc] bg-[#dff2e5] text-[#40835c]"
                                ><svg
                                    viewBox="0 0 12 12"
                                    fill="none"
                                    class="h-3 w-3"
                                >
                                    <path
                                        d="m3 6 2 2 4-4"
                                        stroke="currentColor"
                                        stroke-width="1.5"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    /></svg
                            ></span>
                        </div>
                        <p class="text-sm font-semibold text-[#4c5c55]">
                            Your output will appear here
                        </p>
                        <p
                            class="mt-2 max-w-[270px] text-xs leading-5 text-[#939e98]"
                        >
                            Run the repair to see normalized JSON and a clear
                            schema validation report.
                        </p>
                    </div>

                    <template v-else>
                        <div class="flex min-h-[260px] bg-[#fbfcfb] py-5">
                            <pre
                                v-if="result.json"
                                class="max-h-[360px] w-full overflow-auto px-6 font-mono text-[13px] leading-[24px] text-[#345349]"
                                >{{ result.json }}</pre>
                            <div
                                v-else
                                class="flex w-full items-center px-6 font-mono text-xs leading-5 text-[#9b6256]"
                            >
                                Input could not be parsed into a JSON document.
                            </div>
                        </div>
                        <div
                            class="border-t border-[#edf1ef] px-5 py-4 sm:px-6"
                        >
                            <div class="mb-3 flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span
                                        class="flex h-5 w-5 items-center justify-center rounded-full"
                                        :class="
                                            result.valid
                                                ? 'bg-[#e1f2e7] text-[#3e8a5e]'
                                                : 'bg-[#fff1de] text-[#ad762f]'
                                        "
                                    >
                                        <svg
                                            v-if="result.valid"
                                            viewBox="0 0 16 16"
                                            fill="none"
                                            class="h-3 w-3"
                                        >
                                            <path
                                                d="m3 8 3 3 7-7"
                                                stroke="currentColor"
                                                stroke-width="1.7"
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                            />
                                        </svg>
                                        <svg
                                            v-else
                                            viewBox="0 0 16 16"
                                            fill="none"
                                            class="h-3 w-3"
                                        >
                                            <path
                                                d="M8 4v4m0 3h.01M14 8A6 6 0 1 1 2 8a6 6 0 0 1 12 0Z"
                                                stroke="currentColor"
                                                stroke-width="1.4"
                                                stroke-linecap="round"
                                            />
                                        </svg>
                                    </span>
                                    <h3
                                        class="text-xs font-semibold text-[#53645c]"
                                    >
                                        Harbor Desk event schema
                                    </h3>
                                </div>
                                <span
                                    v-if="result.job_id"
                                    class="font-mono text-[10px] text-[#a2ada7]"
                                    >JOB-{{
                                        String(result.job_id).padStart(4, '0')
                                    }}</span
                                >
                            </div>
                            <div
                                v-if="result.valid"
                                class="flex flex-wrap gap-2"
                            >
                                <span
                                    v-for="field in ['event_type', 'order_id']"
                                    :key="field"
                                    class="inline-flex items-center gap-1.5 rounded-lg border border-[#e1eee5] bg-[#f5fbf6] px-2.5 py-1.5 font-mono text-[10px] text-[#4b775c]"
                                    ><span
                                        class="h-1 w-1 rounded-full bg-[#60b780]"
                                    ></span
                                    >{{ field }}
                                    <span class="text-[#a2b7a8]"
                                        >required</span
                                    ></span
                                >
                            </div>
                            <ul v-else class="space-y-1.5">
                                <li
                                    v-for="error in result.errors"
                                    :key="error"
                                    class="flex gap-2 text-xs leading-5 text-[#a86d35]"
                                >
                                    <span
                                        class="mt-[7px] h-1 w-1 shrink-0 rounded-full bg-[#d6a45f]"
                                    ></span
                                    >{{ error }}
                                </li>
                            </ul>
                            <div
                                class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-[#edf1ef] pt-3"
                            >
                                <p class="text-[11px] text-[#87948d]">
                                    <span v-if="result.llm_used"
                                        >Gemini syntax repair applied</span
                                    >
                                    <span v-else-if="result.changes.length">{{
                                        result.changes.join(' · ')
                                    }}</span>
                                    <span
                                        v-else-if="
                                            result.repair_source === 'none'
                                        "
                                        >No syntax changes needed</span
                                    >
                                    <span v-else>Validated without AI</span>
                                </p>
                                <button
                                    v-if="result.json"
                                    type="button"
                                    class="inline-flex items-center gap-1.5 text-[11px] font-semibold text-[#47735a] hover:text-[#214b36]"
                                    @click="copyResult"
                                >
                                    <svg
                                        v-if="!copied"
                                        viewBox="0 0 16 16"
                                        fill="none"
                                        class="h-3.5 w-3.5"
                                    >
                                        <rect
                                            x="5"
                                            y="5"
                                            width="8"
                                            height="9"
                                            rx="1.5"
                                            stroke="currentColor"
                                            stroke-width="1.2"
                                        />
                                        <path
                                            d="M10 5V3.5A1.5 1.5 0 0 0 8.5 2h-5A1.5 1.5 0 0 0 2 3.5v7A1.5 1.5 0 0 0 3.5 12H5"
                                            stroke="currentColor"
                                            stroke-width="1.2"
                                        />
                                    </svg>
                                    <svg
                                        v-else
                                        viewBox="0 0 16 16"
                                        fill="none"
                                        class="h-3.5 w-3.5"
                                    >
                                        <path
                                            d="m3 8 3 3 7-7"
                                            stroke="currentColor"
                                            stroke-width="1.5"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                        />
                                    </svg>
                                    {{ copied ? 'Copied' : 'Copy JSON' }}
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
            </section>

            <section class="mt-5 grid gap-3 md:grid-cols-3">
                <div
                    class="flex items-start gap-3 rounded-2xl border border-[#e2e9e5] bg-white px-4 py-4"
                >
                    <span
                        class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-[#eef6f0] text-[#4e8a62]"
                        ><span class="font-mono text-xs">01</span></span
                    >
                    <div>
                        <p class="text-xs font-semibold text-[#43534b]">
                            Deterministic cleanup
                        </p>
                        <p class="mt-1 text-[11px] leading-4 text-[#8a9690]">
                            Trailing commas and single quotes are fixed locally
                            first.
                        </p>
                    </div>
                </div>
                <div
                    class="flex items-start gap-3 rounded-2xl border border-[#e2e9e5] bg-white px-4 py-4"
                >
                    <span
                        class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-[#f0f1f8] text-[#6672a0]"
                        ><span class="font-mono text-xs">02</span></span
                    >
                    <div>
                        <p class="text-xs font-semibold text-[#43534b]">
                            Optional AI fallback
                        </p>
                        <p class="mt-1 text-[11px] leading-4 text-[#8a9690]">
                            Gemini is called only if syntax remains invalid;
                            12-second timeout.
                        </p>
                    </div>
                </div>
                <div
                    class="flex items-start gap-3 rounded-2xl border border-[#e2e9e5] bg-white px-4 py-4"
                >
                    <span
                        class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-[#fff5e8] text-[#b17c37]"
                        ><span class="font-mono text-xs">03</span></span
                    >
                    <div>
                        <p class="text-xs font-semibold text-[#43534b]">
                            Schema stays in charge
                        </p>
                        <p class="mt-1 text-[11px] leading-4 text-[#8a9690]">
                            event_type and order_id are checked after every
                            repair.
                        </p>
                    </div>
                </div>
            </section>

            <footer
                class="mt-8 flex flex-col gap-2 border-t border-[#e3eae6] pt-5 text-[11px] text-[#98a39d] sm:flex-row sm:items-center sm:justify-between"
            >
                <p>
                    JSON Repair Desk
                    <span class="mx-1 text-[#c9d1cc]">·</span> Demo payloads
                    contain no customer data.
                </p>
                <p>
                    Laravel <span class="mx-1 text-[#c9d1cc]">·</span> Vue 3
                    <span class="mx-1 text-[#c9d1cc]">·</span> Inertia
                </p>
            </footer>
        </main>
    </div>
</template>
