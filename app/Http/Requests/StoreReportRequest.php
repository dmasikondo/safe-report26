<?php

namespace App\Http\Requests;

use App\Enums\ReportCategoryEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Submission is open to everyone — no auth required.
        return true;
    }

    public function rules(): array
    {
        return [
            'category'              => ['required', new Enum(ReportCategoryEnum::class)],
            'subject'               => ['required', 'string', 'min:10', 'max:200'],
            'body'                  => ['required', 'string', 'min:50', 'max:10000'],
            'incident_date'         => ['nullable', 'string', 'max:100'],
            'incident_location'     => ['nullable', 'string', 'max:200'],
            'organisation_involved' => ['nullable', 'string', 'max:200'],
            'contact_hint'          => ['nullable', 'string', 'max:200'],
        ];
    }

    public function messages(): array
    {
        return [
            'category.required'  => 'Please select a report category.',
            'subject.required'   => 'A brief subject line is required.',
            'subject.min'        => 'The subject must be at least 10 characters.',
            'body.required'      => 'Please describe the incident.',
            'body.min'           => 'Please provide more detail (at least 50 characters).',
        ];
    }
}
