<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VitalsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'temperature' => ['required', 'numeric', 'between:30,45'],
            'weight' => ['required', 'numeric', 'between:0.3,400'],
            'height' => ['nullable', 'numeric', 'between:20,250'],
            'systolic_pressure' => ['nullable', 'required_with:diastolic_pressure', 'integer', 'between:50,260'],
            'diastolic_pressure' => ['nullable', 'required_with:systolic_pressure', 'integer', 'between:30,160', 'lt:systolic_pressure'],
            'pulse_rate' => ['nullable', 'integer', 'between:20,250'],
            'respiratory_rate' => ['nullable', 'integer', 'between:5,80'],
            'oxygen_saturation' => ['nullable', 'integer', 'between:50,100'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'temperature' => 'temperature (degrees Celsius)',
            'weight' => 'weight (kilograms)',
            'height' => 'height (centimetres)',
            'systolic_pressure' => 'systolic blood pressure',
            'diastolic_pressure' => 'diastolic blood pressure',
            'oxygen_saturation' => 'oxygen saturation',
        ];
    }

    public function messages(): array
    {
        return ['diastolic_pressure.lt' => 'The diastolic pressure must be lower than the systolic pressure.'];
    }
}
