<?php

namespace App\Http\Requests\Dhp;

use App\Models\Credential;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Shared rules for issuing a credential. The result lives in
 * test_details only and never enters audit metadata or flash messages.
 */
class StoreCredentialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Credential::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', 'in:vaccination,lab_test'],
            'facility_id' => ['nullable', 'integer', 'exists:facilities,id'],
            'issue_date' => ['required', 'date', 'before_or_equal:today'],
            'replace_of' => ['nullable', 'integer', 'exists:credentials,id'],

            'vaccine_name' => ['required_if:type,vaccination', 'nullable', 'string', 'max:150'],
            'dose_number' => ['required_if:type,vaccination', 'nullable', 'integer', 'min:1', 'max:20'],
            'administration_date' => ['required_if:type,vaccination', 'nullable', 'date', 'before_or_equal:today'],
            'batch_number' => ['nullable', 'string', 'max:50'],
            'next_dose_date' => ['nullable', 'date', 'after_or_equal:administration_date'],

            'test_type' => ['required_if:type,lab_test', 'nullable', 'string', 'max:150'],
            'sample_collection_date' => ['required_if:type,lab_test', 'nullable', 'date', 'before_or_equal:today'],
            'result_date' => ['required_if:type,lab_test', 'nullable', 'date', 'before_or_equal:today', 'after_or_equal:sample_collection_date'],
            'result' => ['required_if:type,lab_test', 'nullable', 'string', 'max:100'],
            'valid_until' => ['required_if:type,lab_test', 'nullable', 'date', 'after_or_equal:result_date'],
        ];
    }
}
