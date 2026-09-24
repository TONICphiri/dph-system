<?php

namespace App\Http\Requests;

use App\Services\ConsultationService;
use App\Services\SettingService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ConsultationRequest extends FormRequest
{
    use PrescriptionItemsRules;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'history' => ['required', 'string', 'max:5000'],
            'examination' => ['nullable', 'string', 'max:5000'],
            'diagnosis' => ['required', 'string', 'max:255'],
            'treatment_plan' => ['nullable', 'string', 'max:5000'],
            'outcome' => ['required', Rule::in([ConsultationService::OUTCOME_SEND_HOME, ConsultationService::OUTCOME_ADMIT])],
            'admission_reason' => ['nullable', 'required_if:outcome,'.ConsultationService::OUTCOME_ADMIT, 'string', 'max:2000'],
            'preferred_ward_type' => ['nullable', Rule::in(app(SettingService::class)->list('ward_types'))],
            ...$this->prescriptionItemRules(false),
        ];
    }

    public function messages(): array
    {
        return [
            'history.required' => 'Enter the patient history and presenting complaint.',
            'admission_reason.required_if' => 'Enter the reason for admission.',
            ...$this->prescriptionItemMessages(),
        ];
    }

    public function notes(): array
    {
        return $this->safe()->only(['history', 'examination', 'diagnosis', 'treatment_plan']);
    }

    public function admission(): array
    {
        return $this->safe()->only(['admission_reason', 'preferred_ward_type']);
    }
}
