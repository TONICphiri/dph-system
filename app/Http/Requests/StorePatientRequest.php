<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePatientRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create_patient');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'national_id' => 'required|string|max:20|unique:patients,national_id',
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'date_of_birth' => 'nullable|date|before:today',
            'gender' => 'nullable|in:M,F,Other',
            'phone_number' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'village' => 'nullable|string|max:100',
            'district' => 'nullable|string|max:100',
            'is_child' => 'boolean',
            'guardian_id' => 'nullable|exists:guardians,id',
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
            'national_id.required' => 'National ID is required',
            'national_id.unique' => 'A patient with this National ID already exists',
            'first_name.required' => 'First name is required',
            'last_name.required' => 'Last name is required',
            'date_of_birth.before' => 'Date of birth must be in the past',
        ];
    }
}
