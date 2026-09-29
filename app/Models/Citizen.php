<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * The passport holder. National ID is an identifier, not a password.
 */
class Citizen extends Model
{
    use HasFactory;

    protected $fillable = [
        'passport_id',
        'national_id',
        'first_name',
        'last_name',
        'sex',
        'date_of_birth',
        'district',
        'village',
        'email',
        'phone',
        'user_id',
        'pin_hash',
        'created_by',
    ];

    protected $hidden = ['pin_hash'];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
        ];
    }

    protected function fullName(): Attribute
    {
        return Attribute::get(fn () => trim("{$this->first_name} {$this->last_name}"));
    }

    /**
     * Masked National ID for screens and printouts. The full value is
     * never shown on search results, slips or certificates.
     */
    public function maskedNationalId(): ?string
    {
        if (! $this->national_id) {
            return null;
        }

        $id = (string) $this->national_id;

        return str_repeat('*', max(0, strlen($id) - 2)).substr($id, -2);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function credentials(): HasMany
    {
        return $this->hasMany(Credential::class)->latest('issue_date');
    }

    /**
     * Search by national_id, passport_id, name or date of birth.
     * Returns minimal fields only; enforced in the controller.
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);

        if ($term === '') {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $inner) use ($term) {
            $inner->where('passport_id', $term)
                ->orWhere('national_id', strtoupper($term))
                ->orWhere('first_name', 'like', "%{$term}%")
                ->orWhere('last_name', 'like', "%{$term}%")
                ->orWhere('date_of_birth', $term)
                ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$term}%"]);
        });
    }
}
