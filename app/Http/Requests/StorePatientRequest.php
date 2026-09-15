<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePatientRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create_patient');
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            'national_id' => $this->national_id ? trim((string) $this->national_id) : null,
            'first_name' => $this->first_name ? trim((string) $this->first_name) : null,
            'last_name' => $this->last_name ? trim((string) $this->last_name) : null,
            'phone_number' => $this->phone_number ? trim((string) $this->phone_number) : null,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'national_id' => 'nullable|string|max:20|unique:patients,national_id|regex:/^[A-Za-z0-9\-\/]+$/',
            'first_name' => 'required|string|max:100|regex:/^[\p{L}\s\'\-]+$/u',
            'last_name' => 'required|string|max:100|regex:/^[\p{L}\s\'\-]+$/u',
            'date_of_birth' => 'nullable|date|before:today|after:1900-01-01',
            'gender' => 'nullable|in:M,F,Other',
            'phone_number' => 'nullable|string|max:20|regex:/^[+\d][\d\s\-()]{5,19}$/',
            'address' => 'nullable|string|max:500',
            'village' => 'nullable|string|max:100',
            'traditional_authority' => 'nullable|string|max:100',
            'district' => ['required', Rule::in(array_keys(config('districts')))],
            'is_child' => 'boolean',
            'guardian_id' => 'nullable|exists:guardians,id',
            // Inline guardian names are required for a child only when no
            // existing guardian was selected (either workflow is valid).
            'guardian_first_name' => [Rule::requiredIf(fn () => $this->boolean('is_child') && !$this->filled('guardian_id')), 'nullable', 'string', 'max:100'],
            'guardian_last_name' => [Rule::requiredIf(fn () => $this->boolean('is_child') && !$this->filled('guardian_id')), 'nullable', 'string', 'max:100'],
            'guardian_national_id' => 'nullable|string|max:20',
            'guardian_phone_number' => 'nullable|string|max:20',
            'guardian_relationship' => 'nullable|string|max:50',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'national_id.unique' => 'A patient with this National ID already exists',
            'national_id.regex' => 'National ID may only contain letters, numbers, dashes and slashes.',
            'first_name.required' => 'First name is required',
            'first_name.regex' => 'Names may only contain letters, spaces, hyphens and apostrophes.',
            'last_name.required' => 'Last name is required',
            'last_name.regex' => 'Names may only contain letters, spaces, hyphens and apostrophes.',
            'date_of_birth.before' => 'Date of birth must be in the past',
            'district.required' => 'District is required to generate the Health Passport ID.',
            'district.in' => 'Select a valid Malawi district.',
            'phone_number.regex' => 'Enter a valid phone number.',
            'guardian_id.exists' => 'Selected guardian does not exist',
            'guardian_first_name.required' => 'Guardian first name is required for child registration.',
            'guardian_last_name.required' => 'Guardian last name is required for child registration.',
        ];
    }
}
