{{-- Vital sign inputs shared by outpatient visits and daily inpatient care. --}}
<div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
    <x-field.input name="temperature" label="Temperature (°C)" type="number" step="0.1" min="30" max="45" required />
    <x-field.input name="weight" label="Weight (kg)" type="number" step="0.1" min="0.3" max="400" required />
    <x-field.input name="height" label="Height (cm)" type="number" step="0.1" min="20" max="250" />
    <x-field.input name="oxygen_saturation" label="Oxygen saturation (%)" type="number" min="50" max="100" />
    <x-field.input name="systolic_pressure" label="Systolic pressure (mmHg)" type="number" min="50" max="260" help="Upper number" />
    <x-field.input name="diastolic_pressure" label="Diastolic pressure (mmHg)" type="number" min="30" max="160" help="Lower number" />
    <x-field.input name="pulse_rate" label="Pulse (beats per minute)" type="number" min="20" max="250" />
    <x-field.input name="respiratory_rate" label="Breathing rate (per minute)" type="number" min="5" max="80" />
    <x-field.textarea name="notes" label="Notes" rows="2" class="sm:col-span-2 lg:col-span-4" />
</div>
