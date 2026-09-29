<?php

namespace App\Http\Requests\Dhp;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Revocation needs a controlled reason category. Free text for "other"
 * is treated as sensitive and never enters general logs or public output.
 */
class RevokeCredentialRequest extends FormRequest
{
    public const REASONS = ['data_entry_error', 'duplicate', 'issued_in_error', 'suspected_fraud', 'other'];

    public function authorize(): bool
    {
        $credential = $this->route('credential');

        return $credential && $this->user()->can('revoke', $credential);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'reason' => ['required', 'in:'.implode(',', self::REASONS)],
            'reason_note' => ['required_if:reason,other', 'nullable', 'string', 'max:500'],
            'replace' => ['nullable', 'boolean'],
        ];
    }
}
