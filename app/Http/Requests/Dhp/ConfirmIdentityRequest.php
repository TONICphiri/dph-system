<?php

namespace App\Http\Requests\Dhp;

use App\Models\Citizen;
use App\Services\DhpIdentityConfirmation;
use Illuminate\Foundation\Http\FormRequest;

/**
 * At least one field may be filled; the controller requires two matches.
 * Raw submitted values are never logged or stored.
 */
class ConfirmIdentityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('viewAny', Citizen::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $fields = array_keys(DhpIdentityConfirmation::fields());

        return [
            'citizen_id' => ['required', 'integer', 'exists:citizens,id'],
            ...array_fill_keys($fields, ['nullable', 'string', 'max:100']),
        ];
    }
}
