<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePatientRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('edit_patient');
    }

    public function prepareForValidation(): void
    {
        foreach (['national_id', 'first_name', 'last_name', 'phone_number'] as $key) {
            if ($this->has($key) && is_string($this->input($key))) {
                $this->merge([$key => trim($this->input($key))]);
            }
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $patientId = $this->route('patient')?->id ?? $this->route('patient');

        return [
            'national_id' => 'nullable|string|max:20|regex:/^[A-Za-z0-9\-\/]+$/|unique:patients,national_id,' . $patientId,
            'first_name' => 'sometimes|required|string|max:100|regex:/^[\p{L}\s\'\-]+$/u',
            'last_name' => 'sometimes|required|string|max:100|regex:/^[\p{L}\s\'\-]+$/u',
            'date_of_birth' => 'nullable|date|before:today|after:1900-01-01',
            'gender' => 'nullable|in:M,F,Other',
            'phone_number' => 'nullable|string|max:20|regex:/^[+\d][\d\s\-()]{5,19}$/',
            'address' => 'nullable|string|max:500',
            'village' => 'nullable|string|max:100',
            'traditional_authority' => 'nullable|string|max:100',
            'district' => ['nullable', Rule::in(array_keys(config('districts')))],
            'status' => 'in:active,inactive,deceased',
        ];
    }
}
