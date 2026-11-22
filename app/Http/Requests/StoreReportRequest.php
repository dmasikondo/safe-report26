<?php

namespace App\Http\Requests;

use App\Enums\ReportCategoryEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class StoreReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Submission is open to everyone — no auth required.
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'category' => ['required', Rule::enum(ReportCategoryEnum::class)],
            'department_id' => ['nullable', 'integer', Rule::exists('departments', 'id')],
            'subject' => ['required', 'string', 'min:10', 'max:200'],
            'body' => ['required', 'string', 'min:50', 'max:10000'],
            'incident_date' => ['nullable', 'string', 'max:100'],
            'incident_location' => ['nullable', 'string', 'max:200'],
            'organisation_involved' => ['nullable', 'string', 'max:200'],
            'contact_hint' => ['nullable', 'string', 'max:200'],
            'attachments' => ['nullable', 'array', 'max:'.config('safereport.attachments.max_files')],
            'attachments.*' => [
                File::types(['jpg', 'jpeg', 'png', 'webp', 'pdf'])
                    ->max(config('safereport.attachments.max_kilobytes')),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'category.required' => 'Please select a report category.',
            'department_id.exists' => 'Please choose a department from the list.',
            'subject.required' => 'A brief subject line is required.',
            'subject.min' => 'The subject must be at least 10 characters.',
            'body.required' => 'Please describe the incident.',
            'body.min' => 'Please provide more detail (at least 50 characters).',
            'attachments.max' => 'You can attach at most :max files.',
            'attachments.*.mimes' => 'Each file must be a photo (JPG, PNG or WEBP) or a PDF.',
            'attachments.*.mimetypes' => 'Each file must be a photo (JPG, PNG or WEBP) or a PDF.',
            'attachments.*.max' => 'Each file must be 2 MB or smaller.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'attachments.*' => 'attachment',
        ];
    }
}
