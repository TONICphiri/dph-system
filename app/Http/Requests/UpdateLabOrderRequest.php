<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLabOrderRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|string|array>
     */
    public function rules()
    {
        return [
            'result_value' => 'required|string|max:255',
            // FIX: was 'max=50' (invalid rule syntax -> throws InvalidArgumentException at runtime)
            'result_units' => 'nullable|string|max:50',
            'result_description' => 'nullable|string|max:5000',
            // Status is set by the controller ('results') — never require it
            // from the client, or result submissions without it will 422.
            'status' => 'sometimes|string|in:requested,pending,results,cancelled',
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     *
     * FIX: was always true. Now actually checks the permission, matching
     * what the controller enforces.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->user()?->can('record_lab_results') ?? false;
    }
}
