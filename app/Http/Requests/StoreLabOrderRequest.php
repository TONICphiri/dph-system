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
     * FIX: this previously always returned true, which meant the form request
     * itself never blocked anyone from creating a lab order. The controller
     * separately calls $this->authorize('create_lab_orders'), but relying on
     * every controller method to remember this is fragile — enforce it here too
     * so the permission is checked before validation even runs.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->user()?->can('create_lab_orders') ?? false;
    }
}
