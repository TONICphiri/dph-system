{{-- Guardian details for a child registered while the mother is not available. --}}
<div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
    <x-field.input name="guardian_name" label="Guardian's full name" :value="$patient->guardian_name" required x-bind:disabled="!emergencyChild || !isChild" />
    <x-field.select name="guardian_relationship" label="Relationship to the child" :options="$relationships" :value="$patient->guardian_relationship" placeholder="Choose" x-bind:disabled="!emergencyChild || !isChild" />
    <x-field.input name="guardian_phone" label="Guardian's phone" :value="$patient->guardian_phone" placeholder="0999 123 456" required x-bind:disabled="!emergencyChild || !isChild" />
    <x-field.select name="emergency_child_reason" label="Why is the mother not available?" :options="$emergencyReasons" :value="$patient->provisional_reason" placeholder="Choose a reason" required x-model="emergencyReason" x-bind:disabled="!emergencyChild || !isChild" />
    <div x-show="emergencyReason === 'other'" x-cloak class="sm:col-span-2 lg:col-span-2">
        <x-field.input name="emergency_child_reason_other" label="Describe the reason" :value="$patient->provisional_reason_note" x-bind:disabled="emergencyReason !== 'other' || !isChild" />
    </div>
    <x-field.checkbox name="dob_is_estimated" label="Date of birth is estimated" help="Tick this if the exact date is not known. The record stays provisional until it is confirmed." :checked="$patient->dob_is_estimated" class="sm:col-span-2 lg:col-span-3" x-bind:disabled="!emergencyChild || !isChild" />
</div>
