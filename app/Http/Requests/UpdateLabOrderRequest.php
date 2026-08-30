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
            'result_units' => 'nullable|string|max=50',
            'result_description' => 'nullable|string',
            'status' => 'required|string|in:requested,pending,results,cancelled',
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