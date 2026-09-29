<?php

namespace App\Http\Requests\Dhp;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Minor corrections keep credential_number and qr_token.
 * Only active credentials may be edited (enforced in controller too).
 */
class UpdateCredentialRequest extends FormRequest
{
    public function authorize(): bool
    {
        $credential = $this->route('credential');

        return $credential && $this->user()->can('update', $credential);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $isVaccination = $this->route('credential')?->type?->value !== 'lab_test';

        return [
            'facility_id' => ['nullable', 'integer', 'exists:facilities,id'],
            'issue_date' => ['required', 'date', 'before_or_equal:today'],

            'vaccine_name' => [$isVaccination ? 'required' : 'nullable', 'nullable', 'string', 'max:150'],
            'dose_number' => [$isVaccination ? 'required' : 'nullable', 'nullable', 'integer', 'min:1', 'max:20'],
            'administration_date' => [$isVaccination ? 'required' : 'nullable', 'nullable', 'date', 'before_or_equal:today'],
            'batch_number' => ['nullable', 'string', 'max:50'],
            'next_dose_date' => ['nullable', 'date', 'after_or_equal:administration_date'],

            'test_type' => [$isVaccination ? 'nullable' : 'required', 'nullable', 'string', 'max:150'],
            'sample_collection_date' => [$isVaccination ? 'nullable' : 'required', 'nullable', 'date', 'before_or_equal:today'],
            'result_date' => [$isVaccination ? 'nullable' : 'required', 'nullable', 'date', 'before_or_equal:today', 'after_or_equal:sample_collection_date'],
            'result' => [$isVaccination ? 'nullable' : 'required', 'nullable', 'string', 'max:100'],
            'valid_until' => [$isVaccination ? 'nullable' : 'required', 'nullable', 'date', 'after_or_equal:result_date'],
        ];
    }
}
