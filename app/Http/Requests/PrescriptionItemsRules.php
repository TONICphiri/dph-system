<?php

namespace App\Http\Requests;

/**
 * Validation rules shared by every form that prescribes medication.
 */
trait PrescriptionItemsRules
{
    /**
     * @return array<string, mixed>
     */
    protected function prescriptionItemRules(bool $required): array
    {
        return [
            'items' => [$required ? 'required' : 'nullable', 'array', 'max:15'],
            'items.*.medicine_id' => ['nullable', 'exists:medicines,id'],
            'items.*.medicine_name' => ['nullable', 'required_without:items.*.medicine_id', 'string', 'max:150'],
            'items.*.dosage' => ['required_with:items.*.medicine_id,items.*.medicine_name', 'nullable', 'string', 'max:100'],
            'items.*.frequency' => ['required_with:items.*.medicine_id,items.*.medicine_name', 'nullable', 'string', 'max:100'],
            'items.*.duration_days' => ['required_with:items.*.medicine_id,items.*.medicine_name', 'nullable', 'integer', 'min:1', 'max:365'],
            'items.*.quantity' => ['required_with:items.*.medicine_id,items.*.medicine_name', 'nullable', 'integer', 'min:1', 'max:10000'],
            'items.*.instructions' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function prescriptionItemMessages(): array
    {
        return [
            'items.*.medicine_name.required_without' => 'Choose a medicine from the list or type its name.',
            'items.*.dosage.required_with' => 'Enter the dosage for each medicine.',
            'items.*.frequency.required_with' => 'Enter how often each medicine is taken.',
            'items.*.duration_days.required_with' => 'Enter the number of days for each medicine.',
            'items.*.quantity.required_with' => 'Enter the quantity to dispense for each medicine.',
        ];
    }

    /**
     * Rows the doctor left empty are removed.
     *
     * @return array<int, array<string, mixed>>
     */
    public function prescriptionItems(): array
    {
        return collect($this->validated('items', []))
            ->filter(fn (array $item) => ! empty($item['medicine_id']) || ! empty($item['medicine_name']))
            ->values()
            ->all();
    }
}
