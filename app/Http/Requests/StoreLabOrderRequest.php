<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLabOrderRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|string|array>
     */
    public function rules()
    {
        return [
            'patient_id' => 'required|exists:patients,id',
            'test_type' => 'required|string|max:100',
            'test_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'encounter_id' => 'nullable|exists:encounters,id',
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }
}