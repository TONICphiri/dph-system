<?php

namespace App\Http\Requests;

use App\Enums\Sex;
use App\Models\Patient;
use App\Services\SettingService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
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
            'emergency' => ['sometimes', 'boolean'],
            'national_id' => [
                'nullable',
                Rule::requiredIf(fn () => ! $this->isChild() && ! $this->isEmergencyRegistration()),
                'string',
                'size:'.$settings->nationalIdLength(),
                'alpha_num',
                Rule::unique('patients')->ignore($patient),
            ],
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
            // emergency that link is completed later, after treatment.
            'mother_id' => [
                'nullable',
                Rule::requiredIf(fn () => ! ($patient instanceof Patient)
                    && $this->isChild()
                    && ! $this->isEmergencyRegistration()),
                'exists:patients,id',
            ],

            'emergency_reason' => [
                'nullable',
                Rule::requiredIf(fn () => $this->isEmergencyRegistration()),
                Rule::in(array_keys(config('health_passport.emergency_reasons', []))),
            ],
            'emergency_reason_other' => ['nullable', 'required_if:emergency_reason,other', 'string', 'max:255'],
            'next_of_kin_name' => ['nullable', Rule::requiredIf(fn () => $this->isEmergencyRegistration()), 'string', 'max:150'],
            'next_of_kin_relationship' => ['nullable', 'string', 'max:50', Rule::in($settings->list('relationship_types'))],
            'next_of_kin_phone' => ['nullable', Rule::requiredIf(fn () => $this->isEmergencyRegistration()), 'string', 'max:30'],

            'contacts' => ['array', 'max:3'],
            'contacts.0.full_name' => ['nullable', Rule::requiredIf(fn () => ! $this->isEmergencyRegistration()), 'string', 'max:150'],
            'contacts.0.phone' => ['nullable', Rule::requiredIf(fn () => ! $this->isEmergencyRegistration()), 'string', 'max:30'],
            'contacts.*.full_name' => ['nullable', 'string', 'max:150'],
            'contacts.*.relationship' => ['nullable', 'required_with:contacts.*.full_name', Rule::in($settings->list('relationship_types'))],
            'contacts.*.phone' => ['nullable', 'required_with:contacts.*.full_name', 'string', 'max:30'],
            'contacts.*.physical_address' => ['nullable', 'string', 'max:255'],

            'create_portal_account' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Whether this request is for a child. On an edit the stored record decides,
     * because the form does not post the adult/child radio again.
     */
    public function isChild(): bool
    {
        $patient = $this->route('patient');

        if ($patient instanceof Patient) {
            return $patient->isChild();
        }

        if ($this->has('is_child')) {
            return $this->boolean('is_child');
        }

        $dob = $this->input('date_of_birth');

        if (! is_string($dob) || $dob === '') {
            return false;
        }

        try {
            return Carbon::parse($dob)->age < app(SettingService::class)->childSeparationAge();
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * An emergency registration may be created without the usual identity
     * documents. On an edit, an existing provisional record keeps that context.
     */
    public function isEmergencyRegistration(): bool
    {
        $patient = $this->route('patient');

        if ($patient instanceof Patient) {
            return $patient->isProvisional();
        }

        return $this->boolean('emergency');
    }

    public function emergencyReason(): ?string
    {
        $reason = $this->validated('emergency_reason');

        return is_string($reason) && $reason !== '' ? $reason : null;
    }

    public function emergencyReasonNote(): ?string
    {
        if ($this->emergencyReason() !== 'other') {
            return null;
        }

        $note = $this->validated('emergency_reason_other');

        return is_string($note) && $note !== '' ? $note : null;
    }

    public function messages(): array
    {
        return [
            'national_id.required' => 'Enter the National ID, or mark the registration as an emergency.',
            'national_id.required_if' => 'Enter the National ID.',
            'mother_id.required' => 'Scan the mother\'s passport to link the child, or mark the registration as an emergency.',
            'mother_id.required_if' => 'Scan the mother\'s passport to link the child, or mark the registration as an emergency.',
            'national_id.size' => 'The National ID must be exactly '.app(SettingService::class)->nationalIdLength().' characters.',
            'national_id.unique' => 'A passport already exists for this National ID. Scan the card to open it.',
            'email.unique' => 'This email address is already used by another passport holder.',
            'sex.not_in' => 'Choose male or female. Unknown is only allowed for emergency registrations.',
            'contacts.0.full_name.required' => 'Enter at least one emergency contact, or mark the registration as an emergency.',
            'contacts.0.phone.required' => 'Enter the phone number of the emergency contact, or mark the registration as an emergency.',
            'contacts.*.relationship.required_with' => 'Choose the relationship of each emergency contact.',
            'contacts.*.phone.required_with' => 'Enter a phone number for each emergency contact.',
            'next_of_kin_name.required' => 'Enter the name of the next of kin or the person who brought the patient.',
            'next_of_kin_phone.required' => 'Enter a phone number for the next of kin or the person who brought the patient.',
            'emergency_reason.required' => 'Choose why this is an emergency registration.',
            'emergency_reason.in' => 'Choose one of the reasons listed.',
            'emergency_reason_other.required_if' => 'Describe the reason for the emergency registration.',
        ];
    }

    public function attributes(): array
    {
        return [
            'district_id' => 'district',
            'mother_id' => 'mother',
            'next_of_kin_name' => 'next of kin name',
            'next_of_kin_phone' => 'next of kin phone number',
            'emergency_reason' => 'emergency reason',
        ];
    }

    /**
     * @return array<int, mixed>
     */
    private function sexRules(): array
    {
        $rules = ['required', Rule::enum(Sex::class)];

        if (! $this->isEmergencyRegistration()) {
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
            'emergency',
            'emergency_reason',
            'emergency_reason_other',
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
