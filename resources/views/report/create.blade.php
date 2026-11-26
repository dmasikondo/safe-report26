<x-layouts::public title="Submit a report">

{{-- ── Wizard ── --}}
<div class="mx-auto max-w-3xl px-4 py-10">
    <form
        method="POST"
        action="{{ route('report.store') }}"
        enctype="multipart/form-data"
        @submit.prevent="submit()"
        x-data="{
            step: {{ $errors->any() ? 2 : 1 }},
            totalSteps: 3,
            selectedCategory: '{{ old('category') }}',
            categoryLabels: @js(collect(\App\Enums\ReportCategoryEnum::cases())->mapWithKeys(fn ($case) => [$case->value => $case->label()])),
            files: [],
            fileError: '',
            maxFiles: {{ (int) config('safereport.attachments.max_files') }},
            maxBytes: {{ (int) config('safereport.attachments.max_kilobytes') * 1024 }},
            uploading: false,
            uploadPercent: 0,
            submitError: '',
            errors: {},

            init() {
                // With JavaScript on, files are sent from the list below, not from the input.
                this.$refs.picker.removeAttribute('name');
            },
            addFiles(event) {
                const messages = [];

                for (const file of Array.from(event.target.files)) {
                    if (this.files.length >= this.maxFiles) {
                        messages.push(`You can attach at most ${this.maxFiles} files.`);
                        break;
                    }
                    if (file.size > this.maxBytes) {
                        messages.push(`${file.name} is larger than 2 MB.`);
                        continue;
                    }
                    this.files.push({
                        file,
                        isImage: file.type.startsWith('image/'),
                        previewUrl: file.type.startsWith('image/') ? URL.createObjectURL(file) : null,
                    });
                }

                this.fileError = messages.join(' ');
                event.target.value = '';
            },
            removeFile(index) {
                const [removed] = this.files.splice(index, 1);
                if (removed.previewUrl) URL.revokeObjectURL(removed.previewUrl);
                this.fileError = '';
            },
            formatSize(bytes) {
                return (bytes / 1024 / 1024).toFixed(2) + ' MB';
            },
            fieldError(name) {
                return (this.errors[name] ?? [])[0] ?? '';
            },
            attachmentErrors() {
                return Object.entries(this.errors)
                    .filter(([key]) => key === 'attachments' || key.startsWith('attachments.'))
                    .map(([, messages]) => messages[0]);
            },
            submit() {
                if (this.uploading) return;

                const data = new FormData(this.$el);
                data.delete('attachments[]');
                this.files.forEach((entry) => data.append('attachments[]', entry.file));

                const xhr = new XMLHttpRequest();
                xhr.open('POST', this.$el.action);
                xhr.setRequestHeader('Accept', 'application/json');
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

                xhr.upload.onprogress = (event) => {
                    if (event.lengthComputable) {
                        this.uploadPercent = Math.round((event.loaded / event.total) * 100);
                    }
                };
                xhr.onload = () => {
                    let response = {};
                    try { response = JSON.parse(xhr.responseText); } catch (e) {}

                    if (xhr.status === 200 && response.redirect) {
                        window.location.href = response.redirect;
                        return;
                    }

                    this.uploading = false;

                    if (xhr.status === 422) {
                        this.errors = response.errors ?? {};
                        const onlyCategory = Object.keys(this.errors).every((key) => key === 'category');
                        this.step = onlyCategory ? 1 : 2;
                        window.scrollTo(0, 0);
                    } else if (xhr.status === 413) {
                        this.submitError = 'Your files are too large to upload. Each file must be 2 MB or smaller.';
                    } else if (xhr.status === 419) {
                        this.submitError = 'Your session expired. Refresh the page and submit again.';
                    } else {
                        this.submitError = 'Something went wrong while submitting. Please try again.';
                    }
                };
                xhr.onerror = () => {
                    this.uploading = false;
                    this.submitError = 'The upload failed. Check your connection and try again.';
                };

                this.errors = {};
                this.submitError = '';
                this.uploadPercent = 0;
                this.uploading = true;
                xhr.send(data);
            },
            departmentName() {
                const select = document.getElementById('department_id');
                return select && select.value ? select.options[select.selectedIndex].text.trim() : 'Not specified';
            },

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
                <p x-show="fieldError('category')" x-text="fieldError('category')" style="display: none" class="mb-4 text-xs text-red-600 dark:text-red-400" role="alert"></p>

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
                        icon:trailing="arrow-right"
                    >
                        Continue
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
                    <p x-show="fieldError('subject')" x-text="fieldError('subject')" style="display: none" class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert"></p>
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
                    <p x-show="fieldError('body')" x-text="fieldError('body')" style="display: none" class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert"></p>
                </div>

                {{-- Where it happened --}}
                <div class="mb-5 grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="department_id" class="mb-1.5 block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                            Department
                        </label>
                        <select
                            id="department_id"
                            name="department_id"
                            class="w-full rounded-lg border @error('department_id') border-red-400 dark:border-red-600 @else border-zinc-300 dark:border-zinc-700 @enderror bg-white dark:bg-zinc-800 px-3.5 py-2.5 text-sm text-zinc-900 dark:text-zinc-100 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 outline-none transition"
                        >
                            <option value="">Select a department (optional)</option>
                            @foreach ($departments as $department)
                                <option value="{{ $department->id }}" @selected((string) old('department_id') === (string) $department->id)>
                                    {{ $department->name }}
                                </option>
                            @endforeach
                            <option value="">Other / not listed</option>
                        </select>
                        <x-report.field-error name="department_id" />
                        <p x-show="fieldError('department_id')" x-text="fieldError('department_id')" style="display: none" class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert"></p>
                    </div>
                    <div>
                        <label for="incident_location" class="mb-1.5 block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                            Specific place
                            <span class="text-xs font-normal text-zinc-400">(optional)</span>
                        </label>
                        <input
                            id="incident_location"
                            name="incident_location"
                            type="text"
                            maxlength="200"
                            value="{{ old('incident_location') }}"
                            placeholder="e.g. Block C, Room 4"
                            class="w-full rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3.5 py-2.5 text-sm text-zinc-900 dark:text-zinc-100 placeholder:text-zinc-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 outline-none transition"
                        />
                        <x-report.field-error name="incident_location" />
                        <p x-show="fieldError('incident_location')" x-text="fieldError('incident_location')" style="display: none" class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert"></p>
                    </div>
                    <p class="flex items-start gap-1.5 text-xs text-zinc-500 dark:text-zinc-400 sm:col-span-2">
                        <flux:icon.information-circle variant="micro" class="mt-px size-4 shrink-0" />
                        Share only what is needed. In a small department, a few extra details can identify you.
                    </p>
                </div>

                <div class="mb-5 grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="incident_date" class="mb-1.5 block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                            Approximate date of incident
                        </label>
                        <input
                            id="incident_date"
                            name="incident_date"
                            type="text"
                            maxlength="100"
                            value="{{ old('incident_date') }}"
                            placeholder="e.g. March 2026 or last semester"
                            class="w-full rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3.5 py-2.5 text-sm text-zinc-900 dark:text-zinc-100 placeholder:text-zinc-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 outline-none transition"
                        />
                        <x-report.field-error name="incident_date" />
                        <p x-show="fieldError('incident_date')" x-text="fieldError('incident_date')" style="display: none" class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert"></p>
                    </div>
                    <div>
                        <label for="organisation_involved" class="mb-1.5 block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                            Person or office involved
                        </label>
                        <input
                            id="organisation_involved"
                            name="organisation_involved"
                            type="text"
                            maxlength="200"
                            value="{{ old('organisation_involved') }}"
                            placeholder="e.g. a job title, office or company"
                            class="w-full rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3.5 py-2.5 text-sm text-zinc-900 dark:text-zinc-100 placeholder:text-zinc-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 outline-none transition"
                        />
                        <x-report.field-error name="organisation_involved" />
                        <p x-show="fieldError('organisation_involved')" x-text="fieldError('organisation_involved')" style="display: none" class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert"></p>
                    </div>
                </div>

                {{-- Evidence attachments --}}
                <div class="mb-5">
                    <label for="attachments" class="mb-1.5 block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                        Evidence
                        <span class="text-xs font-normal text-zinc-400">(optional — up to {{ config('safereport.attachments.max_files') }} files, 2 MB each)</span>
                    </label>
                    {{-- Without JavaScript this is a normal multi-file input; with it, files are added one at a time below. --}}
                    <input
                        id="attachments"
                        name="attachments[]"
                        type="file"
                        multiple
                        accept="image/jpeg,image/png,image/webp,application/pdf"
                        x-ref="picker"
                        @change="addFiles($event)"
                        x-bind:class="'sr-only'"
                        class="block w-full rounded-lg border @if ($errors->has('attachments') || $errors->has('attachments.*')) border-red-400 dark:border-red-600 @else border-zinc-300 dark:border-zinc-700 @endif bg-white dark:bg-zinc-800 text-sm text-zinc-700 dark:text-zinc-300 file:mr-3 file:border-0 file:bg-brand-50 file:px-3.5 file:py-2.5 file:text-sm file:font-medium file:text-brand-700 hover:file:bg-brand-100 dark:file:bg-brand-950 dark:file:text-brand-200"
                    />

                    <ul x-show="files.length" style="display: none" class="mb-2 space-y-2">
                        <template x-for="(entry, index) in files" :key="entry.file.name + entry.file.size + index">
                            <li class="flex items-center gap-3 rounded-lg border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/50 p-2">
                                <template x-if="entry.isImage">
                                    <img :src="entry.previewUrl" :alt="'Preview of ' + entry.file.name" class="size-14 shrink-0 rounded-md object-cover ring-1 ring-zinc-200 dark:ring-zinc-700" />
                                </template>
                                <template x-if="!entry.isImage">
                                    <div class="flex size-14 shrink-0 items-center justify-center rounded-md bg-white dark:bg-zinc-900 ring-1 ring-zinc-200 dark:ring-zinc-700">
                                        <flux:icon.document-text class="size-7 text-zinc-400" />
                                    </div>
                                </template>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-medium text-zinc-800 dark:text-zinc-200" x-text="entry.file.name"></p>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400" x-text="formatSize(entry.file.size)"></p>
                                </div>
                                <flux:button type="button" variant="ghost" size="sm" icon="x-mark" x-on:click="removeFile(index)" x-bind:disabled="uploading" aria-label="Remove file" />
                            </li>
                        </template>
                    </ul>

                    <div x-show="true" style="display: none" class="flex items-center gap-3">
                        <flux:button
                            type="button"
                            size="sm"
                            icon="plus"
                            x-show="files.length < maxFiles"
                            x-on:click="$refs.picker.click()"
                            x-bind:disabled="uploading"
                        >
                            <span x-text="files.length ? 'Add another file' : 'Add file'"></span>
                        </flux:button>
                        <span class="text-xs text-zinc-500 dark:text-zinc-400" x-text="files.length + ' of ' + maxFiles + ' files added'"></span>
                    </div>

                    <p x-show="fileError" x-text="fileError" class="mt-1.5 text-xs text-red-600 dark:text-red-400" role="alert"></p>
                    <template x-for="message in attachmentErrors()" :key="message">
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400" x-text="message"></p>
                    </template>
                    <div class="mt-2 space-y-1 text-xs text-zinc-500 dark:text-zinc-400">
                        <p>Photos (JPG, PNG, WEBP) or PDF documents. Files are encrypted as soon as they arrive.</p>
                        <p>Hidden photo details such as GPS location, phone model and the time taken are removed automatically.</p>
                        <p class="text-amber-700 dark:text-amber-400">PDFs can contain the author's name or computer account. Check the document properties before attaching one.</p>
                    </div>
                    <x-report.field-error name="attachments" />
                    @foreach ($errors->get('attachments.*') as $messages)
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $messages[0] }}</p>
                    @endforeach
                    @if ($errors->any())
                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">For your security, attached files are not kept when the form is returned with a problem — please attach them again.</p>
                    @endif
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
                    <p x-show="fieldError('contact_hint')" x-text="fieldError('contact_hint')" style="display: none" class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert"></p>
                </div>

                <div class="mt-8 flex justify-between gap-3">
                    <flux:button type="button" variant="ghost" @click="prev()" icon="arrow-left">
                        Back
                    </flux:button>
                    <flux:button type="button" variant="primary" @click="next()" class="px-6" icon:trailing="arrow-right">
                        Review report
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
                        <span class="text-sm text-zinc-800 dark:text-zinc-200 font-medium"
                              x-text="selectedCategory ? (categoryLabels[selectedCategory] ?? selectedCategory) : '—'"></span>
                    </div>
                    <div class="flex items-start gap-3 px-4 py-3">
                        <span class="mt-0.5 text-xs font-semibold uppercase tracking-wider text-zinc-400 w-28 shrink-0">Department</span>
                        <span class="text-sm text-zinc-800 dark:text-zinc-200"
                              x-text="step === 3 ? departmentName() : ''"></span>
                    </div>
                    <div class="flex items-start gap-3 px-4 py-3 bg-zinc-50 dark:bg-zinc-800/50">
                        <span class="mt-0.5 text-xs font-semibold uppercase tracking-wider text-zinc-400 w-28 shrink-0">Subject</span>
                        <span class="text-sm text-zinc-800 dark:text-zinc-200"
                              x-text="step === 3 ? (document.getElementById('subject')?.value || '—') : ''"></span>
                    </div>
                    <div class="flex items-start gap-3 px-4 py-3">
                        <span class="mt-0.5 text-xs font-semibold uppercase tracking-wider text-zinc-400 w-28 shrink-0">Description</span>
                        <span class="text-sm text-zinc-800 dark:text-zinc-200 whitespace-pre-wrap break-words line-clamp-6"
                              x-text="step === 3 ? (document.getElementById('body')?.value || '—') : ''"></span>
                    </div>
                    <div class="flex items-start gap-3 px-4 py-3 bg-zinc-50 dark:bg-zinc-800/50">
                        <span class="mt-0.5 text-xs font-semibold uppercase tracking-wider text-zinc-400 w-28 shrink-0">Evidence</span>
                        <span x-show="!files.length" class="text-sm text-zinc-800 dark:text-zinc-200">None</span>
                        <ul x-show="files.length" style="display: none" class="flex flex-wrap gap-3">
                            <template x-for="(entry, index) in files" :key="'review-' + entry.file.name + index">
                                <li class="flex items-center gap-2">
                                    <template x-if="entry.isImage">
                                        <img :src="entry.previewUrl" :alt="'Preview of ' + entry.file.name" class="size-10 rounded object-cover ring-1 ring-zinc-200 dark:ring-zinc-700" />
                                    </template>
                                    <template x-if="!entry.isImage">
                                        <flux:icon.document-text class="size-6 text-zinc-400" />
                                    </template>
                                    <span class="max-w-40 truncate text-sm text-zinc-800 dark:text-zinc-200" x-text="entry.file.name"></span>
                                </li>
                            </template>
                        </ul>
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

                {{-- Upload progress --}}
                <div x-show="uploading" style="display: none" class="mb-6" role="status" aria-live="polite">
                    <div class="mb-1.5 flex justify-between text-sm">
                        <span class="font-medium text-zinc-700 dark:text-zinc-300"
                              x-text="uploadPercent < 100 ? (files.length ? 'Uploading files…' : 'Sending report…') : 'Encrypting your report…'"></span>
                        <span class="tabular-nums text-zinc-500 dark:text-zinc-400" x-text="uploadPercent + '%'"></span>
                    </div>
                    <div class="h-2 overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-700">
                        <div class="h-full rounded-full bg-brand-600 transition-all duration-200"
                             :class="uploadPercent === 100 && 'animate-pulse'"
                             :style="'width: ' + uploadPercent + '%'"></div>
                    </div>
                </div>

                <p x-show="submitError" x-text="submitError" style="display: none" class="mb-4 text-sm text-red-600 dark:text-red-400" role="alert"></p>

                <div class="flex justify-between gap-3">
                    <flux:button type="button" variant="ghost" @click="prev()" icon="arrow-left" x-bind:disabled="uploading">
                        Back
                    </flux:button>
                    <flux:button type="submit" variant="primary" class="px-6" icon="paper-airplane" x-bind:disabled="uploading">
                        <span x-text="uploading ? 'Submitting…' : 'Submit report'">Submit report</span>
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
</div>

</x-layouts::public>
