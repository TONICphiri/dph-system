<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = ['key', 'value', 'group'];

    /**
     * Get a setting value, decoded from JSON when it was stored as JSON.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $record = static::where('key', $key)->first();

        if (!$record || $record->value === null) {
            return $default;
        }

        $decoded = json_decode($record->value, true);

        return json_last_error() === JSON_ERROR_NONE && (is_array($decoded) || is_numeric($decoded) || is_bool($decoded))
            ? $decoded
            : $record->value;
    }

    /**
     * Store a setting value, encoding arrays as JSON.
     */
    public static function set(string $key, mixed $value, ?string $group = null): self
    {
        return static::updateOrCreate(
            ['key' => $key],
            [
                'value' => is_array($value) ? json_encode(array_values($value)) : ($value === null ? null : (string) $value),
                'group' => $group,
            ]
        );
    }
}
