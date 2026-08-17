<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePatientRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('edit_patient');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'first_name' => 'sometimes|string|max:100',
            'last_name' => 'sometimes|string|max:100',
            'date_of_birth' => 'nullable|date|before:today',
            'gender' => 'nullable|in:M,F,Other',
            'phone_number' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'village' => 'nullable|string|max:100',
            'district' => 'nullable|string|max:100',
            'status' => 'in:active,inactive,deceased',
        ];
    }
}
