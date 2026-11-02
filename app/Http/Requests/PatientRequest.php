<?php

namespace App\Http\Requests;

use App\Enums\IdentityStatus;
use App\Enums\Sex;
use App\Models\Patient;
use App\Services\SettingService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PatientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $patient = $this->route('patient');
        $settings = app(SettingService::class);

        return [
            'is_child' => ['sometimes', 'boolean'],
            'emergency_child' => ['sometimes', 'boolean'],
            'national_id' => ['nullable', 'required_if:is_child,0', 'string', 'size:'.$settings->nationalIdLength(), 'alpha_num', Rule::unique('patients')->ignore($patient)],
            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'date_of_birth' => ['required', 'date', 'before_or_equal:today', 'after:1900-01-01'],
            'dob_is_estimated' => ['sometimes', 'boolean'],
            'sex' => $this->sexRules(),
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:150', Rule::unique('patients')->ignore($patient)],
            'sms_consent' => ['nullable', 'boolean'],
            'district_id' => ['required', 'exists:districts,id'],
            'traditional_authority' => ['nullable', 'string', 'max:100'],
            'village' => ['nullable', 'string', 'max:100'],
            'physical_address' => ['nullable', 'string', 'max:255'],
            'occupation' => ['nullable', 'string', 'max:100'],
            'blood_group' => ['nullable', Rule::in(config('health_passport.blood_groups'))],
            'allergies' => ['nullable', 'string', 'max:1000'],
            'chronic_conditions' => ['nullable', 'string', 'max:1000'],
            'disabilities' => ['nullable', 'string', 'max:1000'],
            'health_notes' => ['nullable', 'string', 'max:2000'],

            // A child is normally linked to the mother's passport. In an
            // emergency the guardian details replace that link until the
            // mother can be found and her passport scanned.
            'mother_id' => ['nullable', Rule::requiredIf(fn () => $this->boolean('is_child') && ! $this->isEmergencyChild()), 'exists:patients,id'],
            'emergency_child_reason' => ['nullable', Rule::requiredIf(fn () => $this->isEmergencyChild()), Rule::in(array_keys(config('health_passport.emergency_child_reasons', [])))],
            'emergency_child_reason_other' => ['nullable', 'required_if:emergency_child_reason,other', 'string', 'max:255'],
            'guardian_name' => ['nullable', Rule::requiredIf(fn () => $this->isEmergencyChild()), 'string', 'max:150'],
            'guardian_relationship' => ['nullable', 'string', 'max:50', Rule::in($settings->list('relationship_types'))],
            'guardian_phone' => ['nullable', Rule::requiredIf(fn () => $this->isEmergencyChild()), 'string', 'max:30'],

            'contacts' => ['array', 'max:3'],
            'contacts.0.full_name' => ['nullable', Rule::requiredIf(fn () => ! $this->isEmergencyChild()), 'string', 'max:150'],
            'contacts.0.phone' => ['nullable', Rule::requiredIf(fn () => ! $this->isEmergencyChild()), 'string', 'max:30'],
            'contacts.*.full_name' => ['nullable', 'string', 'max:150'],
            'contacts.*.relationship' => ['nullable', 'required_with:contacts.*.full_name', Rule::in($settings->list('relationship_types'))],
            'contacts.*.phone' => ['nullable', 'required_with:contacts.*.full_name', 'string', 'max:30'],
            'contacts.*.physical_address' => ['nullable', 'string', 'max:255'],

            'create_portal_account' => ['nullable', 'boolean'],
        ];
    }

    /**
     * A child registration is an emergency when the mother's passport cannot
     * be scanned. On an edit, the existing provisional child sets the context.
     */
    public function isEmergencyChild(): bool
    {
        $patient = $this->route('patient');

        if ($patient instanceof Patient) {
            return $patient->identity_status === IdentityStatus::Provisional && $patient->isChild();
        }

        return $this->boolean('emergency_child') && $this->boolean('is_child');
    }

    public function emergencyReason(): ?string
    {
        $reason = $this->validated('emergency_child_reason');

        return is_string($reason) && $reason !== '' ? $reason : null;
    }

    public function emergencyReasonNote(): ?string
    {
        if ($this->emergencyReason() !== 'other') {
            return null;
        }

        $note = $this->validated('emergency_child_reason_other');

        return is_string($note) && $note !== '' ? $note : null;
    }

    public function messages(): array
    {
        return [
            'national_id.required_if' => 'Enter the National ID.',
            'mother_id.required_if' => 'Scan the mother\'s passport to link the child.',
            'mother_id.required' => 'Scan the mother\'s passport to link the child, or register the child as an emergency.',
            'national_id.size' => 'The National ID must be exactly '.app(SettingService::class)->nationalIdLength().' characters.',
            'national_id.unique' => 'A passport already exists for this National ID. Scan the card to open it.',
            'email.unique' => 'This email address is already used by another passport holder.',
            'sex.not_in' => 'Choose male or female. Unknown is only allowed for an emergency child registration.',
            'contacts.0.full_name.required' => 'Enter at least one emergency contact.',
            'contacts.0.phone.required' => 'Enter the phone number of the emergency contact.',
            'contacts.*.relationship.required_with' => 'Choose the relationship of each emergency contact.',
            'contacts.*.phone.required_with' => 'Enter a phone number for each emergency contact.',
            'guardian_name.required' => 'Enter the name of the adult who brought the child.',
            'guardian_phone.required' => 'Enter a phone number for the adult who brought the child.',
            'emergency_child_reason.required' => 'Choose why the mother is not available.',
            'emergency_child_reason.in' => 'Choose one of the reasons listed.',
            'emergency_child_reason_other.required_if' => 'Describe the reason the mother is not available.',
        ];
    }

    public function attributes(): array
    {
        return [
            'district_id' => 'district',
            'mother_id' => 'mother',
            'guardian_name' => 'guardian name',
            'guardian_phone' => 'guardian phone number',
            'emergency_child_reason' => 'reason the mother is not available',
        ];
    }

    /**
     * @return array<int, mixed>
     */
    private function sexRules(): array
    {
        $rules = ['required', Rule::enum(Sex::class)];

        if (! $this->isEmergencyChild()) {
            $rules[] = Rule::notIn([Sex::Unknown->value]);
        }

        return $rules;
    }

    /**
     * @return array<string, mixed>
     */
    public function patientData(): array
    {
        return $this->safe()->except([
            'contacts',
            'create_portal_account',
            'is_child',
            'emergency_child',
            'emergency_child_reason',
            'emergency_child_reason_other',
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function contacts(): array
    {
        return $this->validated('contacts', []);
    }
}
