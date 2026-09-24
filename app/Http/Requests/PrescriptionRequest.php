<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PrescriptionRequest extends FormRequest
{
    use PrescriptionItemsRules;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [...$this->prescriptionItemRules(true), 'notes' => ['nullable', 'string', 'max:1000']];
    }

    public function messages(): array
    {
        return $this->prescriptionItemMessages();
    }
}
