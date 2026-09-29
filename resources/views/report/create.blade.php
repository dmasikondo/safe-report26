<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Submit a Report — SafeReport</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-zinc-50 dark:bg-zinc-950 text-zinc-900 dark:text-zinc-100 antialiased">

{{-- ── Page header ── --}}
<header class="border-b border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900">
    <div class="mx-auto flex max-w-3xl items-center justify-between px-4 py-4">
        <a href="{{ route('home') }}" class="flex items-center gap-2 text-sm font-semibold text-zinc-900 dark:text-white">
            <flux:icon.shield-check class="size-5 text-blue-600" />
            SafeReport
        </a>
        <a href="{{ route('report.track') }}" class="text-sm text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300 transition-colors">
            Track my report →
        </a>
    </div>
</header>

{{-- ── Wizard ── --}}
<main class="mx-auto max-w-3xl px-4 py-10">
    <form
        method="POST"
        action="{{ route('report.store') }}"
        x-data="{
            step: {{ $errors->any() ? 2 : 1 }},
            totalSteps: 3,
            selectedCategory: '{{ old('category') }}',

            next() {
                if (this.validate()) this.step++;
                window.scrollTo(0, 0);
            },
            prev() {
                this.step--;
                window.scrollTo(0, 0);
            },
            validate() {
                if (this.step === 1 && !this.selectedCategory) {
                    alert('Please select a category to continue.');
                    return false;
                }
                return true;
            },
        }"
    >
        @csrf

        <div class="rounded-2xl bg-white dark:bg-zinc-900 shadow-sm ring-1 ring-zinc-200 dark:ring-zinc-800 p-6 sm:p-8">

            {{-- ──────────────────────────────────────────────────────────── --}}
            {{-- Step 1: Category                                             --}}
            {{-- ──────────────────────────────────────────────────────────── --}}
            <div x-show="step === 1" x-transition.opacity>
                <x-report.step-header
                    :step="1"
                    :total="3"
                    title="Select a category"
                    description="Choose the type of misconduct you want to report. Your identity is never disclosed."
                />

                @if ($errors->has('category'))
                    <div class="mb-4">
                        <x-report.field-error name="category" />
                    </div>
                @endif

                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 sm:gap-4">
                    @foreach (\App\Enums\ReportCategoryEnum::cases() as $category)
                        <x-report.category-card
                            :category="$category"
                            :selected="old('category') === $category->value"
                        />
                    @endforeach
                </div>

                {{-- Hidden input kept in sync with Alpine selectedCategory --}}
                <input type="hidden" name="category" :value="selectedCategory" />

                <div class="mt-8 flex justify-end">
                    <flux:button
                        type="button"
                        variant="primary"
                        @click="next()"
                        class="px-6"
                    >
                        Continue
                        <flux:icon.arrow-right class="ml-1 size-4" />
                    </flux:button>
                </div>
            </div>

            {{-- ──────────────────────────────────────────────────────────── --}}
            {{-- Step 2: Report details                                       --}}
            {{-- ──────────────────────────────────────────────────────────── --}}
            <div x-show="step === 2" x-transition.opacity>
                <x-report.step-header
                    :step="2"
                    :total="3"
                    title="Report details"
                    description="Provide as much detail as you can. The more specific, the easier it is to investigate."
                />

                {{-- Subject --}}
                <div class="mb-5">
                    <label for="subject" class="mb-1.5 block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                        Subject <span class="text-red-500">*</span>
                    </label>
                    <input
                        id="subject"
                        name="subject"
                        type="text"
                        maxlength="200"
                        value="{{ old('subject') }}"
                        placeholder="Brief summary of what happened…"
                        class="w-full rounded-lg border @error('subject') border-red-400 dark:border-red-600 @else border-zinc-300 dark:border-zinc-700 @enderror bg-white dark:bg-zinc-800 px-3.5 py-2.5 text-sm text-zinc-900 dark:text-zinc-100 placeholder:text-zinc-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 outline-none transition"
                    />
                    <x-report.field-error name="subject" />
                </div>

                {{-- Body --}}
                <div class="mb-5">
                    <label for="body" class="mb-1.5 block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                        Full description <span class="text-red-500">*</span>
                    </label>
                    <textarea
                        id="body"
                        name="body"
                        rows="7"
                        maxlength="10000"
                        placeholder="Describe what happened, who was involved, when, and where. Stick to facts you observed or have evidence of."
                        class="w-full rounded-lg border @error('body') border-red-400 dark:border-red-600 @else border-zinc-300 dark:border-zinc-700 @enderror bg-white dark:bg-zinc-800 px-3.5 py-2.5 text-sm text-zinc-900 dark:text-zinc-100 placeholder:text-zinc-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 outline-none transition resize-y"
                    >{{ old('body') }}</textarea>
                    <x-report.field-error name="body" />
                </div>

                {{-- Optional fields --}}
                <div class="mb-5 grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="incident_date" class="mb-1.5 block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                            Approximate date of incident
                        </label>
                        <input
                            id="incident_date"
                            name="incident_date"
                            type="text"
                            value="{{ old('incident_date') }}"
                            placeholder="e.g. March 2026 or Q1 2026"
                            class="w-full rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3.5 py-2.5 text-sm text-zinc-900 dark:text-zinc-100 placeholder:text-zinc-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 outline-none transition"
                        />
                        <x-report.field-error name="incident_date" />
                    </div>
                    <div>
                        <label for="incident_location" class="mb-1.5 block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                            Location
                        </label>
                        <input
                            id="incident_location"
                            name="incident_location"
                            type="text"
                            value="{{ old('incident_location') }}"
                            placeholder="City, district or department"
                            class="w-full rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3.5 py-2.5 text-sm text-zinc-900 dark:text-zinc-100 placeholder:text-zinc-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 outline-none transition"
                        />
                        <x-report.field-error name="incident_location" />
                    </div>
                </div>

                <div class="mb-5">
                    <label for="organisation_involved" class="mb-1.5 block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                        Organisation or entity involved
                    </label>
                    <input
                        id="organisation_involved"
                        name="organisation_involved"
                        type="text"
                        value="{{ old('organisation_involved') }}"
                        placeholder="Ministry, company, department name…"
                        class="w-full rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3.5 py-2.5 text-sm text-zinc-900 dark:text-zinc-100 placeholder:text-zinc-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 outline-none transition"
                    />
                    <x-report.field-error name="organisation_involved" />
                </div>

                <div class="mb-2">
                    <label for="contact_hint" class="mb-1.5 block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                        Preferred contact method
                        <span class="text-xs font-normal text-zinc-400">(optional — for follow-up only)</span>
                    </label>
                    <input
                        id="contact_hint"
                        name="contact_hint"
                        type="text"
                        value="{{ old('contact_hint') }}"
                        placeholder="e.g. Signal username, encrypted email — never your real name"
                        class="w-full rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3.5 py-2.5 text-sm text-zinc-900 dark:text-zinc-100 placeholder:text-zinc-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 outline-none transition"
                    />
                    <p class="mt-1.5 text-xs text-zinc-400">
                        Stored encrypted, visible only to investigators. Do not use identifying details.
                    </p>
                    <x-report.field-error name="contact_hint" />
                </div>

                <div class="mt-8 flex justify-between gap-3">
                    <flux:button type="button" variant="ghost" @click="prev()">
                        <flux:icon.arrow-left class="mr-1 size-4" />
                        Back
                    </flux:button>
                    <flux:button type="button" variant="primary" @click="next()" class="px-6">
                        Review report
                        <flux:icon.arrow-right class="ml-1 size-4" />
                    </flux:button>
                </div>
            </div>

            {{-- ──────────────────────────────────────────────────────────── --}}
            {{-- Step 3: Review & submit                                      --}}
            {{-- ──────────────────────────────────────────────────────────── --}}
            <div x-show="step === 3" x-transition.opacity>
                <x-report.step-header
                    :step="3"
                    :total="3"
                    title="Review and submit"
                    description="Read through your report before submitting. Once submitted you cannot edit it."
                />

                {{-- Review card (reads live form values) --}}
                <div class="mb-6 divide-y divide-zinc-100 dark:divide-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden">
                    <div class="flex items-start gap-3 px-4 py-3 bg-zinc-50 dark:bg-zinc-800/50">
                        <span class="mt-0.5 text-xs font-semibold uppercase tracking-wider text-zinc-400 w-28 shrink-0">Category</span>
                        <span class="text-sm text-zinc-800 dark:text-zinc-200 font-medium capitalize"
                              x-text="selectedCategory ? selectedCategory.replace(/_/g, ' ') : '—'"></span>
                    </div>
                    <div class="flex items-start gap-3 px-4 py-3">
                        <span class="mt-0.5 text-xs font-semibold uppercase tracking-wider text-zinc-400 w-28 shrink-0">Subject</span>
                        <span class="text-sm text-zinc-800 dark:text-zinc-200"
                              x-text="document.getElementById('subject')?.value || '—'"></span>
                    </div>
                    <div class="flex items-start gap-3 px-4 py-3 bg-zinc-50 dark:bg-zinc-800/50">
                        <span class="mt-0.5 text-xs font-semibold uppercase tracking-wider text-zinc-400 w-28 shrink-0">Description</span>
                        <span class="text-sm text-zinc-800 dark:text-zinc-200 whitespace-pre-wrap break-words line-clamp-6"
                              x-text="document.getElementById('body')?.value || '—'"></span>
                    </div>
                </div>

                {{-- Privacy notice --}}
                <div class="mb-6 flex gap-3 rounded-xl bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800 p-4">
                    <flux:icon.lock-closed class="mt-0.5 size-5 shrink-0 text-amber-600 dark:text-amber-400" />
                    <div class="text-sm">
                        <p class="font-medium text-amber-800 dark:text-amber-300">Your anonymity is protected</p>
                        <p class="mt-0.5 text-amber-700 dark:text-amber-400">
                            We do not log IP addresses or browser fingerprints. No personal data is collected unless you voluntarily provided a contact method.
                        </p>
                    </div>
                </div>

                <div class="flex justify-between gap-3">
                    <flux:button type="button" variant="ghost" @click="prev()">
                        <flux:icon.arrow-left class="mr-1 size-4" />
                        Back
                    </flux:button>
                    <flux:button type="submit" variant="primary" class="px-6">
                        <flux:icon.paper-airplane class="mr-1.5 size-4" />
                        Submit report
                    </flux:button>
                </div>
            </div>

        </div>

        <p class="mt-6 text-center text-xs text-zinc-400 dark:text-zinc-600">
            Reports are handled confidentially under our
            <a href="#" class="underline hover:text-zinc-600 dark:hover:text-zinc-400">Whistleblower Policy</a>.
            Retaliation against reporters is prohibited.
        </p>

    </form>
</main>

</body>
</html>
