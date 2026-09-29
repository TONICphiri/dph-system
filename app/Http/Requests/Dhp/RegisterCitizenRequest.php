<?php

namespace App\Http\Requests\Dhp;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Health-worker-driven citizen registration. National ID is an
 * identifier, never a password, PIN seed or QR content.
 */
class RegisterCitizenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Citizen::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'min:2', 'max:100', 'regex:/^[\p{L} .\'-]+$/u'],
            'last_name' => ['required', 'string', 'min:2', 'max:100', 'regex:/^[\p{L} .\'-]+$/u'],
            'sex' => ['required', 'in:female,male'],
            'date_of_birth' => ['required', 'date', 'before_or_equal:today'],
            'district' => ['required', 'string', 'max:100', 'exists:districts,name'],
            'village' => ['nullable', 'string', 'max:100'],
            'national_id' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255', 'unique:citizens,email', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30', 'unique:citizens,phone', 'unique:users,phone'],
            'create_account' => ['nullable', 'boolean'],
            'duplicate_override' => ['nullable', 'boolean'],
            'duplicate_reason' => ['required_if:duplicate_override,1', 'nullable', 'string', 'min:10', 'max:500'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('national_id') && is_string($this->input('national_id'))) {
            $this->merge(['national_id' => strtoupper(trim($this->input('national_id')))]);
        }
    }
}
